<?php

namespace App\Support;

use App\Models\MembershipLevel;
use App\Models\WarehouseExclusiveProduct;
use App\Models\WarehouseProduct;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Builds the product warehouse shown on the admin warehouse page.
 *
 * Every membership level has five days, each holding an "A" and a "B" group of
 * products. Groups are drawn from a level product pool that combines the level
 * catalog in resources/data/warehouse.json with generated variants, mirroring
 * the warehouse generator of the reference platform.
 */
class ProductWarehouse
{
    public const DAY_COUNT = 5;

    /** @var list<string> */
    public const GROUPS = ['a', 'b'];

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
     * Number of products in a single group of the level.
     */
    public static function productCount(string $level): int
    {
        return (int) self::data()['counts'][$level]['count'];
    }

    /**
     * Products per day group of the level, including admin additions and deletions.
     *
     * @return array<int, array<string, int>>
     */
    public static function groupCounts(string $level): array
    {
        if (self::usesDatabase($level)) {
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

        $counts = [];

        for ($day = 1; $day <= self::DAY_COUNT; $day++) {
            foreach (self::GROUPS as $group) {
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
        if (self::usesDatabase($level)) {
            return (float) (WarehouseProduct::query()
                ->where('level', $level)
                ->value('commission') ?? self::membershipLevel($level)?->percentage ?? match ($level) {
                    'normal' => 0.5,
                    'silver' => 0.75,
                    'gold' => 1.0,
                    'platinum' => 1.25,
                    'diamond' => 1.5,
                    default => 0.0,
                });
        }

        return (float) self::data()['counts'][$level]['commission'];
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
        if (self::usesDatabase($level)) {
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
        $count = self::productCount($level);
        $levelIndex = (int) array_search($level, self::data()['levels'], true);
        $groupIndex = (int) array_search($group, self::GROUPS, true);
        $offset = (($day - 1) * count(self::GROUPS) + $groupIndex) * $count;
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
     * Premium combos of a level, with admin edits applied and admin additions appended.
     *
     * @return list<array{id: string, name: string, image: string|null, image_url: string|null, brand: string, price: float, commission: float, description: string|null, name2: string|null, image2: string|null, image_url2: string|null, brand2: string, price2: float|null}>
     */
    public static function exclusiveProducts(string $level): array
    {
        $items = array_map(fn (array $product): array => [
            'id' => $product['id'],
            'name' => $product['name'],
            'image' => ($product['image'] ?? '') !== '' ? $product['image'] : null,
            'brand' => ProductBrand::forSeed($level.'-'.$product['id']),
            'price' => (float) $product['price'],
            'commission' => (float) $product['commission'],
            'description' => $product['description'],
            'name2' => $product['name2'] ?? null,
            'image2' => ($product['image2'] ?? '') !== '' ? $product['image2'] : null,
            'brand2' => ProductBrand::forSeed($level.'-'.$product['id'].'-2'),
            'price2' => isset($product['price2']) ? (float) $product['price2'] : null,
        ], self::data()['exclusives'][$level] ?? []);

        $changes = WarehouseExclusiveProduct::query()
            ->where('level', $level)
            ->get();

        if ($changes->isNotEmpty()) {
            $byExclusiveId = $changes->keyBy('exclusive_id');
            $edited = [];

            foreach ($items as $item) {
                $change = $byExclusiveId->get($item['id']);

                if ($change?->is_deleted) {
                    continue;
                }

                $edited[] = $change === null ? $item : self::applyExclusiveChange($item, $change);
            }

            foreach ($changes->where('is_custom', true)->sortBy('id') as $change) {
                $edited[] = [
                    'id' => $change->exclusive_id,
                    'name' => (string) $change->name,
                    'image' => $change->image_url,
                    'brand' => ProductBrand::resolve($change->brand, $change->exclusive_id),
                    'price' => (float) ($change->price ?? 0),
                    'commission' => (float) ($change->commission ?? 0),
                    'description' => $change->description,
                    'name2' => $change->name2,
                    'image2' => $change->image_url2,
                    'brand2' => ProductBrand::resolve($change->brand2, $change->exclusive_id.'-2'),
                    'price2' => $change->price2 === null ? null : (float) $change->price2,
                ];
            }

            $items = $edited;
        }

        return array_map(fn (array $item): array => [
            ...$item,
            'image_url' => self::imageUrl($item['image']),
            'image_url2' => self::imageUrl($item['image2']),
        ], $items);
    }

    /**
     * Apply an admin edit to a generated premium combo.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private static function applyExclusiveChange(array $item, WarehouseExclusiveProduct $change): array
    {
        return [
            ...$item,
            'name' => (string) ($change->name ?? $item['name']),
            'image' => $change->image_url ?? $item['image'],
            'brand' => ProductBrand::resolve($change->brand, (string) $item['id']),
            'price' => (float) ($change->price ?? $item['price']),
            'commission' => (float) ($change->commission ?? $item['commission']),
            'description' => $change->description ?? $item['description'],
            'name2' => $change->name2 ?? $item['name2'],
            'image2' => $change->image_url2 ?? $item['image2'],
            'brand2' => ProductBrand::resolve($change->brand2, (string) $item['id'].'-2'),
            'price2' => $change->price2 ?? $item['price2'],
        ];
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

        $catalog = self::data()['catalogs'][$level];
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
        $clamp = self::data()['clamp'][$level];

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
