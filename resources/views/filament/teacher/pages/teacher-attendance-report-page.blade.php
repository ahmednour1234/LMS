<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-filament::card>
                <div class="text-sm text-gray-500">{{ __('attendance.sessions') ?: 'Sessions' }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $summary['sessions_count'] ?? 0 }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500">{{ __('attendance.present') ?: 'Present' }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $summary['present_count'] ?? 0 }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500">{{ __('attendance.absent') ?: 'Absent' }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $summary['absent_count'] ?? 0 }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500">{{ __('attendance.rate') ?: 'Attendance Rate %' }}</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ number_format((float)($summary['attendance_rate'] ?? 0), 2) }}%
                </div>
            </x-filament::card>
        </div>

        <x-filament::section :heading="__('reports.details') ?: 'Details'" icon="heroicon-o-table-cells">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-gray-600">
                        <tr class="border-b">
                            <th class="py-2 text-left">{{ __('courses.course') ?: 'Course' }}</th>
                            <th class="py-2 text-left">{{ __('attendance.sessions') ?: 'Sessions' }}</th>
                            <th class="py-2 text-left">{{ __('attendance.enrollments') ?: 'Enrollments' }}</th>
                            <th class="py-2 text-left">{{ __('attendance.present') ?: 'Present' }}</th>
                            <th class="py-2 text-left">{{ __('attendance.absent') ?: 'Absent' }}</th>
                            <th class="py-2 text-left">{{ __('attendance.late') ?: 'Late' }}</th>
                            <th class="py-2 text-left">{{ __('attendance.excused') ?: 'Excused' }}</th>
                            <th class="py-2 text-left">{{ __('attendance.rate') ?: 'Rate %' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($report as $row)
                            <tr>
                                <td class="py-2 font-medium">{{ $row['course_name'] ?? '' }}</td>
                                <td class="py-2">{{ $row['sessions_count'] ?? 0 }}</td>
                                <td class="py-2">{{ $row['enrollments_count'] ?? 0 }}</td>
                                <td class="py-2">{{ $row['present_count'] ?? 0 }}</td>
                                <td class="py-2">{{ $row['absent_count'] ?? 0 }}</td>
                                <td class="py-2">{{ $row['late_count'] ?? 0 }}</td>
                                <td class="py-2">{{ $row['excused_count'] ?? 0 }}</td>
                                <td class="py-2">{{ number_format((float)($row['attendance_rate'] ?? 0), 2) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-gray-500">
                                    {{ __('reports.no_data') ?: 'No data found for the selected filters.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
