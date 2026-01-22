<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeliveryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public $start_date;
    protected $end_date;
    protected $number;

    public function __construct(
        $start_date = null,
        $end_date = null,
        $number = null
    ) {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->number = $number;
    }

    public function collection()
    {
        $query = Sale::with([
            'client',
            'user',
            'payments.payment_method',
            'details.product',
            'sale_details',
            'details'
        ])
            ->where('type_status', 2);

        if ($this->start_date) {
            $query->whereDate('date', '>=', $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate('date', '<=', $this->end_date);
        }

        if ($this->number) {
            $query->where('id', 'like', '%' . $this->number . '%');
        }


        return $query->where('deleted', 0)->get();
    }

    public function map($sale): array
    {
        // ✅ Obtener método de pago
        $payment_method = optional(optional($sale->payments->first())->payment_method)->name ?? 'N/A';

        $type_sale_text = '';
        switch ($sale->type_sale) {
            case 0:
                $type_sale_text = 'Punto de venta';
                break;
            case 1:
                $type_sale_text = 'Cafetería';
                break;
            default:
                $type_sale_text = 'N/A';
                break;
        }

        // ✅ Estado o tipo de atención
        $type_status_text = '';
        switch ($sale->type_status) {
            case 0:
                $type_status_text = 'Directa';
                break;
            case 1:
                $type_status_text = 'Anticipada';
                break;
            case 2:
                $type_status_text = 'Delivery';
                break;
            default:
                $type_status_text = 'N/A';
                break;
        }

        // ✅ Nombre del cliente
        $client_name = 'N/A';
        if ($sale->client && $sale->client->business_name) {
            $client_name = $sale->client->business_name;
        } elseif (!empty($sale->client_name)) {
            $client_name = $sale->client_name;
        } elseif (!empty($sale->client)) {
            $client_name = $sale->client;
        }

        //Saldo
        $saldo_name = "";
        if ($sale->saldo() == 0) {
            $saldo_name = "POR ENTREGAR";
        } else {
            $saldo_name = "POR PAGAR";
        }
        // ✅ Devolvemos solo una fila por venta
        return [
            optional($sale->date)->format('d/m/Y') ?? 'N/A',           // Fecha
            optional($sale->date)->format('d') ?? 'N/A',               // Día
            optional($sale->date)->translatedFormat('F') ?? 'N/A',     // Mes completo
            optional($sale->date)->format('Y') ?? 'N/A',               // Año
            optional($sale->date)->translatedFormat('l') ?? 'N/A',     // Día de semana
            $sale->number,                                             // Número de comprobante
            $sale->total,                                              // Total
            $sale->saldo(),                                       // Saldo
            $payment_method,                                           // Método de pago
            $saldo_name,                                         //Estado
            $sale->details->map(function ($detail) {
                return $detail->quantity . 'x ' . ($detail->product->name ?? 'Producto');
            })->implode("\n"),
            $sale->observation ?? "Sin observaciones",
            $sale->delivery_date ?? "-",
            $sale->delivery_hour ?? "-"

        ];
    }

    public function headings(): array
    {
        return [
            'FECHA',
            'DÍA',
            'MES',
            'AÑO',
            'DÍA DE SEMANA',
            'NÚMERO DE COMPROBANTE',
            'TOTAL',
            'SALDO',
            'METODO DE PAGO',
            'ESTADO',
            'PRODUCTOS',
            'OBSERVACION',
            'FECHA ENTREGA',
            'HORA ENTREGA'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}

