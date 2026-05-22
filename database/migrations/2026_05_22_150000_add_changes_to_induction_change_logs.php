<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('induction_change_logs')) {
            return;
        }

        Schema::table('induction_change_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('induction_change_logs', 'changes')) {
                $table->json('changes')->nullable()->after('metadata');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('induction_change_logs')) {
            return;
        }

        Schema::table('induction_change_logs', function (Blueprint $table) {
            if (Schema::hasColumn('induction_change_logs', 'changes')) {
                $table->dropColumn('changes');
            }
        });
    }
};
