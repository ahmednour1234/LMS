<x-filament-panels::page>
    <div class="space-y-8">

        {{-- FILTERS / GENERATE --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ __('attendance.generate_report') }}
            </x-slot>

            <form wire:submit="generate" class="space-y-4">
                {{ $this->form }}

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <x-filament::button type="submit" class="w-full sm:w-auto">
                        <x-slot name="icon">
                            <x-heroicon-o-arrow-path class="w-5 h-5" />
                        </x-slot>
                        {{ __('attendance.generate') }}
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        @if($this->dateFrom && $this->dateTo)
            @php
                $service = app(\App\Http\Services\Reports\AttendanceReportService::class);
                $result = $service->getReport(
                    \Carbon\Carbon::parse($this->dateFrom),
                    \Carbon\Carbon::parse($this->dateTo),
                    $this->teacherId,
                    $this->courseId
                );

                $summary = $result['summary'];
                $teachers = $result['teachers'];
            @endphp

            {{-- SUMMARY CARDS --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('attendance.sessions_count') }}
                            </p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($summary['sessions_count'], 0) }}
                            </p>
                        </div>
                        <span class="inline-flex rounded-full bg-blue-100 p-3 dark:bg-blue-900/20">
                            <x-heroicon-o-calendar-days class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </span>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('attendance.enrollments_count') }}
                            </p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($summary['enrollments_count'], 0) }}
                            </p>
                        </div>
                        <span class="inline-flex rounded-full bg-purple-100 p-3 dark:bg-purple-900/20">
                            <x-heroicon-o-users class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        </span>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('attendance.present_count') }}
                            </p>
                            <p class="mt-2 text-3xl font-semibold text-green-600 dark:text-green-400">
                                {{ number_format($summary['present_count'], 0) }}
                            </p>
                        </div>
                        <span class="inline-flex rounded-full bg-green-100 p-3 dark:bg-green-900/20">
                            <x-heroicon-o-check-circle class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </span>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('attendance.attendance_rate') }}
                            </p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($summary['attendance_rate'], 1) }}%
                            </p>
                        </div>
                        <span class="inline-flex rounded-full bg-indigo-100 p-3 dark:bg-indigo-900/20">
                            <x-heroicon-o-chart-bar class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                        </span>
                    </div>
                </div>
            </div>

            {{-- REPORT --}}
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('attendance.attendance_report') }}
                </x-slot>

                @if(count($teachers) > 0)
                    <div class="space-y-6">
                        @foreach($teachers as $teacher)
                            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                {{-- TEACHER HEADER --}}
                                <div class="flex flex-col gap-4 border-b border-gray-200 px-6 py-5 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                <x-heroicon-o-user class="h-6 w-6" />
                                            </div>

                                            <div class="min-w-0">
                                                <h3 class="truncate text-lg font-semibold text-gray-900 dark:text-white">
                                                    {{ $teacher['teacher_name'] }}
                                                </h3>

                                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 dark:bg-gray-800">
                                                        <x-heroicon-o-calendar-days class="h-4 w-4" />
                                                        {{ $teacher['sessions_count'] }} {{ __('attendance.sessions') }}
                                                    </span>

                                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 dark:bg-gray-800">
                                                        <x-heroicon-o-users class="h-4 w-4" />
                                                        {{ $teacher['enrollments_count'] }} {{ __('attendance.enrollments') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- TEACHER TOTALS --}}
                                    <div class="grid w-full grid-cols-1 gap-3 sm:w-auto sm:grid-cols-4">
                                        <div class="rounded-xl bg-gray-50 px-4 py-3 text-right dark:bg-gray-800/50">
                                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('attendance.present') }}</p>
                                            <p class="text-base font-semibold text-green-600 dark:text-green-400">
                                                {{ number_format($teacher['present_count'], 0) }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-gray-50 px-4 py-3 text-right dark:bg-gray-800/50">
                                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('attendance.absent') }}</p>
                                            <p class="text-base font-semibold text-red-600 dark:text-red-400">
                                                {{ number_format($teacher['absent_count'], 0) }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-gray-50 px-4 py-3 text-right dark:bg-gray-800/50">
                                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('attendance.late') }}</p>
                                            <p class="text-base font-semibold text-orange-600 dark:text-orange-400">
                                                {{ number_format($teacher['late_count'], 0) }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-gray-50 px-4 py-3 text-right dark:bg-gray-800/50">
                                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('attendance.attendance_rate') }}</p>
                                            <p class="text-base font-semibold text-indigo-600 dark:text-indigo-400">
                                                {{ number_format($teacher['attendance_rate'], 1) }}%
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- COURSES TABLE --}}
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                                        <thead class="bg-gray-50 dark:bg-gray-800/40">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('attendance.course') }}
                                                </th>
                                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('attendance.sessions') }}
                                                </th>
                                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('attendance.enrollments') }}
                                                </th>
                                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('attendance.present') }}
                                                </th>
                                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('attendance.absent') }}
                                                </th>
                                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('attendance.late') }}
                                                </th>
                                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ __('attendance.attendance_rate') }}
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                            @foreach($teacher['courses'] as $course)
                                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                                    <td class="px-6 py-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                                <x-heroicon-o-book-open class="h-5 w-5" />
                                                            </div>
                                                            <div>
                                                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                                    {{ $course['course_name'] }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">
                                                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                            {{ $course['sessions_count'] }}
                                                        </span>
                                                    </td>

                                                    <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">
                                                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                            {{ $course['enrollments_count'] }}
                                                        </span>
                                                    </td>

                                                    <td class="px-6 py-4 text-right text-sm font-semibold text-green-600 dark:text-green-400">
                                                        {{ number_format($course['present_count'], 0) }}
                                                    </td>

                                                    <td class="px-6 py-4 text-right text-sm font-semibold text-red-600 dark:text-red-400">
                                                        {{ number_format($course['absent_count'], 0) }}
                                                    </td>

                                                    <td class="px-6 py-4 text-right text-sm font-semibold text-orange-600 dark:text-orange-400">
                                                        {{ number_format($course['late_count'], 0) }}
                                                    </td>

                                                    <td class="px-6 py-4 text-right text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                                        {{ number_format($course['attendance_rate'], 1) }}%
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- FOOTER --}}
                                <div class="flex flex-col gap-2 border-t border-gray-200 px-6 py-4 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400 sm:flex-row sm:items-center sm:justify-between">
                                    <span>
                                        {{ __('attendance.teacher_footer_hint') }}
                                    </span>
                                    <span class="font-medium">
                                        {{ __('attendance.generated_at') }}: {{ now()->format('Y-m-d H:i') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- EMPTY STATE --}}
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                            <x-heroicon-o-magnifying-glass class="h-6 w-6 text-gray-700 dark:text-gray-200" />
                        </div>

                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ __('attendance.no_data_title') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('attendance.no_data_desc') }}
                        </p>
                    </div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
