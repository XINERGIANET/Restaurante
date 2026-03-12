<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de caja - {{ $shift->cashMovementEnd?->movement?->number ?? 'Turno' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 12px; margin: 0; padding: 16px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 8px; text-align: center; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #6b7280; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 9999px; font-size: 10px; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .section { margin-bottom: 16px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media print {
            body { padding: 8px; }
        }
    </style>
</head>
<body>
    @php
        $movApertura = $shift->cashMovementStart?->movement;
        $movCierre = $shift->cashMovementEnd?->movement;

        $ingresosTotal = 0;
        $egresosTotal = 0;
        $desgloseIngresos = [];
        $desgloseEgresos = [];

        if ($shift->movements) {
            foreach ($shift->movements as $mov) {
                if ($mov->id == $shift->cash_movement_start_id || $mov->id == $shift->cash_movement_end_id) {
                    continue;
                }
                if ($mov->paymentConcept && $mov->details) {
                    $tipo = $mov->paymentConcept->type;
                    foreach ($mov->details as $detail) {
                        $metodo = $detail->paymentMethod->name ?? ($detail->payment_method ?? 'Otros');
                        $monto = $detail->amount;
                        if ($tipo === 'I') {
                            $ingresosTotal += $monto;
                            $desgloseIngresos[$metodo] = ($desgloseIngresos[$metodo] ?? 0) + $monto;
                        } elseif ($tipo === 'E') {
                            $egresosTotal += $monto;
                            $desgloseEgresos[$metodo] = ($desgloseEgresos[$metodo] ?? 0) + $monto;
                        }
                    }
                }
            }
        }
        $neto = $ingresosTotal - $egresosTotal;
    @endphp

    <h1>Cierre de caja</h1>
    <p class="muted text-center">
        Caja: {{ $shift->cashMovementStart?->cashRegister?->number ?? '-' }} ·
        Sucursal: {{ $shift->branch?->name ?? '-' }}<br>
        Impreso el {{ $printedAt->format('d/m/Y H:i:s') }}
    </p>

    <div class="section grid-2">
        <div>
            <h2>Apertura</h2>
            <table>
                <tr><th>Persona</th><td>{{ $movApertura?->person_name ?: '-' }}</td></tr>
                <tr><th>Responsable</th><td>{{ $movApertura?->responsible_name ?: '-' }}</td></tr>
                <tr><th>Documento</th><td>
                    {{ $movApertura?->movementType?->description ?? '-' }}
                    {{ $movApertura?->documentType?->name ? ' - '.$movApertura->documentType->name : '' }}
                    {{ $movApertura?->salesMovement?->series ?? '' }}-{{ $movApertura?->number ?? '-' }}
                </td></tr>
                <tr><th>Fecha / Hora</th><td>
                    @if($shift->started_at)
                        {{ \Carbon\Carbon::parse($shift->started_at)->format('d/m/Y H:i') }}
                    @else
                        -
                    @endif
                </td></tr>
                <tr><th>Monto apertura</th><td class="text-right">
                    S/ {{ number_format((float) ($shift->cashMovementStart->total ?? 0), 2) }}
                </td></tr>
            </table>
        </div>
        <div>
            <h2>Cierre</h2>
            <table>
                <tr><th>Persona</th><td>{{ $movCierre?->person_name ?: '-' }}</td></tr>
                <tr><th>Responsable</th><td>{{ $movCierre?->responsible_name ?: '-' }}</td></tr>
                <tr><th>Documento</th><td>
                    {{ $movCierre?->movementType?->description ?? '-' }}
                    {{ $movCierre?->documentType?->name ? ' - '.$movCierre->documentType->name : '' }}
                    {{ $movCierre?->salesMovement?->series ?? '' }}-{{ $movCierre?->number ?? '-' }}
                </td></tr>
                <tr><th>Fecha / Hora</th><td>
                    @if($shift->ended_at)
                        {{ \Carbon\Carbon::parse($shift->ended_at)->format('d/m/Y H:i') }}
                    @else
                        --
                    @endif
                </td></tr>
                <tr><th>Monto cierre</th><td class="text-right">
                    S/ {{ number_format((float) ($shift->cashMovementEnd->total ?? 0), 2) }}
                </td></tr>
            </table>
        </div>
    </div>

    <div class="section">
        <h2>Detalle de movimientos</h2>
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th>Método</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shift->movements as $mov)
                    @if($mov->id == $shift->cash_movement_start_id || $mov->id == $shift->cash_movement_end_id)
                        @continue
                    @endif
                    @if(!$mov->paymentConcept || !$mov->details)
                        @continue
                    @endif
                    @foreach($mov->details as $detail)
                        <tr>
                            <td>
                                @if($mov->paymentConcept->type === 'I')
                                    <span class="badge badge-success">Ingreso</span>
                                @elseif($mov->paymentConcept->type === 'E')
                                    <span class="badge badge-danger">Egreso</span>
                                @else
                                    <span class="badge">{{ $mov->paymentConcept->type }}</span>
                                @endif
                            </td>
                            <td>{{ $mov->paymentConcept->description ?? '-' }}</td>
                            <td>{{ $detail->paymentMethod->name ?? ($detail->payment_method ?? 'Otros') }}</td>
                            <td class="text-right">S/ {{ number_format((float) $detail->amount, 2) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="4" class="text-center muted">Sin movimientos operativos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section grid-2">
        <div>
            <h2>Resumen Ingresos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Método</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($desgloseIngresos as $metodo => $monto)
                        <tr>
                            <td>{{ $metodo }}</td>
                            <td class="text-right">S/ {{ number_format($monto, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center muted">Sin ingresos</td>
                        </tr>
                    @endforelse
                    <tr>
                        <th>Total ingresos</th>
                        <th class="text-right">S/ {{ number_format($ingresosTotal, 2) }}</th>
                    </tr>
                </tbody>
            </table>
        </div>
        <div>
            <h2>Resumen Egresos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Método</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($desgloseEgresos as $metodo => $monto)
                        <tr>
                            <td>{{ $metodo }}</td>
                            <td class="text-right">S/ {{ number_format($monto, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center muted">Sin egresos</td>
                        </tr>
                    @endforelse
                    <tr>
                        <th>Total egresos</th>
                        <th class="text-right">S/ {{ number_format($egresosTotal, 2) }}</th>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section">
        <h2>Balance final</h2>
        <table>
            <tr>
                <th>Balance operativo (Ingresos - Egresos)</th>
                <td class="text-right">S/ {{ number_format($neto, 2) }}</td>
            </tr>
            <tr>
                <th>Observaciones</th>
                <td>{{ $movCierre?->comment ?? '-' }}</td>
            </tr>
        </table>
    </div>

    @if(($autoPrint ?? false) === true)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>

