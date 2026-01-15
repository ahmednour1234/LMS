<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                {{ __('Generate Report') }}
            </x-slot>
            <form wire:submit="generate" class="space-y-4">
                {{ $this->form }}
                
                <x-filament::button type="submit" class="w-full sm:w-auto">
                    <x-slot name="icon">
                        <x-heroicon-o-arrow-path class="w-5 h-5" />
                    </x-slot>
                    {{ __('Generate') }}
                </x-filament::button>
            </form>
        </x-filament::section>

        @if($this->dateFrom && $this->dateTo)
            @php
                $service = app(\App\Http\Services\Reports\TeacherRevenueReportService::class);
                $result = $service->getReport(
                    \Carbon\Carbon::parse($this->dateFrom),
                    \Carbon\Carbon::parse($this->dateTo),
                    $this->teacherId,
                    $this->courseId,
                    $this->paymentStatus
                );
                
                $summary = $result['summary'];
                $teachers = $result['teachers'];
            @endphp

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-filament::section class="!p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('Total Revenue') }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                                {{ number_format($summary['total_revenue'], 2) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/20">
                            <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section class="!p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('Total Paid') }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold text-blue-600 dark:text-blue-400">
                                {{ number_format($summary['total_paid'], 2) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/20">
                            <x-heroicon-o-check-circle class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section class="!p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('Total Outstanding') }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold {{ $summary['total_outstanding'] >= 0 ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ number_format($summary['total_outstanding'], 2) }}
                            </p>
                        </div>
                        <div class="rounded-full {{ $summary['total_outstanding'] >= 0 ? 'bg-orange-100 dark:bg-orange-900/20' : 'bg-red-100 dark:bg-red-900/20' }} p-3">
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6 {{ $summary['total_outstanding'] >= 0 ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400' }}" />
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('Teachers Revenue Report') }}
                </x-slot>
                
                @if(count($teachers) > 0)
                    <div class="space-y-4">
                        @foreach($teachers as $teacher)
                            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $teacher['teacher_name'] }}
                                            </h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $teacher['courses_count'] }} {{ __('Courses') }} | 
                                                {{ $teacher['enrollments_count'] }} {{ __('Enrollments') }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total Amount') }}</p>
                                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ number_format($teacher['total_amount'], 2) }}
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Paid') }}: {{ number_format($teacher['total_paid'], 2) }}</p>
                                            <p class="text-sm {{ $teacher['outstanding'] >= 0 ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ __('Outstanding') }}: {{ number_format($teacher['outstanding'], 2) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-100 dark:bg-gray-800">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('Course') }}
                                                </th>
                                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('Enrollments') }}
                                                </th>
                                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('Total Amount') }}
                                                </th>
                                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('Total Paid') }}
                                                </th>
                                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('Outstanding') }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                            @foreach($teacher['courses'] as $course)
                                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $course['course_name'] }}
                                                    </td>
                                                    <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">
                                                        {{ $course['enrollments_count'] }}
                                                    </td>
                                                    <td class="px-6 py-4 text-right text-sm text-gray-700 dark:text-gray-300">
                                                        {{ number_format($course['total_amount'], 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 text-right text-sm text-green-600 dark:text-green-400">
                                                        {{ number_format($course['total_paid'], 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 text-right text-sm {{ $course['outstanding'] >= 0 ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400' }}">
                                                        {{ number_format($course['outstanding'], 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No data found for the selected filters.') }}
                    </div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
