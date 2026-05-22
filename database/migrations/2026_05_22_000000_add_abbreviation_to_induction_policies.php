<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('induction_policies', function (Blueprint $table) {
            $table->string('abbreviation', 16)->nullable()->after('name');
        });

        $used = [];
        foreach (DB::table('induction_policies')->orderBy('id')->get() as $row) {
            $abbreviation = $this->deriveAbbreviation($row->name, $row->slug, $used);
            $used[] = $abbreviation;
            DB::table('induction_policies')->where('id', $row->id)->update(['abbreviation' => $abbreviation]);
        }

        Schema::table('induction_policies', function (Blueprint $table) {
            $table->string('abbreviation', 16)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('induction_policies', function (Blueprint $table) {
            $table->dropColumn('abbreviation');
        });
    }

    /**
     * @param  list<string>  $used
     */
    private function deriveAbbreviation(string $name, string $slug, array $used): string
    {
        $parts = array_filter(explode('-', $slug));
        $candidate = '';
        if (count($parts) >= 2) {
            $candidate = strtoupper(implode('', array_map(
                fn (string $part): string => Str::substr($part, 0, 1),
                array_slice($parts, 0, 4)
            )));
        }

        if ($candidate === '') {
            $candidate = strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'POL', 0, 4));
        }

        $candidate = Str::limit($candidate, 16, '');
        $base = $candidate !== '' ? $candidate : 'POL';
        $abbreviation = $base;
        $i = 1;
        while (in_array($abbreviation, $used, true)) {
            $suffix = (string) $i;
            $abbreviation = Str::limit($base, 16 - strlen($suffix), '').$suffix;
            $i++;
        }

        return $abbreviation;
    }
};
