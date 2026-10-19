<?php

namespace App\Console\Commands;

use App\Models\WarehouseProduct;
use App\Support\ProductWarehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImportDiamondWarehouseProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-diamond-warehouse-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the supplied diamond warehouse catalog and product images';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourcePath = public_path('diamond-products');

        if (! File::isDirectory($sourcePath) || WarehouseProduct::query()->where('level', 'diamond')->exists()) {
            return self::FAILURE;
        }

        $products = [];

        for ($day = 1; $day <= ProductWarehouse::DAY_COUNT; $day++) {
            foreach (ProductWarehouse::GROUPS as $group) {
                $notesPath = sprintf('%s/notes-diamond-day%d-group%s.txt', $sourcePath, $day, strtoupper($group));
                $photosPath = sprintf('%s/warehouse-diamond-day%d-group%s-photos', $sourcePath, $day, strtoupper($group));
                $names = array_values(array_filter(array_map('trim', file($notesPath, FILE_IGNORE_NEW_LINES))));
                $photos = File::files($photosPath);
                usort($photos, fn ($first, $second): int => strnatcasecmp($first->getFilename(), $second->getFilename()));

                if (count($names) !== count($photos)) {
                    throw new RuntimeException("{$notesPath} and {$photosPath} must contain the same number of products.");
                }

                $legacyProducts = ProductWarehouse::legacyGroupItems('diamond', $day, $group);

                foreach ($names as $index => $name) {
                    $products[] = [
                        'product_id' => sprintf('diamond-d%d-%s-%02d', $day, $group, $index + 1),
                        'level' => 'diamond',
                        'day' => $day,
                        'group_key' => $group,
                        'name' => $name,
                        'image_url' => sprintf('warehouse/diamond/day%d/group%s/%s', $day, $group, $photos[$index]->getFilename()),
                        'description' => $legacyProducts[$index]['description'] ?? null,
                        'price' => $legacyProducts[$index]['price'],
                        'commission' => 1.5,
                        'is_custom' => false,
                        'is_deleted' => false,
                    ];
                }

                $destination = storage_path(sprintf('app/public/warehouse/diamond/day%d/group%s', $day, $group));
                File::ensureDirectoryExists(dirname($destination));
                File::moveDirectory($photosPath, $destination);
            }
        }

        WarehouseProduct::query()->insert($products);
        $this->info(sprintf('Imported %d diamond warehouse products.', count($products)));

        return self::SUCCESS;
    }
}
