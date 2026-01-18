<x-filament-panels::page>
    <div class="space-y-8">

        {{-- FILTERS / GENERATE --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ __('report.generate_report') ?? 'Generate Report' }}
            </x-slot>

            <form wire:submit="generate" class="space-y-4">
                {{ $this->form }}

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <x-filament::button type="submit" class="w-full sm:w-auto">
                        <x-slot name="icon">
                            <x-heroicon-o-arrow-path class="w-5 h-5" />
                        </x-slot>
                        {{ __('report.generate') ?? 'Generate' }}
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        @if($this->dateFrom && $this->dateTo)
            @php
                $teacherId = auth('teacher')->id();
                
                $query = \App\Domain\Enrollment\Models\Enrollment::query()
                    ->whereHas('course', fn ($q) => $q->where('owner_teacher_id', $teacherId))
                    ->with(['student', 'course', 'payments']);

                if ($this->dateFrom && $this->dateTo) {
                    $query->whereBetween('created_at', [\Carbon\Carbon::parse($this->dateFrom), \Carbon\Carbon::parse($this->dateTo)]);
                }

                if ($this->courseId) {
                    $query->where('course_id', $this->courseId);
                }

                $enrollments = $query->get();

                $totalSales = $enrollments->sum('total_amount');
                $totalPaid = $enrollments->sum(function ($enrollment) {
                    return $enrollment->payments()->where('status', 'completed')->sum('amount');
                });
                $totalDue = $totalSales - $totalPaid;

                $paymentStatusFilter = $this->paymentStatus;
                if ($paymentStatusFilter) {
                    $enrollments = $enrollments->filter(function ($enrollment) use ($paymentStatusFilter) {
                        $paidAmount = $enrollment->payments()->where('status', 'completed')->sum('amount');
                        $dueAmount = $enrollment->total_amount - $paidAmount;
                        
                        if ($paymentStatusFilter === 'completed') {
                            return $dueAmount <= 0;
                        } elseif ($paymentStatusFilter === 'partial') {
                            return $dueAmount > 0 && $paidAmount > 0;
                        } elseif ($paymentStatusFilter === 'pending') {
                            return $paidAmount == 0;
                        }
                        return true;
                    });
                }
            @endphp

            {{-- SUMMARY CARDS --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
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
                                <span class="font-medium">
                                    {{ \Carbon\Carbon::parse($this->dateFrom)->format('Y-m-d') }}
                                </span>
                                —
                                <span class="font-medium">
                                    {{ \Carbon\Carbon::parse($this->dateTo)->format('Y-m-d') }}
                                </span>
                            </p>
                        </div>

                        <span class="inline-flex rounded-full bg-green-100 p-3 dark:bg-green-900/20">
                            <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </span>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
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
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('report.total_due') ?? 'Total Due' }}
                            </p>
                            <p class="mt-2 text-3xl font-semibold {{ $totalDue >= 0 ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ number_format($totalDue, 2) }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('report.outstanding_hint') ?? 'Outstanding amount' }}
                            </p>
                        </div>

                        <span class="inline-flex rounded-full {{ $totalDue >= 0 ? 'bg-orange-100 dark:bg-orange-900/20' : 'bg-red-100 dark:bg-red-900/20' }} p-3">
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6 {{ $totalDue >= 0 ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400' }}" />
                        </span>
                    </div>
                </div>
            </div>

            {{-- REPORT --}}
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('report.teacher_revenue_report') ?? 'Revenue Report' }}
                </x-slot>

                @if($enrollments->count() > 0)
                    <div class="overflow-x-auto">
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

                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                @foreach($enrollments as $enrollment)
                                    @php
                                        $paidAmount = $enrollment->payments()->where('status', 'completed')->sum('amount');
                                        $dueAmount = $enrollment->total_amount - $paidAmount;
                                        $statusLabel = $dueAmount <= 0 ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Pending');
                                        $statusColor = $dueAmount <= 0 ? 'success' : ($paidAmount > 0 ? 'warning' : 'danger');
                                        $courseName = is_array($enrollment->course->name ?? []) 
                                            ? ($enrollment->course->name['en'] ?? $enrollment->course->name['ar'] ?? '') 
                                            : ($enrollment->course->name ?? '');
                                    @endphp
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $enrollment->reference }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $enrollment->student->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $courseName }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-gray-200">
                                            {{ number_format($enrollment->total_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold text-green-600 dark:text-green-400">
                                            {{ number_format($paidAmount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold {{ $dueAmount >= 0 ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ number_format($dueAmount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/20 dark:text-{{ $statusColor }}-400">
                                                {{ $statusLabel }}
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
        @endif
    </div>
</x-filament-panels::page>
