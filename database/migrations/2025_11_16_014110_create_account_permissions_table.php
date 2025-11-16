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
        Schema::create('account_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_user_id')->constrained()->onDelete('cascade');

            // Permission categories
            $table->enum('permission_type', [
                'orders',
                'products',
                'invoices',
                'rfqs',
                'analytics',
                'account_management',
                'chat'
            ]);

            // Permission actions
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_approve')->default(false);

            // Additional constraints
            $table->json('restrictions')->nullable()->comment('Additional restrictions like budget limits');

            $table->timestamps();

            $table->unique(['account_user_id', 'permission_type']);
            $table->index('permission_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_permissions');
    }
};
