<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\AccountUser;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    /**
     * Get all account users
     */
    public function index(Request $request)
    {
        $vendor = Auth::user();

        $users = $this->accountService->getAccountUsers(
            $vendor,
            $request->get('status')
        );

        $stats = $this->accountService->getAccountUserStats($vendor);

        return response()->json([
            'status' => 'success',
            'data' => [
                'account_users' => $users,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Create new account user
     */
    public function store(Request $request)
    {
        $vendor = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'permission_level' => 'required|in:full,manage,view',
            'budget' => 'nullable|array',
            'budget.budget_period' => 'required_with:budget|in:daily,weekly,monthly,yearly,unlimited',
            'budget.budget_limit' => 'required_with:budget|numeric|min:0',
        ]);

        try {
            $accountUser = $this->accountService->createAccountUser(
                $vendor,
                $request->name,
                $request->email,
                $request->permission_level,
                $request->phone,
                $request->position,
                $request->department,
                $request->budget
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Account user created successfully',
                'data' => $accountUser,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create account user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific account user
     */
    public function show(AccountUser $accountUser)
    {
        $vendor = Auth::user();

        if ($accountUser->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $accountUser->load(['permissions', 'budget']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'account_user' => $accountUser,
                'budget_status' => $this->accountService->getBudgetStatus($accountUser),
            ],
        ]);
    }

    /**
     * Update account user
     */
    public function update(Request $request, AccountUser $accountUser)
    {
        $vendor = Auth::user();

        if ($accountUser->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
        ]);

        try {
            $accountUser = $this->accountService->updateAccountUser($accountUser, $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Account user updated successfully',
                'data' => $accountUser,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update account user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate account user
     */
    public function activate(AccountUser $accountUser)
    {
        $vendor = Auth::user();

        if ($accountUser->vendor_id !== $vendor->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $this->accountService->activateAccountUser($accountUser);

        return response()->json([
            'status' => 'success',
            'message' => 'Account user activated',
        ]);
    }

    /**
     * Suspend account user
     */
    public function suspend(Request $request, AccountUser $accountUser)
    {
        $vendor = Auth::user();

        if ($accountUser->vendor_id !== $vendor->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $this->accountService->suspendAccountUser($accountUser, $request->reason);

        return response()->json([
            'status' => 'success',
            'message' => 'Account user suspended',
        ]);
    }

    /**
     * Delete account user
     */
    public function destroy(AccountUser $accountUser)
    {
        $vendor = Auth::user();

        if ($accountUser->vendor_id !== $vendor->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        try {
            $this->accountService->deleteAccountUser($accountUser);

            return response()->json([
                'status' => 'success',
                'message' => 'Account user deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update permissions
     */
    public function updatePermissions(Request $request, AccountUser $accountUser)
    {
        $vendor = Auth::user();

        if ($accountUser->vendor_id !== $vendor->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'permission_type' => 'required|in:orders,products,invoices,rfqs,analytics,account_management,chat',
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_approve' => 'boolean',
        ]);

        $permission = $this->accountService->updatePermissions(
            $accountUser,
            $request->permission_type,
            $request->only(['can_view', 'can_create', 'can_edit', 'can_delete', 'can_approve'])
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions updated',
            'data' => $permission,
        ]);
    }

    /**
     * Update budget
     */
    public function updateBudget(Request $request, AccountUser $accountUser)
    {
        $vendor = Auth::user();

        if ($accountUser->vendor_id !== $vendor->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'budget_period' => 'required|in:daily,weekly,monthly,yearly,unlimited',
            'budget_limit' => 'required|numeric|min:0',
            'alert_threshold' => 'nullable|numeric|min:0|max:100',
        ]);

        $budget = $this->accountService->createBudget($accountUser, $request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Budget updated',
            'data' => [
                'budget' => $budget,
                'budget_status' => $this->accountService->getBudgetStatus($accountUser),
            ],
        ]);
    }
}
