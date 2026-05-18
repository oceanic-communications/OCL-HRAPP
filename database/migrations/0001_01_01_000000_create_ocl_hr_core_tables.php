<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('title', 20)->nullable();
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_staff_super_user')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->index('archived_at');
            $table->index('is_staff_super_user');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 128)->unique();
            $table->string('module_code', 64);
            $table->string('resource_code', 64);
            $table->string('action', 16);
            $table->timestamps();
            $table->unique(['module_code', 'resource_code', 'action'], 'permissions_module_resource_action_unique');
        });

        Schema::create('role_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 128);
            $table->string('audience', 16);
            $table->timestamps();
        });

        Schema::create('permission_role_template', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('role_template_id')->constrained('role_templates')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_template_id'], 'prt_permission_template_primary');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_template_id')->constrained('role_templates')->restrictOnDelete();
            $table->string('name', 128);
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id'], 'role_user_primary');
            $table->unique('user_id', 'role_user_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permission_role_template');
        Schema::dropIfExists('role_templates');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
