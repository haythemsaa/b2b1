<?php

namespace App\Services;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequest;
use App\Models\ApprovalAction;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApprovalService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create approval workflow
     */
    public function createWorkflow(
        User $vendor,
        string $name,
        string $workflowType,
        array $approvalChain,
        ?string $description = null,
        ?array $triggerConditions = null,
        ?float $amountThreshold = null,
        ?int $quantityThreshold = null,
        bool $requiresAllApprovers = false,
        ?int $timeoutHours = null,
        int $priority = 0
    ): ApprovalWorkflow {
        return ApprovalWorkflow::create([
            'vendor_id' => $vendor->id,
            'name' => $name,
            'description' => $description,
            'workflow_type' => $workflowType,
            'trigger_conditions' => $triggerConditions,
            'amount_threshold' => $amountThreshold,
            'quantity_threshold' => $quantityThreshold,
            'approval_chain' => $approvalChain,
            'requires_all_approvers' => $requiresAllApprovers,
            'approval_timeout_hours' => $timeoutHours,
            'is_active' => true,
            'priority' => $priority,
        ]);
    }

    /**
     * Update workflow
     */
    public function updateWorkflow(
        ApprovalWorkflow $workflow,
        array $data
    ): ApprovalWorkflow {
        $workflow->update($data);
        return $workflow->fresh();
    }

    /**
     * Activate workflow
     */
    public function activateWorkflow(ApprovalWorkflow $workflow): void
    {
        $workflow->update(['is_active' => true]);
    }

    /**
     * Deactivate workflow
     */
    public function deactivateWorkflow(ApprovalWorkflow $workflow): void
    {
        $workflow->update(['is_active' => false]);
    }

    /**
     * Delete workflow
     */
    public function deleteWorkflow(ApprovalWorkflow $workflow): void
    {
        // Cancel all pending requests for this workflow
        $workflow->approvalRequests()
            ->where('status', 'pending')
            ->each(fn($request) => $request->cancel());

        $workflow->delete();
    }

    /**
     * Check if approval is required
     */
    public function requiresApproval(
        User $vendor,
        string $workflowType,
        array $conditions
    ): ?ApprovalWorkflow {
        return ApprovalWorkflow::findApplicable($vendor->id, $workflowType, $conditions);
    }

    /**
     * Create approval request
     */
    public function createApprovalRequest(
        User $vendor,
        User $requester,
        ApprovalWorkflow $workflow,
        Model $approvable,
        string $requestType,
        ?string $reason = null,
        ?array $requestData = null,
        ?float $requestAmount = null
    ): ApprovalRequest {
        return DB::transaction(function () use (
            $vendor,
            $requester,
            $workflow,
            $approvable,
            $requestType,
            $reason,
            $requestData,
            $requestAmount
        ) {
            // Calculate expiry if timeout is set
            $expiresAt = $workflow->approval_timeout_hours
                ? now()->addHours($workflow->approval_timeout_hours)
                : null;

            // Create approval request
            $request = ApprovalRequest::create([
                'workflow_id' => $workflow->id,
                'vendor_id' => $vendor->id,
                'requester_id' => $requester->id,
                'approvable_id' => $approvable->id,
                'approvable_type' => get_class($approvable),
                'request_type' => $requestType,
                'request_reason' => $reason,
                'request_data' => $requestData,
                'request_amount' => $requestAmount,
                'status' => 'pending',
                'current_step' => 0,
                'submitted_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            // Notify approvers
            $this->notifyApprovers($request);

            return $request;
        });
    }

    /**
     * Approve request
     */
    public function approve(
        ApprovalRequest $request,
        User $approver,
        ?string $comments = null
    ): array {
        if (!$request->canApprove($approver->id)) {
            throw new \Exception('You are not authorized to approve this request');
        }

        $isFinalApproval = $request->approve($approver->id, $comments);

        // Notify requester
        $this->notificationService->notify(
            $request->requester_id,
            $isFinalApproval ? 'approval_approved' : 'approval_step_completed',
            $isFinalApproval ? 'Request Approved' : 'Approval Step Completed',
            $isFinalApproval
                ? "Your {$request->request_type} request has been approved"
                : "Your {$request->request_type} request has passed step " . ($request->current_step + 1),
            $request->vendor_id,
            $request,
            ['request_id' => $request->id],
            null,
            'high'
        );

        // If not final approval, notify next approver
        if (!$isFinalApproval) {
            $this->notifyNextApprover($request);
        }

        return [
            'final_approval' => $isFinalApproval,
            'request' => $request->fresh(),
        ];
    }

    /**
     * Reject request
     */
    public function reject(
        ApprovalRequest $request,
        User $approver,
        ?string $comments = null
    ): ApprovalRequest {
        if (!$request->canApprove($approver->id)) {
            throw new \Exception('You are not authorized to reject this request');
        }

        $request->reject($approver->id, $comments);

        // Notify requester
        $this->notificationService->notify(
            $request->requester_id,
            'approval_rejected',
            'Request Rejected',
            "Your {$request->request_type} request has been rejected" .
            ($comments ? ": {$comments}" : ''),
            $request->vendor_id,
            $request,
            ['request_id' => $request->id],
            null,
            'high'
        );

        return $request->fresh();
    }

    /**
     * Cancel approval request
     */
    public function cancelRequest(ApprovalRequest $request): void
    {
        $request->cancel();

        // Notify approvers
        $approverIds = $request->workflow->approval_chain ?? [];
        foreach ($approverIds as $approverId) {
            $this->notificationService->notify(
                $approverId,
                'approval_cancelled',
                'Approval Request Cancelled',
                "An approval request for {$request->request_type} has been cancelled",
                $request->vendor_id,
                $request
            );
        }
    }

    /**
     * Delegate approval
     */
    public function delegateApproval(
        ApprovalRequest $request,
        User $approver,
        User $delegateTo,
        ?string $reason = null
    ): void {
        // Record delegation action
        $request->actions()->create([
            'approver_id' => $approver->id,
            'step_number' => $request->current_step,
            'action' => 'delegated',
            'comments' => $reason,
            'delegated_to' => $delegateTo->id,
            'delegation_reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Notify delegated user
        $this->notificationService->notify(
            $delegateTo->id,
            'approval_delegated',
            'Approval Delegated to You',
            "An approval request has been delegated to you by {$approver->name}",
            $request->vendor_id,
            $request,
            ['request_id' => $request->id],
            null,
            'high'
        );
    }

    /**
     * Get pending approvals for user
     */
    public function getPendingApprovalsForUser(User $user, ?int $vendorId = null)
    {
        $query = ApprovalRequest::forApprover($user->id)
            ->with(['workflow', 'requester', 'approvable'])
            ->orderBy('submitted_at', 'desc');

        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        return $query->get();
    }

    /**
     * Get approval requests for vendor
     */
    public function getApprovalRequestsForVendor(
        User $vendor,
        ?string $status = null,
        ?string $requestType = null
    ) {
        $query = ApprovalRequest::forVendor($vendor->id)
            ->with(['workflow', 'requester', 'actions.approver'])
            ->orderBy('submitted_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($requestType) {
            $query->where('request_type', $requestType);
        }

        return $query->get();
    }

    /**
     * Get approval statistics
     */
    public function getApprovalStats(User $vendor, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $requests = ApprovalRequest::forVendor($vendor->id)
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->get();

        return [
            'total_requests' => $requests->count(),
            'pending' => $requests->where('status', 'pending')->count(),
            'approved' => $requests->where('status', 'approved')->count(),
            'rejected' => $requests->where('status', 'rejected')->count(),
            'cancelled' => $requests->where('status', 'cancelled')->count(),
            'expired' => $requests->where('status', 'expired')->count(),
            'approval_rate' => $requests->count() > 0
                ? ($requests->where('status', 'approved')->count() / $requests->count() * 100)
                : 0,
            'average_approval_time' => $this->calculateAverageApprovalTime($requests),
            'by_type' => $requests->groupBy('request_type')->map->count(),
        ];
    }

    /**
     * Process expired requests
     */
    public function processExpiredRequests(): int
    {
        $expiredRequests = ApprovalRequest::expired()->get();

        foreach ($expiredRequests as $request) {
            $request->markAsExpired();

            // Notify requester
            $this->notificationService->notify(
                $request->requester_id,
                'approval_expired',
                'Approval Request Expired',
                "Your {$request->request_type} request has expired",
                $request->vendor_id,
                $request
            );
        }

        return $expiredRequests->count();
    }

    /**
     * Notify approvers
     */
    protected function notifyApprovers(ApprovalRequest $request): void
    {
        if ($request->workflow->requires_all_approvers) {
            // Notify only first approver
            $approverId = $request->getCurrentApprover();
            if ($approverId) {
                $this->notifyApprover($request, $approverId);
            }
        } else {
            // Notify all approvers
            $approverIds = $request->workflow->approval_chain ?? [];
            foreach ($approverIds as $approverId) {
                $this->notifyApprover($request, $approverId);
            }
        }
    }

    /**
     * Notify next approver in chain
     */
    protected function notifyNextApprover(ApprovalRequest $request): void
    {
        $approverId = $request->getCurrentApprover();
        if ($approverId) {
            $this->notifyApprover($request, $approverId);
        }
    }

    /**
     * Notify single approver
     */
    protected function notifyApprover(ApprovalRequest $request, int $approverId): void
    {
        $this->notificationService->notify(
            $approverId,
            'approval_required',
            'Approval Required',
            "A new {$request->request_type} request requires your approval" .
            ($request->request_amount ? " (Amount: " . number_format($request->request_amount, 2) . ")" : ''),
            $request->vendor_id,
            $request,
            ['request_id' => $request->id],
            null,
            'high'
        );
    }

    /**
     * Calculate average approval time
     */
    protected function calculateAverageApprovalTime($requests): float
    {
        $approvedRequests = $requests->where('status', 'approved')
            ->filter(fn($r) => $r->completed_at && $r->submitted_at);

        if ($approvedRequests->count() === 0) {
            return 0;
        }

        $totalMinutes = $approvedRequests->sum(function ($request) {
            return $request->submitted_at->diffInMinutes($request->completed_at);
        });

        return $totalMinutes / $approvedRequests->count();
    }
}
