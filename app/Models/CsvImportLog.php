<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsvImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'filename',
        'total_rows',
        'success_count',
        'error_count',
        'errors',
        'valid_items',
        'status',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'success_count' => 'integer',
        'error_count' => 'integer',
        'errors' => 'array',
        'valid_items' => 'array',
    ];

    /**
     * Get the vendor that owns the import log
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Check if import is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->success_count > 0;
    }

    /**
     * Check if import has errors
     */
    public function hasErrors(): bool
    {
        return $this->error_count > 0;
    }

    /**
     * Get success rate
     */
    public function getSuccessRate(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return ($this->success_count / $this->total_rows) * 100;
    }
}
