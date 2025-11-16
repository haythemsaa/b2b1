<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\ApprovalService;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    protected ApprovalService $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    // Workflows
    public function workflows(Request $request)
    {
        $vendor = Auth::user();
        $workflows = ApprovalWorkflow::forVendor($vendor->id)->get();

        return response()->json([
            'status' => 'success',
            'data' => $workflows,
        ]);
    }

    public function createWorkflow(Request $request)
    {
        $vendor = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'workflow_type' => 'required|in:order_approval,rfq_approval,budget_approval,invoice_payment_approval,account_creation_approval',
            'approval_chain' => 'required|array|min:1',
            'approval_chain.*' => 'required|integer|exists:users,id',
            'amount_threshold' => 'nullable|numeric|min:0',
            'quantity_threshold' => 'nullable|integer|min:0',
            'requires_all_approvers' => 'boolean',
            'approval_timeout_hours' => 'nullable|integer|min:1',
        ]);

        $workflow = $this->approvalService->createWorkflow(
            $vendor,
            $request->name,
            $request->workflow_type,
            $request->approval_chain,
            $request->description,
            $request->trigger_conditions,
            $request->amount_threshold,
            $request->quantity_threshold,
            $request->requires_all_approvers ?? false,
            $request->approval_timeout_hours,
            $request->priority ?? 0
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Workflow created successfully',
            'data' => $workflow,
        ], 201);
    }

    public function updateWorkflow(Request $request, ApprovalWorkflow $workflow)
    {
        $vendor = Auth::user();

        if ($workflow->vendor_id !== $vendor->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $workflow = $this->approvalService->updateWorkflow($workflow, $request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Workflow updated successfully',
            'data' => $workflow,
        ]);
    }

    public function deleteWorkflow(ApprovalWorkflow $workflow)
    {
        $vendor = Auth::user();

        if ($workflow->vendor_id !== $vendor->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $this->approvalService->deleteWorkflow($workflow);

        return response()->json([
            'status' => 'success',
            'message' => 'Workflow deleted successfully',
        ]);
    }

    // Approval Requests
    public function requests(Request $request)
    {
        $vendor = Auth::user();

        $requests = $this->approvalService->getApprovalRequestsForVendor(
            $vendor,
            $request->status,
            $request->request_type
        );

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    public function pendingApprovals(Request $request)
    {
        $user = Auth::user();

        $approvals = $this->approvalService->getPendingApprovalsForUser($user);

        return response()->json([
            'status' => 'success',
            'data' => $approvals,
        ]);
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        $user = Auth::user();

        $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->approvalService->approve(
                $approvalRequest,
                $user,
                $request->comments
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Request approved successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        $user = Auth::user();

        $request->validate([
            'comments' => 'required|string|max:1000',
        ]);

        try {
            $result = $this->approvalService->reject(
                $approvalRequest,
                $user,
                $request->comments
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Request rejected successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    public function stats(Request $request)
    {
        $vendor = Auth::user();

        $stats = $this->approvalService->getApprovalStats($vendor);

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
}
