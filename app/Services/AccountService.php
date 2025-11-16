<?php

namespace App\Services;

use App\Models\AccountUser;
use App\Models\AccountPermission;
use App\Models\AccountBudget;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AccountService
{
    /**
     * Create a new account user (sub-account)
     */
    public function createAccountUser(
        User $vendor,
        string $name,
        string $email,
        string $permissionLevel = 'manage',
        ?string $phone = null,
        ?string $position = null,
        ?string $department = null,
        ?array $budgetConfig = null
    ): AccountUser {
        return DB::transaction(function () use (
            $vendor,
            $name,
            $email,
            $permissionLevel,
            $phone,
            $position,
            $department,
            $budgetConfig
        ) {
            // Create the user account
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)), // Temporary password
                'role' => 'vendor',
            ]);

            // Create account user
            $accountUser = AccountUser::create([
                'vendor_id' => $vendor->id,
                'user_id' => $user->id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'position' => $position,
                'department' => $department,
                'status' => 'inactive', // Will be activated when they accept invitation
                'is_primary' => false,
                'invited_at' => now(),
            ]);

            // Create default permissions
            AccountPermission::createDefaultPermissions($accountUser, $permissionLevel);

            // Create budget if specified
            if ($budgetConfig) {
                $this->createBudget($accountUser, $budgetConfig);
            }

            Log::info('Account user created', [
                'vendor_id' => $vendor->id,
                'account_user_id' => $accountUser->id,
                'email' => $email,
            ]);

            return $accountUser->load(['permissions', 'budget']);
        });
    }

    /**
     * Update account user
     */
    public function updateAccountUser(
        AccountUser $accountUser,
        array $data
    ): AccountUser {
        $accountUser->update(array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'position' => $data['position'] ?? null,
            'department' => $data['department'] ?? null,
        ]));

        Log::info('Account user updated', ['account_user_id' => $accountUser->id]);

        return $accountUser->fresh();
    }

    /**
     * Update permissions for account user
     */
    public function updatePermissions(
        AccountUser $accountUser,
        string $permissionType,
        array $permissions
    ): AccountPermission {
        $permission = $accountUser->permissions()
            ->where('permission_type', $permissionType)
            ->firstOrFail();

        $permission->update($permissions);

        Log::info('Permissions updated', [
            'account_user_id' => $accountUser->id,
            'permission_type' => $permissionType,
        ]);

        return $permission;
    }

    /**
     * Create or update budget for account user
     */
    public function createBudget(
        AccountUser $accountUser,
        array $config
    ): AccountBudget {
        $budget = $accountUser->budget()->first();

        if ($budget) {
            $budget->update($config);
        } else {
            $budget = $accountUser->budget()->create($config);
            $budget->initializePeriod();
        }

        Log::info('Budget configured', [
            'account_user_id' => $accountUser->id,
            'budget_limit' => $config['budget_limit'] ?? 0,
        ]);

        return $budget;
    }

    /**
     * Activate account user
     */
    public function activateAccountUser(AccountUser $accountUser): void
    {
        $accountUser->activate();

        Log::info('Account user activated', ['account_user_id' => $accountUser->id]);
    }

    /**
     * Suspend account user
     */
    public function suspendAccountUser(AccountUser $accountUser, ?string $reason = null): void
    {
        $accountUser->suspend($reason);

        Log::info('Account user suspended', [
            'account_user_id' => $accountUser->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Deactivate account user
     */
    public function deactivateAccountUser(AccountUser $accountUser): void
    {
        $accountUser->deactivate();

        Log::info('Account user deactivated', ['account_user_id' => $accountUser->id]);
    }

    /**
     * Delete account user
     */
    public function deleteAccountUser(AccountUser $accountUser): void
    {
        if ($accountUser->isPrimary()) {
            throw new \Exception('Cannot delete primary account user');
        }

        DB::transaction(function () use ($accountUser) {
            $userId = $accountUser->user_id;

            $accountUser->delete();

            // Also delete the associated user account
            User::find($userId)?->delete();

            Log::info('Account user deleted', ['account_user_id' => $accountUser->id]);
        });
    }

    /**
     * Get all account users for a vendor
     */
    public function getAccountUsers(User $vendor, ?string $status = null)
    {
        $query = AccountUser::forVendor($vendor->id)
            ->with(['permissions', 'budget']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get account user statistics
     */
    public function getAccountUserStats(User $vendor): array
    {
        $users = AccountUser::forVendor($vendor->id);

        return [
            'total' => $users->count(),
            'active' => $users->active()->count(),
            'inactive' => $users->inactive()->count(),
            'suspended' => $users->suspended()->count(),
            'with_budget' => $users->whereHas('budget')->count(),
        ];
    }

    /**
     * Check if account user can perform action
     */
    public function canPerformAction(
        AccountUser $accountUser,
        string $permissionType,
        string $action,
        ?float $amount = null
    ): bool {
        // Check if user is active
        if (!$accountUser->isActive()) {
            return false;
        }

        // Check permissions
        if (!$accountUser->hasPermission($permissionType, $action)) {
            return false;
        }

        // Check budget if amount specified
        if ($amount !== null && !$accountUser->isWithinBudget($amount)) {
            return false;
        }

        return true;
    }

    /**
     * Record spending for account user with budget
     */
    public function recordSpending(AccountUser $accountUser, float $amount): void
    {
        if ($budget = $accountUser->budget) {
            $budget->recordSpending($amount);

            Log::info('Spending recorded', [
                'account_user_id' => $accountUser->id,
                'amount' => $amount,
                'budget_remaining' => $budget->getRemainingBudget(),
            ]);
        }
    }

    /**
     * Reset budget for account user
     */
    public function resetBudget(AccountUser $accountUser): void
    {
        if ($budget = $accountUser->budget) {
            $budget->resetBudget();

            Log::info('Budget reset', ['account_user_id' => $accountUser->id]);
        }
    }

    /**
     * Get budget status
     */
    public function getBudgetStatus(AccountUser $accountUser): ?array
    {
        $budget = $accountUser->budget;

        if (!$budget) {
            return null;
        }

        return [
            'budget_limit' => (float) $budget->budget_limit,
            'budget_used' => (float) $budget->budget_used,
            'budget_remaining' => $budget->getRemainingBudget(),
            'usage_percentage' => $budget->getUsagePercentage(),
            'is_over_budget' => $budget->isOverBudget(),
            'period_type' => $budget->budget_period,
            'period_start' => $budget->period_start,
            'period_end' => $budget->period_end,
            'alert_threshold' => (float) $budget->alert_threshold,
            'should_alert' => $budget->shouldAlert(),
        ];
    }

    /**
     * Reset budgets that need auto-reset
     */
    public function resetExpiredBudgets(): int
    {
        $count = 0;

        AccountBudget::where('auto_reset', true)->chunk(100, function ($budgets) use (&$count) {
            foreach ($budgets as $budget) {
                if ($budget->needsReset()) {
                    $budget->resetBudget();
                    $count++;
                }
            }
        });

        Log::info('Expired budgets reset', ['count' => $count]);

        return $count;
    }
}
