<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}
