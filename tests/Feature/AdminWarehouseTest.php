<?php

declare(strict_types=1);

use App\Livewire\Admin\Warehouse;
use App\Models\User;
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
        ->assertDontSee('Group A');
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

it('renders the warehouse page with day counts for the normal level', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->assertSet('level', 'normal')
        ->assertSet('openDay', null)
        ->assertSet('openGroup', null)
        ->assertSee('60 products')
        ->assertDontSee('Group A')
        ->assertDontSee('Canvas Sneakers');
});

it('switches to other membership levels and shows their product counts', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('selectLevel', 'silver')
        ->assertSet('level', 'silver')
        ->assertSee('60 products')
        ->call('selectLevel', 'diamond')
        ->assertSet('level', 'diamond')
        ->assertSee('60 products');
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

it('builds the warehouse product data for every membership level', function () {
    $expectations = [
        'normal' => ['commission' => 0.5, 'min' => 28, 'max' => 38],
        'silver' => ['commission' => 0.75, 'min' => 42, 'max' => 54],
        'gold' => ['commission' => 1.0, 'min' => 62, 'max' => 75],
        'platinum' => ['commission' => 1.25, 'min' => 62, 'max' => 71],
        'diamond' => ['commission' => 1.5, 'min' => 60, 'max' => 66],
    ];

    foreach ($expectations as $level => $expected) {
        $items = ProductWarehouse::groupItems($level, 1, 'a');

        expect($items)->toHaveCount(ProductWarehouse::PRODUCTS_PER_SET)
            ->and(array_sum(ProductWarehouse::groupCounts($level)[1]))->toBe(ProductWarehouse::PRODUCTS_PER_SET * 2);

        foreach ($items as $item) {
            expect($item['price'])->toBeGreaterThanOrEqual($expected['min'])
                ->and($item['price'])->toBeLessThanOrEqual($expected['max'])
                ->and($item['commission'])->toBe($expected['commission'])
                ->and($item['id'])->toStartWith("prod-{$level}-d1-a-")
                ->and($item['name'])->not->toBe('')
                ->and($item['brand'])->toBeIn(ProductBrand::BRANDS);
        }

    }
});

it('shows an empty group c with an add product action for every day', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $component = Livewire::actingAs($admin)->test(Warehouse::class);

    foreach (range(1, ProductWarehouse::DAY_COUNT) as $day) {
        expect(ProductWarehouse::groupItems('normal', $day, 'c'))->toBe([])
            ->and(ProductWarehouse::groupCounts('normal')[$day]['c'])->toBe(0);

        $component->call('toggleDay', $day)
            ->call('toggleGroup', 'c')
            ->assertSee('Group C')
            ->assertSee('No products in this group yet')
            ->assertSee('Add Product');
    }
});

it('keeps group c empty for generated membership levels', function () {
    WarehouseProduct::query()->delete();

    expect(ProductWarehouse::groupItems('normal', 1, 'c'))->toBe([])
        ->and(ProductWarehouse::groupCounts('normal')[1]['c'])->toBe(0)
        ->and(ProductWarehouse::legacyGroupItems('normal', 1, 'c'))->toBe([]);
});

it('adds the first product to group c', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(Warehouse::class)
        ->call('toggleDay', 1)
        ->call('toggleGroup', 'c')
        ->assertSee('No products in this group yet')
        ->call('openAddProduct')
        ->assertSet('showProductModal', true)
        ->set('productName', 'Group C Starter')
        ->set('productPrice', '45')
        ->set('productCommission', '0.5')
        ->call('saveProduct')
        ->assertHasNoErrors()
        ->assertSet('showProductModal', false)
        ->assertSee('Group C Starter')
        ->assertSee('1 items')
        ->assertSee('61 products');

    $product = WarehouseProduct::query()->where('name', 'Group C Starter')->first();

    expect($product)->not->toBeNull()
        ->and($product->group_key)->toBe('c')
        ->and($product->is_custom)->toBeTrue()
        ->and(ProductWarehouse::groupItems('normal', 1, 'c'))->toHaveCount(1);
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
