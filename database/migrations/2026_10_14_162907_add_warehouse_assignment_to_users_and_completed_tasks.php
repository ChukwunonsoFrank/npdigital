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
            $table->json('warehouse_groups')->nullable();
            $table->json('submitted_warehouse_products')->nullable();
        });

        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->string('warehouse_product_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->dropColumn('warehouse_product_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['warehouse_groups', 'submitted_warehouse_products']);
        });
    }
};
