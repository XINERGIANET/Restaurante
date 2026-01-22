<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public $start_date;
    protected $end_date;
    protected $number;
    protected $client;
    protected $voucher_type;
    protected $payment_method;
    public function __construct(
        $start_date = null,
        $end_date = null,
        $number = null,
        $client = null,
        $voucher_type = null,
        $payment_method = null
    ) {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->number = $number;
        $this->client = $client;
        $this->voucher_type = $voucher_type;
        $this->payment_method = $payment_method;
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
        ]);

        if ($this->start_date) {
            $query->whereDate('date', '>=', $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate('date', '<=', $this->end_date);
        }

        if ($this->number) {
            $query->where('number', 'like', '%' . $this->number . '%');
        }

        if ($this->client) {
            $client = $this->client; // Guardar en variable local
            $query->where(function ($q) use ($client) {
                // Buscar por client_name directamente
                $q->where('client_name', 'like', '%' . $client . '%')
                    // O buscar por relación client
                    ->orWhereHas('client', function ($subQuery) use ($client) {
                        $subQuery->where('business_name', 'like', '%' . $client . '%')
                            ->orWhere('contact_name', 'like', '%' . $client . '%');
                    });
            });
        }

        if ($this->voucher_type) {
            $query->where('voucher_type', $this->voucher_type);
        }

        if ($this->payment_method) {
            $query->whereHas('payments', function ($q) {
                $q->where('payment_method_id', $this->payment_method);
            });
        }

        return $query->where('deleted', 0)->get();
    }

    public function map($sale): array
    {
        // ✅ Obtener método de pago
        $payment_method = optional(optional($sale->payments->first())->payment_method)->name ?? 'N/A';

        // ✅ Tipo de atencion en venta
        $type_status_text = $sale->type_status === 0 ? 'Directa' : 'Delivery';

        // ✅ Nombre del cliente (igual que en la vista web)
        $client_name = $sale->client_name ?? 'varios';

        // ✅ Devolvemos solo una fila por venta, en el mismo orden que la tabla web
        return [
            $sale->number ?? 'N/A',
            optional($sale->date)->format('d/m/Y') ?? 'N/A',          // Fecha
            optional($sale->date)->format('d') ?? 'N/A',               // Día
            optional($sale->date)->translatedFormat('F') ?? 'N/A',     // Mes completo
            optional($sale->date)->format('Y') ?? 'N/A',               // Año
            optional($sale->date)->translatedFormat('l') ?? 'N/A',     // Día de semana                                    // N° Comprobante
            $client_name,                                              // Cliente
            number_format($sale->total, 2, '.', ''),                  // Total
            $sale->number_persons ?? '-',                              // N° de personas
            $payment_method,                                           // Método de pago
            optional($sale->delivery_date)->format('d/m/Y') ?? 'N/A', // Fecha entrega
            $sale->voucher_type ?? '-',                                // Comprobante
        ];
    }

    public function headings(): array
    {
        return [
            'N° COMPROBANTE',
            'FECHA',
            'DÍA',
            'MES',            
            'AÑO',
            'DÍA DE SEMANA',
            'CLIENTE',
            'TOTAL',
            'N° DE PERSONAS',
            'MÉTODO DE PAGO',
            'FECHA ENTREGA',
            'COMPROBANTE',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
