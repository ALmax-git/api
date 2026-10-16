<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'aggrement_statement', // 'agreement_statement',
        'aggrement_file_id', // 'agreement_file_id',
        'type', // ProjectType enum: DOCUMENTATION or CONTRACT
        'budget',
        'budget_breakdown',
        'approval_notes',
        'completion_notes',
        'approval_date',
        'start_date',
        'due_date',
        'end_date',
        'status', // ProjectStatus enum
        'progress',
        'is_archived',
        'archived_reason',
        'opening_signature',
        'closing_signature',
        'created_by_id',
        'approved_by_id',
        'client_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'end_date' => 'datetime',
        'approval_date' => 'datetime',
        'progress' => 'decimal:2',
        'is_archived' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    // Project owner
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // Project approver
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    // Client associated
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Agreement file (optional)
    public function agreementFile(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class, 'aggrement_file_id');
    }

    // Participants and their roles
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user_roles')
            ->withPivot('role', 'position')
            ->withTimestamps();
    }

    // Project files
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    // Linked Journal Entries
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    // Linked Todos
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    // Progress Updates
    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ProgressUpdate::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    // Check if contract project is completed (requires both signatures)
    public function isContractCompleted(): bool
    {
        if ($this->type !== \App\Enums\ProjectType::CONTRACT->value) {
            return $this->status === ProjectStatus::COMPLETED->value;
        }

        return !empty($this->opening_signature) && !empty($this->closing_signature);
    }

    // Add or update participant with role/position
    public function addParticipant(User $user, string $role, ?string $position = null): void
    {
        $this->participants()->syncWithoutDetaching([
            $user->id => ['role' => $role, 'position' => $position]
        ]);
    }

    // Remove participant
    public function removeParticipant(User $user): void
    {
        $this->participants()->detach($user->id);
    }

    // Calculate project progress from related progress updates or linked todos
    public function calculateProgress(): float
    {
        $progressFromUpdates = $this->progressUpdates()->avg('progress') ?? 0;
        $todoProgress = $this->todos()->exists()
            ? $this->todos()->where('is_completed', true)->count() / max($this->todos()->count(), 1) * 100
            : 0;

        // Weighted average (50/50 between updates and todos)
        $calculated = ($progressFromUpdates + $todoProgress) / 2;

        $this->update(['progress' => $calculated]);

        return $calculated;
    }

    // Archive the project
    public function archive(string $reason): void
    {
        $this->update([
            'is_archived' => true,
            'archived_reason' => $reason
        ]);
    }

    // Unarchive
    public function unarchive(): void
    {
        $this->update([
            'is_archived' => false,
            'archived_reason' => null
        ]);
    }

    // Check if a user is participant in project
    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    // Get participant role
    public function participantRole(User $user): ?string
    {
        $participant = $this->participants()->where('user_id', $user->id)->first();
        return $participant?->pivot->role;
    }
}
