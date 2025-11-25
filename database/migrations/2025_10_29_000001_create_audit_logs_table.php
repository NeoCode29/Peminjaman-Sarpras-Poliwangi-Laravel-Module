<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('performed_by_type')->nullable();
            $table->string('context')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('performed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
