<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $sale->number }}</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: "Liberation Sans", "DejaVu Sans", Arial, Helvetica, sans-serif;
            color: #000;
        }

        @page {
            size: 80mm auto;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 80mm;
            background: #fff;
        }

        body {
            font-size: 11px;
            line-height: 1.18;
        }

        .ticket {
            width: 80mm;
            padding: 10px 12px 12px;
        }

        .center {
            text-align: center;
        }

        .logo-wrap {
            margin-bottom: 6px;
        }

        .logo {
            display: block;
            max-width: 132px;
            max-height: 52px;
            margin: 0 auto;
            object-fit: contain;
        }

        .company {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.02;
        }

        .subhead {
            margin: 2px 0 0;
            font-size: 16px;
            line-height: 1.04;
        }

        .doc-code {
            margin: 6px 0 0;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.04;
        }

        .separator {
            border-top: 1px dashed #8ea9cf;
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table td {
            padding: 1px 0;
            vertical-align: top;
            font-size: 15px;
            line-height: 1.04;
        }

        .info-label {
            width: 104px;
            font-weight: 800;
            padding-right: 6px;
            white-space: nowrap;
        }

        .info-value {
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .items-table th,
        .items-table td {
            padding: 3px 0;
            font-size: 15px;
        }

        .items-table th {
            font-weight: 800;
            border-bottom: 0.2mm solid #bdbdbd;
        }

        .items-table td {
            vertical-align: top;
        }

        .col-product {
            width: 42%;
            text-align: left;
            padding-right: 8px;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .col-qty {
            width: 15%;
            text-align: right;
        }

        .col-unit {
            width: 19%;
            text-align: right;
        }

        .col-subtotal {
            width: 24%;
            text-align: right;
        }

        .totals-table td {
            padding: 2px 0;
            font-size: 16px;
        }

        .totals-label {
            font-weight: 800;
        }

        .totals-value {
            text-align: right;
            white-space: nowrap;
        }

        .grand-total td {
            border-top: 1px solid #8ea9cf;
            padding-top: 6px;
            font-size: 21px;
            font-weight: 800;
        }

        .notes {
            font-size: 14px;
            line-height: 1.18;
        }

        .notes strong {
            font-weight: 800;
        }

        .footer {
            text-align: center;
            font-size: 13px;
            line-height: 1.15;
        }

        .thanks {
            margin-top: 4px;
            font-size: 15px;
        }
    </style>
</head>
<body>
@php
    $docName = strtoupper($sale->documentType?->name ?? 'TICKET DE VENTA');
    $ticketSeries = $sale->salesMovement?->series ?? '001';
    $docCode = strtoupper(substr($sale->documentType?->name ?? 'T', 0, 1)) . $ticketSeries . '-' . $sale->number;
    $ticketSubtotal = (float) ($sale->salesMovement?->subtotal ?? $sale->orderMovement?->subtotal ?? 0);
    $ticketTax = (float) ($sale->salesMovement?->tax ?? $sale->orderMovement?->tax ?? 0);
    $ticketTotal = (float) ($sale->salesMovement?->total ?? $sale->orderMovement?->total ?? 0);
@endphp

<div class="ticket">
    <div class="center">
        @if(!empty($logoFileUrl) || !empty($logoUrl))
            <div class="logo-wrap">
                <img src="{{ $logoFileUrl ?: $logoUrl }}" alt="Logo sucursal" class="logo">
            </div>
        @endif
        <p class="company">{{ strtoupper($branchForLogo->legal_name ?? 'SUCURSAL') }}</p>
        <p class="subhead">RUC: {{ $branchForLogo->ruc ?? '-' }}</p>
        <p class="subhead">{{ $docName }}</p>
        <p class="doc-code">{{ $docCode }}</p>
    </div>

    <div class="separator"></div>

    <table class="info-table">
        <tr>
            <td class="info-label">Fecha:</td>
            <td class="info-value">{{ optional($sale->moved_at)->format('d/m/Y H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Cliente:</td>
            <td class="info-value">{{ $sale->person_name ?? 'CLIENTES VARIOS' }}</td>
        </tr>
        <tr>
            <td class="info-label">Dir.:</td>
            <td class="info-value">{{ $sale->person?->address ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">RUC/DNI:</td>
            <td class="info-value">{{ $sale->person?->document_number ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Forma pago:</td>
            <td class="info-value">{{ $paymentLabel }}</td>
        </tr>
    </table>

    <div class="separator"></div>

    <table class="items-table">
        <thead>
        <tr>
            <th class="col-product">Prod.</th>
            <th class="col-qty">Cant</th>
            <th class="col-unit">P.Unit.</th>
            <th class="col-subtotal">Subt.</th>
        </tr>
        </thead>
        <tbody>
        @foreach($details as $detail)
            @php
                $qty = (float) $detail->quantity;
                $lineTotal = (float) $detail->amount;
                $unitPrice = $qty > 0 ? ($lineTotal / $qty) : 0;
            @endphp
            <tr>
                <td class="col-product">{{ $detail->description ?? $detail->product?->description ?? '-' }}</td>
                <td class="col-qty">{{ number_format($qty, 2) }}</td>
                <td class="col-unit">{{ number_format($unitPrice, 2) }}</td>
                <td class="col-subtotal">{{ number_format($lineTotal, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="separator"></div>

    <table class="totals-table">
        <tr>
            <td class="totals-label">Op. gravada:</td>
            <td class="totals-value">S/ {{ number_format($ticketSubtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="totals-label">IGV:</td>
            <td class="totals-value">S/ {{ number_format($ticketTax, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>TOTAL:</td>
            <td class="totals-value">S/ {{ number_format($ticketTotal, 2) }}</td>
        </tr>
    </table>

    @if($sale->comment)
        <div class="separator"></div>
        <div class="notes"><strong>Notas:</strong> {{ $sale->comment }}</div>
    @endif

    <div class="separator"></div>

    <div class="footer">
        Impreso: {{ $printedAt->format('d/m/Y H:i:s') }}<br>
        <div class="thanks">Gracias por su preferencia</div>
    </div>
</div>

@if(($autoPrint ?? true) === true)
<script>
    window.addEventListener('load', function () { window.print(); });
</script>
@endif
</body>
</html>
