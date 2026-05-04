<?php

namespace App\Models;

use App\Enums\RunStatus;
use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'status',
        'message_sent',
        'external_message_id',
        'delivery_status',
        'error_message',
        'cycle_due_date',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => RunStatus::class,
        'delivery_status' => DeliveryStatus::class,
        'cycle_due_date' => 'date',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function durationInSeconds(): ?int
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        return (int) $this->finished_at->diffInSeconds($this->started_at);
    }

    public function wasSuccessful(): bool
    {
        return $this->status === RunStatus::Success;
    }
}
