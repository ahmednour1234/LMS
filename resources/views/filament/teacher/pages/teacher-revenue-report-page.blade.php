<x-filament-panels::page>
    <div class="space-y-8">

        <x-filament::section
            :heading="__('report.generate_report') ?? 'Generate Report'"
            icon="heroicon-o-funnel"
        >
            <form wire:submit.prevent="generate" class="space-y-4">
                {{ $this->form }}

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <x-filament::button type="submit" class="w-full sm:w-auto">
                        <x-slot name="icon">
                            <x-heroicon-o-arrow-path class="h-5 w-5" />
                        </x-slot>
                        {{ __('report.generate') ?? 'Generate' }}
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        @php
            $from = $this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom)->format('Y-m-d') : null;
            $to = $this->dateTo ? \Carbon\Carbon::parse($this->dateTo)->format('Y-m-d') : null;

            $totalSales = (float) ($this->summary['total_sales'] ?? 0);
            $totalPaid = (float) ($this->summary['total_paid'] ?? 0);
            $totalDue = (float) ($this->summary['total_due'] ?? max($totalSales - $totalPaid, 0));
            $count = (int) ($this->summary['count'] ?? count($this->rows ?? []));
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-filament::card class="h-full">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ __('report.total_revenue') ?? 'Total Revenue' }}
                        </p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            {{ number_format($totalSales, 2) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('report.range') ?? 'Range' }}:
                            <span class="font-medium">{{ $from ?? '-' }}</span> — <span class="font-medium">{{ $to ?? '-' }}</span>
                        </p>
                    </div>

                    <span class="inline-flex rounded-full bg-green-100 p-3 dark:bg-green-900/20">
                        <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </span>
                </div>
            </x-filament::card>

            <x-filament::card class="h-full">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ __('report.total_paid') ?? 'Total Paid' }}
                        </p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            {{ number_format($totalPaid, 2) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('report.payments_based') ?? 'Based on completed payments' }}
                        </p>
                    </div>

                    <span class="inline-flex rounded-full bg-blue-100 p-3 dark:bg-blue-900/20">
                        <x-heroicon-o-check-circle class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </span>
                </div>
            </x-filament::card>

            <x-filament::card class="h-full">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ __('report.total_due') ?? 'Total Due' }}
                        </p>
                        <p class="mt-2 text-3xl font-semibold text-orange-600 dark:text-orange-400">
                            {{ number_format($totalDue, 2) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('report.outstanding_hint') ?? 'Outstanding amount' }}
                        </p>
                    </div>

                    <span class="inline-flex rounded-full bg-orange-100 p-3 dark:bg-orange-900/20">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                    </span>
                </div>
            </x-filament::card>

            <x-filament::card class="h-full">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ __('report.total_enrollments') ?? 'Enrollments' }}
                        </p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            {{ $count }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('report.filtered_hint') ?? 'Filtered by selected criteria' }}
                        </p>
                    </div>

                    <span class="inline-flex rounded-full bg-gray-100 p-3 dark:bg-gray-800">
                        <x-heroicon-o-user-group class="h-6 w-6 text-gray-700 dark:text-gray-200" />
                    </span>
                </div>
            </x-filament::card>
        </div>

        <x-filament::section :heading="__('report.teacher_revenue_report') ?? 'Revenue Report'" icon="heroicon-o-table-cells">
            @if(!empty($this->rows))
                <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/40">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    {{ __('report.reference') ?? 'Reference' }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    {{ __('report.student') ?? 'Student' }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    {{ __('report.course') ?? 'Course' }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    {{ __('report.total_amount') ?? 'Total Amount' }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    {{ __('report.paid_amount') ?? 'Paid Amount' }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    {{ __('report.due_amount') ?? 'Due Amount' }}
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    {{ __('report.status') ?? 'Status' }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($this->rows as $row)
                                @php
                                    $status = strtolower((string)($row['status'] ?? ''));
                                    $badge = match($status) {
                                        'paid', 'completed' => ['success', __('report.status_paid') ?? 'Paid'],
                                        'partial' => ['warning', __('report.status_partial') ?? 'Partial'],
                                        default => ['danger', __('report.status_pending') ?? 'Pending'],
                                    };
                                @endphp
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $row['reference'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $row['student_name'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $row['course_name'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-gray-200">
                                        {{ number_format((float)($row['total_amount'] ?? 0), 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-green-600 dark:text-green-400">
                                        {{ number_format((float)($row['paid_amount'] ?? 0), 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-orange-600 dark:text-orange-400">
                                        {{ number_format((float)($row['due_amount'] ?? 0), 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold bg-{{ $badge[0] }}-100 text-{{ $badge[0] }}-800 dark:bg-{{ $badge[0] }}-900/20 dark:text-{{ $badge[0] }}-400">
                                            {{ $badge[1] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                        <x-heroicon-o-magnifying-glass class="h-6 w-6 text-gray-700 dark:text-gray-200" />
                    </div>

                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('report.no_data_title') ?? 'No Data Found' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('report.no_data_desc') ?? 'No enrollments found for the selected filters.' }}
                    </p>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
