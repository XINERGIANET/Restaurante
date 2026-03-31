<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $sale->number }}</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
        }

        @page {
            size: 80mm auto;
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 80mm;
            background: #fff;
        }

        body {
            font-size: 14px;
            line-height: 1.08;
            display: flex;
            justify-content: center;
        }

        .ticket {
            width: 75.5mm;
            margin: 0 auto;
            padding: 2mm 0 2mm;
        }

        .center {
            text-align: center;
        }

        .logo {
            display: block;
            max-width: 44mm;
            max-height: 18mm;
            margin: 0 auto 2mm;
            object-fit: contain;
        }

        .company {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: .3px;
        }

        .subhead {
            margin: 0;
            font-size: 12px;
        }

        .doc-code {
            margin: 1mm 0 1.2mm;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: .2px;
        }

        .separator {
            border-top: 1px dashed #9fb5d4;
            margin: 1.6mm 0;
        }

        .info-table,
        .items-table,
        .totals-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            vertical-align: top;
            padding: 0;
        }

        .info-label {
            width: 16mm;
            font-weight: 800;
            padding-right: .8mm;
            white-space: nowrap;
        }

        .info-value {
            word-break: break-word;
        }

        .items-table th,
        .items-table td {
            padding: .45mm 0;
        }

        .items-table thead th {
            font-size: 12px;
            font-weight: 800;
            border-bottom: 1px solid #b7b7b7;
        }

        .items-table tbody tr:last-child td {
            padding-bottom: .8mm;
        }

        .col-product {
            width: 45%;
            text-align: left;
        }

        .col-qty {
            width: 12%;
            text-align: right;
        }

        .col-unit {
            width: 20%;
            text-align: right;
        }

        .col-subtotal {
            width: 23%;
            text-align: right;
        }

        .product-text {
            word-break: break-word;
        }

        .totals-table td {
            padding: .4mm 0;
            font-size: 13px;
        }

        .totals-label {
            font-weight: 800;
        }

        .totals-value {
            text-align: right;
            white-space: nowrap;
        }

        .grand-total td {
            border-top: 1px solid #9fb5d4;
            padding-top: 1mm;
            font-size: 15px;
            font-weight: 800;
        }

        .notes {
            margin-top: 1mm;
            font-size: 12px;
        }

        .notes strong {
            font-weight: 800;
        }

        .footer {
            margin-top: 1.2mm;
            font-size: 11px;
            text-align: center;
        }

        .footer .thanks {
            margin-top: .8mm;
            font-size: 12px;
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
            <img src="{{ $logoFileUrl ?: $logoUrl }}" alt="Logo sucursal" class="logo">
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
                <td class="col-product product-text">{{ \Illuminate\Support\Str::limit($detail->description ?? $detail->product?->description ?? '-', 40) }}</td>
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
