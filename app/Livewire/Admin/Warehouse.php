<?php

namespace App\Livewire\Admin;

use App\Models\WarehouseExclusiveProduct;
use App\Models\WarehouseProduct;
use App\Support\ProductWarehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.dashboard')]
class Warehouse extends Component
{
    use WithFileUploads;

    public string $level = 'normal';

    public ?int $openDay = null;

    public ?string $openGroup = null;

    public bool $openExclusive = false;

    public bool $showProductModal = false;

    public ?string $editingProductId = null;

    public string $productName = '';

    public string $productImage = '';

    public string $productDescription = '';

    public string $productPrice = '';

    public string $productCommission = '';

    public $productImageUpload;

    public bool $showExclusiveModal = false;

    public ?string $editingExclusiveId = null;

    public string $exclusiveName = '';

    public string $exclusiveImage = '';

    public string $exclusiveName2 = '';

    public string $exclusiveImage2 = '';

    public string $exclusivePrice = '';

    public string $exclusivePrice2 = '';

    public string $exclusiveCommission = '';

    public string $exclusiveDescription = '';

    public $exclusiveImageUpload;

    public $exclusiveImageUpload2;

    public function selectLevel(string $level): void
    {
        if (! ProductWarehouse::isValidLevel($level)) {
            return;
        }

        $this->level = $level;
        $this->openDay = null;
        $this->openGroup = null;
        $this->openExclusive = false;
    }

    public function toggleDay(int $day): void
    {
        if ($day < 1 || $day > ProductWarehouse::DAY_COUNT) {
            return;
        }

        $this->openDay = $this->openDay === $day ? null : $day;
        $this->openGroup = null;
    }

    public function toggleGroup(string $group): void
    {
        if (! in_array($group, ProductWarehouse::GROUPS, true)) {
            return;
        }

        $this->openGroup = $this->openGroup === $group ? null : $group;
    }

    public function toggleExclusive(): void
    {
        $this->openExclusive = ! $this->openExclusive;
    }

    public function openAddProduct(): void
    {
        if ($this->openDay === null || $this->openGroup === null) {
            return;
        }

        $this->editingProductId = null;
        $this->productName = '';
        $this->productImage = '';
        $this->productDescription = '';
        $this->productPrice = '';
        $this->productCommission = $this->formatAmount(ProductWarehouse::commission($this->level));
        $this->productImageUpload = null;
        $this->resetValidation();
        $this->showProductModal = true;
    }

    public function openEditProduct(string $productId): void
    {
        $product = $this->findProduct($productId);

        if ($product === null) {
            return;
        }

        $this->editingProductId = $productId;
        $this->productName = $product['name'];
        $this->productImage = (string) ($product['image'] ?? '');
        $this->productDescription = (string) ($product['description'] ?? '');
        $this->productPrice = $this->formatAmount((float) $product['price']);
        $this->productCommission = $this->formatAmount((float) $product['commission']);
        $this->productImageUpload = null;
        $this->resetValidation();
        $this->showProductModal = true;
    }

    public function saveProduct(): void
    {
        $this->guardAdmin();

        $this->validate([
            'productName' => 'required|string|max:255',
            'productImage' => 'nullable|string|max:2048',
            'productImageUpload' => 'nullable|image|max:4096',
            'productDescription' => 'nullable|string|max:2000',
            'productPrice' => 'required|numeric|min:0',
            'productCommission' => 'required|numeric|min:0',
        ]);

        if ($this->openDay === null || $this->openGroup === null) {
            return;
        }

        if ($this->productImageUpload) {
            $this->productImage = $this->productImageUpload->store('warehouse', 'public');
        }

        $attributes = [
            'level' => $this->level,
            'day' => $this->openDay,
            'group_key' => $this->openGroup,
            'name' => $this->productName,
            'image_url' => $this->productImage !== '' ? $this->productImage : null,
            'description' => $this->productDescription !== '' ? $this->productDescription : null,
            'price' => $this->productPrice,
            'commission' => $this->productCommission,
            'is_deleted' => false,
        ];

        if ($this->editingProductId !== null) {
            WarehouseProduct::query()
                ->firstOrNew(['product_id' => $this->editingProductId])
                ->fill($attributes)
                ->save();

            session()->flash('success-message', 'Product updated successfully');
        } else {
            WarehouseProduct::query()->create([
                'product_id' => 'custom-'.$this->level.'-d'.$this->openDay.'-'.$this->openGroup.'-'.Str::uuid(),
                'is_custom' => true,
                ...$attributes,
            ]);

            session()->flash('success-message', 'Product added successfully');
        }

        $this->closeProductModal();
    }

    public function deleteProduct(string $productId): void
    {
        $this->guardAdmin();

        if ($this->openDay === null || $this->openGroup === null) {
            return;
        }

        $product = WarehouseProduct::query()->where('product_id', $productId)->first();

        if (in_array($this->level, ['normal', 'silver', 'gold', 'platinum', 'diamond'], true) || $product?->is_custom) {
            $product->delete();
        } else {
            WarehouseProduct::query()->updateOrCreate(
                ['product_id' => $productId],
                [
                    'level' => $this->level,
                    'day' => $this->openDay,
                    'group_key' => $this->openGroup,
                    'is_deleted' => true,
                ],
            );
        }

        session()->flash('success-message', 'Product deleted successfully');
    }

    public function closeProductModal(): void
    {
        $this->showProductModal = false;
        $this->editingProductId = null;
        $this->productImageUpload = null;
    }

    public function openAddExclusive(): void
    {
        $this->editingExclusiveId = null;
        $this->exclusiveName = '';
        $this->exclusiveImage = '';
        $this->exclusiveName2 = '';
        $this->exclusiveImage2 = '';
        $this->exclusivePrice = '';
        $this->exclusivePrice2 = '';
        $this->exclusiveCommission = '';
        $this->exclusiveDescription = '';
        $this->exclusiveImageUpload = null;
        $this->exclusiveImageUpload2 = null;
        $this->resetValidation();
        $this->showExclusiveModal = true;
    }

    public function openEditExclusive(string $exclusiveId): void
    {
        $product = collect(ProductWarehouse::exclusiveProducts($this->level))
            ->firstWhere('id', $exclusiveId);

        if ($product === null) {
            return;
        }

        $this->editingExclusiveId = $exclusiveId;
        $this->exclusiveName = (string) $product['name'];
        $this->exclusiveImage = (string) ($product['image'] ?? '');
        $this->exclusiveName2 = (string) ($product['name2'] ?? '');
        $this->exclusiveImage2 = (string) ($product['image2'] ?? '');
        $this->exclusivePrice = $this->formatAmount((float) $product['price']);
        $this->exclusivePrice2 = $this->formatAmount((float) ($product['price2'] ?? 0));
        $this->exclusiveCommission = $this->formatAmount((float) $product['commission']);
        $this->exclusiveDescription = (string) ($product['description'] ?? '');
        $this->exclusiveImageUpload = null;
        $this->exclusiveImageUpload2 = null;
        $this->resetValidation();
        $this->showExclusiveModal = true;
    }

    public function saveExclusive(): void
    {
        $this->guardAdmin();

        if ($this->editingExclusiveId === null && $this->exclusiveCommission === '') {
            $this->exclusiveCommission = '5';
        }

        $this->validate([
            'exclusiveName' => 'required|string|max:255',
            'exclusiveName2' => 'required|string|max:255',
            'exclusiveImage' => 'nullable|string|max:2048',
            'exclusiveImage2' => 'nullable|string|max:2048',
            'exclusiveImageUpload' => 'nullable|image|max:4096',
            'exclusiveImageUpload2' => 'nullable|image|max:4096',
            'exclusivePrice' => 'required|numeric|min:0',
            'exclusivePrice2' => 'required|numeric|min:0',
            'exclusiveCommission' => 'required|numeric|min:0',
            'exclusiveDescription' => 'nullable|string|max:2000',
        ]);

        if ($this->exclusiveImageUpload) {
            $this->exclusiveImage = $this->exclusiveImageUpload->store('warehouse', 'public');
        }

        if ($this->exclusiveImageUpload2) {
            $this->exclusiveImage2 = $this->exclusiveImageUpload2->store('warehouse', 'public');
        }

        $attributes = [
            'level' => $this->level,
            'name' => $this->exclusiveName,
            'image_url' => $this->exclusiveImage !== '' ? $this->exclusiveImage : null,
            'price' => $this->exclusivePrice,
            'name2' => $this->exclusiveName2,
            'image_url2' => $this->exclusiveImage2 !== '' ? $this->exclusiveImage2 : null,
            'price2' => $this->exclusivePrice2,
            'commission' => $this->exclusiveCommission,
            'description' => $this->exclusiveDescription !== '' ? $this->exclusiveDescription : null,
            'is_deleted' => false,
        ];

        if ($this->editingExclusiveId !== null) {
            WarehouseExclusiveProduct::query()
                ->firstOrNew(['exclusive_id' => $this->editingExclusiveId])
                ->fill($attributes)
                ->save();

            session()->flash('success-message', 'Premium product updated');
        } else {
            WarehouseExclusiveProduct::query()->create([
                'exclusive_id' => 'custom-'.$this->level.'-'.Str::uuid(),
                'is_custom' => true,
                ...$attributes,
            ]);

            session()->flash('success-message', 'Premium product added');
        }

        $this->closeExclusiveModal();
    }

    public function deleteExclusive(string $exclusiveId): void
    {
        $this->guardAdmin();

        $product = WarehouseExclusiveProduct::query()
            ->where('level', $this->level)
            ->where('exclusive_id', $exclusiveId)
            ->first();

        if ($product?->is_custom) {
            $product->delete();
        } else {
            WarehouseExclusiveProduct::query()->updateOrCreate(
                ['exclusive_id' => $exclusiveId],
                [
                    'level' => $this->level,
                    'is_deleted' => true,
                ],
            );
        }

        session()->flash('success-message', 'Premium product removed');
    }

    public function closeExclusiveModal(): void
    {
        $this->showExclusiveModal = false;
        $this->editingExclusiveId = null;
        $this->exclusiveImageUpload = null;
        $this->exclusiveImageUpload2 = null;
    }

    public function render(): View
    {
        $counts = ProductWarehouse::groupCounts($this->level);

        $days = [];

        for ($day = 1; $day <= ProductWarehouse::DAY_COUNT; $day++) {
            $days[$day] = [
                'count' => $counts[$day]['a'] + $counts[$day]['b'],
                'groups' => [
                    'a' => ['count' => $counts[$day]['a'], 'items' => $this->groupItems($day, 'a')],
                    'b' => ['count' => $counts[$day]['b'], 'items' => $this->groupItems($day, 'b')],
                ],
            ];
        }

        return view('livewire.admin.warehouse', [
            'levels' => ProductWarehouse::levels(),
            'days' => $days,
            'levelLabel' => ProductWarehouse::label($this->level),
            'exclusiveProducts' => ProductWarehouse::exclusiveProducts($this->level),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findProduct(string $productId): ?array
    {
        if ($this->openDay === null || $this->openGroup === null) {
            return null;
        }

        foreach (ProductWarehouse::groupItems($this->level, $this->openDay, $this->openGroup) as $product) {
            if ($product['id'] === $productId) {
                return $product;
            }
        }

        return null;
    }

    private function guardAdmin(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);
    }

    private function formatAmount(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function groupItems(int $day, string $group): array
    {
        if ($this->openDay !== $day || $this->openGroup !== $group) {
            return [];
        }

        return ProductWarehouse::groupItems($this->level, $day, $group);
    }
}
