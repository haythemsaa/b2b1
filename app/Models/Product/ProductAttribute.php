<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductAttribute extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'options',
        'is_filterable',
        'is_required',
        'is_variant',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_filterable' => 'boolean',
        'is_required' => 'boolean',
        'is_variant' => 'boolean',
        'order' => 'integer',
    ];

    const TYPE_TEXT = 'text';
    const TYPE_NUMBER = 'number';
    const TYPE_SELECT = 'select';
    const TYPE_MULTISELECT = 'multiselect';
    const TYPE_COLOR = 'color';
    const TYPE_BOOLEAN = 'boolean';

    public static function types(): array
    {
        return [
            self::TYPE_TEXT => 'Text',
            self::TYPE_NUMBER => 'Number',
            self::TYPE_SELECT => 'Select',
            self::TYPE_MULTISELECT => 'Multi-select',
            self::TYPE_COLOR => 'Color',
            self::TYPE_BOOLEAN => 'Yes/No',
        ];
    }

    // Relationships
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductCategory::class,
            'category_attributes',
            'attribute_id',
            'category_id'
        )->withPivot('is_required', 'order');
    }

    // Scopes
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    public function scopeVariant($query)
    {
        return $query->where('is_variant', true);
    }

    // Methods
    public function getOptionsArray(): array
    {
        if (in_array($this->type, [self::TYPE_SELECT, self::TYPE_MULTISELECT])) {
            return is_array($this->options) ? $this->options : [];
        }
        return [];
    }

    public function validateValue($value): bool
    {
        switch ($this->type) {
            case self::TYPE_NUMBER:
                return is_numeric($value);
            case self::TYPE_COLOR:
                return preg_match('/^#[0-9A-F]{6}$/i', $value);
            case self::TYPE_BOOLEAN:
                return in_array($value, [0, 1, '0', '1', true, false], true);
            case self::TYPE_SELECT:
                return in_array($value, $this->getOptionsArray());
            case self::TYPE_MULTISELECT:
                $values = is_array($value) ? $value : [$value];
                return count(array_diff($values, $this->getOptionsArray())) === 0;
            default:
                return is_string($value);
        }
    }
}
