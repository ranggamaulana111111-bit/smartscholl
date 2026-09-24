@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'panel ' . $class]) }}>
    {{ $slot }}
</div>