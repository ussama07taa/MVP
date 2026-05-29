<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Service::latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'calculation_type' => 'required|string',
            'unit_price' => 'required|numeric|min:0'
        ]);

        $tenantId = auth()->user()->tenant_id;

        return Service::create(array_merge($validated, ['tenant_id' => $tenantId]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $service->update($request->only(['name', 'calculation_type', 'unit_price']));

        return $service;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Service::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
