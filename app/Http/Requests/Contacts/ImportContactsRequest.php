<?php

namespace App\Http\Requests\Contacts;

use Illuminate\Foundation\Http\FormRequest;

class ImportContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chat_ids' => ['required', 'array', 'min:1', 'max:5000'],
            'chat_ids.*' => ['required', 'string', 'max:255', 'distinct'],
        ];
    }

    public function chatIds(): array
    {
        return $this->validated('chat_ids');
    }
}
