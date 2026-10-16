<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMedia extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'file_system_item_id',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function file()
    {
        return $this->belongsTo(FileSystemItem::class, 'file_system_item_id');
    }
}
