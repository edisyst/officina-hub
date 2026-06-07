<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalvaLavorazioneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descrizione'          => ['required', 'string', 'max:255'],
            'minuti_preventivati'  => ['required', 'integer', 'min:0'],
            'user_id'              => ['required', 'exists:users,id'],
            'ponte_id'             => ['nullable', 'exists:ponti,id'],
            'commessa_riga_id'     => ['nullable', 'exists:commessa_righe,id'],
            'note'                 => ['nullable', 'string'],
        ];
    }
}
