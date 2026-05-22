<?php

use App\Support\InductionAcknowledgementMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('induction_policies', function (Blueprint $table) {
            $table->string('acknowledgement_mode', 32)
                ->default(InductionAcknowledgementMode::READ_ONLY)
                ->after('is_active');
        });

        Schema::table('induction_sections', function (Blueprint $table) {
            $table->string('acknowledgement_mode', 32)
                ->default(InductionAcknowledgementMode::READ_ONLY)
                ->after('requires_signature');
        });

        Schema::table('induction_sub_clauses', function (Blueprint $table) {
            $table->string('acknowledgement_mode', 32)
                ->default(InductionAcknowledgementMode::READ_ONLY)
                ->after('body');
        });

        DB::table('induction_sections')->update([
            'acknowledgement_mode' => DB::raw(
                "CASE WHEN requires_signature = 1 THEN '".InductionAcknowledgementMode::READ_AND_SIGN."' ELSE '".InductionAcknowledgementMode::READ_ONLY."' END"
            ),
        ]);
    }

    public function down(): void
    {
        Schema::table('induction_sub_clauses', function (Blueprint $table) {
            $table->dropColumn('acknowledgement_mode');
        });

        Schema::table('induction_sections', function (Blueprint $table) {
            $table->dropColumn('acknowledgement_mode');
        });

        Schema::table('induction_policies', function (Blueprint $table) {
            $table->dropColumn('acknowledgement_mode');
        });
    }
};
