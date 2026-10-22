<?php

use App\Support\ProductBrand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warehouse_products', function (Blueprint $table) {
            $table->string('brand', 32)->nullable()->after('image_url');
        });

        Schema::table('warehouse_exclusive_products', function (Blueprint $table) {
            $table->string('brand', 32)->nullable()->after('image_url');
            $table->string('brand2', 32)->nullable()->after('image_url2');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('brand', 32)->nullable()->after('task_image_path');
        });

        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->string('brand', 32)->nullable()->after('task_image_path');
        });

        $this->assignBrands('warehouse_products', ['brand']);
        $this->assignBrands('warehouse_exclusive_products', ['brand', 'brand2']);
        $this->assignBrands('tasks', ['brand']);
        $this->assignBrands('completed_tasks', ['brand']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->dropColumn('brand');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('brand');
        });

        Schema::table('warehouse_exclusive_products', function (Blueprint $table) {
            $table->dropColumn(['brand', 'brand2']);
        });

        Schema::table('warehouse_products', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }

    /**
     * Assign a random brand to every existing row.
     *
     * @param  list<string>  $columns
     */
    private function assignBrands(string $table, array $columns): void
    {
        DB::table($table)
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $columns): void {
                foreach ($rows as $row) {
                    $brands = [];

                    foreach ($columns as $column) {
                        $brands[$column] = ProductBrand::random();
                    }

                    DB::table($table)->where('id', $row->id)->update($brands);
                }
            });
    }
};
