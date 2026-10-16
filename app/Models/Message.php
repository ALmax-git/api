<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',    # if the sender and reciever have same client_id and the sender can_access('staff_management) == ture
        'message_id',   # if its a reply to another message
        'sender_id',    # User who send the message
        'reciever_id',  # Target User for the Message
        'status',       # ['unread', 'read', 'draft']
        'level',        # [// info, success, warning, danger | client only ^ ]
        'content',      # mackdown support, LongText
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reciever_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public function attachments()
    {
        return $this->hasMany(ChatAttachment::class);
    }
    public function files()
    {
        return $this->belongsToMany(
            FileSystemItem::class,
            'chat_attachments',
            'message_id',
            'file_system_item_id'
        );
    }

    public function tags()
    {
        return $this->hasMany(ChatMessageProductTag::class, 'message_id');
    }
}
