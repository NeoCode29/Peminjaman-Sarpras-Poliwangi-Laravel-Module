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
        Schema::create('peminjaman_approval_workflow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->enum('approval_type', ['global', 'sarana', 'prasarana'])
                ->comment('Jenis approval: global, sarana spesifik, atau prasarana spesifik');
            $table->foreignId('sarana_id')->nullable()->constrained('saranas')->onDelete('cascade')
                ->comment('ID sarana jika approval_type = sarana');
            $table->foreignId('prasarana_id')->nullable()->constrained('prasarana')->onDelete('cascade')
                ->comment('ID prasarana jika approval_type = prasarana');
            $table->integer('approval_level')->default(1)->comment('Level hierarki approval');
            $table->enum('status', ['pending', 'approved', 'rejected', 'overridden'])->default('pending');
            $table->text('notes')->nullable()->comment('Catatan approval/rejection');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->onDelete('set null')
                ->comment('User yang melakukan override');
            $table->timestamp('overridden_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['peminjaman_id']);
            $table->index(['approver_id']);
            $table->index(['approval_type']);
            $table->index(['sarana_id']);
            $table->index(['prasarana_id']);
            $table->index(['status']);
            $table->index(['approval_level']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman_approval_workflow');
    }
};
