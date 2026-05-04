<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'provider',
        'phone_number',
        'last_connected_at',
        'metadata',
    ];
    
    protected $casts = [
        'status' => SessionStatus::class,
        'last_connected_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function isConnected(): bool
    {
        return $this->status === SessionStatus::Connected;
    }
}
