<?php

namespace App\Console\Commands;

use App\Models\WarehouseProduct;
use App\Support\ProductWarehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImportGoldWarehouseProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-gold-warehouse-products {--force : Replace the existing gold warehouse products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the supplied gold warehouse catalog and product images';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourcePath = public_path('gold-products');

        if (! File::isDirectory($sourcePath)) {
            $this->error('The gold-products source directory does not exist.');

            return self::FAILURE;
        }

        if (WarehouseProduct::query()->where('level', 'gold')->exists() && ! $this->option('force')) {
            $this->error('Gold warehouse products already exist. Re-run with --force to replace them.');

            return self::FAILURE;
        }

        $products = $this->products($sourcePath);

        WarehouseProduct::query()->where('level', 'gold')->delete();

        foreach ($products as $product) {
            WarehouseProduct::query()->create($product);
        }

        $this->moveImages($sourcePath);
        $this->info(sprintf('Imported %d gold warehouse products.', count($products)));

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, int|string|float|bool|null>>
     */
    private function products(string $sourcePath): array
    {
        $products = [];

        for ($day = 1; $day <= ProductWarehouse::DAY_COUNT; $day++) {
            foreach (ProductWarehouse::GROUPS as $group) {
                $notesPath = sprintf('%s/notes-gold-day%d-group%s.txt', $sourcePath, $day, strtoupper($group));
                $photosPath = sprintf('%s/warehouse-gold-day%d-group%s-photos', $sourcePath, $day, strtoupper($group));
                $names = array_values(array_filter(array_map('trim', file($notesPath, FILE_IGNORE_NEW_LINES))));
                $photos = File::files($photosPath);
                usort($photos, fn ($first, $second): int => strnatcasecmp($first->getFilename(), $second->getFilename()));

                if (count($names) !== count($photos)) {
                    throw new RuntimeException("{$notesPath} and {$photosPath} must contain the same number of products.");
                }

                $legacyProducts = ProductWarehouse::legacyGroupItems('gold', $day, $group);

                foreach ($names as $index => $name) {
                    $products[] = [
                        'product_id' => sprintf('gold-d%d-%s-%02d', $day, $group, $index + 1),
                        'level' => 'gold',
                        'day' => $day,
                        'group_key' => $group,
                        'name' => $name,
                        'image_url' => sprintf('warehouse/gold/day%d/group%s/%s', $day, $group, $photos[$index]->getFilename()),
                        'description' => $legacyProducts[$index]['description'] ?? null,
                        'price' => $legacyProducts[$index]['price'],
                        'commission' => ProductWarehouse::commission('gold'),
                        'is_custom' => false,
                        'is_deleted' => false,
                    ];
                }
            }
        }

        return $products;
    }

    private function moveImages(string $sourcePath): void
    {
        for ($day = 1; $day <= ProductWarehouse::DAY_COUNT; $day++) {
            foreach (ProductWarehouse::GROUPS as $group) {
                $source = sprintf('%s/warehouse-gold-day%d-group%s-photos', $sourcePath, $day, strtoupper($group));
                $destination = storage_path(sprintf('app/public/warehouse/gold/day%d/group%s', $day, $group));

                File::ensureDirectoryExists(dirname($destination));

                if (File::isDirectory($destination)) {
                    File::deleteDirectory($destination);
                }

                File::moveDirectory($source, $destination);
            }
        }
    }
}
