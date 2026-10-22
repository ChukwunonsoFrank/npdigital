<?php

declare(strict_types=1);

use App\Livewire\Admin\Warehouse;
use App\Models\User;
use App\Models\WarehouseExclusiveProduct;
use App\Models\WarehouseProduct;
use App\Support\ProductBrand;
use App\Support\ProductWarehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['normal', 'silver', 'gold', 'platinum', 'diamond'] as $level) {
        for ($day = 1; $day <= ProductWarehouse::DAY_COUNT; $day++) {
            foreach (ProductWarehouse::GROUPS as $group) {
                foreach (ProductWarehouse::legacyGroupItems($level, $day, $group) as $product) {
                    WarehouseProduct::query()->create([
                        'product_id' => $product['id'],
                        'level' => $level,
                        'day' => $day,
                        'group_key' => $group,
                        'name' => $product['name'],
                        'image_url' => $product['image'],
                        'description' => $product['description'],
                        'price' => $product['price'],
                        'commission' => $product['commission'],
                        'is_custom' => false,
                        'is_deleted' => false,
                    ]);
                }
            }
        }
    }
});

it('renders the product warehouse page for admins', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard.warehouse'))
        ->assertOk()
        ->assertSee('Product Warehouse')
        ->assertSee('Normal')
        ->assertSee('Silver')
        ->assertSee('Gold')
        ->assertSee('Platinum')
        ->assertSee('Diamond')
        ->assertSee('Day 1')
        ->assertSee('Day 2')
        ->assertSee('Day 3')
        ->assertSee('Day 4')
        ->assertSee('Day 5')
        ->assertSee('60 products')
        ->assertDontSee('Group A')
        ->assertSee('Exclusive Products')
        ->assertSee('5 products');
});

it('does not render the product warehouse page for regular users', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.dashboard.warehouse'))
        ->assertForbidden();
});

it('links to the product warehouse from the admin sidebar', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.dashboard.warehouse'))
        ->assertSee('Product Warehouse');
});

it('renders the warehouse page with day counts and exclusive products for the normal level', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->assertSet('level', 'normal')
        ->assertSet('openDay', null)
        ->assertSet('openGroup', null)
        ->assertSet('openExclusive', false)
        ->assertSee('60 products')
        ->assertDontSee('Group A')
        ->assertSee('Exclusive Products — Normal')
        ->assertSee('5 products')
        ->assertDontSee('Canvas Sneakers');
});

it('switches to other membership levels and shows their product counts', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('selectLevel', 'silver')
        ->assertSet('level', 'silver')
        ->assertSee('70 products')
        ->assertSee('Exclusive Products — Silver')
        ->call('selectLevel', 'diamond')
        ->assertSet('level', 'diamond')
        ->assertSee('100 products')
        ->assertSee('Exclusive Products — Diamond');
});

it('toggles day and group visibility', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->assertSet('openDay', null)
        ->call('toggleDay', 2)
        ->assertSet('openDay', 2)
        ->assertSet('openGroup', null)
        ->call('toggleGroup', 'a')
        ->assertSet('openGroup', 'a')
        ->assertSee('Group A')
        ->call('toggleGroup', 'b')
        ->assertSet('openGroup', 'b')
        ->call('toggleDay', 2)
        ->assertSet('openDay', null)
        ->assertSet('openGroup', null);
});

it('reopens a day after closing it', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->assertSet('openDay', null)
        ->call('toggleDay', 1)
        ->assertSet('openDay', 1)
        ->call('toggleDay', 1)
        ->assertSet('openDay', null)
        ->call('toggleDay', 1)
        ->assertSet('openDay', 1)
        ->assertSee('Group A');
});

it('toggles the exclusive products section', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->assertSet('openExclusive', false)
        ->assertDontSee('High-value products assigned during task creation')
        ->call('toggleExclusive')
        ->assertSet('openExclusive', true)
        ->assertSee('High-value products assigned during task creation')
        ->assertSee('Deluxe Tea Sampler')
        ->assertSee('$35')
        ->assertSee('Profit: $1.75')
        ->assertSee('5% commission')
        ->call('toggleExclusive')
        ->assertSet('openExclusive', false)
        ->assertDontSee('Deluxe Tea Sampler');
});

it('builds the warehouse product data for every membership level', function () {
    $expectations = [
        'normal' => ['count' => 30, 'commission' => 0.5, 'min' => 28, 'max' => 38],
        'silver' => ['count' => 35, 'commission' => 0.75, 'min' => 42, 'max' => 54],
        'gold' => ['count' => 40, 'commission' => 1.0, 'min' => 62, 'max' => 75],
        'platinum' => ['count' => 45, 'commission' => 1.25, 'min' => 62, 'max' => 71],
        'diamond' => ['count' => 50, 'commission' => 1.5, 'min' => 60, 'max' => 66],
    ];

    foreach ($expectations as $level => $expected) {
        $items = ProductWarehouse::groupItems($level, 1, 'a');

        expect($items)->toHaveCount($expected['count'])
            ->and(array_sum(ProductWarehouse::groupCounts($level)[1]))->toBe($expected['count'] * 2);

        foreach ($items as $item) {
            expect($item['price'])->toBeGreaterThanOrEqual($expected['min'])
                ->and($item['price'])->toBeLessThanOrEqual($expected['max'])
                ->and($item['commission'])->toBe($expected['commission'])
                ->and($item['id'])->toStartWith("prod-{$level}-d1-a-")
                ->and($item['name'])->not->toBe('')
                ->and($item['brand'])->toBeIn(ProductBrand::BRANDS);
        }

        $exclusives = ProductWarehouse::exclusiveProducts($level);

        expect($exclusives)->toHaveCount(5);

        foreach ($exclusives as $exclusive) {
            expect($exclusive['brand'])->toBeIn(ProductBrand::BRANDS)
                ->and($exclusive['brand2'])->toBeIn(ProductBrand::BRANDS);
        }
    }
});

it('generates the same warehouse products on every call', function () {
    $first = ProductWarehouse::groupItems('normal', 1, 'a');
    $second = ProductWarehouse::groupItems('normal', 1, 'a');

    expect(array_column($first, 'name'))->toBe(array_column($second, 'name'))
        ->and(array_column($first, 'price'))->toBe(array_column($second, 'price'));
});

it('shows level specific product details when a group is expanded', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleDay', 1)
        ->call('toggleGroup', 'a')
        ->assertSee('Canvas Sneakers')
        ->assertSee('38')
        ->assertSee('0.5% comm')
        ->assertSeeHtml('brands/');
});

it('assigns a brand when a warehouse product is created', function () {
    $product = WarehouseProduct::factory()->create();

    expect($product->brand)->toBeIn(ProductBrand::BRANDS);
});

it('adds a product to the open day group', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleDay', 1)
        ->call('toggleGroup', 'a')
        ->call('openAddProduct')
        ->assertSet('showProductModal', true)
        ->assertSet('editingProductId', null)
        ->assertSet('productCommission', '0.5')
        ->set('productName', 'Solar Power Bank')
        ->set('productImage', 'https://example.com/power-bank.jpg')
        ->set('productDescription', 'Portable charger with solar panel.')
        ->set('productPrice', '45')
        ->set('productCommission', '0.5')
        ->call('saveProduct')
        ->assertHasNoErrors()
        ->assertSet('showProductModal', false)
        ->assertSee('Solar Power Bank')
        ->assertSee('31 items')
        ->assertSee('61 products');

    $product = WarehouseProduct::query()->where('name', 'Solar Power Bank')->first();

    expect($product)->not->toBeNull()
        ->and($product->is_custom)->toBeTrue()
        ->and($product->level)->toBe('normal')
        ->and($product->day)->toBe(1)
        ->and($product->group_key)->toBe('a')
        ->and((float) $product->price)->toBe(45.0);

    $items = ProductWarehouse::groupItems('normal', 1, 'a');

    expect($items)->toHaveCount(31)
        ->and($items[array_key_last($items)]['name'])->toBe('Solar Power Bank');
});

it('edits a product in the open day group', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = ProductWarehouse::groupItems('normal', 1, 'a')[0];

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleDay', 1)
        ->call('toggleGroup', 'a')
        ->call('openEditProduct', $product['id'])
        ->assertSet('showProductModal', true)
        ->assertSet('editingProductId', $product['id'])
        ->assertSet('productName', $product['name'])
        ->set('productName', 'Renamed Warehouse Item')
        ->set('productPrice', '33')
        ->call('saveProduct')
        ->assertHasNoErrors()
        ->assertSet('showProductModal', false)
        ->assertSee('Renamed Warehouse Item')
        ->assertSee('30 items');

    $stored = WarehouseProduct::query()->where('product_id', $product['id'])->first();

    expect($stored)->not->toBeNull()
        ->and($stored->name)->toBe('Renamed Warehouse Item')
        ->and((float) $stored->price)->toBe(33.0)
        ->and($stored->is_custom)->toBeFalse();

    $edited = collect(ProductWarehouse::groupItems('normal', 1, 'a'))->firstWhere('id', $product['id']);

    expect($edited['name'])->toBe('Renamed Warehouse Item')
        ->and((float) $edited['price'])->toBe(33.0);
});

it('deletes a product from the open day group', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = ProductWarehouse::groupItems('normal', 1, 'a')[0];

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleDay', 1)
        ->call('toggleGroup', 'a')
        ->call('deleteProduct', $product['id'])
        ->assertSee('29 items')
        ->assertSee('59 products')
        ->assertDontSee($product['id']);

    expect(WarehouseProduct::query()->where('product_id', $product['id'])->exists())->toBeFalse()
        ->and(ProductWarehouse::groupItems('normal', 1, 'a'))->toHaveCount(29);
});

it('permanently removes an added product when deleted', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $custom = WarehouseProduct::factory()->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleDay', 1)
        ->call('toggleGroup', 'a')
        ->call('deleteProduct', $custom->product_id)
        ->assertSee('30 items');

    expect(WarehouseProduct::query()->whereKey($custom->id)->exists())->toBeFalse()
        ->and(ProductWarehouse::groupItems('normal', 1, 'a'))->toHaveCount(30);
});

it('validates the product form before saving', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleDay', 1)
        ->call('toggleGroup', 'a')
        ->call('openAddProduct')
        ->set('productName', '')
        ->set('productPrice', 'free')
        ->call('saveProduct')
        ->assertHasErrors(['productName', 'productPrice'])
        ->assertSet('showProductModal', true);

    expect(WarehouseProduct::query()->where('name', 'Attempted Product')->exists())->toBeFalse();
});

it('forbids non admins from changing warehouse products', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $custom = WarehouseProduct::factory()->create(['level' => 'normal', 'day' => 1, 'group_key' => 'a']);

    Livewire::actingAs($user)
        ->test(Warehouse::class)
        ->call('toggleDay', 1)
        ->call('toggleGroup', 'a')
        ->call('deleteProduct', $custom->product_id)
        ->assertForbidden();

    Livewire::actingAs($user)
        ->test(Warehouse::class)
        ->call('toggleDay', 1)
        ->call('toggleGroup', 'a')
        ->call('openAddProduct')
        ->set('productName', 'Attempted Product')
        ->set('productPrice', '10')
        ->call('saveProduct')
        ->assertForbidden();

    expect(WarehouseProduct::query()->whereKey($custom->id)->exists())->toBeTrue()
        ->and(WarehouseProduct::query()->where('name', 'Attempted Product')->exists())->toBeFalse();
});

it('keeps exclusive combo edits scoped to their membership level', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('selectLevel', 'silver')
        ->call('toggleExclusive')
        ->call('openEditExclusive', 'ps1')
        ->set('exclusiveName', 'Repackaged Perfume Set')
        ->set('exclusivePrice', '200')
        ->call('saveExclusive')
        ->assertHasNoErrors()
        ->assertSee('Repackaged Perfume Set');

    $silver = collect(ProductWarehouse::exclusiveProducts('silver'))->firstWhere('id', 'ps1');
    $normal = collect(ProductWarehouse::exclusiveProducts('normal'))->firstWhere('id', 'pn1');

    expect($silver['name'])->toBe('Repackaged Perfume Set')
        ->and((float) $silver['price'])->toBe(200.0)
        ->and($normal['name'])->toBe('Deluxe Tea Sampler')
        ->and((float) $normal['price'])->toBe(35.0)
        ->and(WarehouseExclusiveProduct::query()->where('exclusive_id', 'ps1')->value('level'))->toBe('silver');
});

it('adds an exclusive premium combo to the open level', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleExclusive')
        ->call('openAddExclusive')
        ->assertSet('showExclusiveModal', true)
        ->assertSet('editingExclusiveId', null)
        ->assertSet('exclusiveName', '')
        ->set('exclusiveName', 'Cast Iron Skillet')
        ->set('exclusiveImage', 'https://example.com/skillet.jpg')
        ->set('exclusivePrice', '65')
        ->set('exclusiveName2', 'Chef Knife Set')
        ->set('exclusiveImage2', 'https://example.com/knives.jpg')
        ->set('exclusivePrice2', '90')
        ->set('exclusiveCommission', '6')
        ->set('exclusiveDescription', 'Professional kitchen duo.')
        ->call('saveExclusive')
        ->assertHasNoErrors()
        ->assertSet('showExclusiveModal', false)
        ->assertSee('Cast Iron Skillet')
        ->assertSee('6 products');

    $combo = WarehouseExclusiveProduct::query()->where('name', 'Cast Iron Skillet')->first();

    expect($combo)->not->toBeNull()
        ->and($combo->is_custom)->toBeTrue()
        ->and($combo->level)->toBe('normal')
        ->and($combo->exclusive_id)->toStartWith('custom-normal-')
        ->and($combo->name2)->toBe('Chef Knife Set')
        ->and((float) $combo->price)->toBe(65.0)
        ->and((float) $combo->price2)->toBe(90.0)
        ->and((float) $combo->commission)->toBe(6.0);

    $items = ProductWarehouse::exclusiveProducts('normal');

    expect($items)->toHaveCount(6)
        ->and($items[array_key_last($items)]['name'])->toBe('Cast Iron Skillet')
        ->and($items[array_key_last($items)]['name2'])->toBe('Chef Knife Set')
        ->and($items[array_key_last($items)]['image_url'])->toBe('https://example.com/skillet.jpg')
        ->and($items[array_key_last($items)]['image_url2'])->toBe('https://example.com/knives.jpg');
});

it('edits a generated exclusive premium combo', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleExclusive')
        ->call('openEditExclusive', 'pn1')
        ->assertSet('showExclusiveModal', true)
        ->assertSet('editingExclusiveId', 'pn1')
        ->assertSet('exclusiveName', 'Deluxe Tea Sampler')
        ->assertSet('exclusiveName2', 'Bamboo Kitchen Set')
        ->assertSet('exclusivePrice', '35')
        ->assertSet('exclusivePrice2', '42')
        ->assertSet('exclusiveCommission', '5')
        ->set('exclusiveName', 'Grand Tea Collection')
        ->set('exclusivePrice', '40')
        ->set('exclusiveCommission', '6')
        ->call('saveExclusive')
        ->assertHasNoErrors()
        ->assertSet('showExclusiveModal', false)
        ->assertSee('Grand Tea Collection')
        ->assertSee('Profit: $2.40')
        ->assertSee('6% commission');

    $combo = WarehouseExclusiveProduct::query()->where('exclusive_id', 'pn1')->first();

    expect($combo)->not->toBeNull()
        ->and($combo->is_custom)->toBeFalse()
        ->and($combo->level)->toBe('normal')
        ->and($combo->name)->toBe('Grand Tea Collection')
        ->and((float) $combo->price)->toBe(40.0)
        ->and((float) $combo->commission)->toBe(6.0);

    $edited = collect(ProductWarehouse::exclusiveProducts('normal'))->firstWhere('id', 'pn1');

    expect($edited['name'])->toBe('Grand Tea Collection')
        ->and($edited['name2'])->toBe('Bamboo Kitchen Set')
        ->and((float) $edited['price'])->toBe(40.0)
        ->and((float) $edited['commission'])->toBe(6.0);
});

it('deletes a generated exclusive premium combo', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleExclusive')
        ->call('deleteExclusive', 'pn1')
        ->assertSee('4 products')
        ->assertDontSee('Deluxe Tea Sampler')
        ->assertSee('Aromatherapy Diffuser');

    expect(WarehouseExclusiveProduct::query()->where('exclusive_id', 'pn1')->value('is_deleted'))->toBeTrue()
        ->and(ProductWarehouse::exclusiveProducts('normal'))->toHaveCount(4);
});

it('permanently removes an added exclusive combo when deleted', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $combo = WarehouseExclusiveProduct::factory()->create(['level' => 'normal']);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleExclusive')
        ->call('deleteExclusive', $combo->exclusive_id)
        ->assertSee('5 products');

    expect(WarehouseExclusiveProduct::query()->whereKey($combo->id)->exists())->toBeFalse()
        ->and(ProductWarehouse::exclusiveProducts('normal'))->toHaveCount(5);
});

it('shows the empty state when every exclusive combo is deleted', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    foreach (ProductWarehouse::exclusiveProducts('normal') as $combo) {
        WarehouseExclusiveProduct::factory()->create([
            'exclusive_id' => $combo['id'],
            'level' => 'normal',
            'is_custom' => false,
            'is_deleted' => true,
        ]);
    }

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleExclusive')
        ->assertSee('0 products')
        ->assertSee('No premium products for Normal level.');
});

it('validates the exclusive combo form before saving', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleExclusive')
        ->call('openAddExclusive')
        ->set('exclusiveName', '')
        ->set('exclusiveName2', '')
        ->set('exclusivePrice', 'free')
        ->set('exclusivePrice2', '')
        ->set('exclusiveCommission', '-2')
        ->call('saveExclusive')
        ->assertHasErrors(['exclusiveName', 'exclusiveName2', 'exclusivePrice', 'exclusivePrice2', 'exclusiveCommission'])
        ->assertSet('showExclusiveModal', true);

    expect(WarehouseExclusiveProduct::query()->count())->toBe(0);
});

it('forbids non admins from changing exclusive products', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $combo = WarehouseExclusiveProduct::factory()->create(['level' => 'normal']);

    Livewire::actingAs($user)
        ->test(Warehouse::class)
        ->call('toggleExclusive')
        ->call('deleteExclusive', $combo->exclusive_id)
        ->assertForbidden();

    Livewire::actingAs($user)
        ->test(Warehouse::class)
        ->call('toggleExclusive')
        ->call('openAddExclusive')
        ->set('exclusiveName', 'Attempted Combo')
        ->set('exclusiveName2', 'Second Item')
        ->set('exclusivePrice', '10')
        ->set('exclusivePrice2', '20')
        ->set('exclusiveCommission', '5')
        ->call('saveExclusive')
        ->assertForbidden();

    expect(WarehouseExclusiveProduct::query()->whereKey($combo->id)->exists())->toBeTrue()
        ->and(WarehouseExclusiveProduct::query()->where('name', 'Attempted Combo')->exists())->toBeFalse();
});
