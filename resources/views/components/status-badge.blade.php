@props(['label', 'color' => 'gray', 'icon' => null])

@php
    $colors = [
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'orange' => 'bg-orange-100 text-orange-800',
        'gray' => 'bg-gray-100 text-gray-800',
    ];
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colors[$color] ?? $colors['gray'] }}">
    @if ($icon)
        <span class="mr-1.5">{{ $icon }}</span>
    @endif
    {{ $label }}
</span>