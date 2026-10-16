<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalJournal extends Model
{
    protected $fillable = [
        'animal_id',
        'user_id',
        'file_system_item_id',
        'title',
        'note',
        'entry_date',
        'is_important',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->belongsTo(FileSystemItem::class, 'file_system_item_id');
    }
}
