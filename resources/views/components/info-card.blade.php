@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl bg-white shadow-sm border border-gray-200']) }}>
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
        @if($subtitle)
            <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="px-6 py-5 space-y-4">
        {{ $slot }}
    </div>
</div>