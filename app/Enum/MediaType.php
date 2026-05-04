<?php

namespace App\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Document = 'document';
    case Audio = 'audio';
    case Video = 'video';
}
