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
        Schema::create('peminjaman_item_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->onDelete('cascade');
            $table->foreignId('peminjaman_item_id')->constrained('peminjaman_items')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('sarana_units')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->enum('status', ['active', 'released'])->default('active');
            $table->foreignId('released_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['peminjaman_id']);
            $table->index(['peminjaman_item_id']);
            $table->index(['unit_id']);
            $table->index(['status']);
            $table->unique(['peminjaman_id', 'unit_id'], 'peminjaman_unit_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman_item_units');
    }
};
