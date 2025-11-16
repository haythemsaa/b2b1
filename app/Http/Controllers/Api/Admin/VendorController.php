<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorGroup;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Get all vendors
     */
    public function index(Request $request)
    {
        $query = User::vendors()
            ->with(['vendorProfile.vendorGroup'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vendor_group_id')) {
            $query->whereHas('vendorProfile', function($q) use ($request) {
                $q->where('vendor_group_id', $request->vendor_group_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('vendorProfile', function($vp) use ($search) {
                      $vp->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->input('per_page', 15);
        $vendors = $query->paginate($perPage);

        return response()->json($vendors);
    }

    /**
     * Get single vendor details
     */
    public function show(User $vendor)
    {
        if (!$vendor->isVendor()) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $vendor->load(['vendorProfile.vendorGroup', 'orders']);

        $stats = $this->orderService->getVendorOrderStats($vendor);

        return response()->json([
            'id' => $vendor->id,
            'name' => $vendor->name,
            'email' => $vendor->email,
            'phone' => $vendor->phone,
            'status' => $vendor->status,
            'locale' => $vendor->locale,
            'created_at' => $vendor->created_at,
            'vendor_profile' => $vendor->vendorProfile ? [
                'company_name' => $vendor->vendorProfile->company_name,
                'tax_id' => $vendor->vendorProfile->tax_id,
                'vendor_group' => $vendor->vendorProfile->vendorGroup ? [
                    'id' => $vendor->vendorProfile->vendorGroup->id,
                    'name' => $vendor->vendorProfile->vendorGroup->name,
                ] : null,
                'credit_limit' => $vendor->vendorProfile->credit_limit,
                'payment_term' => $vendor->vendorProfile->payment_term,
                'minimum_order_amount' => $vendor->vendorProfile->minimum_order_amount,
                'priority_shipping' => $vendor->vendorProfile->priority_shipping,
                'shipping_address' => $vendor->vendorProfile->shipping_address,
                'billing_address' => $vendor->vendorProfile->billing_address,
            ] : null,
            'statistics' => $stats,
        ]);
    }

    /**
     * Create new vendor
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'locale' => 'nullable|in:fr,ar',
            'company_name' => 'required|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'vendor_group_id' => 'nullable|exists:vendor_groups,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_term' => 'nullable|in:immediate,net_30,net_60,net_90',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'priority_shipping' => 'nullable|boolean',
            'shipping_address' => 'nullable|string',
            'billing_address' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Create user
            $vendor = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'vendor',
                'status' => 'active',
                'phone' => $validated['phone'] ?? null,
                'locale' => $validated['locale'] ?? 'fr',
            ]);

            // Create vendor profile
            VendorProfile::create([
                'user_id' => $vendor->id,
                'company_name' => $validated['company_name'],
                'tax_id' => $validated['tax_id'] ?? null,
                'vendor_group_id' => $validated['vendor_group_id'] ?? null,
                'credit_limit' => $validated['credit_limit'] ?? 0,
                'payment_term' => $validated['payment_term'] ?? 'immediate',
                'minimum_order_amount' => $validated['minimum_order_amount'] ?? 0,
                'priority_shipping' => $validated['priority_shipping'] ?? false,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'billing_address' => $validated['billing_address'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Vendeur créé avec succès',
                'vendor' => $vendor->load('vendorProfile.vendorGroup'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update vendor
     */
    public function update(Request $request, User $vendor)
    {
        if (!$vendor->isVendor()) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $vendor->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive,suspended',
            'locale' => 'sometimes|in:fr,ar',
            'company_name' => 'sometimes|string|max:255',
            'tax_id' => 'sometimes|nullable|string|max:50',
            'vendor_group_id' => 'sometimes|nullable|exists:vendor_groups,id',
            'credit_limit' => 'sometimes|nullable|numeric|min:0',
            'payment_term' => 'sometimes|in:immediate,net_30,net_60,net_90',
            'minimum_order_amount' => 'sometimes|nullable|numeric|min:0',
            'priority_shipping' => 'sometimes|boolean',
            'shipping_address' => 'sometimes|nullable|string',
            'billing_address' => 'sometimes|nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Update user fields
            $userFields = array_intersect_key($validated, array_flip(['name', 'email', 'phone', 'status', 'locale']));
            if (!empty($userFields)) {
                $vendor->update($userFields);
            }

            // Update vendor profile fields
            $profileFields = array_intersect_key($validated, array_flip([
                'company_name', 'tax_id', 'vendor_group_id', 'credit_limit',
                'payment_term', 'minimum_order_amount', 'priority_shipping',
                'shipping_address', 'billing_address'
            ]));
            if (!empty($profileFields) && $vendor->vendorProfile) {
                $vendor->vendorProfile->update($profileFields);
            }

            DB::commit();

            return response()->json([
                'message' => 'Vendeur mis à jour avec succès',
                'vendor' => $vendor->load('vendorProfile.vendorGroup'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete vendor (soft delete)
     */
    public function destroy(User $vendor)
    {
        if (!$vendor->isVendor()) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $vendor->delete();

        return response()->json([
            'message' => 'Vendeur supprimé avec succès',
        ]);
    }

    /**
     * Get vendor groups
     */
    public function groups(Request $request)
    {
        $groups = VendorGroup::withCount('vendors')
            ->orderBy('priority_level', 'desc')
            ->get();

        return response()->json($groups);
    }
}
