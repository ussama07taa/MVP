<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        return response()->json(User::where('tenant_id', $request->user()->tenant_id)->get());
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,cashier,worker'
        ]);
        
        $user = User::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true
        ]);
        
        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') abort(403);

        $user = User::where('tenant_id', $request->user()->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|min:6',
            'role' => 'required|in:admin,cashier,worker',
            'is_active' => 'boolean'
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') abort(403);

        $user = User::where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
        
        // Prevent deleting oneself
        if ($request->user()->id === $user->id) {
            return response()->json(['error' => 'Vous ne pouvez pas supprimer votre propre compte.'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }
}
