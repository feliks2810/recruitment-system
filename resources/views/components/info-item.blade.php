@props(['label', 'value' => null, 'subValue' => null])

<div>
    <dt class="text-sm font-medium text-gray-500">{{ $label }}</dt>
    <dd {{ $attributes->merge(['class' => 'mt-1 text-sm text-gray-900']) }}>
        @if ($value)
            {{ $value }}
        @else
            {{ $slot }}
        @endif

        @if($subValue)
            <span class="text-gray-400 text-xs ml-1">({{ $subValue }})</span>
        @endif
    </dd>
</div>