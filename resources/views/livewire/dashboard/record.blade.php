<div x-data>
    <div class="animate-fade-in space-y-6">
        <div>
            <h1 class="font-display text-2xl font-bold">Submission Records</h1>
            <p class="mt-1 text-sm text-muted-foreground">View all your product submissions</p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="card-elevated rounded-lg border bg-card text-card-foreground shadow-sm">
                <div class="p-6 pb-4 pt-4 text-center">
                    <div class="mb-1 flex items-center justify-center gap-1.5">
                        <x-dashboard.icon name="package" class="h-4 w-4 text-primary" />
                        <p class="text-[10px] font-bold uppercase tracking-wider text-primary">Total Submitted</p>
                    </div>
                    <p class="font-display text-2xl font-bold">{{ $totalSubmitted }}</p>
                </div>
            </div>

            <div class="card-elevated rounded-lg border bg-card text-card-foreground shadow-sm">
                <div class="p-6 pb-4 pt-4 text-center">
                    <div class="mb-1 flex items-center justify-center gap-1.5">
                        <x-dashboard.icon name="clock" class="h-4 w-4 text-amber-500" />
                        <p class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Pending</p>
                    </div>
                    <p class="font-display text-2xl font-bold text-amber-500">{{ $pendingCount }}</p>
                </div>
            </div>

            <div class="col-span-2 card-elevated rounded-lg border bg-card text-card-foreground shadow-sm sm:col-span-1">
                <div class="p-6 pb-4 pt-4 text-center">
                    <div class="mb-1 flex items-center justify-center gap-1.5">
                        <x-dashboard.icon name="dollar-sign" class="h-4 w-4 text-green-600" />
                        <p class="text-[10px] font-bold uppercase tracking-wider text-green-600">Total Earned</p>
                    </div>
                    <p class="font-display text-2xl font-bold text-green-600">${{ number_format($totalEarned, 2) }}</p>
                </div>
            </div>
        </div>

        @foreach ($pendingTasks as $task)
            <button type="button" wire:key="pending-{{ $task->id }}" wire:click="openSubmission({{ $task->id }})"
                class="block w-full rounded-lg border border-amber-300 bg-amber-50 p-3 text-left transition-colors hover:border-amber-400 hover:bg-amber-100">
                <div class="flex items-start gap-3">
                    <img src="{{ route('storage.file', $task->task_image_path) }}" alt="{{ $task->title }}"
                        class="h-16 w-16 shrink-0 rounded-lg object-cover">
                    <div class="min-w-0">
                        <p class="flex items-center gap-1 text-[11px] font-bold uppercase tracking-wide text-amber-600">
                            <x-dashboard.icon name="clock" class="h-3.5 w-3.5" />
                            Pending &mdash; Tap to submit
                        </p>
                        <p class="mt-0.5 font-bold text-foreground">{{ $task->title }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">Cost:
                            <span class="font-semibold text-foreground">${{ number_format((float) $task->cost, 2) }}</span>
                            &middot; Profit:
                            <span class="font-semibold text-green-600">${{ number_format((float) $task->cost * ($commissionPercentage / 100), 2) }}</span>
                        </p>
                        <p class="mt-0.5 text-[10px] text-amber-600">Tap to submit this product</p>
                    </div>
                </div>
            </button>
        @endforeach

        @if ($submittedTasks->isNotEmpty())
            <div class="card-elevated overflow-hidden rounded-lg border bg-card shadow-sm">
                <div class="border-b border-border px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-foreground">Completed</h2>
                </div>
                <div class="p-4">
                    <div class="divide-y divide-border">
                        @foreach ($submittedTasks as $task)
                            <div wire:key="submitted-{{ $task->id }}"
                                class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                                <img src="{{ route('storage.file', $task->task_image_path) }}" alt="{{ $task->title }}"
                                    class="h-12 w-12 shrink-0 rounded-lg object-cover">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-foreground">{{ $task->title }}</p>
                                    <p class="text-xs text-muted-foreground">Cost:
                                        <span class="font-semibold text-foreground">${{ number_format((float) $task->cost, 2) }}</span>
                                        &middot; Profit:
                                        <span class="font-semibold text-green-600">${{ number_format((float) $task->cost * ($commissionPercentage / 100), 2) }}</span>
                                    </p>
                                    <p class="mt-0.5 text-[10px] text-muted-foreground">
                                        {{ $task->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700">Approved</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex justify-center">
                {{ $submittedTasks->links() }}
            </div>
        @elseif ($pendingTasks->isEmpty())
            <div class="card-elevated rounded-lg border bg-card shadow-sm">
                <div class="p-6 py-12 text-center">
                    <x-dashboard.icon name="package" class="mx-auto mb-3 h-12 w-12 text-muted-foreground/30" />
                    <p class="text-muted-foreground">No submissions yet. Start submitting tasks!</p>
                </div>
            </div>
        @endif
    </div>

    <x-dashboard.task-modal :pending-task="$pendingTask" :commission="$commission"
        :commission-percentage="$commissionPercentage" :rating="$rating" :show-pending-alert="$showPendingAlert" />
</div>

<script>
    let lastToast = null;

    function toast(message) {
        if (lastToast) {
            lastToast.hideToast();
        }

        const copiedToastMarkup = `
            <div class="flex items-start p-2">
                <div class="shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6236ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big-icon lucide-circle-check-big"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                </div>
                <div class="ms-1 flex-1">
                    <p class="text-xs font-semibold text-black" style="margin-bottom: 0 !important;">${message}</p>
                </div>
            </div>
        `;

        lastToast = Toastify({
            text: copiedToastMarkup,
            className: "fixed top-1 start-1/2 -translate-x-1/2 z-[1000] w-4/5 md:w-1/2 lg:w-1/4 bg-white text-sm text-black rounded-xl shadow-lg [&>.toast-close]:hidden",
            duration: 4000,
            close: false,
            escapeMarkup: false
        });

        lastToast.showToast();
    }
</script>

@script
    <script>
        $wire.on('pending-task', (event) => {
            toast(event.message)
        });

        $wire.on('task-completed', (event) => {
            toast(event.message)
        });
    </script>
@endscript
