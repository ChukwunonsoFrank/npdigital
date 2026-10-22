<div>
    <!-- App Capsule -->
    <div id="appCapsule">
        @if ($task)
            <div class="section mt-2">
                <div class="card">
                    <div class="relative">
                        <img src="{{ \App\Support\ProductWarehouse::imageUrl($task->task_image_path) }}"
                            class="card-img-top" alt="{{ $task->title }}">
                        <x-product-brand-logo :brand="$task->brand" />
                    </div>
                    <div class="card-body">
                        <h6 class="card-title text-black text-center mb-3 fs-6">{{ $task->title }}</h6>
                        <h5 class="card-title text-black text-center font-bold mb-3">Rate Us Now</h5>

                        <div class="flex justify-center gap-1 mb-3">
                            @for ($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="setRating({{ $i }})" class="border-0 bg-transparent p-0" style="cursor: pointer; font-size: 2rem; color: {{ $i <= $rating ? '#F5A623' : '#D1D5DB' }};">
                                    ★
                                </button>
                            @endfor
                        </div>

                        <div class="flex items-center space-x-2 py-2 border-b border-gray-200 mb-2">
                            <div class="flex-1">
                                <p class="text-gray-600 text-xs mb-0">Total Amount</p>
                            </div>
                            <div class="flex-none text-end text-black font-medium text-sm">{{ number_format($task->cost, 2) }} USDT</div>
                        </div>

                        <div class="flex items-center space-x-2 py-2 border-b border-gray-200 mb-2">
                            <div class="flex-1">
                                <p class="text-gray-600 text-xs mb-0">Commission</p>
                            </div>
                            <div class="flex-none text-end text-black font-medium text-sm">{{ number_format($commission, 2) }} USDT</div>
                        </div>

                        <div class="flex items-center space-x-2 py-2 border-b border-gray-200 mb-2">
                            <div class="flex-1">
                                <p class="text-gray-600 text-xs mb-0">Creation Time</p>
                            </div>
                            <div class="flex-none text-end text-black font-medium text-sm">{{ $task->created_at->format('Y-m-d h:i A') }}</div>
                        </div>

                        <div class="flex items-center space-x-2 py-2 border-b border-gray-200 mb-2">
                            <div class="flex-1">
                                <p class="text-gray-600 text-xs mb-0">Rating ID</p>
                            </div>
                            <div class="flex-none text-end text-black font-medium text-sm">{{ $task->rating_id }}</div>
                        </div>

                        <div class="mt-4">
                            <button type="button" class="btn btn-primary btn-lg btn-block" wire:click="submitTask">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <!-- * App Capsule -->
</div>

@script
    <script>
        $wire.on('upgrade-required', (event) => {
            if (window.showToast) {
                window.showToast(event.message);
            }
        });
    </script>
@endscript
