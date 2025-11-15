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
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('company_name');
            $table->string('company_registration')->nullable();
            $table->string('tax_number')->nullable();
            $table->text('billing_address');
            $table->text('shipping_address')->nullable();
            $table->string('city');
            $table->string('postal_code')->nullable();
            $table->string('country')->default('TN');
            $table->foreignId('vendor_group_id')->nullable()->constrained();
            $table->decimal('credit_limit', 12, 3)->default(0);
            $table->enum('payment_term', ['immediate', 'net_30', 'net_60', 'net_90'])->default('immediate');
            $table->decimal('minimum_order_amount', 10, 3)->default(0);
            $table->boolean('priority_shipping')->default(false);
            $table->json('allowed_features')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
