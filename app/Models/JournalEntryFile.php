<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'file_system_item_id',
    ];
    /**
     * Get the journal entry associated with the file.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
    public function file()
    {
        return $this->belongsTo(FileSystemItem::class, 'file_system_item_id');
    }
    /**
     * Get the file system item (the actual file record).
     */
    public function fileSystemItem(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class);
    }
}
