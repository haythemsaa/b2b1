<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'workflow_type',
        'trigger_conditions',
        'amount_threshold',
        'quantity_threshold',
        'approval_chain',
        'requires_all_approvers',
        'approval_timeout_hours',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'approval_chain' => 'array',
        'requires_all_approvers' => 'boolean',
        'is_active' => 'boolean',
        'amount_threshold' => 'decimal:3',
        'priority' => 'integer',
        'approval_timeout_hours' => 'integer',
    ];

    /**
     * Get the vendor that owns the workflow
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get approval requests for this workflow
     */
    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'workflow_id');
    }

    /**
     * Get approvers from the approval chain
     */
    public function getApproversAttribute(): array
    {
        return $this->approval_chain ?? [];
    }

    /**
     * Check if workflow should be triggered for given conditions
     */
    public function shouldTrigger(array $conditions): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check amount threshold
        if ($this->amount_threshold && isset($conditions['amount'])) {
            if ($conditions['amount'] < $this->amount_threshold) {
                return false;
            }
        }

        // Check quantity threshold
        if ($this->quantity_threshold && isset($conditions['quantity'])) {
            if ($conditions['quantity'] < $this->quantity_threshold) {
                return false;
            }
        }

        // Check custom trigger conditions
        if ($this->trigger_conditions) {
            foreach ($this->trigger_conditions as $key => $value) {
                if (!isset($conditions[$key]) || $conditions[$key] != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get approver at specific step
     */
    public function getApproverAtStep(int $step): ?int
    {
        $approvers = $this->approval_chain ?? [];
        return $approvers[$step] ?? null;
    }

    /**
     * Get total approval steps
     */
    public function getTotalStepsAttribute(): int
    {
        return count($this->approval_chain ?? []);
    }

    /**
     * Scope: Active workflows
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By workflow type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('workflow_type', $type);
    }

    /**
     * Scope: For vendor
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: Order by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Find applicable workflow for given type and conditions
     */
    public static function findApplicable(int $vendorId, string $workflowType, array $conditions): ?self
    {
        $workflows = static::forVendor($vendorId)
            ->byType($workflowType)
            ->active()
            ->byPriority()
            ->get();

        foreach ($workflows as $workflow) {
            if ($workflow->shouldTrigger($conditions)) {
                return $workflow;
            }
        }

        return null;
    }

    /**
     * Add approver to chain
     */
    public function addApprover(int $userId): void
    {
        $chain = $this->approval_chain ?? [];
        $chain[] = $userId;
        $this->update(['approval_chain' => $chain]);
    }

    /**
     * Remove approver from chain
     */
    public function removeApprover(int $userId): void
    {
        $chain = $this->approval_chain ?? [];
        $chain = array_values(array_filter($chain, fn($id) => $id != $userId));
        $this->update(['approval_chain' => $chain]);
    }

    /**
     * Set approval chain
     */
    public function setApprovalChain(array $userIds): void
    {
        $this->update(['approval_chain' => $userIds]);
    }
}
