<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('induction_policies', function (Blueprint $table) {
            $table->json('numbering_scheme')->nullable()->after('is_active');
        });

        Schema::table('induction_sections', function (Blueprint $table) {
            $table->string('number_prefix', 32)->nullable()->after('title');
            $table->string('numbering_style', 32)->nullable()->after('number_prefix');
            $table->string('number_separator', 16)->nullable()->after('numbering_style');
        });

        Schema::table('induction_sub_clauses', function (Blueprint $table) {
            $table->string('number_prefix', 32)->nullable()->after('title');
            $table->string('numbering_style', 32)->nullable()->after('number_prefix');
            $table->string('number_separator', 16)->nullable()->after('numbering_style');
        });
    }

    public function down(): void
    {
        Schema::table('induction_sub_clauses', function (Blueprint $table) {
            $table->dropColumn(['number_prefix', 'numbering_style', 'number_separator']);
        });

        Schema::table('induction_sections', function (Blueprint $table) {
            $table->dropColumn(['number_prefix', 'numbering_style', 'number_separator']);
        });

        Schema::table('induction_policies', function (Blueprint $table) {
            $table->dropColumn('numbering_scheme');
        });
    }
};
