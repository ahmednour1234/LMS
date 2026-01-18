@php
    $record = $getRecord();
    $total = (float) ($record->total_amount ?? 0);
    $paid = (float) ($record->paid_amount_sum ?? 0);
    $percent = $total > 0 ? min(($paid / $total) * 100, 100) : 0;

    $label = $percent >= 100 ? 'Completed' : ($paid > 0 ? 'Partial' : 'Pending');
@endphp

<div class="w-44">
    <div class="mb-1 flex items-center justify-between text-xs text-gray-500">
        <span>{{ __($label) }}</span>
        <span>{{ number_format($percent, 0) }}%</span>
    </div>

    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-800">
        <div class="h-2 rounded-full bg-primary-600" style="width: {{ $percent }}%"></div>
    </div>
</div>
