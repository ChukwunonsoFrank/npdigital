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
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('task_image_path', 2048)->change();
        });

        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->string('task_image_path', 2048)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('task_image_path')->change();
        });

        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->string('task_image_path')->change();
        });
    }
};
