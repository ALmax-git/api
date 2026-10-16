<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{

    use SoftDeletes;
    // Define fillable fields
    protected $fillable = [
        'parent_id', //nullable
        'user_id',
        'livestock_id',
        'client_id',          //
        'name',
        'date_of_birth',
        'date_of_death',
        'is_alive',
        'tag_number',
        'is_sold',
        'date_of_sale',
        'gender',
        'weight',
        'in_quarantine',
        'color',
        'breed',
    ];

    protected $casts = [
        'in_quarantine'                 => 'boolean',
        'date_of_birth'                 => 'datetime',
        'date_of_death'                 => 'datetime',
        'date_of_sale'                  => 'datetime',
        'is_sold'                       => 'boolean',
        'deleted_at'                    => 'datetime',
    ];
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function children()
    {
        return $this->hasMany(Animal::class, 'parent_id');
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(LiveStock::class, 'livestock_id');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(AnimalJournal::class);
    }

    public function media(): BelongsToMany
    {
        // return $this->hasMany(AnimalMedia::class);
        return $this->belongsToMany(
            FileSystemItem::class,
            'animal_media',
            'animal_id',
            'file_system_item_id'
        );
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(AnimalHealthRecord::class);
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
