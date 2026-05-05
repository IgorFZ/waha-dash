<?php

namespace App\Http\Requests\Contacts;

use Illuminate\Foundation\Http\FormRequest;

class IndexContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function search(): string
    {
        return trim((string) $this->validated('q', ''));
    }
}
