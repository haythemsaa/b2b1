<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_user_id',
        'permission_type',
        'can_view',
        'can_create',
        'can_edit',
        'can_delete',
        'can_approve',
        'restrictions',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_approve' => 'boolean',
        'restrictions' => 'array',
    ];

    // Relationships
    public function accountUser(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class);
    }

    // Scopes
    public function scopeForUser($query, $accountUserId)
    {
        return $query->where('account_user_id', $accountUserId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('permission_type', $type);
    }

    // Helper methods
    public function grantAll(): void
    {
        $this->update([
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
            'can_approve' => true,
        ]);
    }

    public function revokeAll(): void
    {
        $this->update([
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
            'can_approve' => false,
        ]);
    }

    public function grantViewOnly(): void
    {
        $this->update([
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
            'can_approve' => false,
        ]);
    }

    public function hasAnyPermission(): bool
    {
        return $this->can_view
            || $this->can_create
            || $this->can_edit
            || $this->can_delete
            || $this->can_approve;
    }

    public function getPermissionLevel(): string
    {
        if ($this->can_delete && $this->can_approve) {
            return 'full';
        }
        if ($this->can_create && $this->can_edit) {
            return 'manage';
        }
        if ($this->can_view) {
            return 'view';
        }
        return 'none';
    }

    public static function createDefaultPermissions(AccountUser $accountUser, string $level = 'manage'): void
    {
        $types = ['orders', 'products', 'invoices', 'rfqs', 'analytics', 'account_management', 'chat'];

        foreach ($types as $type) {
            $permissions = match($level) {
                'full' => [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                    'can_approve' => true,
                ],
                'manage' => [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_delete' => false,
                    'can_approve' => false,
                ],
                'view' => [
                    'can_view' => true,
                    'can_create' => false,
                    'can_edit' => false,
                    'can_delete' => false,
                    'can_approve' => false,
                ],
                default => [
                    'can_view' => false,
                    'can_create' => false,
                    'can_edit' => false,
                    'can_delete' => false,
                    'can_approve' => false,
                ],
            };

            // Account management should be restricted
            if ($type === 'account_management' && $level !== 'full') {
                $permissions = [
                    'can_view' => true,
                    'can_create' => false,
                    'can_edit' => false,
                    'can_delete' => false,
                    'can_approve' => false,
                ];
            }

            static::create(array_merge([
                'account_user_id' => $accountUser->id,
                'permission_type' => $type,
            ], $permissions));
        }
    }
}
