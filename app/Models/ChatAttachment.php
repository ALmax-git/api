<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'file_system_item_id',
    ];

    public function file()
    {
        return $this->belongsTo(FileSystemItem::class);
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
