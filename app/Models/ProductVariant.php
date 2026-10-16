<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    // Define fillable fields
    protected $fillable = [
        'product_id',
        'label',
        'size',
        'color',
        'si_unit',
        'weight',
        'stock_price',
        'sale_price',
        'available_stock',
        'sold',
        'product_global_key'
    ];

    /**
     * Relationship with the ProductVariant model (a product can have many variants).
     */
    public function sale_units()
    {
        return $this->hasMany(SaleUnit::class, 'variant_id')->where('product_id', $this->product_id);
    }
    /**
     * Relationship with the Product model (a variant belongs to a product).
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    /**
     * Relationship with the Label model (a variant can have many labels).
     */
    public function labels()
    {
        return $this->hasMany(Label::class);
    }
}
