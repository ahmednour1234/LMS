<?php

namespace App\Filament\Teacher\Pages\Reports;

use App\Domain\Training\Models\Course;
use App\Http\Services\Reports\TeacherAttendanceReportService;
use App\Services\PdfService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\App;
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
        return __('attendance.attendance_report') ?? 'Attendance Report';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $courseId = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->form->fill([
            'dateFrom' => now()->startOfMonth(),
            'dateTo' => now(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label(__('exports.excel'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $service = App::make(TeacherAttendanceReportService::class);
                    $result = $service->getReport(
                        $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
                        $this->dateTo ? Carbon::parse($this->dateTo) : null,
                        auth('teacher')->id(),
                        $this->courseId
                    );

                    $exportData = collect();
                    $exportData->push(['Course', 'Sessions', 'Enrollments', 'Present', 'Absent', 'Late', 'Excused', 'Attendance Rate %']);

                    foreach ($result['courses'] ?? [] as $course) {
                        $exportData->push([
                            'Course' => $course['course_name'] ?? '',
                            'Sessions' => $course['sessions_count'] ?? 0,
                            'Enrollments' => $course['enrollments_count'] ?? 0,
                            'Present' => $course['present_count'] ?? 0,
                            'Absent' => $course['absent_count'] ?? 0,
                            'Late' => $course['late_count'] ?? 0,
                            'Excused' => $course['excused_count'] ?? 0,
                            'Attendance Rate %' => number_format($course['attendance_rate'] ?? 0, 2),
                        ]);
                    }

                    $filename = 'teacher-attendance-report-' . now()->format('Y-m-d_His');
                    $filePath = storage_path('app/temp/' . $filename . '.xlsx');
                    $directory = dirname($filePath);
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    (new FastExcel($exportData))->export($filePath);

                    return response()->download($filePath, $filename . '.xlsx')->deleteFileAfterSend(true);
                }),
            Action::make('exportPdf')
                ->label(__('exports.pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function (): StreamedResponse {
                    $service = App::make(TeacherAttendanceReportService::class);
                    $pdfService = App::make(PdfService::class);

                    $result = $service->getReport(
                        $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
                        $this->dateTo ? Carbon::parse($this->dateTo) : null,
                        auth('teacher')->id(),
                        $this->courseId
                    );

                    $pdfResponse = $pdfService->report('teacher-attendance-report', [
                        'summary' => $result['summary'] ?? [],
                        'courses' => $result['courses'] ?? [],
                        'dateFrom' => $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
                        'dateTo' => $this->dateTo ? Carbon::parse($this->dateTo) : null,
                    ]);

                    $pdfContent = $pdfResponse->getContent();

                    return response()->streamDownload(function () use ($pdfContent) {
                        echo $pdfContent;
                    }, 'teacher-attendance-report-' . now()->format('YmdHis') . '.pdf', [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),
            Action::make('print')
                ->label(__('exports.print'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function () {
                    $service = App::make(TeacherAttendanceReportService::class);
                    $result = $service->getReport(
                        $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
                        $this->dateTo ? Carbon::parse($this->dateTo) : null,
                        auth('teacher')->id(),
                        $this->courseId
                    );

                    return view('exports.print-table', [
                        'records' => $result['courses'] ?? [],
                        'columns' => collect([
                            ['name' => 'course_name', 'label' => 'Course'],
                            ['name' => 'sessions_count', 'label' => 'Sessions'],
                            ['name' => 'present_count', 'label' => 'Present'],
                            ['name' => 'absent_count', 'label' => 'Absent'],
                            ['name' => 'attendance_rate', 'label' => 'Attendance Rate %'],
                        ]),
                        'title' => 'Attendance Report',
                        'locale' => app()->getLocale(),
                        'isRtl' => app()->getLocale() === 'ar',
                    ]);
                })
                ->openUrlInNewTab(),
        ];
    }

    public function form(Form $form): Form
    {
        $teacherId = auth('teacher')->id();

        return $form
            ->schema([
                DatePicker::make('dateFrom')
                    ->label(__('filters.date_from'))
                    ->required()
                    ->default(now()->startOfMonth()),
                DatePicker::make('dateTo')
                    ->label(__('filters.date_to'))
                    ->required()
                    ->default(now()),
                Select::make('courseId')
                    ->label(__('Course'))
                    ->options(Course::query()
                        ->where('owner_teacher_id', $teacherId)
                        ->get()
                        ->mapWithKeys(function ($course) {
                            $name = is_array($course->name) ? ($course->name['en'] ?? $course->name['ar'] ?? '') : $course->name;
                            return [$course->id => $name];
                        }))
                    ->searchable()
                    ->nullable(),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $this->form->validate();
        $data = $this->form->getState();
        $this->dateFrom = $data['dateFrom'] ?? $this->dateFrom;
        $this->dateTo = $data['dateTo'] ?? $this->dateTo;
        $this->courseId = $data['courseId'] ?? $this->courseId;
    }
}
