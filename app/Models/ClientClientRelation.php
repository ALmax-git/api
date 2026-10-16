<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClientClientRelation extends Pivot
{

    protected $table = 'client_client_relations';
    protected $fillable = [
        'client_id',
        'related_client_id',
        'sign_by_id',
        'acknowledged_by_id',
        'relationships',
        'related_phone_number',
        'related_telephone',
    ];

    protected $casts = [
        'relationships' => 'array',
    ];

    /**
     * The primary client.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    /**
     * The related client.
     */
    public function related_client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'related_client_id');
    }

    /**
     * User who signed/created the relationship.
     */
    public function signed_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sign_by_id');
    }

    /**
     * User who acknowledged the relationship.
     */
    public function acknowledged_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_id');
    }
}
