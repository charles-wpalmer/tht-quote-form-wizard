<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wizards', function (Blueprint $table) {
            $table->foreignId('current_published_version_id')
                ->nullable()
                ->after('is_active')
                ->constrained('journey_publications')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wizards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_published_version_id');
        });
    }
};
