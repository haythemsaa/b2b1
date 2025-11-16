<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_60', 'net_90'])
                ->default('immediate')
                ->after('vendor_group_id');

            $table->decimal('credit_limit', 15, 3)->default(0)->after('payment_terms');
            $table->decimal('credit_used', 15, 3)->default(0)->after('credit_limit');

            $table->boolean('credit_hold')->default(false)->after('status');
            $table->text('credit_hold_reason')->nullable()->after('credit_hold');

            $table->decimal('early_payment_discount', 5, 2)->default(0)
                ->after('credit_hold_reason')
                ->comment('Discount % if paid early (e.g., 2.00 for 2%)');
            $table->integer('early_payment_days')->default(10)
                ->after('early_payment_discount')
                ->comment('Days to get discount (e.g., 10 for 2/10 NET 30)');

            $table->index(['payment_terms', 'credit_hold']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropIndex(['payment_terms', 'credit_hold']);
            $table->dropColumn([
                'payment_terms',
                'credit_limit',
                'credit_used',
                'credit_hold',
                'credit_hold_reason',
                'early_payment_discount',
                'early_payment_days',
            ]);
        });
    }
};
