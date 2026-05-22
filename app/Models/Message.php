<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'role',
        'content',
        'file_path',
        'file_name',
        'file_type',
        'image_url',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }
}
