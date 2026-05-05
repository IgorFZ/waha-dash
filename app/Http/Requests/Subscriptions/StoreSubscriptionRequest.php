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
