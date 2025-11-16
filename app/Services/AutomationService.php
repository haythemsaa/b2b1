<?php

namespace App\Services;

use App\Models\AutomationRule;
use App\Models\AutomationExecution;
use App\Models\User;
use Carbon\Carbon;

class AutomationService
{
    public function createRule(User $vendor, array $data): AutomationRule
    {
        return AutomationRule::create(array_merge($data, [
            'vendor_id' => $vendor->id,
        ]));
    }

    public function executeRule(AutomationRule $rule, array $context = []): AutomationExecution
    {
        $startTime = microtime(true);
        $execution = AutomationExecution::create([
            'automation_rule_id' => $rule->id,
            'vendor_id' => $rule->vendor_id,
            'trigger_data' => $context,
            'started_at' => now(),
        ]);

        try {
            $actionsPerformed = [];
            
            foreach ($rule->actions as $action) {
                $result = $this->performAction($action, $rule->action_parameters ?? [], $context);
                $actionsPerformed[] = ['action' => $action, 'result' => $result];
            }

            $execution->update([
                'status' => 'success',
                'actions_performed' => $actionsPerformed,
                'actions_count' => count($actionsPerformed),
                'completed_at' => now(),
                'execution_time_ms' => (int)((microtime(true) - $startTime) * 1000),
            ]);

            $rule->recordExecution();
        } catch (\Exception $e) {
            $execution->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $execution;
    }

    protected function performAction(string $action, array $parameters, array $context): array
    {
        return match($action) {
            'send_notification' => ['sent' => true],
            'create_order' => ['order_id' => null],
            'approve_automatically' => ['approved' => true],
            default => ['executed' => true],
        };
    }

    public function getRules(User $vendor, ?string $type = null, ?bool $activeOnly = true)
    {
        $query = AutomationRule::forVendor($vendor->id);

        if ($type) {
            $query->byType($type);
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query->byPriority()->get();
    }

    public function getExecutions(AutomationRule $rule, int $limit = 100)
    {
        return AutomationExecution::forRule($rule->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
