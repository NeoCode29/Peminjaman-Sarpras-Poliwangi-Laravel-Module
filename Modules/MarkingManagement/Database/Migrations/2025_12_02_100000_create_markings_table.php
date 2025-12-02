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
        Schema::create('markings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ukm_id')->nullable()->constrained('ukm')->nullOnDelete();
            $table->foreignId('prasarana_id')->nullable()->constrained('prasarana')->nullOnDelete();
            $table->string('lokasi_custom', 255)->nullable()->comment('Lokasi lainnya jika tidak menggunakan prasarana Poliwangi');
            $table->datetime('start_datetime');
            $table->datetime('end_datetime');
            $table->integer('jumlah_peserta')->nullable();
            $table->timestamp('expires_at')->comment('Waktu kadaluarsa marking');
            $table->timestamp('planned_submit_by')->nullable()->comment('Rencana submit pengajuan sebelum marking expired');
            $table->enum('status', ['active', 'expired', 'converted', 'cancelled'])->default('active');
            $table->string('event_name', 255);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id']);
            $table->index(['ukm_id']);
            $table->index(['prasarana_id']);
            $table->index(['start_datetime', 'end_datetime']);
            $table->index(['expires_at']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('markings');
    }
};
