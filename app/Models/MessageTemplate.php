<?php

namespace App\Models;

use App\Enums\MediaType;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'media_url',
        'media_type',
    ];

    protected $casts = [
        'media_type' => MediaType::class,
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'template_id');
    }
}
