<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_rule_id',
        'vendor_id',
        'trigger_data',
        'execution_result',
        'status',
        'error_message',
        'started_at',
        'completed_at',
        'execution_time_ms',
        'actions_performed',
        'actions_count',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'execution_result' => 'array',
        'actions_performed' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the automation rule
     */
    public function automationRule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Check if successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Scope: Successful executions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope: Failed executions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: For rule
     */
    public function scopeForRule($query, int $ruleId)
    {
        return $query->where('automation_rule_id', $ruleId);
    }
}
