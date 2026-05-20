<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockPanelRequest extends FormRequest
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
            'type' => 'required|string|max:255',
            'color_code' => 'nullable|string|max:255',
            'color_name' => 'nullable|string|max:255',
            'finish_type' => 'nullable|string|max:255',
            'provider_catalog' => 'nullable|string|max:255',
            'size_x' => 'required|numeric|min:0',
            'size_y' => 'required|numeric|min:0',
            'thickness' => 'required|numeric|min:0',
            'base_price_sell' => 'required|numeric|min:0',
            'alert_threshold' => 'nullable|numeric|min:0',
            'supplier_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('suppliers', 'id')->where('tenant_id', auth()->user()->tenant_id)
            ],
        ];
    }
}
