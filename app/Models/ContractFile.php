<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractFile extends Model
{
    protected $fillable = ['contract_id', 'file_system_item_id', 'user_id',];
    /* |-------------------------------------------------------------------------- | Relationships |-------------------------------------------------------------------------- */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class, 'file_system_item_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
