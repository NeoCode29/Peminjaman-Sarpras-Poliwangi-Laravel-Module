<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prasarana_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prasarana_id')->constrained('prasarana')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('approval_level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['prasarana_id', 'approver_id', 'approval_level'], 'prasarana_approvers_unique_assignment');
            $table->index('approval_level');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prasarana_approvers');
    }
};
