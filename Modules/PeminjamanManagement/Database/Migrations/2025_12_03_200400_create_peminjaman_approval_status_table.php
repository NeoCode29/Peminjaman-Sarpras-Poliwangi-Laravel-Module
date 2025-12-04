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
        Schema::create('peminjaman_approval_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->onDelete('cascade');
            $table->enum('overall_status', [
                'pending',
                'approved',
                'partially_approved',
                'rejected'
            ])->default('pending');
            $table->enum('global_approval_status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');
            $table->foreignId('global_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('global_approved_at')->nullable();
            $table->foreignId('global_rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('global_rejected_at')->nullable();
            $table->text('global_rejection_reason')->nullable();
            $table->json('specific_approval_summary')->nullable()->comment('Summary approval per sarpras');
            $table->timestamps();

            // Indexes
            $table->unique(['peminjaman_id']);
            $table->index(['overall_status']);
            $table->index(['global_approval_status']);
            $table->index(['global_approved_by']);
            $table->index(['global_rejected_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman_approval_status');
    }
};
