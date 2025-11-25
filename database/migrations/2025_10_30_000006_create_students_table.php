<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nim')->unique();
            $table->year('angkatan')->nullable();
            $table->foreignId('jurusan_id')->nullable()->constrained('jurusan')->nullOnDelete();
            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $table->tinyInteger('semester')->nullable();
            $table->enum('status_mahasiswa', ['aktif', 'cuti', 'dropout', 'lulus'])->default('aktif');
            $table->timestamps();

            $table->unique('user_id');
            $table->index('angkatan');
            $table->index('jurusan_id');
            $table->index('prodi_id');
            $table->index('status_mahasiswa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
