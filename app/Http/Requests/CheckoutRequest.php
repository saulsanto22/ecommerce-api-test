<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:10',
            'shipping_address' => 'required|string|min:10|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Items wajib diisi',
            'items.min' => 'Minimal 1 item',
            'items.*.product_id.required' => 'Product ID wajib diisi',
            'items.*.product_id.exists' => 'Produk tidak ditemukan',
            'items.*.quantity.required' => 'Quantity wajib diisi',
            'items.*.quantity.min' => 'Quantity minimal 1',
            'items.*.quantity.max' => 'Quantity maksimal 10',
            'shipping_address.required' => 'Alamat pengiriman wajib diisi',
            'shipping_address.min' => 'Alamat pengiriman terlalu pendek',
            'shipping_address.max' => 'Alamat pengiriman terlalu panjang',
        ];
    }
}
