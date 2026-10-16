<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalHealthRecord extends Model
{
    protected $fillable = [
        'animal_id',
        'user_id',
        'file_system_item_id',
        'title',
        'description',
        'record_type',
        'record_date',
        'next_due_date',
        'vet_name',
        'medicine_name',
        'dosage',
        'result',
        'cost',
        'is_important',
    ];

    protected $casts = [
        'record_date' => 'datetime',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function file()
    {
        return $this->belongsTo(FileSystemItem::class, 'file_system_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
