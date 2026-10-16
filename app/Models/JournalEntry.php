<?php

namespace App\Models;

use App\Enums\JournalEntryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'journal_id',
        'client_id',
        'user_id',
        'wallet_id',
        'label',
        'currency',
        'amount',
        'type',
        'date',
        'tx_ref',
        'tx_method',
        'is_adjustment',
        'is_verified',
        'journal_entry_id',
        'amend_by_id',
        'verified_by_id',
        'has_amendment',
        'amendment_at',
        'verified_at',
        'amendment_reason',
    ];

    protected $casts =
    [
        'amount'                => 'decimal:2',
        'type'                  => JournalEntryType::class,
        'date'                  => 'datetime',
        'is_adjustment'         => 'boolean',
        'is_verified'           => 'boolean',
        'has_amendment'         => 'boolean',
        'amendment_at'          => 'datetime',
        'verified_at'           => 'datetime',
        'amendment_reason'      => 'string',
    ];

    // Relationships to other entities
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
    // Relationship to Files (via JournalEntryFile pivot)
    public function files()
    {
        return $this->belongsToMany(
            FileSystemItem::class,
            'journal_entry_files',
            'journal_entry_id',
            'file_system_item_id'
        );
    }
    public function entryFiles()
    {
        return $this->hasMany(JournalEntryFile::class);
    }
    // Self-referencing relationship for adjustments
    public function previousEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
    public function verified_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }
    public function amend_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'amend_by_id');
    }

    public function adjustmentEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'journal_entry_id');
    }

    // Assuming Client, User, and Wallet models exist
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
