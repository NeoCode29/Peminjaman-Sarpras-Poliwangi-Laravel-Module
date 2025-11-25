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
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Polymorphic relation
            $table->string('uploadable_type')->nullable();
            $table->unsignedBigInteger('uploadable_id')->nullable();
            
            // File information
            $table->string('file_type', 50); // image, document, identity, avatar
            $table->string('category', 50); // sarpras, documents, avatars, etc.
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('file_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size'); // bytes
            $table->string('disk', 50)->default('local');
            
            // Access tracking
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('downloaded_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index(['uploadable_type', 'uploadable_id']);
            $table->index('user_id');
            $table->index('file_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};
