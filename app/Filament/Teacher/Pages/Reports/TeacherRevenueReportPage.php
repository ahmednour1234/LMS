<?php

namespace App\Filament\Teacher\Pages\Reports;

use App\Domain\Training\Models\Course;
use App\Enums\PaymentStatus;
use App\Http\Services\Reports\TeacherRevenueReportService;
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

class TeacherRevenueReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.teacher.pages.teacher-revenue-report-page';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('Teachers Revenue Report') ?? 'Revenue Report';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $courseId = null;
    public ?string $paymentStatus = null;

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
                    $service = App::make(TeacherRevenueReportService::class);
                    $result = $service->getReport(
                        $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
                        $this->dateTo ? Carbon::parse($this->dateTo) : null,
                        auth('teacher')->id(),
                        $this->courseId,
                        $this->paymentStatus
                    );

                    $exportData = collect();
                    $exportData->push(['Reference', 'Student', 'Course', 'Total Amount', 'Paid Amount', 'Due Amount', 'Status']);

                    foreach ($result['enrollments'] ?? [] as $enrollment) {
                        $exportData->push([
                            'Reference' => $enrollment['reference'] ?? '',
                            'Student' => $enrollment['student_name'] ?? '',
                            'Course' => $enrollment['course_name'] ?? '',
                            'Total Amount' => number_format($enrollment['total_amount'] ?? 0, 2),
                            'Paid Amount' => number_format($enrollment['paid_amount'] ?? 0, 2),
                            'Due Amount' => number_format($enrollment['due_amount'] ?? 0, 2),
                            'Status' => $enrollment['status'] ?? '',
                        ]);
                    }

                    $filename = 'teacher-revenue-report-' . now()->format('Y-m-d_His');
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
                    $service = App::make(TeacherRevenueReportService::class);
                    $pdfService = App::make(PdfService::class);

                    $result = $service->getReport(
                        $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
                        $this->dateTo ? Carbon::parse($this->dateTo) : null,
                        auth('teacher')->id(),
                        $this->courseId,
                        $this->paymentStatus
                    );

                    $pdfResponse = $pdfService->report('teacher-revenue-report', [
                        'summary' => $result['summary'] ?? [],
                        'enrollments' => $result['enrollments'] ?? [],
                        'dateFrom' => $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
                        'dateTo' => $this->dateTo ? Carbon::parse($this->dateTo) : null,
                    ]);

                    $pdfContent = $pdfResponse->getContent();

                    return response()->streamDownload(function () use ($pdfContent) {
                        echo $pdfContent;
                    }, 'teacher-revenue-report-' . now()->format('YmdHis') . '.pdf', [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),
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
                Select::make('paymentStatus')
                    ->label(__('Payment Status'))
                    ->options([
                        PaymentStatus::PENDING->value => __('Pending'),
                        PaymentStatus::COMPLETED->value => __('Completed'),
                        PaymentStatus::FAILED->value => __('Failed'),
                    ])
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
        $this->paymentStatus = $data['paymentStatus'] ?? $this->paymentStatus;
    }
}
