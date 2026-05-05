<?php

namespace App\Http\Requests\MessageTemplates;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'media_url' => ['nullable', 'url', 'max:2048'],
            'media_type' => ['nullable', Rule::enum(MediaType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título é obrigatório.',
            'title.string' => 'O título deve ser uma string.',
            'title.max' => 'O título não pode exceder 255 caracteres.',
            'body.required' => 'O corpo da mensagem é obrigatório.',
            'body.string' => 'O corpo da mensagem deve ser uma string.',
            'body.max' => 'O corpo da mensagem não pode exceder 5000 caracteres.',
            'media_url.url' => 'A URL do mídia deve ser um link válido.',
            'media_url.max' => 'A URL do mídia não pode exceder 2048 caracteres.',
            'media_type.enum' => 'O tipo de mídia selecionado é inválido.',
        ];
    }
}
