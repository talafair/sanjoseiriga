<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->boolean('research_consent_enabled')->default(false);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->json('research_profile')->nullable();
            $table->timestamp('research_consent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropColumn(['research_profile', 'research_consent_at']);
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('research_consent_enabled');
        });
    }
};
