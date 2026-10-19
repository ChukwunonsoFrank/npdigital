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
        Schema::create('warehouse_exclusive_products', function (Blueprint $table) {
            $table->id();
            $table->string('exclusive_id');
            $table->string('level');
            $table->string('name')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('name2')->nullable();
            $table->string('image_url2', 2048)->nullable();
            $table->decimal('price2', 10, 2)->nullable();
            $table->decimal('commission', 6, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->unique(['level', 'exclusive_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_exclusive_products');
    }
};
