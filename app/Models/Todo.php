<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Todo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'has_attachment',
        'client_id',
        'staff_id'
    ];
    protected $casts = [
        'due_date' => 'datetime',
    ];
    public function attachments()
    {
        // fix this
        return $this->hasMany(FileSystemItem::class, 'id', 'id')->where('owner_id', null);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class);
    }
}
