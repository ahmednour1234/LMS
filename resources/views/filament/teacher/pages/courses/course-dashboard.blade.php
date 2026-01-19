<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $stats = $this->getOverviewStats();
            $activeTab = $this->activeTab ?? 'overview';
        @endphp

        <div>
            <x-filament::tabs>
                <x-filament::tabs.item
                    wire:click="$set('activeTab', 'overview')"
                    :active="$activeTab === 'overview'"
                >
                    {{ __('course_dashboard.tabs.overview') ?? 'Overview' }}
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    wire:click="$set('activeTab', 'lessons')"
                    :active="$activeTab === 'lessons'"
                >
                    {{ __('course_dashboard.tabs.lessons') ?? 'Lessons' }}
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    wire:click="$set('activeTab', 'registrations')"
                    :active="$activeTab === 'registrations'"
                >
                    {{ __('course_dashboard.tabs.registrations') ?? 'Registrations & Payments' }}
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    wire:click="$set('activeTab', 'tasks')"
                    :active="$activeTab === 'tasks'"
                >
                    {{ __('course_dashboard.tabs.tasks') ?? 'Tasks & Submissions' }}
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    wire:click="$set('activeTab', 'exams')"
                    :active="$activeTab === 'exams'"
                >
                    {{ __('course_dashboard.tabs.exams') ?? 'Exams & Grades' }}
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    wire:click="$set('activeTab', 'sessions')"
                    :active="$activeTab === 'sessions'"
                >
                    {{ __('course_dashboard.tabs.sessions') ?? 'Sessions' }}
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    wire:click="$set('activeTab', 'attendance')"
                    :active="$activeTab === 'attendance'"
                >
                    {{ __('course_dashboard.tabs.attendance') ?? 'Attendance' }}
                </x-filament::tabs.item>
            </x-filament::tabs>

            <div class="mt-6">
                @if($activeTab === 'overview')
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('course_dashboard.stats.total_enrolled') ?? 'Total Enrolled' }}
                            </x-slot>
                            <div class="text-3xl font-bold">
                                {{ $stats['total_enrolled'] }}
                            </div>
                        </x-filament::section>

                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('course_dashboard.stats.total_paid') ?? 'Total Paid' }}
                            </x-slot>
                            <div class="text-3xl font-bold">
                                {{ number_format($stats['total_paid'], 3) }} {{ __('course_dashboard.currency') ?? 'OMR' }}
                            </div>
                        </x-filament::section>

                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('course_dashboard.stats.total_due') ?? 'Total Due' }}
                            </x-slot>
                            <div class="text-3xl font-bold">
                                {{ number_format($stats['total_due'], 3) }} {{ __('course_dashboard.currency') ?? 'OMR' }}
                            </div>
                        </x-filament::section>

                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('course_dashboard.stats.completion_rate') ?? 'Completion Rate' }}
                            </x-slot>
                            <div class="text-3xl font-bold">
                                {{ $stats['completion_rate'] }}%
                            </div>
                        </x-filament::section>

                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('course_dashboard.stats.tasks_count') ?? 'Tasks' }}
                            </x-slot>
                            <div class="text-3xl font-bold">
                                {{ $stats['tasks_count'] }}
                            </div>
                        </x-filament::section>

                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('course_dashboard.stats.pending_submissions') ?? 'Pending Submissions' }}
                            </x-slot>
                            <div class="text-3xl font-bold">
                                {{ $stats['pending_submissions'] }}
                            </div>
                        </x-filament::section>

                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('course_dashboard.stats.sessions_count') ?? 'Sessions' }}
                            </x-slot>
                            <div class="text-3xl font-bold">
                                {{ $stats['sessions_count'] }}
                            </div>
                        </x-filament::section>

                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('course_dashboard.stats.attendance_rate') ?? 'Attendance Rate' }}
                            </x-slot>
                            <div class="text-3xl font-bold">
                                {{ $stats['attendance_rate'] }}%
                            </div>
                        </x-filament::section>
                    </div>

                    <div class="mt-6">
                        <x-filament::button
                            wire:click="exportCourseReportPdf"
                            icon="heroicon-o-document-arrow-down"
                            color="danger"
                        >
                            {{ __('course_dashboard.export_full_report_pdf') ?? 'Export Full Report PDF' }}
                        </x-filament::button>

                        <x-filament::button
                            wire:click="exportCourseReportExcel"
                            icon="heroicon-o-arrow-down-tray"
                            color="success"
                            class="ml-2"
                        >
                            {{ __('course_dashboard.export_registrations_excel') ?? 'Export Registrations Excel' }}
                        </x-filament::button>
                    </div>
                @elseif($activeTab === 'lessons')
                    {{ $this->lessonsTable }}
                @elseif($activeTab === 'registrations')
                    {{ $this->registrationsTable }}
                @elseif($activeTab === 'tasks')
                    {{ $this->tasksTable }}
                @elseif($activeTab === 'exams')
                    <div class="space-y-6">
                        <div class="relative">
                            <h3 class="text-lg font-semibold mb-4">{{ __('course_dashboard.exams') ?? 'Exams' }}</h3>
                            {{ $this->examsTable }}
                        </div>
                        <div class="relative">
                            <h3 class="text-lg font-semibold mb-4">{{ __('course_dashboard.exam_attempts') ?? 'Exam Attempts' }}</h3>
                            {{ $this->examAttemptsTable }}
                        </div>
                    </div>
                @elseif($activeTab === 'sessions')
                    <div class="p-6 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ __('course_dashboard.sessions_coming_soon') ?? 'Sessions management coming soon.' }}
                        </p>
                    </div>
                @elseif($activeTab === 'attendance')
                    {{ $this->attendanceTable }}
                @elseif($activeTab === 'reports')
                    <div class="space-y-4">
                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('course_dashboard.reports.full_course_report') ?? 'Full Course Report' }}
                            </x-slot>
                            <x-slot name="description">
                                {{ __('course_dashboard.reports.full_course_report_desc') ?? 'Generate a comprehensive PDF report with all course data' }}
                            </x-slot>
                            <x-filament::button
                                wire:click="exportCourseReportPdf"
                                icon="heroicon-o-document-arrow-down"
                                color="danger"
                            >
                                {{ __('course_dashboard.export_full_report_pdf') ?? 'Export Full Report PDF' }}
                            </x-filament::button>
                        </x-filament::section>

                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('course_dashboard.reports.separate_exports') ?? 'Separate Exports' }}
                            </x-slot>
                            <div class="space-y-2">
                                <x-filament::button
                                    wire:click="exportRegistrationsExcel"
                                    icon="heroicon-o-arrow-down-tray"
                                    color="success"
                                    outline
                                >
                                    {{ __('course_dashboard.export_registrations_excel') ?? 'Export Registrations Excel' }}
                                </x-filament::button>

                                <x-filament::button
                                    wire:click="exportTasksExcel"
                                    icon="heroicon-o-arrow-down-tray"
                                    color="success"
                                    outline
                                >
                                    {{ __('course_dashboard.export_tasks_excel') ?? 'Export Tasks Excel' }}
                                </x-filament::button>

                                <x-filament::button
                                    wire:click="exportAttendanceExcel"
                                    icon="heroicon-o-arrow-down-tray"
                                    color="success"
                                    outline
                                >
                                    {{ __('course_dashboard.export_attendance_excel') ?? 'Export Attendance Excel' }}
                                </x-filament::button>
                            </div>
                        </x-filament::section>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
