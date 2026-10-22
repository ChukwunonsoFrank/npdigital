<div>
    <div class="space-y-4 animate-fade-in">

        <div class="flex items-center justify-between">
            <h1 class="font-display text-2xl font-bold text-foreground">Product Warehouse</h1>
        </div>

        @if (session()->has('success-message'))
            <div wire:key="warehouse-success-{{ now()->timestamp }}" x-data="{ show: true }" x-show="show"
                x-init="setTimeout(() => { show = false }, 4000)" x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                {{ session('success-message') }}
            </div>
        @endif

        @if (session()->has('error-message'))
            <div wire:key="warehouse-error-{{ now()->timestamp }}" x-data="{ show: true }" x-show="show"
                x-init="setTimeout(() => { show = false }, 4000)" x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm font-medium text-destructive">
                {{ session('error-message') }}
            </div>
        @endif

        <div class="flex min-h-10 w-full flex-wrap items-center justify-center gap-1 rounded-md bg-muted p-1 text-muted-foreground"
            role="tablist" aria-label="Membership level">
            @foreach ($levels as $levelOption)
                <button type="button" role="tab" wire:key="level-{{ $levelOption['value'] }}"
                    wire:click="selectLevel('{{ $levelOption['value'] }}')"
                    aria-selected="{{ $level === $levelOption['value'] ? 'true' : 'false' }}"
                    @class([
                        'inline-flex flex-1 basis-[80px] items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-xs font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                        'bg-card text-foreground shadow-sm' => $level === $levelOption['value'],
                        'text-muted-foreground hover:text-foreground' => $level !== $levelOption['value'],
                    ])>
                    {{ $levelOption['label'] }}
                </button>
            @endforeach
        </div>

        <div class="space-y-2">
            @foreach ($days as $day => $dayData)
                <div wire:key="day-{{ $level }}-{{ $day }}">
                    <button type="button" wire:click="toggleDay({{ $day }})"
                        aria-expanded="{{ $openDay === $day ? 'true' : 'false' }}"
                        @class([
                            'inline-flex h-10 w-full items-center justify-between gap-2 whitespace-nowrap rounded-md px-4 py-2 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                            'bg-primary text-primary-foreground hover:bg-primary/90' => $openDay === $day,
                            'border border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground' => $openDay !== $day,
                        ])>
                        <span>Day {{ $day }}</span>
                        <span class="text-xs opacity-70">{{ $dayData['count'] }} products</span>
                    </button>

                    @if ($openDay === $day)
                        <div class="space-y-2 pl-4 pt-2">
                            @foreach ($dayData['groups'] as $group => $groupData)
                                <div wire:key="group-{{ $level }}-{{ $day }}-{{ $group }}">
                                    <button type="button" wire:click="toggleGroup('{{ $group }}')"
                                        aria-expanded="{{ $openGroup === $group ? 'true' : 'false' }}"
                                        @class([
                                            'inline-flex h-9 w-full items-center justify-between gap-2 whitespace-nowrap rounded-md px-3 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                                            'bg-secondary text-secondary-foreground hover:bg-secondary/80' => $openGroup === $group,
                                            'text-foreground hover:bg-accent hover:text-accent-foreground' => $openGroup !== $group,
                                        ])>
                                        <span>Group {{ strtoupper($group) }}</span>
                                        <span
                                            class="inline-flex items-center rounded-full border border-border px-2.5 py-0.5 text-[10px] font-semibold text-foreground">{{ $groupData['count'] }} items</span>
                                    </button>

                                    @if ($openGroup === $group)
                                        <div class="pb-2 pt-3">
                                            <div class="mb-3 flex flex-wrap items-center justify-end gap-2">
                                                <button type="button" wire:click="openAddProduct"
                                                    class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                                    <x-dashboard.icon name="plus" class="h-3.5 w-3.5" /> Add Product
                                                </button>
                                            </div>

                                            <div
                                                class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                                @foreach ($groupData['items'] as $product)
                                                    <div wire:key="{{ $product['id'] }}"
                                                        class="relative flex flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                                                        <div
                                                            class="relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-muted">
                                                            <x-dashboard.icon name="image"
                                                                class="h-10 w-10 text-muted-foreground/40" />
                                                            @if ($product['image_url'])
                                                                <img src="{{ $product['image_url'] }}"
                                                                    alt="{{ $product['name'] }}" loading="lazy"
                                                                    class="absolute inset-0 h-full w-full object-cover">
                                                                <x-product-brand-logo :brand="$product['brand']" size="sm" />
                                                            @endif
                                                        </div>
                                                        <div class="flex-1 space-y-1 p-3">
                                                            <p class="truncate text-sm font-semibold text-foreground"
                                                                title="{{ $product['name'] }}">{{ $product['name'] }}
                                                            </p>
                                                            <p class="text-sm font-bold text-primary">
                                                                ${{ number_format($product['price']) }}</p>
                                                            <p class="text-xs font-medium text-[#2bab8b] dark:text-emerald-400">
                                                                {{ $product['commission'] }}% comm</p>
                                                            <div
                                                                class="flex items-center gap-1 text-xs text-muted-foreground">
                                                                <x-dashboard.icon name="users" class="h-3 w-3" />
                                                                {{ $product['assigned'] }}/{{ $product['total'] }}
                                                            </div>
                                                        </div>
                                                        <div class="flex items-stretch border-t border-border">
                                                            <button type="button"
                                                                wire:click="openEditProduct('{{ $product['id'] }}')"
                                                                class="flex h-9 flex-1 items-center justify-center text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                                aria-label="Edit {{ $product['name'] }}">
                                                                <x-dashboard.icon name="pencil" class="h-3.5 w-3.5" />
                                                            </button>
                                                            <div class="w-px bg-border"></div>
                                                            <button type="button"
                                                                wire:click="deleteProduct('{{ $product['id'] }}')"
                                                                wire:confirm="Delete this product? This cannot be undone."
                                                                class="flex h-9 flex-1 items-center justify-center text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                                aria-label="Delete {{ $product['name'] }}">
                                                                <x-dashboard.icon name="trash-2" class="h-3.5 w-3.5" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div>
            <button type="button" wire:click="toggleExclusive"
                aria-expanded="{{ $openExclusive ? 'true' : 'false' }}"
                class="inline-flex h-10 w-full items-center justify-between gap-2 whitespace-nowrap rounded-md border border-input bg-background px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                <span>⭐ Exclusive Products — {{ $levelLabel }}</span>
                <span class="text-xs opacity-70">{{ count($exclusiveProducts) }} products</span>
            </button>

            @if ($openExclusive)
                <div class="space-y-3 pt-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-muted-foreground">High-value products assigned during task creation
                        </p>
                        <button type="button" wire:click="openAddExclusive"
                            class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            <x-dashboard.icon name="plus" class="h-3 w-3" /> Add Exclusive
                        </button>
                    </div>

                    @if (count($exclusiveProducts) === 0)
                        <div class="rounded-xl border border-border py-8 text-center text-sm text-muted-foreground">
                            No premium products for {{ $levelLabel }} level.
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                            @foreach ($exclusiveProducts as $product)
                                <div wire:key="{{ $product['id'] }}"
                                    class="flex flex-col overflow-hidden rounded-xl border-2 border-amber-400/30 bg-gradient-to-b from-amber-50/50 to-card shadow-sm dark:from-amber-950/20">
                                    <span
                                        class="mx-3 mt-3 inline-flex w-fit items-center rounded-full bg-amber-500 px-2.5 py-0.5 text-[9px] font-semibold text-white">EXCLUSIVE</span>

                                    <div class="flex items-start gap-3 px-3 pb-2 pt-2">
                                        <div
                                            class="relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted">
                                            <x-dashboard.icon name="image"
                                                class="h-8 w-8 text-muted-foreground/40" />
                                            @if ($product['image_url'])
                                                <img src="{{ $product['image_url'] }}"
                                                    alt="{{ $product['name'] }}" loading="lazy"
                                                    class="h-full w-full object-cover">
                                                <x-product-brand-logo :brand="$product['brand']" size="sm" />
                                            @endif
                                        </div>

                                        <div class="min-w-0 flex-1 space-y-0.5">
                                            <p class="truncate text-sm font-semibold text-foreground"
                                                title="{{ $product['name'] }}">{{ $product['name'] }}</p>
                                            <p class="text-sm font-bold text-primary">
                                                ${{ rtrim(rtrim(number_format((float) $product['price'], 2, '.', ','), '0'), '.') }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">Profit: ${{ number_format($product['price'] * $product['commission'] / 100, 2) }}</p>
                                        </div>
                                    </div>

                                    <div class="px-3 pb-2">
                                        <p class="text-xs font-medium text-amber-600 dark:text-amber-400">
                                            {{ $product['commission'] }}% commission</p>
                                    </div>

                                    <div class="mt-auto flex items-stretch border-t border-amber-400/30">
                                        <button type="button"
                                            wire:click="openEditExclusive('{{ $product['id'] }}')"
                                            class="flex flex-1 items-center justify-center py-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <x-dashboard.icon name="pencil" class="mr-1 h-3.5 w-3.5" /> Edit
                                        </button>
                                        <button type="button"
                                            wire:click="deleteExclusive('{{ $product['id'] }}')"
                                            wire:confirm="Remove this premium product? This cannot be undone."
                                            class="flex flex-1 items-center justify-center border-l border-amber-400/30 py-2 text-xs font-medium text-destructive transition-colors hover:bg-destructive/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <x-dashboard.icon name="trash-2" class="mr-1 h-3.5 w-3.5" /> Delete
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($showProductModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" role="presentation"
            wire:click.self="closeProductModal">
            <section role="dialog" aria-modal="true" aria-labelledby="product-modal-title"
                class="relative max-h-[90vh] w-full max-w-sm overflow-y-auto rounded-lg border border-border bg-card p-6 shadow-lg">
                <div class="flex flex-col space-y-1.5 text-center sm:text-left">
                    <h2 id="product-modal-title" class="text-lg font-semibold leading-none tracking-tight text-foreground">
                        {{ $editingProductId ? 'Edit Product' : 'Add Product' }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ $editingProductId ? 'Update product details' : 'Day '.$openDay.', Group '.strtoupper((string) $openGroup).' — '.$levelLabel }}
                    </p>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="space-y-1">
                        <label for="product-name" class="text-xs font-medium text-foreground">Product Name</label>
                        <input id="product-name" type="text" wire:model="productName" placeholder="Product name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                        @error('productName')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="product-image"
                            class="text-xs font-medium text-foreground">{{ $editingProductId ? 'Poster Photo/Video URL' : 'Image URL' }}</label>
                        <input id="product-image" type="text" wire:model="productImage" placeholder="https://..."
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                        @error('productImage')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror

                        <label
                            class="mt-2 inline-flex h-9 w-full cursor-pointer items-center justify-center rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                            Upload Image
                            <input type="file" accept="image/*" class="hidden" wire:model="productImageUpload">
                        </label>
                        <div wire:loading wire:target="productImageUpload" class="text-xs text-muted-foreground">
                            Uploading…
                        </div>
                        @if ($productImageUpload)
                            <p class="truncate text-xs text-muted-foreground">
                                {{ $productImageUpload->getClientOriginalName() }}
                            </p>
                        @endif
                        @error('productImageUpload')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="product-description" class="text-xs font-medium text-foreground">Description</label>
                        <textarea id="product-description" rows="3" wire:model="productDescription"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"></textarea>
                        @error('productDescription')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label for="product-price"
                                class="text-xs font-medium text-foreground">{{ $editingProductId ? 'Amount ($)' : 'Price ($)' }}</label>
                            <input id="product-price" type="number" min="0" step="0.01" wire:model.live="productPrice"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                            @error('productPrice')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1">
                            <label for="product-commission" class="text-xs font-medium text-foreground">Commission
                                (%)</label>
                            <input id="product-commission" type="number" min="0" step="0.1"
                                wire:model.live="productCommission"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                            @error('productCommission')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if ($editingProductId)
                        <p class="text-xs font-medium text-[#2bab8b] dark:text-emerald-400">
                            Profit:
                            ${{ number_format(((float) ($productPrice ?: 0)) * ((float) ($productCommission ?: 0)) / 100, 2) }}
                        </p>
                    @endif
                </div>

                <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    @if ($editingProductId)
                        <button type="button" wire:click="saveProduct" wire:loading.attr="disabled"
                            wire:target="saveProduct"
                            class="inline-flex h-10 w-full items-center justify-center whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                            Save Changes
                        </button>
                    @else
                        <button type="button" wire:click="closeProductModal"
                            class="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            Cancel
                        </button>
                        <button type="button" wire:click="saveProduct" wire:loading.attr="disabled"
                            wire:target="saveProduct"
                            class="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                            Add Product
                        </button>
                    @endif
                </div>

                <button type="button" wire:click="closeProductModal"
                    class="absolute right-4 top-4 rounded-sm text-muted-foreground opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                    <span class="sr-only">Close</span>
                </button>
            </section>
        </div>
    @endif

    @if ($showExclusiveModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4" role="presentation"
            wire:click.self="closeExclusiveModal">
            <section role="dialog" aria-modal="true" aria-labelledby="exclusive-modal-title"
                class="relative max-h-[85vh] w-full max-w-md overflow-y-auto rounded-lg border border-border bg-card p-6 shadow-lg">
                <div class="flex flex-col space-y-1.5 text-center sm:text-left">
                    <h2 id="exclusive-modal-title"
                        class="text-lg font-semibold leading-none tracking-tight text-foreground">
                        {{ $editingExclusiveId ? 'Edit Premium Combo' : 'Add Premium Combo' }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ $editingExclusiveId ? 'Update both products in this combo' : $levelLabel.' — Two products submitted as one package' }}
                    </p>
                </div>

                <div class="mt-4 space-y-4">
                    <p class="text-xs font-bold uppercase text-amber-600 dark:text-amber-400">Product 1</p>

                    <div class="space-y-1">
                        <label for="exclusive-name" class="text-xs font-medium text-foreground">Name</label>
                        <input id="exclusive-name" type="text" wire:model="exclusiveName" placeholder="Product 1 name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                        @error('exclusiveName')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="exclusive-image" class="text-xs font-medium text-foreground">Image</label>
                        <input id="exclusive-image" type="text" wire:model="exclusiveImage" placeholder="https://..."
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                        @error('exclusiveImage')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror

                        <label
                            class="mt-2 inline-flex h-9 w-full cursor-pointer items-center justify-center rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                            Upload Image
                            <input type="file" accept="image/*" class="hidden" wire:model="exclusiveImageUpload">
                        </label>
                        <div wire:loading wire:target="exclusiveImageUpload" class="text-xs text-muted-foreground">
                            Uploading…
                        </div>
                        @if ($exclusiveImageUpload)
                            <p class="truncate text-xs text-muted-foreground">
                                {{ $exclusiveImageUpload->getClientOriginalName() }}
                            </p>
                        @endif
                        @error('exclusiveImageUpload')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="exclusive-price" class="text-xs font-medium text-foreground">Price ($)</label>
                        <input id="exclusive-price" type="number" min="0" step="0.01"
                            wire:model.live="exclusivePrice"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                        @error('exclusivePrice')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($editingExclusiveId)
                        <p class="text-xs text-muted-foreground">
                            Profit:
                            ${{ number_format(((float) ($exclusivePrice ?: 0)) * ((float) ($exclusiveCommission ?: 0)) / 100, 2) }}
                        </p>
                    @endif

                    <div class="border-t pt-3"></div>

                    <p class="text-xs font-bold uppercase text-amber-600 dark:text-amber-400">Product 2</p>

                    <div class="space-y-1">
                        <label for="exclusive-name2" class="text-xs font-medium text-foreground">Name</label>
                        <input id="exclusive-name2" type="text" wire:model="exclusiveName2"
                            placeholder="Product 2 name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                        @error('exclusiveName2')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="exclusive-image2" class="text-xs font-medium text-foreground">Image</label>
                        <input id="exclusive-image2" type="text" wire:model="exclusiveImage2"
                            placeholder="https://..."
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                        @error('exclusiveImage2')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror

                        <label
                            class="mt-2 inline-flex h-9 w-full cursor-pointer items-center justify-center rounded-md border border-input bg-background px-3 text-xs font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                            Upload Image
                            <input type="file" accept="image/*" class="hidden" wire:model="exclusiveImageUpload2">
                        </label>
                        <div wire:loading wire:target="exclusiveImageUpload2" class="text-xs text-muted-foreground">
                            Uploading…
                        </div>
                        @if ($exclusiveImageUpload2)
                            <p class="truncate text-xs text-muted-foreground">
                                {{ $exclusiveImageUpload2->getClientOriginalName() }}
                            </p>
                        @endif
                        @error('exclusiveImageUpload2')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="exclusive-price2" class="text-xs font-medium text-foreground">Price ($)</label>
                        <input id="exclusive-price2" type="number" min="0" step="0.01"
                            wire:model="exclusivePrice2"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                        @error('exclusivePrice2')
                            <p class="text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="border-t pt-3"></div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label for="exclusive-commission" class="text-xs font-medium text-foreground">Commission %
                                (shared)</label>
                            <input id="exclusive-commission" type="number" min="0" step="0.1"
                                wire:model.live="exclusiveCommission"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                            @error('exclusiveCommission')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1">
                            <label for="exclusive-description"
                                class="text-xs font-medium text-foreground">Description</label>
                            <input id="exclusive-description" type="text" wire:model="exclusiveDescription"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm">
                            @error('exclusiveDescription')
                                <p class="text-xs text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    @if ($editingExclusiveId)
                        <button type="button" wire:click="saveExclusive" wire:loading.attr="disabled"
                            wire:target="saveExclusive"
                            class="inline-flex h-10 w-full items-center justify-center whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                            Save Changes
                        </button>
                    @else
                        <button type="button" wire:click="closeExclusiveModal"
                            class="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            Cancel
                        </button>
                        <button type="button" wire:click="saveExclusive" wire:loading.attr="disabled"
                            wire:target="saveExclusive"
                            class="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                            Add Combo
                        </button>
                    @endif
                </div>

                <button type="button" wire:click="closeExclusiveModal"
                    class="absolute right-4 top-4 rounded-sm text-muted-foreground opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                    <x-dashboard.icon name="x" class="h-4 w-4" />
                    <span class="sr-only">Close</span>
                </button>
            </section>
        </div>
    @endif
</div>
