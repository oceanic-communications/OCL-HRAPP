<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('induction_application_audit_events')) {
            return;
        }

        Schema::create('induction_application_audit_events', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->index('iaae_occurred_idx');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_code', 96)->index('iaae_event_code_idx');
            $table->unsignedBigInteger('induction_policy_id')->nullable()->index('iaae_policy_idx');
            $table->unsignedBigInteger('induction_policy_version_id')->nullable()->index('iaae_policy_ver_idx');
            $table->unsignedBigInteger('induction_section_id')->nullable();
            $table->unsignedBigInteger('induction_enrollment_id')->nullable()->index('iaae_enrollment_idx');
            $table->unsignedBigInteger('induction_section_completion_id')->nullable();
            $table->unsignedBigInteger('portal_user_notification_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index('iaae_actor_idx');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->uuid('correlation_id')->nullable()->index('iaae_correlation_idx');
            $table->json('payload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('induction_application_audit_events');
    }
};
