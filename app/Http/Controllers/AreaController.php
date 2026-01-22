<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $areas = Area::where('deleted', 0)->paginate(10);
        return view('areas.index', compact('areas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('areas.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // Validar el campo 'name' requerido y único
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name,NULL,id,deleted,0',
        ]);

        // Crear la área
        Area::create([
            'name' => $validated['name'],
            'deleted' => 0, // Por defecto, el área está activa
        ]);

        return redirect()->route('areas.index')
            ->with('success', 'Área creada exitosamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id): JsonResponse
    {
        try {
            $area = Area::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$area) {
                return response()->json([
                    'status' => false,
                    'message' => 'Área no encontrada'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Datos del área para edición',
                'data' => $area
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener datos del área para edición: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener datos para edición'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $area = Area::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$area) {
                return response()->json([
                    'status' => false,
                    'message' => 'Área no encontrada'
                ], 404);
            }

            // Validar el campo 'name' requerido y único (excluyendo el registro actual)
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:areas,name,' . $id . ',id,deleted,0',
            ]);

            // Actualizar el área
            $area->update([
                'name' => $validated['name'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Área actualizada correctamente',
                'data' => $area->fresh()
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al actualizar el área: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $area = Area::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$area) {
                return response()->json([
                    'status' => false,
                    'message' => 'Área no encontrada'
                ], 404);
            }

            // Verificar si el área está siendo usada en mesas
            $isUsedInTables = DB::table('tables')
                ->where('area_id', $id)
                ->where('deleted', 0)
                ->exists();

            if ($isUsedInTables) {
                return response()->json([
                    'status' => false,
                    'message' => 'No se puede eliminar el área porque está siendo utilizada en mesas'
                ], 400);
            }

            // Soft delete - marcar como eliminado
            $area->update(['deleted' => 1]);

            return response()->json([
                'status' => true,
                'message' => 'Área eliminada correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al eliminar el área: ' . $e->getMessage()
            ], 500);
        }
    }
}
