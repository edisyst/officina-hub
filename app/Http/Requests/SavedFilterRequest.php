<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavedFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:100',
            'page_key' => 'required|string|max:100',
            'filters'  => 'required|array',
        ];
    }
}
