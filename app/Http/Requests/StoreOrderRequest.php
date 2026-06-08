<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize() { return true; }

    protected function prepareForValidation(): void
    {
        if (is_string($this->items)) {
            $this->merge([
                'items' => json_decode($this->items, true) ?? [],
            ]);
        }

        if ($this->has('send_to_workshop')) {
            $this->merge([
                'send_to_workshop' => filter_var($this->send_to_workshop, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function rules() {
        return [
            'client_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('clients', 'id')->where('tenant_id', auth()->user()->tenant_id)
            ],
            'amount_paid' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:panel,canto,service,custom_labor',
            'items.*.id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.with_pose' => 'nullable|boolean',
            'items.*.custom_pose_price' => 'nullable|numeric|min:0',
            'items.*.with_canto_service' => 'nullable|boolean',
            'items.*.custom_canto_service_price' => 'nullable|numeric|min:0',
            'items.*.base_canto_price' => 'nullable|numeric|min:0',
            'send_to_workshop' => 'nullable|boolean',
            'workshop_notes' => 'nullable|string|max:1000',
            'tefsil_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];
    }
}
