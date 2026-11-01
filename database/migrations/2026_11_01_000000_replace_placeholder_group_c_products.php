<?php

use App\Models\WarehouseProduct;
use App\Support\ProductWarehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace placeholder group C products with real catalog products.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (array_column(ProductWarehouse::levels(), 'value') as $level) {
                if (! WarehouseProduct::query()->where('level', $level)->exists()) {
                    continue;
                }

                for ($day = 1; $day <= ProductWarehouse::DAY_COUNT; $day++) {
                    ProductWarehouse::replaceGroupC($level, $day);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
