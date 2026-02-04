<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Movement;
use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Models\CashRegister;

class PettyCashController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $documentTypes = DocumentType::where('movement_type_id', 4)->get();
        $hasOpening = Movement::where('comment', 'like', '%Apertura de caja%')
                            ->where('movement_type_id', 4)
                            ->exists();

        $docIngreso = $documentTypes->firstWhere('name', 'Ingreso'); 
        $ingresoDocId = $docIngreso ? $docIngreso->id : '';

        $docEgreso = $documentTypes->firstWhere('name', 'Egreso');
        $egresoDocId = $docEgreso ? $docEgreso->id : '';

        $cashRegisters = CashRegister::where('status', '1')->get();

        $movements = Movement::query()
            ->with('documentType')
            ->where('movement_type_id', 4)
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('person_name', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%");
                });
            })
            ->orderBy('moved_at', 'desc')
            ->paginate(10);

        return view('petty_cash.index', [
            'title'         => 'Caja Chica',
            'movements'     => $movements,
            'documentTypes' => $documentTypes,            
            'hasOpening'    => $hasOpening,   
            'ingresoDocId'  => $ingresoDocId, 
            'egresoDocId'   => $egresoDocId,
            'cashRegisters'  => $cashRegisters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'comment'          => 'required|string|max:255',
            'document_type_id' => 'nullable|exists:document_types,id',
        ]);

        try {
            DB::transaction(function () use ($request, $validated) {

                $typeId = 4;
                $lastRecord = Movement::where('movement_type_id', $typeId)
                                    ->latest('id')
                                    ->lockForUpdate() 
                                    ->first();

                if ($lastRecord) {
                    $nextSequence = intval($lastRecord->number) + 1;
                } else {
                    $nextSequence = 1;
                }

                $generatedNumber = str_pad($nextSequence, 8, '0', STR_PAD_LEFT);

                $dataToInsert = [
                    'number'           => $generatedNumber, 
                    'moved_at'         => now(),

                    'user_id'          => session('user_id'),
                    'user_name'        => session('user_name'),
                    'person_id'        => session('person_id'),
                    'person_name'      => session('person_fullname'),                    
                    'responsible_id'   => session('person_id'),
                    'responsible_name' => session('person_fullname'),

                    'comment'          => $validated['comment'],
                    'status'           => '1',
                    'movement_type_id' => $typeId,
                    'document_type_id' => $request->document_type_id,
                    'branch_id'        => session('branch_id'),

                    'shift_id'         => session('shift_id'),
                    'shift_snapshot'       => session('shift_snapshot'),
                ];
                Movement::create($dataToInsert);
            }); 

            return redirect()->route('admin.petty-cash.index')
                            ->with('success', 'Movimiento registrado correctamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()])
                        ->withInput();
        }
    }
}
