<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prasarana', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('kategori_id')->constrained('kategori_prasarana');
            $table->text('description')->nullable();
            $table->string('lokasi')->nullable();
            $table->unsignedInteger('kapasitas')->nullable();
            $table->enum('status', ['tersedia', 'rusak', 'maintenance'])->default('tersedia');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prasarana');
    }
};
