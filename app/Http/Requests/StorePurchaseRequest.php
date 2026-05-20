<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('suppliers', 'id')->where('tenant_id', auth()->user()->tenant_id)
            ],
            'reference_invoice' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'items' => 'required', // Can be JSON string or array
            'invoice_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }
}
