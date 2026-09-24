<div class="card p-6">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-widest text-text-muted">{{ $label }}</p>
            <p class="mt-2 text-4xl font-bold tracking-tight text-text">{{ $value }}</p>
            @isset($sub)
                <p class="mt-1 text-xs text-text-muted truncate">{{ $sub }}</p>
            @endisset
        </div>
        <span class="w-11 h-11 rounded-lg bg-primary text-accent flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">{!! $icon !!}</svg>
        </span>
    </div>
</div>