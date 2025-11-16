<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'approver_id',
        'step_number',
        'action',
        'comments',
        'action_data',
        'delegated_to',
        'delegation_reason',
        'ip_address',
        'user_agent',
        'action_time',
    ];

    protected $casts = [
        'action_data' => 'array',
        'action_time' => 'datetime',
    ];

    /**
     * Get the approval request
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * Get the approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the user this action was delegated to
     */
    public function delegatedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }

    /**
     * Check if action is approval
     */
    public function isApproval(): bool
    {
        return $this->action === 'approved';
    }

    /**
     * Check if action is rejection
     */
    public function isRejection(): bool
    {
        return $this->action === 'rejected';
    }

    /**
     * Check if action is delegation
     */
    public function isDelegation(): bool
    {
        return $this->action === 'delegated';
    }

    /**
     * Scope: By action type
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: By approver
     */
    public function scopeByApprover($query, int $approverId)
    {
        return $query->where('approver_id', $approverId);
    }
}
