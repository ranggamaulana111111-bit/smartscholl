@props(['title', 'hint' => null])

<div {{ $attributes->class('empty-state') }}>
    @isset($icon)
        <span class="empty-state__icon">{{ $icon }}</span>
    @endisset
    <p class="empty-state__title">{{ $title }}</p>
    @if($hint)
        <p class="empty-state__hint">{{ $hint }}</p>
    @endif
    @isset($action)
        <div class="mt-3">{{ $action }}</div>
    @endisset
</div>