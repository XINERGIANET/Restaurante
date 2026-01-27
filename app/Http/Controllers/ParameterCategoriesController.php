<?php

namespace App\Http\Controllers;

use App\Models\ParameterCategories;
use Illuminate\Http\Request;

class ParameterCategoriesController extends Controller
{
    public function index(Request $request){
        $parameterCategories = ParameterCategories::all();
        $search = $request->input('search');
        return view('parameters.categoriesParam', compact('parameterCategories', 'search'));
    }
    public function store(Request $request){
        $request->validate([
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

    public function destroy(ParameterCategories $parameterCategory){
        $parameterCategory->delete();
        return redirect()->route('admin.parameters.categories.index')->with('status', 'Categoria eliminada correctamente');
    }

    public function update(Request $request, ParameterCategories $parameterCategory){
        $request->validate([
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
