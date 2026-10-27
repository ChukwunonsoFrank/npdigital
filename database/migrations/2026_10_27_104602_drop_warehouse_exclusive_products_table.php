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
        Schema::dropIfExists('warehouse_exclusive_products');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('warehouse_exclusive_products', function (Blueprint $table) {
            $table->id();
            $table->string('exclusive_id')->nullable();
            $table->string('level')->nullable();
            $table->string('name')->nullable();
            $table->string('image_url')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('name2')->nullable();
            $table->string('image_url2')->nullable();
            $table->string('brand2')->nullable();
            $table->decimal('price2', 15, 2)->nullable();
            $table->decimal('commission', 8, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }
};
