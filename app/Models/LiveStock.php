<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveStock extends Model
{
    // Define fillable fields
    protected $fillable = [
        'id',
        'client_id',
        'product_id',
        'scientific_name',
        'breed',
        'notes',
    ];


    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'livestock_id');
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
