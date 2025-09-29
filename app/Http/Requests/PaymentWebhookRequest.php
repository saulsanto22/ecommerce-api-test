<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|string',
            'external_id' => 'required|string',
            'status' => 'required|string|in:PENDING,PAID,EXPIRED,FAILED',
            'amount' => 'required|numeric|min:0',
            'paid_at' => 'nullable|date',
            'payment_method' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Payment ID wajib diisi',
            'external_id.required' => 'External ID wajib diisi',
            'status.required' => 'Status wajib diisi',
            'status.in' => 'Status tidak valid',
            'amount.required' => 'Amount wajib diisi',
            'amount.numeric' => 'Amount harus berupa angka',
            'amount.min' => 'Amount tidak valid',
        ];
    }
}
