<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSaranaApproversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sarana_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sarana_id')->constrained('saranas')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('approval_level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['sarana_id', 'approver_id', 'approval_level'], 'sarana_approvers_unique_assignment');
            $table->index('approval_level');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sarana_approvers');
    }
}
