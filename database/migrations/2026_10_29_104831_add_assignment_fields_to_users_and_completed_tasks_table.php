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
            $table->unsignedSmallInteger('products_per_set')->nullable()->after('task_batch');
        });

        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('commission_amount')->nullable()->after('commission_percentage');
            $table->unsignedBigInteger('merge_negative_balance')->nullable()->after('merge_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('products_per_set');
        });

        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->dropColumn(['commission_amount', 'merge_negative_balance']);
        });
    }
};
