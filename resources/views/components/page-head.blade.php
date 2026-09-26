@props(['title', 'eyebrow' => null, 'description' => null])

<div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between mb-8">
    <div class="max-w-2xl">
        @if($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-3 display-title text-3xl lg:text-4xl">{{ $title }}</h1>
        @if($description)
            <p class="mt-3 text-[15px] text-text-muted leading-relaxed">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2 shrink-0">{{ $actions }}</div>
    @endisset
</div>