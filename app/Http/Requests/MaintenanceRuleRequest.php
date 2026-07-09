<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:200'],
            'every_km'     => ['nullable', 'integer', 'min:1'],
            'every_months' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (empty($this->every_km) && empty($this->every_months)) {
                $v->errors()->add('every_km', 'Almeno un intervallo (km o mesi) è obbligatorio.');
            }
        });
    }
}
