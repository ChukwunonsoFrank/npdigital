<?php

namespace App\Console\Commands;

use App\Models\WarehouseProduct;
use App\Support\ProductBrand;
use App\Support\ProductWarehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedWarehouseGroupCProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-warehouse-group-c
        {--level=* : Membership levels to seed (defaults to every warehouse level)}
        {--count=30 : Products to seed per day group}
        {--force : Replace the existing group C products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed random group C products for every day of the product warehouse';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = filter_var($this->option('count'), FILTER_VALIDATE_INT);

        if ($count === false || $count < 1) {
            $this->error('The --count option must be a positive integer.');

            return self::FAILURE;
        }

        $levels = $this->levels();

        if ($levels === []) {
            $this->error('No valid warehouse membership levels were found.');

            return self::FAILURE;
        }

        foreach ($levels as $level) {
            $this->seedLevel($level, $count);
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function levels(): array
    {
        $requested = array_values(array_filter((array) $this->option('level')));
        $available = array_column(ProductWarehouse::levels(), 'value');

        if ($requested === []) {
            return $available;
        }

        if ($invalid = array_diff($requested, $available)) {
            $this->error(sprintf('Unknown membership level(s): %s.', implode(', ', $invalid)));
        }

        return array_values(array_intersect($requested, $available));
    }

    private function seedLevel(string $level, int $count): void
    {
        $products = $this->products($level, $count);

        if ($products === []) {
            $this->warn(sprintf('No catalog products found for %s. Skipping.', ProductWarehouse::label($level)));

            return;
        }

        $range = ProductWarehouse::priceRange($level);
        $commission = ProductWarehouse::commission($level);
        $seeded = 0;

        for ($day = 1; $day <= ProductWarehouse::DAY_COUNT; $day++) {
            $existing = WarehouseProduct::query()
                ->where('level', $level)
                ->where('day', $day)
                ->where('group_key', 'c');

            if ($existing->exists()) {
                if (! $this->option('force')) {
                    $this->warn(sprintf(
                        '%s day %d group C already has products. Use --force to replace them.',
                        ProductWarehouse::label($level),
                        $day,
                    ));

                    continue;
                }

                $existing->delete();
            }

            $now = now();
            $rows = [];

            for ($index = 0; $index < $count; $index++) {
                $product = $products[$index % count($products)];

                $rows[] = [
                    'product_id' => sprintf('group-c-%s-d%d-%s', $level, $day, Str::uuid()),
                    'level' => $level,
                    'day' => $day,
                    'group_key' => 'c',
                    'name' => $product['name'],
                    'image_url' => $product['img'] ?? null,
                    'brand' => ProductBrand::random(),
                    'description' => $product['desc'] ?? null,
                    'price' => random_int($range['min'], $range['max']),
                    'commission' => $commission,
                    'is_custom' => true,
                    'is_deleted' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            WarehouseProduct::query()->insert($rows);
            $seeded += count($rows);
        }

        if ($seeded > 0) {
            $this->info(sprintf('Seeded %d group C products for %s.', $seeded, ProductWarehouse::label($level)));
        }
    }

    /**
     * @return list<array{name: string, price: int, desc: string, img: string|null}>
     */
    private function products(string $level, int $count): array
    {
        $catalog = ProductWarehouse::catalog($level);

        if ($catalog === []) {
            return [];
        }

        shuffle($catalog);

        return array_slice($catalog, 0, min($count, count($catalog)));
    }
}
