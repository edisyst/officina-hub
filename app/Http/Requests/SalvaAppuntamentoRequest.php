<?php

namespace App\Http\Requests;

use App\Enums\StatoAppuntamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvaAppuntamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titolo'          => ['required', 'string', 'max:255'],
            'data_ora_inizio' => ['required', 'date'],
            'data_ora_fine'   => ['required', 'date', 'after_or_equal:data_ora_inizio'],
            'stato'           => ['required', Rule::enum(StatoAppuntamento::class)],
            'ponte_id'        => ['nullable', 'exists:ponti,id'],
            'user_id'         => ['nullable', 'exists:users,id'],
            'commessa_id'     => ['nullable', 'exists:commesse,id'],
            'cliente_id'      => ['nullable', 'exists:clienti,id'],
            'veicolo_id'      => ['nullable', 'exists:veicoli,id'],
            'tutto_il_giorno' => ['boolean'],
            'note'            => ['nullable', 'string'],
        ];
    }
}
