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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('negative_balance')->default(0)->after('balance');
        });

        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->boolean('is_merge')->default(false)->after('status');
            $table->json('merge_items')->nullable()->after('is_merge');
            $table->unsignedTinyInteger('merge_position')->nullable()->after('merge_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->dropColumn(['is_merge', 'merge_items', 'merge_position']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('negative_balance');
        });
    }
};
