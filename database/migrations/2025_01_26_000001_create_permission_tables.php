<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    private function getModelHasPermissionPrimaryKeyColumns(bool $teams): array
    {
        $columns = [
            'permission_id',
            config('permission.column_names.model_morph_key'),
            'model_type',
        ];

        if ($teams) {
            $columns[] = config('permission.column_names.team_foreign_key');
        }

        return $columns;
    }

    private function getModelHasRolePrimaryKeyColumns(bool $teams): array
    {
        $columns = [
            'role_id',
            config('permission.column_names.model_morph_key'),
            'model_type',
        ];

        if ($teams) {
            $columns[] = config('permission.column_names.team_foreign_key');
        }

        return $columns;
    }

    public function up(): void
    {
        $teams = config('permission.teams');

        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');

        Schema::create('permissions', function (Blueprint $table) use ($teams) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) use ($teams) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            if ($teams) {
                $table->unsignedBigInteger(config('permission.column_names.team_foreign_key'))->nullable();
                $table->index(config('permission.column_names.team_foreign_key'), 'roles_team_foreign_key_index');
            }
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table) use ($teams) {
            $table->unsignedBigInteger('permission_id');

            $table->string('model_type');
            $table->unsignedBigInteger(config('permission.column_names.model_morph_key'));
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

            if ($teams) {
                $table->unsignedBigInteger(config('permission.column_names.team_foreign_key'));
                $table->index(config('permission.column_names.team_foreign_key'), 'model_has_permissions_team_foreign_key_index');
            }

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->cascadeOnDelete();

            $table->primary($this->getModelHasPermissionPrimaryKeyColumns($teams), 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('model_has_roles', function (Blueprint $table) use ($teams) {
            $table->unsignedBigInteger('role_id');

            $table->string('model_type');
            $table->unsignedBigInteger(config('permission.column_names.model_morph_key'));
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

            if ($teams) {
                $table->unsignedBigInteger(config('permission.column_names.team_foreign_key'));
                $table->index(config('permission.column_names.team_foreign_key'), 'model_has_roles_team_foreign_key_index');
            }

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();

            $table->primary($this->getModelHasRolePrimaryKeyColumns($teams), 'model_has_roles_role_model_type_primary');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->cascadeOnDelete();

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();

            $table->primary(['permission_id', 'role_id']);
        });

        app('cache')?->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        app('cache')?->forget(config('permission.cache.key'));

        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
