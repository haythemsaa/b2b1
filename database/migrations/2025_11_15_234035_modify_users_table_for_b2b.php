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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'vendor'])->default('vendor')->after('email');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('role');
            $table->string('phone')->nullable()->after('status');
            $table->string('locale', 5)->default('fr')->after('phone');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'phone', 'locale']);
            $table->dropSoftDeletes();
        });
    }
};
