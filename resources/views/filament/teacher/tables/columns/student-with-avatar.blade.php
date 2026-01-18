@php
    $student = $getRecord()->student;
@endphp

<div class="flex items-center gap-3">
    <x-filament::avatar
        :src="method_exists($student, 'getAvatarUrl') ? $student->getAvatarUrl() : null"
        :alt="$student?->name"
        size="md"
    />
    <div class="min-w-0">
        <div class="truncate font-medium text-gray-900 dark:text-white">{{ $student?->name ?? '-' }}</div>
        <div class="truncate text-xs text-gray-500">{{ $student?->email ?? '' }}</div>
    </div>
</div>
