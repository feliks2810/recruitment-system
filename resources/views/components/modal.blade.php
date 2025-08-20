@props(['name', 'title'])

<div x-show="showUpdateModal"
     x-on:keydown.escape.window="closeModal()"
     x-trap.inert.noscroll="showUpdateModal"
     class="fixed inset-0 z-50 overflow-y-auto"
     role="dialog"
     aria-modal="true"
     aria-labelledby="{{ $name }}-title"
     style="display: none;">

    <div x-show="showUpdateModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-60"
         @click="closeModal()"
         aria-hidden="true">
    </div>

    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div x-show="showUpdateModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.away="closeModal()"
             class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            
            {{ $slot }}
            
        </div>
    </div>
</div>