<!-- filepath: resources/views/sales/pdf/pdf_historic_sales.blade.php -->
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>REPORTE DE VENTAS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 15px;
            line-height: 1.3;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .filters {
            background-color: #f8f9fa;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th {
            background-color: #6c757d;
            color: white;
            padding: 6px;
            text-align: left;
            font-size: 10px;
        }

        .details-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }

        .details-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .grand-total {
            margin-top: 20px;
            text-align: center;
            background-color: #28a745;
            color: white;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ $title ?? 'REPORTE DE VENTAS' }}</h1>
        <p>{{ $subtitle ?? 'LISTADO DE VENTAS REGISTRADAS' }}</p>
    </div>

    <div class="filters">
        <strong>Filtros aplicados:</strong>
        @if (
            ($filters['start_date'] ?? null) ||
                ($filters['end_date'] ?? null) ||
                ($filters['client_name'] ?? null) ||
                ($filters['voucher_type'] ?? null) ||
                ($filters['payment_method_name'] ?? null) ||
                ($filters['number'] ?? null))
            @if ($filters['start_date'])
                Desde: {{ date('d/m/Y', strtotime($filters['start_date'])) }}
            @endif
            @if ($filters['end_date'])
                Hasta: {{ date('d/m/Y', strtotime($filters['end_date'])) }}
            @endif
            @if ($filters['client_name'])
                | Cliente: {{ $filters['client_name'] }}
            @endif
            @if ($filters['voucher_type'])
                | Tipo de comprobante: {{ $filters['voucher_type'] }}
            @endif
            @if ($filters['payment_method_name'])
                | Método de pago: {{ $filters['payment_method_name'] }}
            @endif
            @if ($filters['number'])
                | N° Comprobante: {{ $filters['number'] }}
            @endif
        @else
            Todas las ventas
        @endif
    </div>

    @if ($sales->count() > 0)
        <table class="details-table">
            <thead>
                <tr>
                    <th>N° Comprobante</th>
                    <th>Tipo de Venta</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Saldo</th>
                    <th>Fecha Entrega</th>
                    <th>Comprobante</th>
                </tr>
            </thead>
            <tbody>
                @php $totalVentas = 0; @endphp
                @foreach ($sales as $sale)
                    @php 
                        $totalVentas += $sale->total;
                        $saldo = $sale->total;
                        if (method_exists($sale, 'saldo')) {
                            $saldo = $sale->saldo();
                        }
                    @endphp
                    <tr>
                        <td>{{ $sale->number ?? 'N/A' }}</td>
                            
                        <td>
                            @if ($sale->type_status === 0)
                                Directa
                            @else 
                                Delivery
                            @endif
                        </td>   
                        <td>{{ $sale->client_name ?? 'varios' }}</td>
                        <td>{{ $sale->date ? $sale->date->format('d/m/Y') : '-' }}</td>
                        <td class="text-right">S/ {{ number_format($sale->total, 2) }}</td>
                        <td class="text-right">S/ {{ number_format($saldo, 2) }}</td>
                        <td>{{ $sale->delivery_date ? $sale->delivery_date->format('d/m/Y') : 'N/A' }}</td>
                        <td>{{ $sale->voucher_type ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="grand-total">
            TOTAL VENDIDO: S/ {{ number_format($totalVentas, 2) }}
        </div>
    @else
        <div class="no-data">
            <h3>No se encontraron ventas</h3>
            <p>No hay ventas que coincidan con los filtros aplicados.</p>
        </div>
    @endif
</body> 

</html>