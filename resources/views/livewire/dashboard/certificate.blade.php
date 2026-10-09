<div class="animate-fade-in space-y-4 sm:space-y-6">
    <div class="flex items-start gap-3">
        <x-dashboard.icon name="shield-check" class="mt-0.5 h-6 w-6 shrink-0 text-primary sm:h-7 sm:w-7" />
        <div class="min-w-0">
            <h1 class="font-display text-xl font-bold text-foreground sm:text-2xl">Business Licenses &amp; Certificates</h1>
            <p class="mt-1 text-sm text-muted-foreground">Official business documentation and certifications</p>
        </div>
    </div>

    <div
        class="flex min-h-[240px] items-center justify-center rounded-xl border border-border bg-card shadow-sm sm:min-h-[300px]">
        <x-dashboard.empty-state message="No business licenses available yet" icon="shield-check"
            icon-size="h-14 w-14 sm:h-16 sm:w-16" />
    </div>
</div>
