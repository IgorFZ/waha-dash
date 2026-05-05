<?php

namespace App\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Document = 'document';
    case Audio = 'audio';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Imagem',
            self::Document => 'Documento',
            self::Audio => 'Áudio',
            self::Video => 'Vídeo',
        };
    }
}
