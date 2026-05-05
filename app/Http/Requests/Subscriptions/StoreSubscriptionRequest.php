<?php

namespace App\Http\Requests\Subscriptions;

use App\Enums\FrequencyUnit;
use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'template_id' => ['required', 'integer', 'exists:message_templates,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'frequency_unit' => ['required', Rule::enum(FrequencyUnit::class)],
            'frequency_interval' => ['required', 'integer', 'min:1', 'max:120'],
            'start_date' => ['required', 'date'],
            'next_due_date' => ['nullable', 'date'],
            'send_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::enum(SubscriptionStatus::class)],
            'variables' => ['nullable', 'array'],
            'variables.*.key' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'variables.*.value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function subscriptionData(): array
    {
        $data = $this->validated();
        $data['next_due_date'] = $data['next_due_date'] ?: $data['start_date'];
        $data['template_variables'] = $this->templateVariables();
        unset($data['variables']);

        return $data;
    }

    public function messages(): array
    {
        return [
            'contact_id.required' => 'Selecione um contato.',
            'contact_id.integer' => 'O contato selecionado é inválido.',
            'contact_id.exists' => 'O contato selecionado não foi encontrado.',
            'template_id.required' => 'Selecione um modelo.',
            'template_id.integer' => 'O modelo selecionado é inválido.',
            'template_id.exists' => 'O modelo selecionado não foi encontrado.',
            'title.required' => 'O título é obrigatório.',
            'title.string' => 'O título deve ser um texto.',
            'title.max' => 'O título não pode exceder 255 caracteres.',
            'description.string' => 'A descrição deve ser um texto.',
            'description.max' => 'A descrição não pode exceder 2000 caracteres.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser um número.',
            'amount.min' => 'O valor não pode ser negativo.',
            'amount.max' => 'O valor não pode exceder 99.999.999,99.',
            'frequency_unit.required' => 'Selecione a frequência.',
            'frequency_unit.enum' => 'A frequência selecionada é inválida.',
            'frequency_interval.required' => 'Informe o intervalo da frequência.',
            'frequency_interval.integer' => 'O intervalo da frequência deve ser um número inteiro.',
            'frequency_interval.min' => 'O intervalo da frequência deve ser maior que zero.',
            'frequency_interval.max' => 'O intervalo da frequência não pode exceder 120.',
            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início deve ser válida.',
            'next_due_date.date' => 'A próxima data de vencimento deve ser válida.',
            'send_time.date_format' => 'O horário de envio deve estar no formato HH:MM.',
            'status.required' => 'Selecione um status.',
            'status.enum' => 'O status selecionado é inválido.',
            'variables.array' => 'As variáveis da assinatura são inválidas.',
            'variables.*.key.string' => 'A chave da variável deve ser um texto.',
            'variables.*.key.max' => 'A chave da variável não pode exceder 100 caracteres.',
            'variables.*.key.regex' => 'A chave da variável deve conter apenas letras, números, ponto, sublinhado e hífen.',
            'variables.*.value.string' => 'O valor da variável deve ser um texto.',
            'variables.*.value.max' => 'O valor da variável não pode exceder 2000 caracteres.',
        ];
    }

    public function templateVariables(): array
    {
        return collect($this->validated('variables', []))
            ->mapWithKeys(function (array $variable) {
                $key = trim((string) ($variable['key'] ?? ''));
                $value = trim((string) ($variable['value'] ?? ''));

                if ($key === '' || $value === '') {
                    return [];
                }

                return [$key => $value];
            })
            ->all();
    }
}
