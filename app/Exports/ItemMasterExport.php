<?php

namespace App\Exports;

use App\Modules\MasterData\Models\ItemMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemMasterExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'No',
            'SKU / Part No',
            'Part Name',
            'Description',
            'Model',
            'Stock Type',
            'UOM',
            'Category',
            'Standard Price',
            'Barcode',
            'Volume (m³)',
            'SPQ per Carton',
            'SPQ per Item',
            'SPQ per Pallet',
            'SPQ Weight',
            'm³ per Pallet',
            'Status',
            'Created At',
            'Last Updated'
        ];
    }

    public function map($item): array
    {
        static $no = 1;
        
        return [
            $no++,
            $item->item_number,
            $item->item_name,
            $item->item_description ?? '-',
            $item->model ?? '-',
            $item->stock_type == 'inventory' ? 'Inventory Item' : 'Non-Inventory Item',
            $item->uom ? $item->uom->uom_code : '-',
            $item->category ? $item->category->item_cat_name : '-',
            $item->standard_price,
            $item->barcode ?? '-',
            $item->volume_m3 ?? '-',
            $item->spq_ctn ?? '-',
            $item->spq_item ?? '-',
            $item->spq_pallet ?? '-',
            $item->spq_weight ?? '-',
            $item->m3_pallet ?? '-',
            $item->item_status,
            $item->input_date,
            $item->edit_date ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ],
        ];
    }
}