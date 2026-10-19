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
        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_id')->unique();
            $table->string('level');
            $table->unsignedTinyInteger('day');
            $table->string('group_key', 1);
            $table->string('name')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('commission', 6, 2)->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->index(['level', 'day', 'group_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_products');
    }
};
