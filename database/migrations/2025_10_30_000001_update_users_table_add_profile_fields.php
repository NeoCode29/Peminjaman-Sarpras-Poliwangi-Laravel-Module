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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique('users_username_unique')->after('name');
            $table->string('phone')->nullable()->after('password');
            $table->string('address')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('address');
            $table->string('user_type')->default('mahasiswa')->after('bio');
            $table->string('status')->default('active')->after('user_type');
            $table->foreignId('role_id')->nullable()->after('status')->constrained('roles')->nullOnDelete();
            $table->boolean('profile_completed')->default(false)->after('role_id');
            $table->timestamp('profile_completed_at')->nullable()->after('profile_completed');
            $table->timestamp('blocked_until')->nullable()->after('profile_completed_at');
            $table->string('blocked_reason')->nullable()->after('blocked_until');
            $table->string('sso_id')->nullable()->after('blocked_reason')->unique();
            $table->string('sso_provider')->default('poliwangi')->after('sso_id');
            $table->json('sso_data')->nullable()->after('sso_provider');
            $table->timestamp('last_sso_login')->nullable()->after('sso_data');
            $table->timestamp('last_login_at')->nullable()->after('last_sso_login');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('password_changed_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->unsignedBigInteger('login_count')->default(0)->after('locked_until');
            $table->timestamp('last_activity_at')->nullable()->after('login_count');

            $table->index(['user_type', 'status'], 'users_user_type_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropUnique('users_sso_id_unique');
            $table->dropForeign(['role_id']);
            $table->dropIndex('users_user_type_status_index');

            $table->dropColumn([
                'username',
                'phone',
                'address',
                'bio',
                'user_type',
                'status',
                'role_id',
                'profile_completed',
                'profile_completed_at',
                'blocked_until',
                'blocked_reason',
                'sso_id',
                'sso_provider',
                'sso_data',
                'last_sso_login',
                'last_login_at',
                'password_changed_at',
                'failed_login_attempts',
                'locked_until',
                'login_count',
                'last_activity_at',
            ]);
        });
    }
};
