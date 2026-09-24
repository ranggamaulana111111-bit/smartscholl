@props(['title', 'eyebrow' => null, 'description' => null])

<div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between mb-8">
    <div>
        @if($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-widest text-accent">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-text">{{ $title }}</h1>
        <div class="mt-3 w-16 h-0.5 bg-accent"></div>
        @if($description)
            <p class="mt-4 text-sm text-text-muted max-w-xl">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2 shrink-0">{{ $actions }}</div>
    @endisset
</div>