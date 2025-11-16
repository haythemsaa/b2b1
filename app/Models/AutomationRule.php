<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class AutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'rule_type',
        'trigger_conditions',
        'trigger_frequency',
        'actions',
        'action_parameters',
        'min_threshold',
        'max_threshold',
        'constraints',
        'is_active',
        'priority',
        'execution_count',
        'last_executed_at',
        'next_execution_at',
        'max_executions_per_day',
        'executions_today',
        'execution_date',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'actions' => 'array',
        'action_parameters' => 'array',
        'constraints' => 'array',
        'is_active' => 'boolean',
        'min_threshold' => 'decimal:3',
        'max_threshold' => 'decimal:3',
        'last_executed_at' => 'datetime',
        'next_execution_at' => 'datetime',
        'execution_date' => 'date',
    ];

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get executions
     */
    public function executions(): HasMany
    {
        return $this->hasMany(AutomationExecution::class, 'automation_rule_id');
    }

    /**
     * Check if should execute
     */
    public function shouldExecute(array $context = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check daily limit
        if ($this->max_executions_per_day) {
            if ($this->execution_date != now()->toDateString()) {
                $this->resetDailyCounter();
            }

            if ($this->executions_today >= $this->max_executions_per_day) {
                return false;
            }
        }

        // Check schedule
        if ($this->next_execution_at && $this->next_execution_at->isFuture()) {
            return false;
        }

        // Check trigger conditions
        return $this->evaluateTriggerConditions($context);
    }

    /**
     * Evaluate trigger conditions
     */
    protected function evaluateTriggerConditions(array $context): bool
    {
        if (!$this->trigger_conditions) {
            return true;
        }

        foreach ($this->trigger_conditions as $key => $value) {
            if (!isset($context[$key])) {
                return false;
            }

            if (is_array($value)) {
                // Complex condition
                if (!$this->evaluateComplexCondition($context[$key], $value)) {
                    return false;
                }
            } else {
                // Simple equality check
                if ($context[$key] != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Evaluate complex condition
     */
    protected function evaluateComplexCondition($contextValue, array $condition): bool
    {
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'];

        return match($operator) {
            '=' => $contextValue == $value,
            '!=' => $contextValue != $value,
            '>' => $contextValue > $value,
            '>=' => $contextValue >= $value,
            '<' => $contextValue < $value,
            '<=' => $contextValue <= $value,
            'in' => in_array($contextValue, $value),
            'not_in' => !in_array($contextValue, $value),
            'contains' => str_contains($contextValue, $value),
            default => false,
        };
    }

    /**
     * Record execution
     */
    public function recordExecution(): void
    {
        $this->increment('execution_count');

        if ($this->execution_date != now()->toDateString()) {
            $this->resetDailyCounter();
        }

        $this->increment('executions_today');
        $this->update([
            'last_executed_at' => now(),
            'next_execution_at' => $this->calculateNextExecution(),
            'execution_date' => now()->toDateString(),
        ]);
    }

    /**
     * Calculate next execution time
     */
    protected function calculateNextExecution(): ?Carbon
    {
        return match($this->trigger_frequency) {
            'hourly' => now()->addHour(),
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => null, // realtime
        };
    }

    /**
     * Reset daily counter
     */
    protected function resetDailyCounter(): void
    {
        $this->update([
            'executions_today' => 0,
            'execution_date' => now()->toDateString(),
        ]);
    }

    /**
     * Scope: Active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: For vendor
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope: Due for execution
     */
    public function scopeDueForExecution($query)
    {
        return $query->active()
                     ->where(function($q) {
                         $q->whereNull('next_execution_at')
                           ->orWhere('next_execution_at', '<=', now());
                     });
    }

    /**
     * Scope: By priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
