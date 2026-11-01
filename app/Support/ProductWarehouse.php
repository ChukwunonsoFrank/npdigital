<?php

namespace App\Support;

use App\Models\MembershipLevel;
use App\Models\WarehouseProduct;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Builds the product warehouse shown on the admin warehouse page.
 *
 * Every membership level has five days. The "A" and "B" groups of each day are
 * drawn from a level product pool that combines the level catalog in
 * resources/data/warehouse.json with generated variants, mirroring the warehouse
 * generator of the reference platform. Extra groups start empty for admins to
 * fill manually.
 */
class ProductWarehouse
{
    public const DAY_COUNT = 5;

    /**
     * Products in a single set for every membership level.
     */
    public const PRODUCTS_PER_SET = 30;

    /** @var list<string> */
    public const GROUPS = ['a', 'b', 'c'];

    /**
     * Groups seeded from the generated catalog. Groups outside this list start empty.
     *
     * @var list<string>
     */
    public const CATALOG_GROUPS = ['a', 'b'];

    /** @var array<string, mixed>|null */
    private static ?array $data = null;

    /** @var array<string, list<array{name: string, image: string|null, price: int, description: string}>> */
    private static array $poolCache = [];

    /** @var list<string> */
    private const DATABASE_LEVELS = ['normal', 'silver', 'gold', 'platinum', 'diamond'];

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function levels(): array
    {
        $membershipLevels = MembershipLevel::query()->orderBy('id')->get();
        $levels = [];

        foreach (self::data()['levels'] as $level) {
            $levels[$level] = $membershipLevels
                ->first(fn (MembershipLevel $membershipLevel): bool => strtolower($membershipLevel->name) === $level)
                ?->name ?? ucfirst($level);
        }

        foreach ($membershipLevels as $membershipLevel) {
            $levels[Str::slug($membershipLevel->name)] = $membershipLevel->name;
        }

        return array_map(
            fn (string $value, string $label): array => [
                'value' => $value,
                'label' => $label,
            ],
            array_keys($levels),
            array_values($levels),
        );
    }

    public static function isValidLevel(string $level): bool
    {
        return in_array($level, array_column(self::levels(), 'value'), true);
    }

    public static function label(string $level): string
    {
        return self::membershipLevel($level)?->name ?? ucfirst($level);
    }

    /**
     * Number of products in a single set. Every level uses a uniform set size.
     */
    public static function productCount(string $level): int
    {
        return self::PRODUCTS_PER_SET;
    }

    /**
     * Catalog products used to build the generated groups of a level.
     *
     * @return list<array{name: string, price: int, desc: string, img: string|null}>
     */
    public static function catalog(string $level): array
    {
        return self::data()['catalogs'][self::dataLevel($level)];
    }

    /**
     * Price range applied to warehouse products of a level.
     *
     * @return array{min: int, max: int}
     */
    public static function priceRange(string $level): array
    {
        $clamp = self::data()['clamp'][self::dataLevel($level)];

        return [
            'min' => (int) $clamp['min'],
            'max' => (int) $clamp['max'],
        ];
    }

    /**
     * Products per day group of the level, including admin additions and deletions.
     *
     * @return array<int, array<string, int>>
     */
    public static function groupCounts(string $level): array
    {
        if (self::usesDatabase($level) && self::hasDatabaseProducts($level)) {
            return WarehouseProduct::query()
                ->where('level', $level)
                ->where('is_deleted', false)
                ->selectRaw('day, group_key, count(*) as total')
                ->groupBy('day', 'group_key')
                ->get()
                ->reduce(function (array $counts, WarehouseProduct $product): array {
                    $counts[(int) $product->day][$product->group_key] = (int) $product->total;

                    return $counts;
                }, self::emptyGroupCounts());
        }

        $base = self::productCount($level);
        $counts = self::emptyGroupCounts();

        for ($day = 1; $day <= self::DAY_COUNT; $day++) {
            foreach (self::CATALOG_GROUPS as $group) {
                $counts[$day][$group] = $base;
            }
        }

        $changes = WarehouseProduct::query()
            ->where('level', $level)
            ->get(['day', 'group_key', 'is_custom', 'is_deleted']);

        foreach ($changes as $change) {
            $day = (int) $change->day;
            $group = $change->group_key;

            if (! isset($counts[$day][$group])) {
                continue;
            }

            if ($change->is_custom) {
                $counts[$day][$group]++;
            } elseif ($change->is_deleted) {
                $counts[$day][$group]--;
            }
        }

        return $counts;
    }

    public static function commission(string $level): float
    {
        if (self::usesDatabase($level) && self::hasDatabaseProducts($level)) {
            return (float) (WarehouseProduct::query()
                ->where('level', $level)
                ->value('commission') ?? self::membershipLevel($level)?->percentage ?? 0.0);
        }

        if ($membershipLevel = self::membershipLevel($level)) {
            return (float) $membershipLevel->percentage;
        }

        return (float) (self::data()['counts'][self::dataLevel($level)]['commission'] ?? 0.0);
    }

    /**
     * Resolve a stored image reference into a browser-ready URL.
     */
    public static function imageUrl(?string $image): ?string
    {
        if ($image === null || $image === '') {
            return null;
        }

        if (str_starts_with($image, 'http') || str_starts_with($image, '/')) {
            return $image;
        }

        return route('storage.file', $image);
    }

    /**
     * Products of a day group, with admin edits applied and admin additions appended.
     *
     * @return list<array{id: string, name: string, image: string|null, image_url: string|null, brand: string, description: string|null, price: float, commission: float, assigned: int, total: int}>
     */
    public static function groupItems(string $level, int $day, string $group): array
    {
        if (self::usesDatabase($level) && self::hasDatabaseProducts($level)) {
            return WarehouseProduct::query()
                ->where('level', $level)
                ->where('day', $day)
                ->where('group_key', $group)
                ->where('is_deleted', false)
                ->orderBy('id')
                ->get()
                ->map(fn (WarehouseProduct $product): array => [
                    'id' => $product->product_id,
                    'name' => (string) $product->name,
                    'image' => $product->image_url,
                    'image_url' => self::imageUrl($product->image_url),
                    'brand' => ProductBrand::resolve($product->brand, $product->product_id),
                    'description' => $product->description,
                    'price' => (float) $product->price,
                    'commission' => (float) $product->commission,
                    'assigned' => 0,
                    'total' => 0,
                ])
                ->all();
        }

        $items = self::baseGroupItems($level, $day, $group);

        $changes = self::changes($level, $day, $group);

        if ($changes->isNotEmpty()) {
            $byProductId = $changes->keyBy('product_id');
            $edited = [];

            foreach ($items as $item) {
                $change = $byProductId->get($item['id']);

                if ($change?->is_deleted) {
                    continue;
                }

                $edited[] = $change === null ? $item : self::applyChange($item, $change);
            }

            foreach ($changes->where('is_custom', true)->sortBy('id') as $change) {
                $edited[] = [
                    'id' => $change->product_id,
                    'name' => (string) $change->name,
                    'image' => $change->image_url,
                    'brand' => ProductBrand::resolve($change->brand, $change->product_id),
                    'description' => $change->description,
                    'price' => (float) ($change->price ?? 0),
                    'commission' => (float) ($change->commission ?? 0),
                    'assigned' => 0,
                    'total' => 0,
                ];
            }

            $items = $edited;
        }

        return array_map(fn (array $item): array => [
            ...$item,
            'image_url' => self::imageUrl($item['image']),
        ], $items);
    }

    /**
     * Every product available for a level across all day groups.
     *
     * @return list<array<string, mixed>>
     */
    public static function levelProducts(string $level): array
    {
        $products = [];
        $seen = [];

        for ($day = 1; $day <= self::DAY_COUNT; $day++) {
            foreach (self::GROUPS as $group) {
                foreach (self::groupItems($level, $day, $group) as $product) {
                    if (isset($seen[$product['id']])) {
                        continue;
                    }

                    $seen[$product['id']] = true;
                    $products[] = [
                        ...$product,
                        'level' => $level,
                        'day' => $day,
                        'group' => $group,
                    ];
                }
            }
        }

        return $products;
    }

    /**
     * Find a single product in the level warehouse, or across every level when none is given.
     *
     * @return array<string, mixed>|null
     */
    public static function findProduct(?string $level, string $productId): ?array
    {
        $levels = $level !== null ? [$level] : array_column(self::levels(), 'value');

        foreach ($levels as $candidate) {
            foreach (self::levelProducts($candidate) as $product) {
                if ((string) $product['id'] === $productId) {
                    return $product;
                }
            }
        }

        return null;
    }

    /**
     * Get the legacy generated products used to retain the existing pricing pattern during import.
     *
     * @return list<array{id: string, name: string, image: string|null, brand: string, price: int, commission: float, description: string, assigned: int, total: int}>
     */
    public static function legacyGroupItems(string $level, int $day, string $group): array
    {
        return self::baseGroupItems($level, $day, $group);
    }

    private static function usesDatabase(string $level): bool
    {
        return in_array($level, self::DATABASE_LEVELS, true)
            || self::membershipLevel($level) !== null;
    }

    /**
     * Determine whether a level has imported products to read from the database.
     */
    private static function hasDatabaseProducts(string $level): bool
    {
        return in_array($level, self::DATABASE_LEVELS, true)
            && WarehouseProduct::query()
                ->where('level', $level)
                ->exists();
    }

    /**
     * Catalog key used for generated product data. Levels without their own catalog use the diamond catalog.
     */
    private static function dataLevel(string $level): string
    {
        return array_key_exists($level, self::data()['catalogs']) ? $level : 'diamond';
    }

    /**
     * Find the membership level whose key matches the warehouse level.
     */
    private static function membershipLevel(string $level): ?MembershipLevel
    {
        return MembershipLevel::query()->get()->first(
            fn (MembershipLevel $membershipLevel): bool => strtolower($membershipLevel->name) === $level
                || Str::slug($membershipLevel->name) === $level,
        );
    }

    /**
     * Apply an admin edit to a generated product.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private static function applyChange(array $item, WarehouseProduct $change): array
    {
        return [
            ...$item,
            'name' => (string) ($change->name ?? $item['name']),
            'image' => $change->image_url ?? $item['image'],
            'brand' => ProductBrand::resolve($change->brand, (string) $item['id']),
            'description' => $change->description ?? $item['description'],
            'price' => (float) ($change->price ?? $item['price']),
            'commission' => (float) ($change->commission ?? $item['commission']),
        ];
    }

    /**
     * @return Collection<int, WarehouseProduct>
     */
    private static function changes(string $level, int $day, string $group): Collection
    {
        return WarehouseProduct::query()
            ->where('level', $level)
            ->where('day', $day)
            ->where('group_key', $group)
            ->get();
    }

    /**
     * Products drawn from the level pool before admin changes are applied.
     *
     * @return list<array{id: string, name: string, image: string|null, brand: string, price: int, commission: float, description: string, assigned: int, total: int}>
     */
    private static function baseGroupItems(string $level, int $day, string $group): array
    {
        if (! in_array($group, self::CATALOG_GROUPS, true)) {
            return [];
        }

        $count = self::productCount($level);
        $levelKey = self::dataLevel($level);
        $levelIndex = (int) array_search($levelKey, self::data()['levels'], true);
        $groupIndex = (int) array_search($group, self::CATALOG_GROUPS, true);
        $offset = (($day - 1) * count(self::CATALOG_GROUPS) + $groupIndex) * $count;
        $items = array_slice(self::pool($level), $offset, $count);
        $commission = self::commission($level);
        $seed = $day * 1000 + $levelIndex * 100 + ($groupIndex === 1 ? 50 : 0);

        $products = [];

        foreach (array_values($items) as $index => $item) {
            $id = "prod-{$level}-d{$day}-{$group}-{$seed}-{$index}";

            $products[] = [
                'id' => $id,
                'name' => $item['name'],
                'image' => $item['image'],
                'brand' => ProductBrand::forSeed($id),
                'price' => $item['price'],
                'commission' => $commission,
                'description' => $item['description'],
                'assigned' => 0,
                'total' => 0,
            ];
        }

        return self::shuffle(
            $products,
            $groupIndex === 1 ? $day * 251 + $levelIndex * 47 : $day * 137 + $levelIndex * 31,
        );
    }

    /**
     * @return array<int, array<string, int>>
     */
    private static function emptyGroupCounts(): array
    {
        $counts = [];

        for ($day = 1; $day <= self::DAY_COUNT; $day++) {
            foreach (self::GROUPS as $group) {
                $counts[$day][$group] = 0;
            }
        }

        return $counts;
    }

    /**
     * Search the warehouse catalog across every level for the merge product builder.
     *
     * @return list<array{id: string, name: string, image: string|null, image_url: string|null, brand: string, description: string|null, price: float, commission: float, level: string, day: int, group: string}>
     */
    public static function searchProducts(?string $level, string $term = '', int $limit = 30): array
    {
        $levels = $level !== null && $level !== ''
            ? [$level]
            : array_column(self::levels(), 'value');

        $term = trim($term);
        $results = [];
        $databaseLevels = [];

        $rows = WarehouseProduct::query()
            ->whereIn('level', $levels)
            ->where('is_deleted', false)
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->limit($limit)
            ->get();

        foreach ($rows as $product) {
            $databaseLevels[$product->level] = true;

            $results[] = [
                'id' => $product->product_id,
                'name' => (string) $product->name,
                'image' => $product->image_url,
                'image_url' => self::imageUrl($product->image_url),
                'brand' => ProductBrand::resolve($product->brand, $product->product_id),
                'description' => $product->description,
                'price' => (float) $product->price,
                'commission' => (float) $product->commission,
                'level' => $product->level,
                'day' => (int) $product->day,
                'group' => $product->group_key,
            ];
        }

        if (count($results) >= $limit) {
            return $results;
        }

        foreach ($levels as $levelKey) {
            if (isset($databaseLevels[$levelKey]) || self::hasDatabaseProducts($levelKey)) {
                continue;
            }

            for ($day = 1; $day <= self::DAY_COUNT; $day++) {
                foreach (self::GROUPS as $group) {
                    foreach (self::groupItems($levelKey, $day, $group) as $product) {
                        if ($term !== '' && ! str_contains(strtolower($product['name']), strtolower($term))) {
                            continue;
                        }

                        $results[] = [
                            ...$product,
                            'level' => $levelKey,
                            'day' => $day,
                            'group' => $group,
                        ];

                        if (count($results) >= $limit) {
                            return $results;
                        }
                    }
                }
            }
        }

        usort($results, fn (array $first, array $second): int => strcasecmp($first['name'], $second['name']));

        return array_slice($results, 0, $limit);
    }

    /**
     * Expand the level catalog into the pool the day groups are drawn from.
     *
     * @return list<array{name: string, image: string|null, price: int, description: string}>
     */
    private static function pool(string $level): array
    {
        if (isset(self::$poolCache[$level])) {
            return self::$poolCache[$level];
        }

        $catalog = self::data()['catalogs'][self::dataLevel($level)];
        $target = self::productCount($level) * 20;
        $prefixes = self::data()['prefixes'];
        $suffixes = self::data()['suffixes'];

        $pool = [];
        $names = [];

        foreach ($catalog as $product) {
            $pool[] = [
                'name' => $product['name'],
                'image' => $product['img'],
                'price' => self::clampPrice((int) $product['price'], $level),
                'description' => $product['desc'],
            ];
            $names[$product['name']] = true;
        }

        $round = 0;

        while (count($pool) < $target) {
            $round++;

            foreach ($catalog as $product) {
                if (count($pool) >= $target) {
                    break;
                }

                $prefix = $prefixes[$round % count($prefixes)];
                $suffix = $suffixes[intdiv($round, count($prefixes)) % count($suffixes)];

                $name = match (true) {
                    $prefix !== '' && $suffix !== '' => "{$prefix} {$product['name']} {$suffix}",
                    $prefix !== '' => "{$prefix} {$product['name']}",
                    $suffix !== '' => "{$product['name']} {$suffix}",
                    default => "{$product['name']} Var{$round}",
                };

                if (isset($names[$name])) {
                    $name .= " #{$round}";
                }

                if (isset($names[$name])) {
                    continue;
                }

                $names[$name] = true;
                $random = self::random($round * 1000 + count($pool));
                $price = (int) round($product['price'] * (0.85 + $random() * 0.3));

                $pool[] = [
                    'name' => $name,
                    'image' => null,
                    'price' => self::clampPrice($price, $level),
                    'description' => $product['desc'],
                ];
            }
        }

        return self::$poolCache[$level] = $pool;
    }

    private static function clampPrice(int $price, string $level): int
    {
        $clamp = self::data()['clamp'][self::dataLevel($level)];

        return max((int) $clamp['min'], min((int) $clamp['max'], $price));
    }

    /**
     * Lehmer random number generator used to keep variant prices stable.
     */
    private static function random(int $seed): Closure
    {
        $state = $seed;

        return function () use (&$state): float {
            $state = ($state * 16807) % 2147483647;

            return ($state - 1) / 2147483646;
        };
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private static function shuffle(array $items, int $seed): array
    {
        $random = self::random($seed);

        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = (int) floor($random() * ($i + 1));

            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private static function data(): array
    {
        return self::$data ??= File::json(resource_path('data/warehouse.json'));
    }
}
