@props(['label', 'name', 'icon' => 'mail', 'type' => 'text', 'placeholder' => '', 'required' => true, 'autocomplete' => null])

<div class="min-w-0 space-y-1">
    <label for="{{ $name }}" class="block text-sm font-extrabold">{{ $label }}</label>
    <div class="relative" @if ($type === 'password') x-data="{ visible: false }" @endif>
        <x-dashboard.icon :name="$icon" class="auth-field-icon pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <input wire:model="{{ $name }}" id="{{ $name }}" type="{{ $type }}"
            @if ($type === 'password') :type="visible ? 'text' : 'password'" @endif
            placeholder="{{ $placeholder }}" @required($required) autocomplete="{{ $autocomplete ?? 'off' }}"
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
            {{ $attributes->class(['auth-input h-10 w-full min-w-0 rounded-xl border border-border bg-background py-2 pl-9 pr-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 placeholder:text-muted-foreground/60', 'pr-9' => $type === 'password']) }}>
        @if ($type === 'password')
            <button type="button" @click="visible = !visible" :aria-label="(visible ? 'Hide ' : 'Show ') + @js(strtolower($label))"
                :aria-pressed="visible" class="auth-password-toggle absolute right-3 top-1/2 -translate-y-1/2 rounded text-muted-foreground hover:text-foreground focus-visible:outline-2 focus-visible:outline-primary">
                <span x-show="!visible"><x-dashboard.icon name="eye" class="h-4 w-4" /></span>
                <span x-cloak x-show="visible"><x-dashboard.icon name="eye-off" class="h-4 w-4" /></span>
            </button>
        @endif
    </div>
    @error($name)
        <p id="{{ $name }}-error" class="text-xs text-destructive">{{ $message }}</p>
    @enderror
</div>
