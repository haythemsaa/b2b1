<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_user_id',
        'budget_period',
        'budget_limit',
        'budget_used',
        'period_start',
        'period_end',
        'last_reset_at',
        'alert_enabled',
        'alert_threshold',
        'alert_sent',
        'auto_reset',
    ];

    protected $casts = [
        'budget_limit' => 'decimal:3',
        'budget_used' => 'decimal:3',
        'period_start' => 'date',
        'period_end' => 'date',
        'last_reset_at' => 'date',
        'alert_enabled' => 'boolean',
        'alert_threshold' => 'decimal:2',
        'alert_sent' => 'boolean',
        'auto_reset' => 'boolean',
    ];

    // Relationships
    public function accountUser(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class);
    }

    // Helper methods
    public function getRemainingBudget(): float
    {
        return max(0, $this->budget_limit - $this->budget_used);
    }

    public function getUsagePercentage(): float
    {
        if ($this->budget_limit == 0) {
            return 0;
        }

        return ($this->budget_used / $this->budget_limit) * 100;
    }

    public function canSpend(float $amount): bool
    {
        if ($this->budget_period === 'unlimited') {
            return true;
        }

        // Check if period has expired and needs reset
        if ($this->needsReset()) {
            $this->resetBudget();
        }

        return $this->getRemainingBudget() >= $amount;
    }

    public function recordSpending(float $amount): void
    {
        $this->increment('budget_used', $amount);

        // Check if alert threshold reached
        if ($this->alert_enabled && !$this->alert_sent) {
            $usagePercentage = $this->getUsagePercentage();
            if ($usagePercentage >= $this->alert_threshold) {
                $this->update(['alert_sent' => true]);
                // Here you would trigger the alert notification
            }
        }
    }

    public function needsReset(): bool
    {
        if ($this->budget_period === 'unlimited' || !$this->auto_reset) {
            return false;
        }

        if (!$this->period_end) {
            return true;
        }

        return now()->isAfter($this->period_end);
    }

    public function resetBudget(): void
    {
        $this->update([
            'budget_used' => 0,
            'alert_sent' => false,
            'last_reset_at' => now(),
            'period_start' => now(),
            'period_end' => $this->calculatePeriodEnd(),
        ]);
    }

    protected function calculatePeriodEnd(): ?\Carbon\Carbon
    {
        return match($this->budget_period) {
            'daily' => now()->endOfDay(),
            'weekly' => now()->endOfWeek(),
            'monthly' => now()->endOfMonth(),
            'yearly' => now()->endOfYear(),
            'unlimited' => null,
            default => now()->endOfMonth(),
        };
    }

    public function initializePeriod(): void
    {
        $this->update([
            'period_start' => now(),
            'period_end' => $this->calculatePeriodEnd(),
            'last_reset_at' => now(),
        ]);
    }

    public function isOverBudget(): bool
    {
        if ($this->budget_period === 'unlimited') {
            return false;
        }

        return $this->budget_used > $this->budget_limit;
    }

    public function shouldAlert(): bool
    {
        if (!$this->alert_enabled || $this->alert_sent) {
            return false;
        }

        return $this->getUsagePercentage() >= $this->alert_threshold;
    }

    // Events
    protected static function booted(): void
    {
        static::creating(function (AccountBudget $budget) {
            if (!$budget->period_start) {
                $budget->period_start = now();
            }
            if (!$budget->period_end && $budget->budget_period !== 'unlimited') {
                $budget->period_end = $budget->calculatePeriodEnd();
            }
        });
    }
}
