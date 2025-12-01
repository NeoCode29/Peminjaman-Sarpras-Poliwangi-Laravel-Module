<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('saranas', 'qr_code')) {
            Schema::table('saranas', function (Blueprint $table) {
                $table->dropColumn('qr_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('saranas', 'qr_code')) {
            Schema::table('saranas', function (Blueprint $table) {
                $table->string('qr_code')->nullable()->comment('QR code untuk scanning');
            });
        }
    }
};
