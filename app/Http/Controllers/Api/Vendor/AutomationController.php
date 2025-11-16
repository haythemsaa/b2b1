<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\AutomationService;
use App\Models\AutomationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutomationController extends Controller
{
    protected AutomationService $automationService;

    public function __construct(AutomationService $automationService)
    {
        $this->automationService = $automationService;
    }

    public function index(Request $request)
    {
        $vendor = Auth::user();

        $rules = $this->automationService->getRules($vendor, $request->type);

        return response()->json([
            'status' => 'success',
            'data' => $rules,
        ]);
    }

    public function store(Request $request)
    {
        $vendor = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'rule_type' => 'required|in:auto_reorder,price_alert,stock_alert,approval_routing,document_processing,notification_routing,budget_management,custom',
            'trigger_conditions' => 'required|array',
            'actions' => 'required|array',
        ]);

        $rule = $this->automationService->createRule($vendor, $request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Automation rule created',
            'data' => $rule,
        ], 201);
    }

    public function executions(AutomationRule $rule)
    {
        $executions = $this->automationService->getExecutions($rule);

        return response()->json([
            'status' => 'success',
            'data' => $executions,
        ]);
    }
}
