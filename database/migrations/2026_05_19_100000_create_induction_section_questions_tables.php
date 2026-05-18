<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('induction_section_questions')) {
            Schema::create('induction_section_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('induction_section_id')->constrained('induction_sections')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->text('prompt');
                $table->timestamps();
                $table->index(['induction_section_id', 'sort_order'], 'isq_section_sort_idx');
            });
        } elseif (! $this->indexExists('induction_section_questions', 'isq_section_sort_idx')) {
            Schema::table('induction_section_questions', function (Blueprint $table) {
                $table->index(['induction_section_id', 'sort_order'], 'isq_section_sort_idx');
            });
        }

        if (! Schema::hasTable('induction_section_question_responses')) {
            Schema::create('induction_section_question_responses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('induction_section_completion_id');
                $table->unsignedBigInteger('induction_section_question_id');
                $table->text('response');
                $table->timestamps();
                $table->foreign('induction_section_completion_id', 'isqr_completion_fk')
                    ->references('id')->on('induction_section_completions')->cascadeOnDelete();
                $table->foreign('induction_section_question_id', 'isqr_question_fk')
                    ->references('id')->on('induction_section_questions')->cascadeOnDelete();
                $table->unique(
                    ['induction_section_completion_id', 'induction_section_question_id'],
                    'isqr_completion_question_unique',
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('induction_section_question_responses');
        Schema::dropIfExists('induction_section_questions');
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index],
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
