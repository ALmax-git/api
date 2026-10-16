<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalMedia extends Model
{
    protected $fillable = [
        'animal_id',
        'file_system_item_id',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function file()
    {
        return $this->belongsTo(FileSystemItem::class, 'file_system_item_id');
    }
}
