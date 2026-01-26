<?php

namespace App\Http\Controllers;

use App\Models\Modules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ModulesController extends Controller
{
    public function index(){
        $modules = Modules::all();
        return view('modules.index', ['title' => 'Modulos', 'modules' => $modules]);
    }
    
    public function store(Request $request){
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'icon' => 'string|max:255',
                'order_num' => 'required|integer',
            ],
            [
                'name.required' => 'El nombre es requerido',
                'name.string' => 'El nombre debe ser una cadena de texto',
                'name.max' => 'El nombre debe tener menos de 255 caracteres',
                'icon.string' => 'El icono debe ser una cadena de texto',
                'order_num.required' => 'El orden es requerido',
                'order_num.integer' => 'El orden debe ser un número entero',
            ]
        );
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }
        
        try {
            // Subir la imagen
            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $fileName = Str::slug($request->name) . '-' . time() . '.' . $file->getClientOriginalExtension();
                $iconPath = $file->storeAs('modules/icons', $fileName, 'public');
                
                Modules::create([
                    'name' => $request->name,
                    'icon' => $iconPath, // Guardar la ruta del archivo
                    'order_num' => $request->order_num,
                ]);
                
                return response()->json(['success' => 'Modulo creado correctamente'], 200);
            }

            return response()->json(['error' => 'No se pudo subir la imagen'], 400);
        } catch (\Exception $e) {
            Log::error('Error al crear el modulo', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error al crear el modulo: ' . $e->getMessage()], 500);
        }
        
    }

}
