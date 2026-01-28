<?php

namespace App\Http\Controllers;

use App\Models\ParameterCategories;
use App\Models\Parameters;
use Illuminate\Http\Request;

class ParameterCategoriesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $parameterCategories = ParameterCategories::query()
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'ilike', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
        return view('parameters.categoriesParam', [
            'parameterCategories' => $parameterCategories,
            'search' => $search,
            'perPage' => $perPage,
            'allowedPerPage' => $allowedPerPage,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate(
            [
                'description' => 'required|string|max:255',
            ],
            [
                'description.required' => 'La descripcion es requerida',
                'description.string' => 'La descripcion debe ser una cadena de texto',
                'description.max' => 'La descripcion debe tener menos de 255 caracteres',
            ]
        );
        ParameterCategories::create($request->all());
        return redirect()->route('admin.parameters.categories.index')->with('status', 'Categoria creada correctamente');
    }

    public function destroy(ParameterCategories $parameterCategory)
    {
        // Evitar eliminar una categoría si tiene parámetros relacionados (incluyendo soft-deleted)
        $hasRelatedParameters = Parameters::withTrashed()
            ->where('parameter_category_id', $parameterCategory->id)
            ->exists();

        if ($hasRelatedParameters) {
            return redirect()
                ->route('admin.parameters.categories.index')
                ->with('error', 'No se puede eliminar esta categoría porque tiene parámetros relacionados.');
        }

        $parameterCategory->delete();
        return redirect()->route('admin.parameters.categories.index')->with('status', 'Categoria eliminada correctamente');
    }

    public function update(Request $request, ParameterCategories $parameterCategory)
    {
        $request->validate(
            [
                'description' => 'required|string|max:255',
            ],
            [
                'description.required' => 'La descripcion es requerida',
                'description.string' => 'La descripcion debe ser una cadena de texto',
                'description.max' => 'La descripcion debe tener menos de 255 caracteres',
            ]
        );
        $parameterCategory->update($request->all());
        return redirect()->route('admin.parameters.categories.index')->with('status', 'Categoria actualizada correctamente');
    }
}
