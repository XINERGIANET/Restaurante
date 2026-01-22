<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $employees = Employee::where('deleted', 0)->paginate(10);
        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('employees.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'document' => 'required|string|max:11',
            'birth_date' => 'required|date',
            'phone' => 'required|string|max:15',
            'address' => 'required|string|max:255',
            'is_motoriced' => 'required|integer',
            'pin' => 'required|string|unique:employees,pin',
        ], [
            'pin.unique' => 'ESte PIN ya esta registrado para otro empleado.',
        ]);

        Employee::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'document' => $request->document,
            'birth_date' => $request->birth_date,
            'phone' => $request->phone,
            'address' => $request->address,
            'pin' => $request->pin,
            'is_motoriced' => $request->is_motoriced,
            'deleted' => 0, // Por defecto, el colaborador está activo
        ]);

        return redirect()->route('employees.index')
            ->with('success', 'Empleado creado correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $employee = Employee::findOrFail($id);

        return view('employees.show', compact('employee'));
    }
    
    public function validarPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4', 
        ]);

        $employee = Employee::where('pin', $request->pin)->first();

        if ($employee) {
            return response()->json([
                'valid' => true,
                'employeeId' => $employee->id,
                'employeeName' => $employee->name,
                'is_motoriced' => $employee->is_motoriced,
            ]);
        }

        return response()->json(['valid' => false]);
    }

    public function validarPinMotorizado(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $employee = Employee::where('pin', $request->pin)
        ->where('is_motoriced', 1)
        ->where('deleted', 0)
        ->first();

        if ($employee) {
            return response()->json([
                'valid' => true,
                'employeeId' => $employee->id,
                'employeeName' => $employee->name,
                'is_motoriced' => $employee->is_motoriced,
                'message' => 'PIN motorizado correcto',
            ], 200);
        }
        else {
            return response()->json([
                'valid' => false,
                'message' => 'PIN motorizado incorrecto',
            ], 401);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {

        try {
            $employee = Employee::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$employee) {
                return response()->json(['error' => 'Empleado no encontrado.'], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Datos del empleado para edición',
                'data' => $employee
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error al obtener los datos del empleado: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener los datos para edición: ' . $e->getMessage()
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
    public function update(Request $request, $id)
    {
        try {
            $employee = Employee::where('id', $id)
                ->where('deleted', 0)
                ->first();
            if (!$employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empleado no encontrado'
                ], 404);
            }
            $employee->update($request->all());
            return response()->json([
                'status' => true,
                'message' => 'Empleado actualizado correctamente'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error al actualizar el empleado: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error al actualizar el empleado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $employee = Employee::where('id', $id)
                ->where('deleted', 0)
                ->first();
            if (!$employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empleado no encontrado'
                ], 404);
            }
            $employee->update(['deleted' => 1]);

            // Redirigir con un mensaje de éxito
            return response()->json([
                'status' => true,
                'message' => 'Empleado eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            Log::error('error en' . $e->getMessage());
        }    // Buscar el colaborador por ID
    }
}
