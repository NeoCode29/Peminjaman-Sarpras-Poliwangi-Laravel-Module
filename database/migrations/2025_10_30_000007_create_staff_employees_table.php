<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nip')->nullable()->unique();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('unit_id');
            $table->index('position_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_employees');
    }
};
