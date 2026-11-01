<?php

use App\Models\WarehouseProduct;
use App\Support\ProductWarehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds thirty group c products for every day of a membership level', function () {
    $this->artisan('app:seed-warehouse-group-c', ['--level' => ['normal']])
        ->assertSuccessful();

    foreach (range(1, ProductWarehouse::DAY_COUNT) as $day) {
        expect(WarehouseProduct::query()->where('level', 'normal')->where('day', $day)->where('group_key', 'c')->count())
            ->toBe(30)
            ->and(ProductWarehouse::groupCounts('normal')[$day]['c'])->toBe(30);
    }

    $product = WarehouseProduct::query()->where('level', 'normal')->where('group_key', 'c')->first();

    expect($product)->not->toBeNull()
        ->and($product->is_custom)->toBeTrue()
        ->and($product->is_deleted)->toBeFalse()
        ->and($product->name)->not->toBe('')
        ->and((float) $product->commission)->toBe(0.5)
        ->and((float) $product->price)->toBeGreaterThanOrEqual(28)
        ->and((float) $product->price)->toBeLessThanOrEqual(38);
});

it('seeds every warehouse level by default', function () {
    $this->artisan('app:seed-warehouse-group-c')->assertSuccessful();

    foreach (array_column(ProductWarehouse::levels(), 'value') as $level) {
        expect(WarehouseProduct::query()->where('level', $level)->where('group_key', 'c')->count())
            ->toBe(30 * ProductWarehouse::DAY_COUNT);
    }
});

it('honours the count option', function () {
    $this->artisan('app:seed-warehouse-group-c', ['--level' => ['silver'], '--count' => 5])
        ->assertSuccessful();

    expect(WarehouseProduct::query()->where('level', 'silver')->where('group_key', 'c')->count())
        ->toBe(5 * ProductWarehouse::DAY_COUNT);
});

it('skips days that already have group c products unless forced', function () {
    $existing = WarehouseProduct::factory()->create([
        'level' => 'normal',
        'day' => 1,
        'group_key' => 'c',
        'name' => 'Existing Group C Product',
    ]);

    $this->artisan('app:seed-warehouse-group-c', ['--level' => ['normal']])
        ->assertSuccessful();

    expect(WarehouseProduct::query()->where('level', 'normal')->where('day', 1)->where('group_key', 'c')->count())
        ->toBe(1)
        ->and(WarehouseProduct::query()->where('level', 'normal')->where('day', 2)->where('group_key', 'c')->count())
        ->toBe(30)
        ->and(WarehouseProduct::query()->whereKey($existing->id)->exists())->toBeTrue();
});

it('replaces existing group c products when forced', function () {
    $existing = WarehouseProduct::factory()->create([
        'level' => 'normal',
        'day' => 1,
        'group_key' => 'c',
        'name' => 'Existing Group C Product',
    ]);

    $this->artisan('app:seed-warehouse-group-c', ['--level' => ['normal'], '--force' => true])
        ->assertSuccessful();

    expect(WarehouseProduct::query()->where('level', 'normal')->where('day', 1)->where('group_key', 'c')->count())
        ->toBe(30)
        ->and(WarehouseProduct::query()->whereKey($existing->id)->exists())->toBeFalse();
});

it('fails for unknown membership levels', function () {
    $this->artisan('app:seed-warehouse-group-c', ['--level' => ['bogus']])
        ->assertFailed();

    expect(WarehouseProduct::query()->where('group_key', 'c')->count())->toBe(0);
});

it('fails for an invalid count', function () {
    $this->artisan('app:seed-warehouse-group-c', ['--level' => ['normal'], '--count' => 0])
        ->assertFailed();

    expect(WarehouseProduct::query()->where('group_key', 'c')->count())->toBe(0);
});
