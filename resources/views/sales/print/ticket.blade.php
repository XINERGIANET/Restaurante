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

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            background: #fff;
        }

        body {
            font-size: 10.5px;
            line-height: 1.18;
        }

        .ticket {
            width: 100%;
            padding: 2.2mm 2.6mm 3mm;
        }

        .center {
            text-align: center;
        }

        .logo-wrap {
            margin-bottom: 1.5mm;
        }

        .logo {
            display: block;
            max-width: 38mm;
            max-height: 16mm;
            margin: 0 auto;
            object-fit: contain;
        }

        .company {
            margin: 0;
            font-size: 6.2mm;
            font-weight: 800;
            line-height: 1;
        }

        .subhead {
            margin: 0.4mm 0 0;
            font-size: 3.8mm;
            line-height: 1.05;
        }

        .doc-code {
            margin: 1.2mm 0 0;
            font-size: 4.9mm;
            font-weight: 800;
            line-height: 1.05;
        }

        .separator {
            border-top: 0.3mm dashed #7ea1d4;
            margin: 1.8mm 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table td {
            padding: 0.2mm 0;
            vertical-align: top;
            font-size: 3.05mm;
            line-height: 1.08;
        }

        .info-label {
            width: 21mm;
            font-weight: 800;
            padding-right: 1mm;
            white-space: nowrap;
        }

        .info-value {
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .items-table th,
        .items-table td {
            padding: 0.55mm 0;
            font-size: 2.95mm;
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
            padding-left: 1.4mm;
            padding-right: 1mm;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .col-qty {
            width: 15%;
            text-align: right;
            padding-right: 1.4mm;
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
            padding: 0.45mm 0;
            font-size: 3.2mm;
        }

        .totals-label {
            font-weight: 800;
        }

        .totals-value {
            text-align: right;
            white-space: nowrap;
        }

        .grand-total td {
            border-top: 0.25mm solid #7ea1d4;
            padding-top: 1.1mm;
            font-size: 4.55mm;
            font-weight: 800;
        }

        .notes {
            font-size: 3mm;
            line-height: 1.15;
        }

        .notes strong {
            font-weight: 800;
        }

        .qr-wrap {
            text-align: center;
            margin-top: 1.6mm;
        }

        .qr-wrap img {
            width: 24mm;
            height: 24mm;
            object-fit: contain;
        }

        .footer {
            text-align: center;
            font-size: 2.7mm;
            line-height: 1.15;
        }

        .thanks {
            margin-top: 0.6mm;
            font-size: 3mm;
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
    $customerName = trim((string) ($sale->person_name ?? ''));
    $customerLower = mb_strtolower($customerName, 'UTF-8');
    $customerDocument = trim((string) ($sale->person?->document_number ?? ''));
    if ($customerName === '' || $customerLower === 'sin cliente') {
        $customerName = 'CLIENTES VARIOS';
    }
    if ($customerDocument === '' || $customerDocument === '-') {
        $customerDocument = '0';
    }
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
            <td class="info-value">{{ $customerName }}</td>
        </tr>
        <tr>
            <td class="info-label">Dir.:</td>
            <td class="info-value">{{ $sale->person?->address ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">RUC/DNI:</td>
            <td class="info-value">{{ $customerDocument }}</td>
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
            <th class="col-qty">CANT.</th>
            <th class="col-product">&nbsp;&nbsp;DESCRIPCION</th>
            <th class="col-unit">PRECIO</th>
            <th class="col-subtotal">IMPORTE</th>
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
                <td class="col-qty">{{ number_format($qty, 2) }}</td>
                <td class="col-product">&nbsp;&nbsp;{{ $detail->description ?? $detail->product?->description ?? '-' }}</td>
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

    @if(!empty($qrImageUrl))
        <div class="separator"></div>
        <div class="qr-wrap">
            <img src="{{ $qrImageUrl }}" alt="QR del comprobante">
        </div>
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
