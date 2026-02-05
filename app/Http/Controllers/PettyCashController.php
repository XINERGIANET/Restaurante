<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Movement;
use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Models\CashRegister;
use App\Models\PaymentConcept;
use App\Models\CashMovements;
use App\Models\Shift;
use App\Models\CashShiftRelation;


class PettyCashController extends Controller
{

    public function redirectBase()
    {
        $firstBox = CashRegister::where('status', '1')->first();
        if ($firstBox) {
            return redirect()->route('admin.petty-cash.index', ['cash_register_id' => $firstBox->id]);
        }
        abort(404, 'No hay cajas registradas');
    }

    public function index(Request $request, $cash_register_id = null)
    {
        $search = $request->input('search');

        $cashRegisters = CashRegister::where('status', '1')->orderBy('number', 'asc')->get();
        $selectedBoxId = $cash_register_id;

        if (empty($selectedBoxId) && $cashRegisters->isNotEmpty()) {
            $selectedBoxId = $cashRegisters->first()->id;
        }

        // --- LÓGICA DE ESTADO DE CAJA (MODIFICADO) ---
        $lastShiftRelation = CashShiftRelation::where('branch_id', session('branch_id'))
            ->whereHas('cashMovementStart', function ($query) use ($selectedBoxId) {
                $query->where('cash_register_id', $selectedBoxId);
            })
            ->latest('id') 
            ->first();

        $hasOpening = $lastShiftRelation && $lastShiftRelation->status == '1';

        $documentTypes = DocumentType::where('movement_type_id', 4)->get();
        
        $docIngreso = $documentTypes->firstWhere('name', 'Ingreso'); 
        $ingresoDocId = $docIngreso ? $docIngreso->id : '';
        $docEgreso = $documentTypes->firstWhere('name', 'Egreso');
        $egresoDocId = $docEgreso ? $docEgreso->id : '';

        $conceptsIngreso = PaymentConcept::where('type', 'I')
            ->where(function($query) {
                $query->where('restricted', false)
                    ->orWhere('description', 'like', '%Apertura%'); 
            })
            ->get();
            
        $conceptsEgreso = PaymentConcept::where('type', 'E')
            ->where(function($query) {
                $query->where('restricted', false)
                    ->orWhere('description', 'like', '%Cierre%'); 
            })
            ->get();
                                    
       $movements = Movement::query()
            ->with('documentType') 
            ->where('movement_type_id', 4)            
            ->whereHas('cashMovement', function ($query) use ($selectedBoxId) {
                $query->where('cash_register_id', $selectedBoxId);
            })
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('person_name', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%");
                });
            })
            ->orderBy('moved_at', 'desc')
            ->paginate(10);
        
        $shifts = Shift::where('branch_id', session('branch_id'))->get();

        return view('petty_cash.index', [
            'title'           => 'Caja Chica',
            'movements'       => $movements,
            'documentTypes'   => $documentTypes,            
            'hasOpening'      => $hasOpening,  
            'ingresoDocId'    => $ingresoDocId, 
            'egresoDocId'     => $egresoDocId,
            'cashRegisters'   => $cashRegisters,
            'conceptsIngreso' => $conceptsIngreso,
            'conceptsEgreso'  => $conceptsEgreso,            
            'selectedBoxId'   => $selectedBoxId, 
            'shifts'          => $shifts,
        ]);
    }

    public function store(Request $request, $cash_register_id)
    {
        $request->merge(['cash_register_id' => $cash_register_id]);

        $validated = $request->validate([
            'comment'            => 'required|string|max:255',
            'document_type_id'   => 'nullable|exists:document_types,id',
            'payment_concept_id' => 'required|exists:payment_concepts,id',
            'amount'             => 'required|numeric|min:0',
            'shift_id'           => 'required|exists:shifts,id',
        ]);

        try {
            DB::transaction(function () use ($request, $validated, $cash_register_id) {

                // 1. Datos del Turno
                $selectedShift = \App\Models\Shift::findOrFail($request->shift_id);
                $shiftSnapshotData = [
                    'name'       => $selectedShift->name,
                    'start_time' => $selectedShift->start_time,
                    'end_time'   => $selectedShift->end_time
                ];
                $shiftSnapshotJson = json_encode($shiftSnapshotData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $typeId = 4;

                $lastRecord = Movement::select('movements.*')
                    ->join('cash_movements', 'movements.id', '=', 'cash_movements.movement_id')
                    ->where('movements.movement_type_id', $typeId)
                    ->where('cash_movements.cash_register_id', $cash_register_id) 
                    ->latest('movements.id')
                    ->lockForUpdate()
                    ->first();

                if ($lastRecord) {
                    $nextSequence = intval($lastRecord->number) + 1;
                } else {
                    $nextSequence = 1;
                }

                $generatedNumber = str_pad($nextSequence, 8, '0', STR_PAD_LEFT);

                $movement = Movement::create([
                    'number'             => $generatedNumber, 
                    'moved_at'           => now(),
                    'user_id'            => session('user_id'),
                    'user_name'          => session('user_name'),
                    'person_id'          => session('person_id'),
                    'person_name'        => session('person_fullname'),                    
                    'responsible_id'     => session('person_id'),
                    'responsible_name'   => session('person_fullname'),
                    'comment'            => $validated['comment'],
                    'status'             => '1',
                    'movement_type_id'   => $typeId,
                    'document_type_id'   => $request->document_type_id,
                    'branch_id'          => session('branch_id'),
                    'shift_id'           => $selectedShift->id,
                    'shift_snapshot'     => $shiftSnapshotJson,
                ]);

                $box = CashRegister::find($request->cash_register_id); 
                $boxName = $box ? $box->number : 'Caja Desconocida';

                $cashMovement = CashMovements::create([
                    'payment_concept_id' => $validated['payment_concept_id'],
                    'currency'           => 'PEN',
                    'exchange_rate'      => 3.71,
                    'total'              => $validated['amount'],
                    'cash_register_id'   => $cash_register_id,
                    'cash_register'      => $boxName,
                    'shift_id'           => $selectedShift->id,
                    'shift_snapshot'     => $shiftSnapshotJson, 
                    'movement_id'        => $movement->id, 
                    'branch_id'          => session('branch_id'),
                ]);

                $concept = PaymentConcept::find($validated['payment_concept_id']);
                $conceptName = strtolower($concept->description);

                if (str_contains($conceptName, 'apertura')) {
                    CashShiftRelation::create([
                        'started_at'             => now(),
                        'status'                 => '1',
                        'cash_movement_start_id' => $cashMovement->id, 
                        'branch_id'              => session('branch_id'),
                    ]);

                } elseif (str_contains($conceptName, 'cierre')) {
                    $openRelation = CashShiftRelation::where('branch_id', session('branch_id'))
                                                     ->where('status', '1')
                                                     ->latest('id')
                                                     ->first();

                    if ($openRelation) {
                        $openRelation->update([
                            'ended_at'             => now(),
                            'status'               => '0', 
                            'cash_movement_end_id' => $cashMovement->id,
                        ]);
                    }
                }
            }); 

            return redirect()->route('admin.petty-cash.index', ['cash_register_id' => $cash_register_id])
                             ->with('success', 'Movimiento registrado correctamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()])
                         ->withInput();
        }
    }
}