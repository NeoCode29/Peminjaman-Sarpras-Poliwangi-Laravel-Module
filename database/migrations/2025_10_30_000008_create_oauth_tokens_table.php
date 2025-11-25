<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->integer('expires_in');
            $table->string('token_type', 50)->default('Bearer');
            $table->string('scope', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('expires_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
    }
};
