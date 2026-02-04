<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DocumentType;
use App\Models\Movement;
use App\Models\MovementType;
use App\Models\Person;
use App\Models\Product;
use Illuminate\Http\Request;

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
                $imageUrl = $product->image 
                    ? asset('storage/' . $product->image) 
                    : asset('images/no-image.png');
                return [
                    'id' => $product->id,
                    'name' => $product->description,
                    'price' => 0.00,
                    'img' => $imageUrl,
                    'category' => $product->category ? $product->category->description : 'Sin categoría'
                ];
            });
        return view('sales.create', [
            'products' => $products,
        ]);
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
