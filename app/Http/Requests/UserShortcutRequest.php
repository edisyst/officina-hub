<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserShortcutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'label' => 'required|string|max:100',
            'url'   => ['required', 'string', 'max:500', function ($attribute, $value, $fail) {
                $appUrl = rtrim(config('app.url'), '/');
                $path   = ltrim($value, '/');

                // Allow relative paths (no scheme) or paths matching app URL
                if (str_contains($value, '://') && ! str_starts_with($value, $appUrl)) {
                    $fail('L\'URL deve essere interno all\'applicazione.');
                }
            }],
            'icon'  => 'nullable|string|max:100',
        ];
    }
}
