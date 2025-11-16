<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'string|nullable',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Votre compte est inactif. Veuillez contacter l\'administrateur.'],
            ]);
        }

        // Create token
        $deviceName = $request->device_name ?? $request->userAgent() ?? 'unknown';
        $token = $user->createToken($deviceName)->plainTextToken;

        // Load relationships
        $user->load('vendorProfile.vendorGroup');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'phone' => $user->phone,
                'locale' => $user->locale,
                'vendor_profile' => $user->isVendor() ? [
                    'company_name' => $user->vendorProfile?->company_name,
                    'vendor_group' => $user->vendorProfile?->vendorGroup ? [
                        'id' => $user->vendorProfile->vendorGroup->id,
                        'name' => $user->vendorProfile->vendorGroup->name,
                    ] : null,
                    'credit_limit' => $user->vendorProfile?->credit_limit,
                    'payment_term' => $user->vendorProfile?->payment_term,
                    'priority_shipping' => $user->vendorProfile?->priority_shipping,
                ] : null,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnexion de tous les appareils réussie',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('vendorProfile.vendorGroup');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'phone' => $user->phone,
            'locale' => $user->locale,
            'vendor_profile' => $user->isVendor() ? [
                'company_name' => $user->vendorProfile?->company_name,
                'tax_id' => $user->vendorProfile?->tax_id,
                'vendor_group' => $user->vendorProfile?->vendorGroup ? [
                    'id' => $user->vendorProfile->vendorGroup->id,
                    'name' => $user->vendorProfile->vendorGroup->name,
                ] : null,
                'credit_limit' => $user->vendorProfile?->credit_limit,
                'payment_term' => $user->vendorProfile?->payment_term,
                'minimum_order_amount' => $user->vendorProfile?->minimum_order_amount,
                'priority_shipping' => $user->vendorProfile?->priority_shipping,
                'shipping_address' => $user->vendorProfile?->shipping_address,
                'billing_address' => $user->vendorProfile?->billing_address,
            ] : null,
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'locale' => 'sometimes|in:fr,ar',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user,
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès',
        ]);
    }
}
