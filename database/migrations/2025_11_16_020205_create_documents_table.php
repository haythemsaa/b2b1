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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');

            // Documentable polymorphic relationship
            $table->morphs('documentable'); // documentable_id, documentable_type (order, rfq, invoice, etc.)

            // Document details
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('document_type', [
                'invoice',
                'contract',
                'certificate',
                'tax_document',
                'shipping_document',
                'product_specification',
                'quality_certificate',
                'compliance_document',
                'insurance_document',
                'other'
            ]);

            // File information
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size')->comment('File size in bytes');
            $table->string('file_hash')->nullable()->comment('SHA256 hash for integrity');

            // Metadata
            $table->string('document_number')->nullable()->comment('Invoice #, Contract #, etc.');
            $table->date('document_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->json('metadata')->nullable()->comment('Additional structured data');

            // Access control
            $table->enum('visibility', ['private', 'vendor_only', 'shared'])->default('vendor_only');
            $table->boolean('requires_approval')->default(false);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Status
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_confidential')->default(false);
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'document_type', 'is_archived']);
            $table->index(['documentable_type', 'documentable_id']);
            $table->index(['uploaded_by', 'created_at']);
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
