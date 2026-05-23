<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = $this->currentTenantSettings();

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name'        => 'required|string|max:255',
            'company_phone'       => 'nullable|string|max:50',
            'company_email'       => 'nullable|email|max:255',
            'company_address'     => 'nullable|string|max:1000',
            'company_ice'         => 'nullable|string|max:50',
            'company_rc'          => 'nullable|string|max:50',
            'invoice_footer_text' => 'nullable|string|max:1000',
            'logo'                => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $tenantId = auth()->user()->tenant_id;

        unset($validated['logo']);

        $settings = Setting::firstOrNew(['tenant_id' => $tenantId]);
        $settings->fill($validated);
        $settings->tenant_id = $tenantId;

        if ($request->hasFile('logo')) {
            // Delete previous logo if any to avoid orphaned files.
            if ($settings->company_logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($settings->company_logo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->company_logo);
            }
            $settings->company_logo = $request->file('logo')->store('settings', 'public');
        }

        $settings->save();

        return response()->json($settings);
    }

    /**
     * Get (or create) the Setting row for the current tenant.
     */
    protected function currentTenantSettings(): Setting
    {
        $tenantId = auth()->user()->tenant_id;

        $settings = Setting::first();
        if ($settings) {
            return $settings;
        }

        $settings = new Setting();
        $settings->tenant_id = $tenantId;
        $settings->company_name = 'Mon Entreprise';
        $settings->company_phone = '';
        $settings->invoice_footer_text = 'Merci pour votre confiance !';
        $settings->save();

        return $settings;
    }
}
