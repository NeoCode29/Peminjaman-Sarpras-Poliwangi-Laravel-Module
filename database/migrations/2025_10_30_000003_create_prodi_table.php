<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prodi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_prodi')->unique();
            $table->foreignId('jurusan_id')->constrained('jurusan')->cascadeOnDelete();
            $table->enum('jenjang', ['D3', 'D4', 'S1', 'S2', 'S3']);
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->index('nama_prodi');
            $table->index('jurusan_id');
            $table->index('jenjang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prodi');
    }
};
