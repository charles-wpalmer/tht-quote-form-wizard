<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('key')->nullable()->after('id');
        });

        DB::table('questions')->orderBy('id')->pluck('id')->each(function (int $id): void {
            DB::table('questions')->where('id', $id)->update(['key' => (string) Str::uuid()]);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('key')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn('key');
        });
    }
};
