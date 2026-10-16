<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $clientId;

    public function __construct($clientId)
    {
        $this->clientId = $clientId;
    }

    public function query()
    {
        return Product::query()
            ->where('client_id', $this->clientId)
            ->latest();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Brand',
            'Sale Price',
            'Stock Price',
            'Discount (%)',
            'Real Price',
            'Available Stock',
            'sold',
            'Status',
            'Updated At',
            'Created At',
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->brand,
            $product->sale_price,
            $product->stock_price,
            $product->discount ?? 0,
            $product->real_price,
            $product->available_stock,
            $product->sold,
            $product->status->value,
            $product->updated_at->format('Y-m-d'),
            $product->created_at->format('Y-m-d'),
        ];
    }
}
