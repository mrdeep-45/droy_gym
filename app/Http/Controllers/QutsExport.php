<?php

namespace App\Exports;

use App\Models\GenerateQuotationModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class QutsExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, ShouldAutoSize, WithEvents
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $query = GenerateQuotationModel::with(['customer'])
            ->select(
                'tbl_quotation_generate.*',
                DB::raw("(
                    SELECT qr.remark 
                    FROM quotation_remark qr 
                    WHERE qr.quote_id = tbl_quotation_generate.quotation_id 
                    ORDER BY qr.created_at DESC LIMIT 1
                ) as latest_remark"),
                DB::raw("(
                    SELECT qr.followup_date 
                    FROM quotation_remark qr 
                    WHERE qr.quote_id = tbl_quotation_generate.quotation_id 
                    ORDER BY qr.created_at DESC LIMIT 1
                ) as followup_date")
            );

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_added', [
                $this->startDate->format('Y-m-d') . ' 00:00:00',
                $this->endDate->format('Y-m-d') . ' 23:59:59'
            ]);
        }

        return $query->orderByDesc('quotation_id')->get();
    }

    public function headings(): array
    {
        return [
            [$this->startDate && $this->endDate
                ? 'Date Range: ' . $this->startDate->format('d-m-Y') . ' to ' . $this->endDate->format('d-m-Y')
                : 'All Quotations'],
            [
                'Quotation No',
                'Revision',
                'Quotation Date',
                'Customer',
                'Lead ID',
                'GST Type',
                'Total (₹)',
                'Follow-up Date',
                'Status',
                'Created By',
                'Latest Remark',
            ]
        ];
    }

    public function map($row): array
{
    $gstLabel = match ((int)$row->gst_type) {
        0 => 'SGST 9% + CGST 9%',
        1 => 'IGST 18%',
        default => '-',
    };

    $revision = ($row->rev_no == 0) ? '0' : $row->rev_no;

    // Resolve created by
    if ((int)$row->created_by === -1) {
        $createdBy = 'Arpan Shah';
    } elseif (is_numeric($row->created_by)) {
        $emp = \App\Models\EmployeeModal::find($row->created_by);
        $createdBy = $emp ? $emp->emp_name : 'Unknown';
    } else {
        $createdBy = '-';
    }

    return [
    $row->quote_id,
    $revision,
    Carbon::parse($row->date_added)->format('d-m-Y'),
    $row->customer->org_name ?? '-',
    $row->lead_id,
    $gstLabel,
    number_format($row->total, 2),
    !empty($row->followup_date) ? Carbon::parse($row->followup_date)->format('d-m-Y') : '-',
    $this->getStatusText($row->status),
    $createdBy,
    $row->latest_remark ?? '-'
];

}


    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 10,
            'C' => 15,
            'D' => 35,
            'E' => 12,
            'F' => 20,
            'G' => 15,
            'H' => 18,
            'I' => 12,
            'J' => 20,
            'K' => 50,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->mergeCells('A1:K1');
                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $event->sheet->getStyle('A2:K2')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            }
        ];
    }

    protected function getStatusText($status)
    {
        return match ((int)$status) {
             0, 5, 6 => 'Pending',
            8 => 'Discuss',
            1 => 'Closed',
            7 => 'PO from Customer',
            default => 'Unknown',
        };
    }
}

