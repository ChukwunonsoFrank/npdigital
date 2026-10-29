<?php

namespace App\Console\Commands;

use App\Models\WarehouseProduct;
use App\Support\ProductWarehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImportPlatinumWarehouseProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-platinum-warehouse-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the supplied platinum warehouse catalog and product images';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourcePath = public_path('platinum-products');

        if (! File::isDirectory($sourcePath) || WarehouseProduct::query()->where('level', 'platinum')->exists()) {
            return self::FAILURE;
        }

        $products = [];

        for ($day = 1; $day <= ProductWarehouse::DAY_COUNT; $day++) {
            foreach (ProductWarehouse::CATALOG_GROUPS as $group) {
                $notesPath = sprintf('%s/notes-platinum-day%d-group%s.txt', $sourcePath, $day, strtoupper($group));
                $photosPath = sprintf('%s/warehouse-platinum-day%d-group%s-photos', $sourcePath, $day, strtoupper($group));
                $names = array_values(array_filter(array_map('trim', file($notesPath, FILE_IGNORE_NEW_LINES))));
                $photos = File::files($photosPath);
                usort($photos, fn ($first, $second): int => strnatcasecmp($first->getFilename(), $second->getFilename()));

                if (count($names) !== count($photos)) {
                    throw new RuntimeException("{$notesPath} and {$photosPath} must contain the same number of products.");
                }

                $legacyProducts = ProductWarehouse::legacyGroupItems('platinum', $day, $group);

                foreach ($names as $index => $name) {
                    $products[] = [
                        'product_id' => sprintf('platinum-d%d-%s-%02d', $day, $group, $index + 1),
                        'level' => 'platinum',
                        'day' => $day,
                        'group_key' => $group,
                        'name' => $name,
                        'image_url' => sprintf('warehouse/platinum/day%d/group%s/%s', $day, $group, $photos[$index]->getFilename()),
                        'description' => $legacyProducts[$index]['description'] ?? null,
                        'price' => $legacyProducts[$index]['price'],
                        'commission' => 1.25,
                        'is_custom' => false,
                        'is_deleted' => false,
                    ];
                }

                $destination = storage_path(sprintf('app/public/warehouse/platinum/day%d/group%s', $day, $group));
                File::ensureDirectoryExists(dirname($destination));
                File::moveDirectory($photosPath, $destination);
            }
        }

        WarehouseProduct::query()->insert($products);
        $this->info(sprintf('Imported %d platinum warehouse products.', count($products)));

        return self::SUCCESS;
    }
}
