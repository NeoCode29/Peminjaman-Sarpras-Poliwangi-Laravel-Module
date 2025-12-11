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
        Schema::create('peminjaman_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->onDelete('cascade');
            $table->foreignId('sarana_id')->constrained('saranas')->onDelete('cascade');
            $table->unsignedInteger('qty_requested')->default(0)->comment('Jumlah yang diminta');
            $table->unsignedInteger('qty_approved')->nullable()->comment('Jumlah yang disetujui');
            $table->text('notes')->nullable()->comment('Catatan tambahan untuk item');
            $table->timestamps();

            // Indexes
            $table->index(['peminjaman_id']);
            $table->index(['sarana_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman_items');
    }
};
