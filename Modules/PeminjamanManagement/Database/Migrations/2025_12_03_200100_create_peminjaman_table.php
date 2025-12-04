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
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('prasarana_id')->nullable()->constrained('prasarana')->onDelete('set null');
            $table->string('lokasi_custom', 150)->nullable()->comment('Lokasi custom jika tidak memilih prasarana');
            $table->integer('jumlah_peserta')->nullable()->comment('Jumlah peserta untuk peminjaman prasarana');
            $table->foreignId('ukm_id')->nullable()->constrained('ukm')->onDelete('set null');
            $table->string('event_name')->comment('Nama kegiatan/acara');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'picked_up',
                'returned',
                'cancelled'
            ])->default('pending');
            $table->string('konflik')->nullable()->comment('Kode grup konflik jadwal');
            $table->string('surat_path')->nullable()->comment('Path file surat pengajuan');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('pickup_validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('pickup_validated_at')->nullable();
            $table->foreignId('return_validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('return_validated_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('cancelled_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('foto_pickup_path')->nullable()->comment('Path foto saat pengambilan');
            $table->string('foto_return_path')->nullable()->comment('Path foto saat pengembalian');
            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            $table->index(['prasarana_id']);
            $table->index(['ukm_id']);
            $table->index(['status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['konflik']);
            $table->index(['approved_by']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
