<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'supplier_id'        => ['required', \Illuminate\Validation\Rule::exists('suppliers', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'reference_invoice'  => 'nullable|string|max:100',
            'total_amount'       => 'required|numeric|min:0',
            'amount_paid'        => 'nullable|numeric|min:0',
            'payment_method'     => 'nullable|string|in:cash,cheque,virement,traite',
            'invoice_document'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'items'              => 'required|array|min:1',
            'items.*.category'   => 'required|string|in:mdf,panel,canto,consumable',
            'items.*.data'       => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'supplier_id.required'   => 'Le fournisseur est obligatoire.',
            'supplier_id.exists'     => 'Ce fournisseur n\'existe pas.',
            'total_amount.required'  => 'Le montant total est obligatoire.',
            'total_amount.min'       => 'Le montant total doit être positif.',
            'items.required'         => 'Veuillez ajouter au moins un article.',
            'items.min'              => 'Veuillez ajouter au moins un article.',
            'items.*.category.in'    => 'Catégorie invalide. Valeurs acceptées: mdf, panel, canto, consumable.',
        ];
    }
}
