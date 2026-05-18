<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('induction_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('induction_policy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('induction_policy_id')->constrained('induction_policies')->cascadeOnDelete();
            $table->string('version_label', 64);
            $table->date('effective_date')->nullable();
            $table->string('policy_pdf_disk', 32)->nullable();
            $table->string('policy_pdf_path', 512)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['induction_policy_id', 'published_at']);
        });

        Schema::create('induction_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('induction_policy_version_id')->constrained('induction_policy_versions')->cascadeOnDelete();
            $table->unsignedInteger('sort_order');
            $table->string('title');
            $table->longText('body');
            $table->boolean('requires_signature')->default(false);
            $table->text('acknowledgement_hint')->nullable();
            $table->timestamps();
            $table->index(['induction_policy_version_id', 'sort_order']);
        });

        Schema::create('induction_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('induction_policy_version_id')->constrained('induction_policy_versions')->cascadeOnDelete();
            $table->string('status', 32)->default('in_progress');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->string('completion_pdf_disk', 32)->nullable();
            $table->string('completion_pdf_path', 512)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'induction_policy_version_id'], 'induction_enrollment_user_version_unique');
        });

        Schema::create('induction_section_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('induction_enrollment_id')->constrained('induction_enrollments')->cascadeOnDelete();
            $table->foreignId('induction_section_id')->constrained('induction_sections')->cascadeOnDelete();
            $table->timestamp('completed_at')->useCurrent();
            $table->string('employee_name_snapshot', 512);
            $table->string('policy_version_label_snapshot', 128);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('signature_disk', 32)->nullable();
            $table->string('signature_path', 512)->nullable();
            $table->timestamps();
            $table->unique(['induction_enrollment_id', 'induction_section_id'], 'induction_completion_enrollment_section_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('induction_section_completions');
        Schema::dropIfExists('induction_enrollments');
        Schema::dropIfExists('induction_sections');
        Schema::dropIfExists('induction_policy_versions');
        Schema::dropIfExists('induction_policies');
    }
};
