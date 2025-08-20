@props(['stage', 'actions' => null])

@php
    $hasResult = !empty($stage['result']);
    
    // Konfigurasi ikon dan warna berdasarkan status
    $statusConfig = [
        'completed' => ['icon' => 'fas fa-check', 'color' => 'bg-green-500'],
        'failed' => ['icon' => 'fas fa-times', 'color' => 'bg-red-500'],
        'pending' => ['icon' => 'fas fa-clock', 'color' => 'bg-yellow-500'],
        'skipped' => ['icon' => 'fas fa-minus', 'color' => 'bg-gray-400'],
        'in_progress' => ['icon' => 'pulse', 'color' => 'border-2 border-blue-500 bg-white'],
        'locked' => ['icon' => 'dot', 'color' => 'bg-gray-300'],
    ];

    $config = $statusConfig[$stage['status']] ?? $statusConfig['locked'];
    $lineColor = $stage['status'] === 'completed' ? 'bg-green-400' : 'bg-gray-300';
@endphp

<li class="relative pb-2">
    @if(!$loop->last)
    <div class="absolute left-4 top-1 -ml-px h-full w-0.5 {{ $lineColor }}"></div>
    @endif

    <div class="relative flex items-start space-x-3">
        <div class="flex-shrink-0">
            <div class="flex h-8 w-8 items-center justify-center rounded-full ring-4 ring-white shadow {{ $config['color'] }}">
                @if ($stage['status'] === 'in_progress')
                    <div class="h-3 w-3 rounded-full border-2 border-blue-500 bg-transparent animate-pulse"></div>
                @elseif($stage['status'] === 'locked')
                    <div class="h-2.5 w-2.5 rounded-full bg-gray-500"></div>
                @else
                    <i class="{{ $config['icon'] }} text-sm text-white"></i>
                @endif
            </div>
        </div>
        
        <div class="min-w-0 flex-1 pt-1.5">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <h4 class="text-sm font-medium text-gray-900">{{ $stage['display_name'] }}</h4>
                    @if ($stage['is_locked'])
                        <span class="inline-flex items-center text-xs font-medium text-gray-500" title="Terkunci">
                            <i class="fas fa-lock w-3 h-3"></i>
                        </span>
                    @endif
                </div>
                
                <div class="flex items-center space-x-3">
                    @if ($stage['date'])
                        <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($stage['date'])->format('d/m/Y') }}</span>
                    @endif

                    @if ($stage['notes'])
                        <button @click="$dispatch('open-comment-modal', '{{ addslashes($stage['notes']) }}')" class="text-gray-400 hover:text-gray-600 transition-colors" title="Lihat catatan">
                            <i class="fas fa-comment-alt h-4 w-4"></i>
                        </button>
                    @endif
                    
                    @if($actions) {{ $actions }} @endif
                </div>
            </div>
            
            <div class="mt-2 space-y-2 text-sm">
                @if ($stage['evaluator'])
                    <p class="text-xs text-gray-500">Evaluator: {{ $stage['evaluator'] }}</p>
                @endif

                @if ($hasResult)
                    <x-status-badge :label="$stage['result']" :color="$stage['result_color']" />
                @endif
                
                @if ($stage['notes'])
                    <p class="text-xs text-gray-600 italic truncate" title="{{ $stage['notes'] }}">
                        {{ Str::limit($stage['notes'], 100) }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</li>