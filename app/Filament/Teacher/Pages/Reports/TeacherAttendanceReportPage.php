<?php

namespace App\Filament\Teacher\Pages\Reports;

use App\Domain\Training\Models\Course;
use App\Http\Services\Reports\TeacherAttendanceReportService;
use App\Services\PdfService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherAttendanceReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string $view = 'filament.teacher.pages.teacher-attendance-report-page';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 21;

    public static function getNavigationLabel(): string
    {
        return __('attendance.attendance_report') ?: 'Attendance Report';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('reports.title') ?: __('Reports');
    }

    public ?array $data = [];

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $courseId = null;

    public array $summary = [];
    public array $report = [];

    public function mount(): void
    {
        $from = now()->startOfMonth();
        $to = now();

        $this->dateFrom = $from->format('Y-m-d');
        $this->dateTo = $to->format('Y-m-d');

        $this->form->fill([
            'dateFrom' => $from,
            'dateTo' => $to,
            'courseId' => null,
        ]);

        $this->loadReport();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label(__('exports.excel') ?: 'Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->disabled(fn () => empty($this->report))
                ->action(fn () => $this->downloadExcel()),

            Action::make('exportPdf')
                ->label(__('exports.pdf') ?: 'PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->disabled(fn () => empty($this->report))
                ->action(fn (): StreamedResponse => $this->downloadPdf()),

            Action::make('print')
                ->label(__('exports.print') ?: 'Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->disabled(fn () => empty($this->report))
                ->action(fn () => $this->printView())
                ->openUrlInNewTab(),
        ];
    }

    public function form(Form $form): Form
    {
        $teacherId = auth('teacher')->id();

        return $form
            ->schema([
                Section::make(__('filters.title') ?: 'Filters')
                    ->icon('heroicon-o-funnel')
                    ->description(__('filters.description') ?: 'Choose a date range and optionally a course.')
                    ->collapsible()
                    ->schema([
                        Grid::make(['default' => 12])
                            ->schema([
                                DatePicker::make('dateFrom')
                                    ->label(__('filters.date_from') ?: 'Date from')
                                    ->required()
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->columnSpan(4)
                                    ->maxDate(fn (callable $get) => $get('dateTo') ?: now())
                                    ->default(now()->startOfMonth()),

                                DatePicker::make('dateTo')
                                    ->label(__('filters.date_to') ?: 'Date to')
                                    ->required()
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->columnSpan(4)
                                    ->minDate(fn (callable $get) => $get('dateFrom') ?: now()->startOfMonth())
                                    ->maxDate(now())
                                    ->default(now()),

                                Select::make('courseId')
                                    ->label(__('courses.course') ?: 'Course')
                                    ->placeholder(__('general.all') ?: 'All')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->columnSpan(4)
                                    ->options(
                                        Course::query()
                                            ->where('owner_teacher_id', $teacherId)
                                            ->orderBy('id', 'desc')
                                            ->get()
                                            ->mapWithKeys(function ($course) {
                                                $name = $course->name;
                                                if (is_array($name)) {
                                                    $value = $name[app()->getLocale()] ?? $name['en'] ?? $name['ar'] ?? '';
                                                } else {
                                                    $value = (string) $name;
                                                }
                                                return [$course->id => trim($value)];
                                            })
                                            ->toArray()
                                    ),
                            ]),
                    ])
                    ->footerActions([
                        FormAction::make('generate')
                            ->label(__('reports.generate') ?: 'Generate')
                            ->icon('heroicon-o-play')
                            ->color('primary')
                            ->action(fn () => $this->generate()),

                        FormAction::make('reset')
                            ->label(__('general.reset') ?: 'Reset')
                            ->icon('heroicon-o-arrow-path')
                            ->color('gray')
                            ->action(function () {
                                $from = now()->startOfMonth();
                                $to = now();

                                $this->form->fill([
                                    'dateFrom' => $from,
                                    'dateTo' => $to,
                                    'courseId' => null,
                                ]);

                                $this->generate();
                            }),
                    ])
                    ->footerActionsAlignment(Alignment::Right),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $this->form->validate();

        $state = $this->form->getState();

        $this->dateFrom = isset($state['dateFrom']) ? Carbon::parse($state['dateFrom'])->format('Y-m-d') : $this->dateFrom;
        $this->dateTo = isset($state['dateTo']) ? Carbon::parse($state['dateTo'])->format('Y-m-d') : $this->dateTo;
        $this->courseId = $state['courseId'] ?? null;

        $this->loadReport();

        Notification::make()
            ->title(__('reports.updated') ?: 'Report updated')
            ->success()
            ->send();
    }

    protected function loadReport(): void
    {
        $service = App::make(TeacherAttendanceReportService::class);

        $result = $service->getReport(
            $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
            $this->dateTo ? Carbon::parse($this->dateTo) : null,
            auth('teacher')->id(),
            $this->courseId
        );

        $this->summary = $result['summary'] ?? [];
        $this->report = $result['courses'] ?? [];
    }

    protected function downloadExcel()
    {
        $rows = collect($this->report)->map(function (array $course) {
            return [
                'Course' => $course['course_name'] ?? '',
                'Sessions' => (int) ($course['sessions_count'] ?? 0),
                'Enrollments' => (int) ($course['enrollments_count'] ?? 0),
                'Present' => (int) ($course['present_count'] ?? 0),
                'Absent' => (int) ($course['absent_count'] ?? 0),
                'Late' => (int) ($course['late_count'] ?? 0),
                'Excused' => (int) ($course['excused_count'] ?? 0),
                'Attendance Rate %' => number_format((float) ($course['attendance_rate'] ?? 0), 2),
            ];
        });

        $filename = 'teacher-attendance-report-' . now()->format('Y-m-d_His');
        $filePath = storage_path('app/temp/' . $filename . '.xlsx');

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        (new FastExcel($rows))->export($filePath);

        return response()->download($filePath, $filename . '.xlsx')->deleteFileAfterSend(true);
    }

    protected function downloadPdf(): StreamedResponse
    {
        $pdfService = App::make(PdfService::class);

        $pdfResponse = $pdfService->report('teacher-attendance-report', [
            'summary' => $this->summary,
            'courses' => $this->report,
            'dateFrom' => $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
            'dateTo' => $this->dateTo ? Carbon::parse($this->dateTo) : null,
        ]);

        $pdfContent = $pdfResponse->getContent();

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, 'teacher-attendance-report-' . now()->format('YmdHis') . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    protected function printView()
    {
        $title = __('attendance.attendance_report') ?: 'Attendance Report';
        $range = trim(($this->dateFrom ?? '') . ' → ' . ($this->dateTo ?? ''));

        return view('exports.print-table', [
            'records' => $this->report,
            'columns' => collect([
                ['name' => 'course_name', 'label' => __('courses.course') ?: 'Course'],
                ['name' => 'sessions_count', 'label' => __('attendance.sessions') ?: 'Sessions'],
                ['name' => 'enrollments_count', 'label' => __('attendance.enrollments') ?: 'Enrollments'],
                ['name' => 'present_count', 'label' => __('attendance.present') ?: 'Present'],
                ['name' => 'absent_count', 'label' => __('attendance.absent') ?: 'Absent'],
                ['name' => 'late_count', 'label' => __('attendance.late') ?: 'Late'],
                ['name' => 'excused_count', 'label' => __('attendance.excused') ?: 'Excused'],
                ['name' => 'attendance_rate', 'label' => __('attendance.rate') ?: 'Attendance Rate %'],
            ]),
            'title' => $title . ($range ? ' (' . $range . ')' : ''),
            'locale' => app()->getLocale(),
            'isRtl' => app()->getLocale() === 'ar',
        ]);
    }

    public function getHeading(): string
    {
        $title = __('attendance.attendance_report') ?: 'Attendance Report';

        if ($this->dateFrom && $this->dateTo) {
            return $title . ' • ' . $this->dateFrom . ' → ' . $this->dateTo;
        }

        return $title;
    }

    public function getSubheading(): ?string
    {
        $courseLabel = null;

        if ($this->courseId) {
            $course = Course::query()->find($this->courseId);
            if ($course) {
                $name = $course->name;
                $courseLabel = is_array($name)
                    ? ($name[app()->getLocale()] ?? $name['en'] ?? $name['ar'] ?? '')
                    : (string) $name;
            }
        }

        return $courseLabel ? (__('courses.course') ?: 'Course') . ': ' . Str::limit($courseLabel, 80) : null;
    }
}
