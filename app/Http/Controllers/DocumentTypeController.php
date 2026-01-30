<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\MovementType;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $documentTypes = DocumentType::query()
            ->with('movementType')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $movementTypes = MovementType::query()->orderBy('description')->get(['id', 'description']);

        return view('document_types.index', [
            'documentTypes' => $documentTypes,
            'movementTypes' => $movementTypes,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'in:add,subtract,none'],
            'movement_type_id' => ['required', 'integer', 'exists:movement_types,id'],
        ]);

        DocumentType::create($data);

        return redirect()
            ->route('admin.document-types.index')
            ->with('status', 'Tipo de documento creado correctamente.');
    }

    public function edit(DocumentType $documentType)
    {
        $movementTypes = MovementType::query()->orderBy('description')->get(['id', 'description']);

        return view('document_types.edit', [
            'documentType' => $documentType,
            'movementTypes' => $movementTypes,
        ]);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'in:add,subtract,none'],
            'movement_type_id' => ['required', 'integer', 'exists:movement_types,id'],
        ]);

        $documentType->update($data);

        return redirect()
            ->route('admin.document-types.index')
            ->with('status', 'Tipo de documento actualizado correctamente.');
    }

    public function destroy(DocumentType $documentType)
    {
        $documentType->delete();

        return redirect()
            ->route('admin.document-types.index')
            ->with('status', 'Tipo de documento eliminado correctamente.');
    }
}
