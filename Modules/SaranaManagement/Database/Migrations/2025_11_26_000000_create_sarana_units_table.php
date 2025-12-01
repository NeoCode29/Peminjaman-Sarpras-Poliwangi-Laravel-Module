<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaranaUnitsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sarana_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sarana_id')->constrained('saranas')->onDelete('cascade');
            $table->string('unit_code', 80);
            $table->enum('unit_status', ['tersedia', 'rusak', 'maintenance', 'hilang'])->default('tersedia');
            $table->timestamps();

            $table->unique(['sarana_id', 'unit_code']);
            $table->index('sarana_id');
            $table->index('unit_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarana_units');
    }
}
