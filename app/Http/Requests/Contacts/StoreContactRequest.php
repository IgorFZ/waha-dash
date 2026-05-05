<?php

namespace App\Http\Requests\Contacts;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s().-]{10,20}$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'O nome do contato é obrigatório.',
            'display_name.string' => 'O nome do contato deve ser uma string.',
            'display_name.max' => 'O nome do contato não pode ter mais de 255 caracteres.',
            'phone_number.required' => 'O número de telefone é obrigatório.',
            'phone_number.string' => 'O número de telefone deve ser uma string.',
            'phone_number.max' => 'O número de telefone não pode ter mais de 20 caracteres.',
            'phone_number.regex' => 'O número de telefone deve ser um formato válido, podendo conter apenas dígitos, espaços, parênteses, traços e opcionalmente começar com +.',
            'notes.string' => 'As notas devem ser uma string.',
            'notes.max' => 'As notas não podem ter mais de 2000 caracteres.',
        ];
    }
}
