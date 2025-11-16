<?php

if (!function_exists('format_tnd')) {
    /**
     * Format amount in Tunisian Dinar with 3 decimals
     *
     * @param float $amount
     * @param bool $withCurrency
     * @return string
     */
    function format_tnd(float $amount, bool $withCurrency = true): string
    {
        $formatted = number_format($amount, 3, '.', ' ');
        return $withCurrency ? $formatted . ' TND' : $formatted;
    }
}

if (!function_exists('parse_tnd')) {
    /**
     * Parse TND string to float
     *
     * @param string $amount
     * @return float
     */
    function parse_tnd(string $amount): float
    {
        $cleaned = str_replace([' ', 'TND'], '', $amount);
        return (float) $cleaned;
    }
}

if (!function_exists('is_rtl_locale')) {
    /**
     * Check if current locale is RTL (Right-to-Left)
     *
     * @param string|null $locale
     * @return bool
     */
    function is_rtl_locale(?string $locale = null): bool
    {
        $locale = $locale ?? app()->getLocale();
        return in_array($locale, ['ar']);
    }
}

if (!function_exists('get_localized_name')) {
    /**
     * Get localized name from object with name_fr and name_ar
     *
     * @param object|array $item
     * @param string|null $locale
     * @return string
     */
    function get_localized_name($item, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if (is_array($item)) {
            return $locale === 'ar' && !empty($item['name_ar'])
                ? $item['name_ar']
                : $item['name_fr'];
        }

        return $locale === 'ar' && !empty($item->name_ar)
            ? $item->name_ar
            : $item->name_fr;
    }
}

if (!function_exists('generate_order_number')) {
    /**
     * Generate unique order number
     *
     * @return string
     */
    function generate_order_number(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}

if (!function_exists('generate_rma_number')) {
    /**
     * Generate unique RMA number
     *
     * @return string
     */
    function generate_rma_number(): string
    {
        return 'RMA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}

if (!function_exists('calculate_percentage')) {
    /**
     * Calculate percentage of a value
     *
     * @param float $value
     * @param float $percentage
     * @return float
     */
    function calculate_percentage(float $value, float $percentage): float
    {
        return round($value * ($percentage / 100), 3);
    }
}

if (!function_exists('apply_discount')) {
    /**
     * Apply discount to a value
     *
     * @param float $value
     * @param float $discountPercentage
     * @return float
     */
    function apply_discount(float $value, float $discountPercentage): float
    {
        $discount = calculate_percentage($value, $discountPercentage);
        return round($value - $discount, 3);
    }
}

if (!function_exists('is_admin')) {
    /**
     * Check if current user is admin
     *
     * @return bool
     */
    function is_admin(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }
}

if (!function_exists('is_vendor')) {
    /**
     * Check if current user is vendor
     *
     * @return bool
     */
    function is_vendor(): bool
    {
        return auth()->check() && auth()->user()->isVendor();
    }
}

if (!function_exists('current_vendor_group')) {
    /**
     * Get current vendor's group
     *
     * @return \App\Models\VendorGroup|null
     */
    function current_vendor_group()
    {
        if (!is_vendor()) {
            return null;
        }

        return auth()->user()->vendorProfile?->vendorGroup;
    }
}

if (!function_exists('format_phone_tn')) {
    /**
     * Format Tunisian phone number
     *
     * @param string $phone
     * @return string
     */
    function format_phone_tn(string $phone): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/\D/', '', $phone);

        // Add +216 if not present
        if (!str_starts_with($cleaned, '216')) {
            $cleaned = '216' . $cleaned;
        }

        return '+' . $cleaned;
    }
}

if (!function_exists('log_activity')) {
    /**
     * Log user activity
     *
     * @param string $action
     * @param string|null $description
     * @param array $data
     * @return void
     */
    function log_activity(string $action, ?string $description = null, array $data = []): void
    {
        \Illuminate\Support\Facades\Log::info($action, [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'description' => $description,
            'data' => $data,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
