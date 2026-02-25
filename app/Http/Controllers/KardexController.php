<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\SalesMovementDetail;
use App\Models\WarehouseMovementDetail;
use App\Models\OrderMovementDetail;
use App\Models\OrderMovement;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class KardexController extends Controller
{

    public function index(Request $request)
    {
        $viewId = $request->input('view_id');
        $productId = $request->input('product_id') ?? 'all';
        $branchId = $request->session()->get('branch_id');
        $dateFrom = $request->input('date_from') ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->input('date_to') ?? now()->format('Y-m-d');
        
        $sourceFilter = $request->input('source') ?? 'all'; 
        $typeFilter = $request->input('movement_type') ?? 'all';

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $products = Product::where('kardex', 'S')->with('baseUnit')->orderBy('description')->get();
        $branch = $branchId ? Branch::find($branchId) : null;
        $movementsCollection = collect(); 
        $showAllProducts = ($productId === 'all');

        // Correlativo 001, 002, 003... por tipo de documento para pedidos (como en ventas)
        $orderSeriesMap = $this->buildOrderSeriesMap($branchId ? (int) $branchId : null, $dateFrom, $dateTo);

        if ($showAllProducts) {
            $productIds = $this->getProductIdsWithMovements($branchId ? (int) $branchId : null, $dateFrom, $dateTo);
            $productIds = array_values(array_intersect($productIds, $products->pluck('id')->all()));
            
            if (!empty($productIds)) {
                $productMap = Product::whereIn('id', $productIds)->get()->keyBy('id');

                foreach ($productIds as $pid) {
                    $rows = $this->buildKardexMovements($pid, $branchId ? (int) $branchId : null, $dateFrom, $dateTo, $orderSeriesMap);
                    
                    $p = $productMap->get($pid);
                    foreach ($rows as $r) {
                        $r['product_code'] = $p?->code ?? '-';
                        $r['product_description'] = $p?->description ?? '-';
                        $movementsCollection->push($r);
                    }
                }
                $movementsCollection = $movementsCollection->sortBy([
                    ['date', 'desc'], 
                    ['product_code', 'asc']
                ])->values();
            }

        } elseif ($productId && is_numeric($productId)) {
            $data = $this->buildKardexMovements((int) $productId, $branchId ? (int) $branchId : null, $dateFrom, $dateTo, $orderSeriesMap);
            $movementsCollection = collect($data);
        }

        if ($sourceFilter !== 'all') {
            $movementsCollection = $movementsCollection->filter(function ($m) use ($sourceFilter) {
                $origin = $m['origin'] ?? '';
                $isSale = str_starts_with($origin, 'V - ') || str_starts_with($origin, 'O - ');
                $typeLower = strtolower($m['type'] ?? '');
                if (!$isSale) {
                    $isSale = str_contains($typeLower, 'boleta') || str_contains($typeLower, 'factura')
                        || str_contains($typeLower, 'nota') || str_contains($typeLower, 'ticket');
                }

                if ($sourceFilter === 'sales') return $isSale;
                if ($sourceFilter === 'warehouse') return !$isSale && ($m['type'] ?? '') !== 'Saldo inicial';
                return true;
            });
        }

        $availableTypes = $movementsCollection->pluck('type')
            ->unique()
            ->filter(fn($t) => $t !== 'Saldo inicial')
            ->sort()
            ->values();

        if ($typeFilter !== 'all') {
            $movementsCollection = $movementsCollection->filter(function ($m) use ($typeFilter) {
                return ($m['type'] ?? '') === $typeFilter;
            });
        }
        
        $movementsCollection = $movementsCollection->sortByDesc('date')->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentResults = $movementsCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();
        
        $movements = new LengthAwarePaginator(
            $currentResults,
            $movementsCollection->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(), 
            ]
        );

        return view('kardex.index', compact(
            'viewId', 'productId', 'branchId', 'dateFrom', 'dateTo',
            'products', 'branch', 'movements', 'showAllProducts', 
            'sourceFilter', 'typeFilter', 
            'availableTypes',
            'perPage' 
        ));
    }

    /**
     * Correlativo 001, 002, 003... por tipo de documento para pedidos cobrados en el rango de fechas.
     * @return array<int, string> movement_id => series (ej. "001")
     */
    private function buildOrderSeriesMap(?int $branchId, string $dateFrom, string $dateTo): array
    {
        $dateFromStart = $dateFrom . ' 00:00:00';
        $dateToEnd = $dateTo . ' 23:59:59';

        $orders = OrderMovement::query()
            ->whereIn('status', ['FINALIZADO', 'F'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('movement', fn ($m) => $m->whereBetween('moved_at', [$dateFromStart, $dateToEnd]))
            ->with('movement:id,document_type_id,branch_id,moved_at')
            ->get();

        $movements = $orders->map(fn ($om) => $om->movement)->filter();
        $movements = $movements->sortBy(fn ($m) => [$m->document_type_id, $m->moved_at?->format('Y-m-d H:i:s'), $m->id])->values();

        $map = [];
        $countByDocType = [];
        foreach ($movements as $mov) {
            $key = $mov->document_type_id . '_' . ($mov->branch_id ?? 0);
            $countByDocType[$key] = ($countByDocType[$key] ?? 0) + 1;
            $map[$mov->id] = str_pad((string) $countByDocType[$key], 3, '0', STR_PAD_LEFT);
        }

        return $map;
    }

    private function buildKardexMovements(int $productId, ?int $branchId, string $dateFrom, string $dateTo, array $orderSeriesMap = []): \Illuminate\Support\Collection
    {
        $dateFromStart = $dateFrom . ' 00:00:00';
        $dateToEnd = $dateTo . ' 23:59:59';

        $rows = collect();

        // 1. WarehouseMovementDetail (entradas y salidas según tipo de documento)
        $warehouseDetails = WarehouseMovementDetail::query()
            ->where('product_id', $productId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('warehouseMovement.movement', fn ($q) => $q->whereBetween('moved_at', [$dateFromStart, $dateToEnd]))
            ->with(['warehouseMovement.movement.documentType', 'unit'])
            ->get();

        $product = Product::with('baseUnit')->find($productId);
        $unitName = $product?->baseUnit?->description ?? $product?->baseUnit?->abbreviation ?? '-';

        foreach ($warehouseDetails as $d) {
            $mov = $d->warehouseMovement?->movement;
            if (!$mov) {
                continue;
            }
            // Entrada: prefijo E- o tipo documento Entrada; Salida: prefijo S- o tipo documento Salida
            $docName = strtolower($mov->documentType?->name ?? '');
            $isEntry = str_starts_with((string) $mov->number, 'E-')
                || str_contains($docName, 'entrada')
                || str_contains($docName, 'entry');
            $qty = (float) $d->quantity;
            $detailUnit = $d->unit?->description ?? $d->unit?->abbreviation ?? $unitName;
            $rows->push([
                'date' => $mov->moved_at?->format('Y-m-d H:i:s'),
                'date_sort' => $mov->moved_at?->format('Y-m-d H:i:s'),
                'number' => $mov->number,
                'type' => $isEntry ? 'Entrada' : 'Salida',
                'entry' => $isEntry ? $qty : 0,
                'exit' => $isEntry ? 0 : $qty,
                'unit' => $detailUnit,
                'unit_price' => null,
                'origin' => $mov->movementType?->description . ' - ' . $mov->number
            ]);
        }

        // 2. SalesMovementDetail (siempre salida)
        $salesDetails = SalesMovementDetail::query()
            ->where('product_id', $productId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('salesMovement.movement', fn ($q) => $q->whereBetween('moved_at', [$dateFromStart, $dateToEnd]))
            ->with(['salesMovement.movement.documentType', 'unit'])
            ->get();

        foreach ($salesDetails as $d) {
            $mov = $d->salesMovement?->movement;
            if (!$mov) {
                continue;
            }
            $docTypeName = $mov->documentType?->name ?? 'Venta';
            $qty = (float) $d->quantity;
            $detailUnit = $d->unit?->description ?? $d->unit?->abbreviation ?? $unitName;
            $unitPrice = $qty > 0 ? (float) $d->amount / $qty : null;

            // Prefijo de serie según tipo de documento: T = ticket, B = boleta, F = factura
            $docNameLower = strtolower($mov->documentType?->name ?? '');
            $seriesPrefix = '';
            if (str_contains($docNameLower, 'boleta')) {
                $seriesPrefix = 'B';
            } elseif (str_contains($docNameLower, 'factura')) {
                $seriesPrefix = 'F';
            } elseif (str_contains($docNameLower, 'ticket')) {
                $seriesPrefix = 'T';
            }

            $series = $mov->salesMovement?->series;
            if ($series && $seriesPrefix) {
                $originV = 'V - ' . $seriesPrefix . $series . ' - ' . $mov->number;
            } elseif ($series) {
                $originV = 'V - ' . $series . ' - ' . $mov->number;
            } else {
                $originV = 'V - ' . $mov->number;
            }

            $rows->push([
                'date' => $mov->moved_at?->format('Y-m-d H:i:s'),
                'date_sort' => $mov->moved_at?->format('Y-m-d H:i:s'),
                'number' => $mov->number,
                'type' => $docTypeName,
                'entry' => 0,
                'exit' => $qty,
                'unit' => $detailUnit,
                'unit_price' => $unitPrice,
                'origin' => $originV,
            ]);
        }

        // 3. OrderMovementDetail (pedidos cobrados = salida de stock)
        $orderDetails = OrderMovementDetail::query()
            ->where('product_id', $productId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('orderMovement', function ($q) use ($dateFromStart, $dateToEnd) {
                $q->whereIn('status', ['FINALIZADO', 'F'])
                    ->whereHas('movement', fn ($m) => $m->whereBetween('moved_at', [$dateFromStart, $dateToEnd]));
            })
            ->with(['orderMovement.movement.documentType', 'orderMovement.movement.movementType', 'unit'])
            ->get();

        foreach ($orderDetails as $d) {
            $om = $d->orderMovement;
            $mov = $om?->movement;
            if (!$mov) {
                continue;
            }
            $docTypeName = $mov->documentType?->name ?? 'Pedido';
            $qty = (float) $d->quantity;
            $detailUnit = $d->unit?->description ?? $d->unit?->abbreviation ?? $unitName;
            $unitPrice = $qty > 0 ? (float) $d->amount / $qty : null;

            // Mismo prefijo que ventas: T = ticket, B = boleta, F = factura
            $docNameLower = strtolower($mov->documentType?->name ?? '');
            $seriesPrefix = '';
            if (str_contains($docNameLower, 'boleta')) {
                $seriesPrefix = 'B';
            } elseif (str_contains($docNameLower, 'factura')) {
                $seriesPrefix = 'F';
            } elseif (str_contains($docNameLower, 'ticket')) {
                $seriesPrefix = 'T';
            }

            // Correlativo 001, 002, 003 por tipo de documento (precalculado en orderSeriesMap)
            $series = $orderSeriesMap[$mov->id] ?? '';
            if ($series !== '' && $seriesPrefix !== '') {
                $originO = 'O - ' . $seriesPrefix . $series . ' - ' . $mov->number;
            } elseif ($series !== '') {
                $originO = 'O - ' . $series . ' - ' . $mov->number;
            } else {
                $originO = 'O - ' . $mov->number;
            }

            $rows->push([
                'date' => $mov->moved_at?->format('Y-m-d H:i:s'),
                'date_sort' => $mov->moved_at?->format('Y-m-d H:i:s'),
                'number' => $mov->number,
                'type' => $docTypeName,
                'entry' => 0,
                'exit' => $qty,
                'unit' => $detailUnit,
                'unit_price' => $unitPrice,
                'origin' => $originO,
            ]);
        }

        $rows = $rows->sortBy('date_sort')->values();

        // Calcular saldo acumulado (saldo inicial antes del período)
        $openingBalance = $this->getOpeningBalance($productId, $branchId, $dateFromStart);
        $balance = $openingBalance;

        $result = $rows->map(function ($r) use (&$balance) {
            $previousStock = $balance;
            $balance += ($r['entry'] ?? 0) - ($r['exit'] ?? 0);
            $r['previous_stock'] = $previousStock;
            $r['balance'] = $balance;
            $r['quantity'] = ($r['entry'] ?? 0) > 0 ? $r['entry'] : $r['exit'];
            unset($r['date_sort']);
            return $r;
        });

        if ($openingBalance != 0 && $result->isNotEmpty()) {
            $result->prepend([
                'date' => $dateFrom . ' 00:00',
                'number' => '-',
                'type' => 'Saldo inicial',
                'entry' => 0,
                'exit' => 0,
                'previous_stock' => 0,
                'quantity' => 0,
                'balance' => $openingBalance,
                'unit' => $unitName,
                'unit_price' => null,
                'origin' => '-',
            ]);
        }

        return $result->values();
    }

    private function getOpeningBalance(int $productId, ?int $branchId, string $beforeDate): float
    {
        // Usamos el stock actual de product_branch como punto de partida (fuente de verdad),
        // y revertimos todos los movimientos desde beforeDate hasta hoy para obtener
        // el saldo en ese punto en el tiempo.
        $productBranch = ProductBranch::where('product_id', $productId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->first();

        $balance = (float) ($productBranch?->stock ?? 0);

        // Revertir entradas/salidas de almacén desde beforeDate hasta hoy
        $warehouseDetails = WarehouseMovementDetail::query()
            ->where('product_id', $productId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('warehouseMovement.movement', fn ($q) => $q->where('moved_at', '>=', $beforeDate))
            ->with('warehouseMovement.movement.documentType')
            ->get();

        foreach ($warehouseDetails as $d) {
            $mov = $d->warehouseMovement?->movement;
            if (!$mov) {
                continue;
            }
            $qty = (float) $d->quantity;
            $docName = strtolower($mov->documentType?->name ?? '');
            $isEntry = str_starts_with((string) $mov->number, 'E-')
                || str_contains($docName, 'entrada')
                || str_contains($docName, 'entry');
            // Invertir efecto: si fue entrada la restamos, si fue salida la sumamos
            $balance += $isEntry ? -$qty : $qty;
        }

        // Revertir ventas desde beforeDate (las ventas redujeron stock, las sumamos de vuelta)
        $salesQty = SalesMovementDetail::query()
            ->where('product_id', $productId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('salesMovement.movement', fn ($q) => $q->where('moved_at', '>=', $beforeDate))
            ->sum('quantity');
        $balance += (float) $salesQty;

        // Revertir pedidos finalizados desde beforeDate
        $orderQty = OrderMovementDetail::query()
            ->where('product_id', $productId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('orderMovement', function ($q) use ($beforeDate) {
                $q->whereIn('status', ['FINALIZADO', 'F'])
                    ->whereHas('movement', fn ($m) => $m->where('moved_at', '>=', $beforeDate));
            })
            ->sum('quantity');
        $balance += (float) $orderQty;

        return $balance;
    }

    private function getProductIdsWithMovements(?int $branchId, string $dateFrom, string $dateTo): array
    {
        $dateFromStart = $dateFrom . ' 00:00:00';
        $dateToEnd = $dateTo . ' 23:59:59';
        $ids = collect();

        $ids = $ids->merge(
            WarehouseMovementDetail::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereHas('warehouseMovement.movement', fn ($q) => $q->whereBetween('moved_at', [$dateFromStart, $dateToEnd]))
                ->pluck('product_id')
        );

        $ids = $ids->merge(
            SalesMovementDetail::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereHas('salesMovement.movement', fn ($q) => $q->whereBetween('moved_at', [$dateFromStart, $dateToEnd]))
                ->pluck('product_id')
        );

        $ids = $ids->merge(
            OrderMovementDetail::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereHas('orderMovement', function ($q) use ($dateFromStart, $dateToEnd) {
                    $q->whereIn('status', ['FINALIZADO', 'F'])
                        ->whereHas('movement', fn ($m) => $m->whereBetween('moved_at', [$dateFromStart, $dateToEnd]));
                })
                ->pluck('product_id')
        );

        return $ids->unique()->filter()->values()->all();
    }
}
