<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('induction_policies', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        $policies = DB::table('induction_policies')->orderBy('id')->pluck('id');
        foreach ($policies as $index => $policyId) {
            DB::table('induction_policies')
                ->where('id', $policyId)
                ->update(['sort_order' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('induction_policies', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
