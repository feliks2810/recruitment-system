@php
    $showAlert = false;
    $type = 'success';
    $message = '';

    if (session('success')) {
        $showAlert = true;
        $message = session('success');
    } elseif (session('error')) {
        $showAlert = true;
        $type = 'error';
        $message = session('error');
    } elseif ($errors->any()) {
        $showAlert = true;
        $type = 'error';
        $message = $errors->all();
    }

    $baseClasses = 'fixed top-4 right-4 z-50 flex items-start gap-3 px-6 py-4 rounded-lg shadow-lg max-w-md animate-slide-in-right';
    $typeClasses = [
        'success' => 'bg-green-500 text-white',
        'error' => 'bg-red-500 text-white',
    ];
    $icon = [
        'success' => 'M5 13l4 4L19 7',
        'error' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z',
    ];
    $title = [
        'success' => 'Berhasil!',
        'error' => 'Terjadi Kesalahan!',
    ];
@endphp

@if ($showAlert)
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-x-full"
     x-transition:enter-end="opacity-100 transform translate-x-0"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100 transform translate-x-0"
     x-transition:leave-end="opacity-0 transform translate-x-full"
     class="{{ $baseClasses }} {{ $typeClasses[$type] }}"
     role="alert">
    
    <div class="flex-shrink-0">
        <svg class="h-5 w-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon[$type] }}" />
        </svg>
    </div>
    
    <div class="flex-1">
        <p class="font-medium">{{ $title[$type] }}</p>
        @if (is_array($message))
            <ul class="text-sm space-y-1 list-disc list-inside mt-1">
                @foreach ($message as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        @else
            <p class="text-sm">{{ $message }}</p>
        @endif
    </div>
    
    <button @click="show = false" class="flex-shrink-0 text-white hover:text-gray-200 transition-colors">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
@endif