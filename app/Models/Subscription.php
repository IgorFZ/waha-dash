<?php

namespace App\Models;

use App\Enums\FrequencyUnit;
use App\Enums\SubscriptionStatus;
use App\Models\Contact;
use App\Models\WhatsappSession;
use App\Models\MessageTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'contact_id',
        'template_id',
        'title',
        'description',
        'amount',
        'frequency_unit',
        'frequency_interval',
        'start_date',
        'next_due_date',
        'last_sent_at',
        'template_variables',
        'status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'next_due_date' => 'date',
        'last_sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'template_variables' => 'array',
        'status' => SubscriptionStatus::class,
        'frequency_unit' => FrequencyUnit::class,
        'frequency_interval' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(WhatsappSession::class, 'session_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(SubscriptionRun::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::Cancelled;
    }

    public function frequencyDescription(): string
    {
        return $this->frequency_unit->describe($this->frequency_interval);
    }
}
