<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('induction_change_logs')) {
            Schema::create('induction_change_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('action', 128);
                $table->string('subject_type', 128)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->foreignId('induction_policy_id')->nullable()->constrained('induction_policies')->nullOnDelete();
                $table->foreignId('induction_policy_version_id')->nullable()->constrained('induction_policy_versions')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->boolean('staff_repeat_requested')->default(false);
                $table->boolean('staff_repeat_applied')->default(false);
                $table->timestamps();
                $table->index(['induction_policy_version_id', 'created_at'], 'icl_version_created_idx');
                $table->index(['actor_user_id', 'created_at'], 'icl_actor_created_idx');
            });
        }

        if (! Schema::hasTable('portal_user_notifications')) {
            Schema::create('portal_user_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('type', 64);
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('action_url', 512)->nullable();
                $table->foreignId('induction_policy_version_id')->nullable()->constrained('induction_policy_versions')->nullOnDelete();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'read_at', 'created_at'], 'pun_user_read_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_user_notifications');
        Schema::dropIfExists('induction_change_logs');
    }
};
