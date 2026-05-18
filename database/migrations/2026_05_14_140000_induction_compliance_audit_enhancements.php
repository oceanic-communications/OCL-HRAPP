<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('induction_change_logs')) {
            Schema::table('induction_change_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('induction_change_logs', 'ip_address')) {
                    $table->string('ip_address', 45)->nullable()->after('staff_repeat_applied');
                }
                if (! Schema::hasColumn('induction_change_logs', 'user_agent')) {
                    $table->text('user_agent')->nullable()->after('ip_address');
                }
                if (! Schema::hasColumn('induction_change_logs', 'correlation_id')) {
                    $table->uuid('correlation_id')->nullable()->index('icl_correlation_idx')->after('user_agent');
                }
            });
        }

        if (! Schema::hasTable('induction_application_audit_events')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('induction_application_audit_events');

        if (Schema::hasTable('induction_change_logs')) {
            Schema::table('induction_change_logs', function (Blueprint $table) {
                if (Schema::hasColumn('induction_change_logs', 'correlation_id')) {
                    $table->dropIndex(['correlation_id']);
                }
            });

            Schema::table('induction_change_logs', function (Blueprint $table) {
                $cols = array_values(array_filter(
                    ['ip_address', 'user_agent', 'correlation_id'],
                    fn (string $c) => Schema::hasColumn('induction_change_logs', $c),
                ));
                if ($cols !== []) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
