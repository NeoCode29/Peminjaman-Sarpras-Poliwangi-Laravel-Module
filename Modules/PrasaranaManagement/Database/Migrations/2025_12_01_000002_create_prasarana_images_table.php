<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prasarana_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prasarana_id')->constrained('prasarana')->onDelete('cascade');
            $table->string('image_url');
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prasarana_images');
    }
};
