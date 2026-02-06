<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DocumentType;
use App\Models\Movement;
use App\Models\MovementType;
use App\Models\Person;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\SalesMovement;
use App\Models\SalesMovementDetail;
use App\Models\TaxRate;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $sales = Movement::query()
            ->with(['branch', 'person', 'movementType', 'documentType'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('number', 'like', "%{$search}%")
                        ->orWhere('person_name', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'search' => $search,
            'perPage' => $perPage,
        ] + $this->getFormData());
    }

    public function create()
    {
        $products = Product::where('type', 'PRODUCT')
            ->with('category')
            ->get()
            ->map(function($product) {
                $imageUrl = ($product->image && !empty($product->image))
                    ? asset('storage/' . $product->image) 
                    : null;
                return [
                    'id' => $product->id,
                    'name' => $product->description,
                    'price' => 0.00,
                    'img' => $imageUrl,
                    'category' => $product->category ? $product->category->description : 'Sin categoría'
                ];
            });
        $productsBranches = ProductBranch::where('branch_id', session('branch_id'))
            ->with('product')
            ->get()
            ->filter(function($productBranch) {
                return $productBranch->product !== null;
            })
            ->map(function($productBranch) {
                return [
                    'id' => $productBranch->product_id,
                    'name' => $productBranch->product->description,
                    'price' => $productBranch->price,
                    'image' => $productBranch->product->image,
                ];
            });
        return view('sales.create', [
            'products' => $products,
            'productsBranches' => $productsBranches,
        ]);
    }

    public function processSale(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.pId' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.000001',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $branchId = session('branch_id');
            $branch = Branch::findOrFail($branchId);

            // Obtener tipos de movimiento y documento para ventas
            $movementType = MovementType::where('description', 'like', '%venta%')
                ->orWhere('description', 'like', '%sale%')
                ->orWhere('description', 'like', '%Venta%')
                ->first();
            
            if (!$movementType) {
                // Si no existe, tomar el primero disponible
                $movementType = MovementType::first();
            }
            
            $documentType = DocumentType::where('name', 'like', '%boleta%')
                ->orWhere('name', 'like', '%ticket%')
                ->orWhere('name', 'like', '%Boleta%')
                ->first();
            
            if (!$documentType) {
                // Si no existe, tomar el primero disponible
                $documentType = DocumentType::first();
            }

            if (!$movementType || !$documentType) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron los tipos de movimiento o documento necesarios. Por favor, configúralos en el sistema.'
                ], 400);
            }

            // Calcular totales
            $subtotal = 0;
            $tax = 0;
            foreach ($request->items as $item) {
                $itemTotal = $item['qty'] * $item['price'];
                $subtotal += $itemTotal;
            }
            $tax = $subtotal * 0.10; // 10% de impuesto
            $total = $subtotal + $tax;

            // Generar número de movimiento
            $number = 'V-' . date('Ymd') . '-' . str_pad(Movement::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // Crear Movement
            $movement = Movement::create([
                'number' => $number,
                'moved_at' => now(),
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Sistema',
                'person_id' => null, // Público General
                'person_name' => 'Público General',
                'responsible_id' => $user?->id,
                'responsible_name' => $user?->name ?? 'Sistema',
                'comment' => 'Venta desde punto de venta',
                'status' => 'A',
                'movement_type_id' => $movementType->id,
                'document_type_id' => $documentType->id,
                'branch_id' => $branchId,
                'parent_movement_id' => null,
            ]);

            // Crear SalesMovement
            $salesMovement = SalesMovement::create([
                'branch_snapshot' => [
                    'id' => $branch->id,
                    'legal_name' => $branch->legal_name,
                ],
                'series' => '001',
                'year' => date('Y'),
                'detail_type' => 'DETAILED',
                'consumption' => 'N',
                'payment_type' => 'CASH',
                'status' => '',
                'sale_type' => 'RETAIL',
                'currency' => 'PEN',
                'exchange_rate' => 1.000,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'movement_id' => $movement->id,
                'branch_id' => $branchId,
            ]);

            // Crear SalesMovementDetails
            foreach ($request->items as $item) {
                $product = Product::with('baseUnit')->findOrFail($item['pId']);
                $productBranch = ProductBranch::with('taxRate')
                    ->where('product_id', $item['pId'])
                    ->where('branch_id', $branchId)
                    ->first();

                if (!$productBranch) {
                    throw new \Exception("Producto {$product->description} no disponible en esta sucursal");
                }

                $unit = $product->baseUnit;
                if (!$unit) {
                    throw new \Exception("El producto {$product->description} no tiene una unidad base configurada");
                }

                $taxRate = $productBranch->taxRate;
                $taxRateValue = $taxRate ? ($taxRate->tax_rate / 100) : 0.10;

                $itemSubtotal = $item['qty'] * $item['price'];
                $itemTax = $itemSubtotal * $taxRateValue;

                SalesMovementDetail::create([
                    'detail_type' => 'DETALLADO',
                    'sales_movement_id' => $salesMovement->id,
                    'code' => $product->code,
                    'description' => $product->description,
                    'product_id' => $product->id,
                    'product_snapshot' => [
                        'id' => $product->id,
                        'code' => $product->code,
                        'description' => $product->description,
                    ],
                    'unit_id' => $unit->id,
                    'tax_rate_id' => $taxRate?->id,
                    'tax_rate_snapshot' => $taxRate ? [
                        'id' => $taxRate->id,
                        'description' => $taxRate->description,
                        'tax_rate' => $taxRate->tax_rate,
                    ] : null,
                    'quantity' => $item['qty'],
                    'amount' => $itemSubtotal + $itemTax,
                    'discount_percentage' => 0.000000,
                    'original_amount' => $itemSubtotal,
                    'comment' => $item['note'] ?? null,
                    'parent_detail_id' => null,
                    'complements' => [],
                    'status' => 'A',
                    'branch_id' => $branchId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta procesada correctamente',
                'data' => [
                    'movement_id' => $movement->id,
                    'number' => $number,
                    'total' => $total,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $this->validateSale($request);

        $user = $request->user();
        $personName = null;
        if (!empty($data['person_id'])) {
            $person = Person::find($data['person_id']);
            $personName = $person ? ($person->first_name . ' ' . $person->last_name) : null;
        }

        Movement::create([
            'number' => $data['number'],
            'moved_at' => $data['moved_at'],
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? '',
            'person_id' => $data['person_id'] ?? null,
            'person_name' => $personName ?? '',
            'responsible_id' => $user?->id,
            'responsible_name' => $user?->name ?? '',
            'comment' => $data['comment'] ?? '',
            'status' => $data['status'],
            'movement_type_id' => $data['movement_type_id'],
            'document_type_id' => $data['document_type_id'],
            'branch_id' => $data['branch_id'],
            'parent_movement_id' => $data['parent_movement_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.sales.index')
            ->with('status', 'Venta creada correctamente.');
    }

    public function edit(Movement $sale)
    {
        return view('sales.edit', [
            'sale' => $sale,
        ] + $this->getFormData($sale));
    }

    public function update(Request $request, Movement $sale)
    {
        $data = $this->validateSale($request);

        $personName = null;
        if (!empty($data['person_id'])) {
            $person = Person::find($data['person_id']);
            $personName = $person ? ($person->first_name . ' ' . $person->last_name) : null;
        }

        $sale->update([
            'number' => $data['number'],
            'moved_at' => $data['moved_at'],
            'person_id' => $data['person_id'] ?? null,
            'person_name' => $personName ?? '',
            'comment' => $data['comment'] ?? '',
            'status' => $data['status'],
            'movement_type_id' => $data['movement_type_id'],
            'document_type_id' => $data['document_type_id'],
            'branch_id' => $data['branch_id'],
            'parent_movement_id' => $data['parent_movement_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.sales.index')
            ->with('status', 'Venta actualizada correctamente.');
    }

    public function destroy(Movement $sale)
    {
        $sale->delete();

        return redirect()
            ->route('admin.sales.index')
            ->with('status', 'Venta eliminada correctamente.');
    }

    private function validateSale(Request $request): array
    {
        return $request->validate([
            'number' => ['required', 'string', 'max:255'],
            'moved_at' => ['required', 'date'],
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'comment' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:1'],
            'movement_type_id' => ['required', 'integer', 'exists:movement_types,id'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'parent_movement_id' => ['nullable', 'integer', 'exists:movements,id'],
        ]);
    }

    private function getFormData(?Movement $sale = null): array
    {
        $branches = Branch::query()->orderBy('legal_name')->get(['id', 'legal_name']);
        $people = Person::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $movementTypes = MovementType::query()->orderBy('description')->get(['id', 'description']);
        $documentTypes = DocumentType::query()->orderBy('name')->get(['id', 'name']);

        return [
            'branches' => $branches,
            'people' => $people,
            'movementTypes' => $movementTypes,
            'documentTypes' => $documentTypes,
        ];
    }
}
