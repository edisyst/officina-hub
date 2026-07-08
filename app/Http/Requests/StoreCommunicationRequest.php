<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'   => ['required', 'integer', 'exists:clienti,id'],
            'work_order_id' => ['nullable', 'integer', 'exists:commesse,id'],
            'channel'       => ['required', 'in:whatsapp,sms,email,phone,note'],
            'direction'     => ['required', 'in:outbound,inbound'],
            'subject'       => ['nullable', 'string', 'max:255'],
            'body'          => ['required', 'string', 'max:10000'],
            'occurred_at'   => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required'       => 'Il testo è obbligatorio.',
            'channel.in'          => 'Canale non valido.',
            'direction.in'        => 'Direzione non valida.',
            'customer_id.exists'  => 'Cliente non trovato.',
        ];
    }
}
