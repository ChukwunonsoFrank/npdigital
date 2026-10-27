<?php

use App\Models\WarehouseProduct;
use App\Support\ProductWarehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Every membership level must offer uniform sets of 30 products per day group.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $levels = WarehouseProduct::query()->distinct()->pluck('level');

            foreach ($levels as $level) {
                if (! ProductWarehouse::isValidLevel((string) $level)) {
                    continue;
                }

                for ($day = 1; $day <= ProductWarehouse::DAY_COUNT; $day++) {
                    foreach (ProductWarehouse::GROUPS as $group) {
                        $this->normalizeGroup((string) $level, $day, $group);
                    }
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

    private function normalizeGroup(string $level, int $day, string $group): void
    {
        $activeQuery = WarehouseProduct::query()
            ->where('level', $level)
            ->where('day', $day)
            ->where('group_key', $group)
            ->where('is_deleted', false)
            ->orderByDesc('is_custom')
            ->orderBy('id');

        $active = $activeQuery->get();
        $excess = $active->count() - ProductWarehouse::PRODUCTS_PER_SET;

        if ($excess > 0) {
            WarehouseProduct::query()
                ->whereIn('id', $active->slice(ProductWarehouse::PRODUCTS_PER_SET)->pluck('id')->all())
                ->update(['is_deleted' => true]);

            return;
        }

        if ($excess === 0) {
            return;
        }

        $missing = abs($excess);
        $names = $active->pluck('name')->all();
        $commission = ProductWarehouse::commission($level);
        $generated = ProductWarehouse::legacyGroupItems($level, $day, $group);
        $added = 0;

        foreach ($generated as $index => $product) {
            if ($added >= $missing) {
                break;
            }

            $name = $product['name'];

            if (in_array($name, $names, true)) {
                continue;
            }

            WarehouseProduct::query()->create([
                'product_id' => 'normalized-'.$level.'-d'.$day.'-'.$group.'-'.$index,
                'level' => $level,
                'day' => $day,
                'group_key' => $group,
                'name' => $name,
                'image_url' => $product['image'],
                'description' => $product['description'],
                'price' => $product['price'],
                'commission' => $commission,
                'is_custom' => false,
                'is_deleted' => false,
            ]);

            $names[] = $name;
            $added++;
        }
    }
};
