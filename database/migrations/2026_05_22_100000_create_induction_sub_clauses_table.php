<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('induction_sub_clauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('induction_section_id')->constrained('induction_sections')->cascadeOnDelete();
            $table->unsignedInteger('sort_order');
            $table->string('title');
            $table->longText('body');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['induction_section_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('induction_sub_clauses');
    }
};
