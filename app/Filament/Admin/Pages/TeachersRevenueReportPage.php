<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Teacher;
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

class TeachersRevenueReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.admin.pages.teachers-revenue-report-page';

    protected static ?string $navigationGroup = 'reports';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('Teachers Revenue Report');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $teacherId = null;
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
                        $this->teacherId,
                        $this->courseId,
                        $this->paymentStatus
                    );

                    $exportData = collect();
                    $exportData->push(['Teacher', 'Course', 'Enrollments', 'Total Amount', 'Total Paid', 'Outstanding']);

                    foreach ($result['teachers'] as $teacher) {
                        foreach ($teacher['courses'] as $course) {
                            $exportData->push([
                                'Teacher' => $teacher['teacher_name'],
                                'Course' => $course['course_name'],
                                'Enrollments' => $course['enrollments_count'],
                                'Total Amount' => number_format($course['total_amount'], 2),
                                'Total Paid' => number_format($course['total_paid'], 2),
                                'Outstanding' => number_format($course['outstanding'], 2),
                            ]);
                        }
                    }

                    $filename = 'teachers-revenue-report-' . now()->format('Y-m-d_His');
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
                        $this->teacherId,
                        $this->courseId,
                        $this->paymentStatus
                    );

                    $pdfResponse = $pdfService->report('teachers-revenue-report', [
                        'summary' => $result['summary'],
                        'teachers' => $result['teachers'],
                        'dateFrom' => $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
                        'dateTo' => $this->dateTo ? Carbon::parse($this->dateTo) : null,
                    ]);

                    $pdfContent = $pdfResponse->getContent();

                    return response()->streamDownload(function () use ($pdfContent) {
                        echo $pdfContent;
                    }, 'teachers-revenue-report-' . now()->format('YmdHis') . '.pdf', [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),
        ];
    }

    public function form(Form $form): Form
    {
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
                Select::make('teacherId')
                    ->label(__('Teacher'))
                    ->options(Teacher::pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
                Select::make('courseId')
                    ->label(__('Course'))
                    ->options(Course::query()->get()->mapWithKeys(function ($course) {
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
        $this->teacherId = $data['teacherId'] ?? $this->teacherId;
        $this->courseId = $data['courseId'] ?? $this->courseId;
        $this->paymentStatus = $data['paymentStatus'] ?? $this->paymentStatus;
    }
}
