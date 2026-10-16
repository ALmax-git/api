<?php

namespace App\Models;

use App\Enums\StockStatus;
use Illuminate\Database\Eloquent\Model;

class StockEntry extends Model
{
    protected $fillable = [
        'last_stock_entry_id',
        'client_id',
        'created_by_id',
        'confirmed_by_id',
        'stock_id',
        'product_id',
        'variant_id',
        'quantity',
        'available_stock_before',
        'available_stock_after',
        'status',
        'note',
        'confirmed_at',
        'expire_date',
        'production_date',
        'best_before',
        'batch_number',
        'unit_cost_price',
        'sale_price',
        'quantity_recieved',
        'quantity_damage',
    ];

    protected $casts = [
        'confirmed_at'      => 'datetime',
        'expire_date'       => 'datetime',
        'production_date'   => 'datetime',
        'status'            => StockStatus::class,
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by_id');
    }
}
