@props(['gpa'])

@if($gpa)
    <span class="font-medium">{{ number_format($gpa, 2) }}</span>
    @if($gpa >= 3.5)
        <span class="ml-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800">
            Cumlaude
        </span>
    @endif
@else
    -
@endif