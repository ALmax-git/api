<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'title',
        'client_id',
        'created_by_id',
        'label',
        'note',
        'invoice_number',
        'supplier_id',
        'other_fees',
        'total_amount',
        'paid_amount',
        'products_count',
        'status',
        'payment_status',
        'internal_note',
        'order_date',
        'expected_date',
    ];


    protected $casts = [
        'order_date'      => 'datetime',
        'expected_date'       => 'datetime',
    ];

    public function entries()
    {
        return $this->hasMany(StockEntry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
    public function supplier()
    {
        return $this->belongsTo(Client::class, 'supplier_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
