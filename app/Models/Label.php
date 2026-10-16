<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    /*
    */
    protected $fillable = [
        'product_id',
        'variant_id', // nullable
        'stock_entry_id',
        'qr_key',     // unique key
        'serial_number', // optional but recommended
        'agent_id',
        'status',     // ['verified', 'new', 'inactive', 'out_of_stock', 'discontinued', 'archived']
    ];

    protected $casts = [
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'qr_key' => 'string',
        'serial_number' => 'string',
        // 'status' => \App\Enums\ProductStatus::class, in ['verified', 'new', 'inactive', 'out_of_stock', 'discontinued', 'archived']
    ];
    protected $attributes = [
        'status' => 'active', // default status
    ];

    public $timestamps = true;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function stock_entry()
    {
        return $this->belongsTo(StockEntry::class);
    }
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
