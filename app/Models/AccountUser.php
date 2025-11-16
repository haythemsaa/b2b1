<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'user_id',
        'name',
        'email',
        'phone',
        'position',
        'department',
        'status',
        'is_primary',
        'last_login_at',
        'last_login_ip',
        'invited_at',
        'activated_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'last_login_at' => 'datetime',
        'invited_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(AccountPermission::class);
    }

    public function budget(): HasOne
    {
        return $this->hasOne(AccountBudget::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isPrimary(): bool
    {
        return (bool) $this->is_primary;
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

    public function suspend(?string $reason = null): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function deactivate(): void
    {
        $this->update(['status' => 'inactive']);
    }

    public function recordLogin(?string $ipAddress = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    public function hasPermission(string $permissionType, string $action): bool
    {
        $permission = $this->permissions()
            ->where('permission_type', $permissionType)
            ->first();

        if (!$permission) {
            return false;
        }

        return match($action) {
            'view' => $permission->can_view,
            'create' => $permission->can_create,
            'edit' => $permission->can_edit,
            'delete' => $permission->can_delete,
            'approve' => $permission->can_approve,
            default => false,
        };
    }

    public function hasAnyPermission(string $permissionType): bool
    {
        $permission = $this->permissions()
            ->where('permission_type', $permissionType)
            ->first();

        if (!$permission) {
            return false;
        }

        return $permission->can_view
            || $permission->can_create
            || $permission->can_edit
            || $permission->can_delete
            || $permission->can_approve;
    }

    public function isWithinBudget(float $amount): bool
    {
        if (!$this->budget) {
            return true; // No budget limit
        }

        return $this->budget->canSpend($amount);
    }
}
