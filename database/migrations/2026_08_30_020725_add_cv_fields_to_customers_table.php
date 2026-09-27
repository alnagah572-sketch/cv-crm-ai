<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('cv_path')->nullable();
            $table->string('cv_original_name')->nullable();
            $table->string('cv_analysis_status')->default('not_analyzed');
            $table->timestamp('cv_analyzed_at')->nullable();
            $table->json('cv_ai_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'cv_path',
                'cv_original_name',
                'cv_analysis_status',
                'cv_analyzed_at',
                'cv_ai_data',
            ]);
        });
    }
};
