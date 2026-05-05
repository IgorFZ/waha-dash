<?php

namespace App\Models;

use App\Models\WhatsappSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'chat_id',
        'phone_number',
        'push_name',
        'display_name',
        'is_business',
        'is_blocked',
        'notes',
        'source',
        'synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'is_business' => 'boolean',
        'is_blocked' => 'boolean',
        'synced_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(WhatsappSession::class, 'session_id');
    }
}
