@props(['type', 'url'])

@php
    $iconColor = $type === 'CV' ? 'text-red-600' : 'text-green-600';
@endphp

<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
    <div class="flex items-center">
        <div class="flex-shrink-0">
             <i class="fas fa-file-alt text-3xl w-8 text-center {{ $iconColor }}"></i>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">{{ $type }}</p>
            <p class="text-xs text-gray-500">PDF Document</p>
        </div>
    </div>
    <a href="{{ $url }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-full hover:bg-blue-200 transition-colors">
        <i class="fas fa-external-link-alt w-3 h-3 mr-1.5"></i>
        Lihat
    </a>
</div>