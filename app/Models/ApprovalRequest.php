<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'vendor_id',
        'requester_id',
        'approvable_id',
        'approvable_type',
        'request_type',
        'request_reason',
        'request_data',
        'request_amount',
        'status',
        'current_step',
        'approval_history',
        'submitted_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'approval_history' => 'array',
        'request_amount' => 'decimal:3',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the workflow
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the requester
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the approvable item (order, RFQ, etc.)
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get approval actions
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'approval_request_id');
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if request is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Get current approver
     */
    public function getCurrentApprover(): ?int
    {
        return $this->workflow->getApproverAtStep($this->current_step);
    }

    /**
     * Check if user can approve at current step
     */
    public function canApprove(int $userId): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        $currentApprover = $this->getCurrentApprover();

        if ($this->workflow->requires_all_approvers) {
            return $currentApprover === $userId;
        }

        // If any approver can approve, check if user is in approval chain
        return in_array($userId, $this->workflow->approval_chain ?? []);
    }

    /**
     * Approve request
     */
    public function approve(int $approverId, ?string $comments = null): bool
    {
        if (!$this->canApprove($approverId)) {
            return false;
        }

        // Record approval action
        $this->actions()->create([
            'approver_id' => $approverId,
            'step_number' => $this->current_step,
            'action' => 'approved',
            'comments' => $comments,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Update approval history
        $history = $this->approval_history ?? [];
        $history[] = [
            'step' => $this->current_step,
            'approver_id' => $approverId,
            'action' => 'approved',
            'timestamp' => now()->toISOString(),
            'comments' => $comments,
        ];

        // Check if this is the final approval
        if ($this->workflow->requires_all_approvers) {
            if ($this->current_step >= $this->workflow->total_steps - 1) {
                // Final approval
                $this->update([
                    'status' => 'approved',
                    'approval_history' => $history,
                    'completed_at' => now(),
                ]);
                return true;
            } else {
                // Move to next step
                $this->update([
                    'current_step' => $this->current_step + 1,
                    'approval_history' => $history,
                ]);
                return false; // Not final approval yet
            }
        } else {
            // Any approver can approve - mark as approved immediately
            $this->update([
                'status' => 'approved',
                'approval_history' => $history,
                'completed_at' => now(),
            ]);
            return true;
        }
    }

    /**
     * Reject request
     */
    public function reject(int $approverId, ?string $comments = null): void
    {
        // Record rejection action
        $this->actions()->create([
            'approver_id' => $approverId,
            'step_number' => $this->current_step,
            'action' => 'rejected',
            'comments' => $comments,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Update approval history
        $history = $this->approval_history ?? [];
        $history[] = [
            'step' => $this->current_step,
            'approver_id' => $approverId,
            'action' => 'rejected',
            'timestamp' => now()->toISOString(),
            'comments' => $comments,
        ];

        $this->update([
            'status' => 'rejected',
            'approval_history' => $history,
            'completed_at' => now(),
        ]);
    }

    /**
     * Cancel request
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
            'completed_at' => now(),
        ]);
    }

    /**
     * Scope: Pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: For vendor
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: For approver
     */
    public function scopeForApprover($query, int $approverId)
    {
        return $query->whereHas('workflow', function($q) use ($approverId) {
            $q->whereJsonContains('approval_chain', $approverId);
        })->where('status', 'pending');
    }

    /**
     * Scope: Expired requests
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                     ->where('status', 'pending');
    }
}
