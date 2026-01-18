<div class="space-y-4">
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Reference</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $enrollment->reference }}</p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Student</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $enrollment->student->name ?? 'N/A' }}</p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Course</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
            {{ is_array($enrollment->course->name) ? ($enrollment->course->name[app()->getLocale()] ?? $enrollment->course->name['en'] ?? 'N/A') : $enrollment->course->name }}
        </p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
            {{ __('enrollments.status_options.' . $enrollment->status->value) ?? $enrollment->status->value }}
        </p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Amount</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($enrollment->total_amount, 3) }} OMR</p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Paid Amount</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
            {{ number_format($enrollment->payments()->where('status', 'completed')->sum('amount'), 3) }} OMR
        </p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Due Amount</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
            @php
                $paid = $enrollment->payments()->where('status', 'completed')->sum('amount');
                $due = max(0, $enrollment->total_amount - $paid);
            @endphp
            {{ number_format($due, 3) }} OMR
        </p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Created At</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $enrollment->created_at->format('Y-m-d H:i') }}</p>
    </div>
</div>
