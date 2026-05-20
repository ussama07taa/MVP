<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryAdjustmentRequest extends FormRequest
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
            'item_id' => 'required|numeric',
            'item_type' => 'required|string',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|in:kosor,chute,erreur,autre',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
