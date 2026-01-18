<?php

namespace App\Filament\Teacher\Pages\Reports;

use App\Domain\Training\Models\Course;
use App\Enums\PaymentStatus;
use App\Http\Services\Reports\TeacherRevenueReportService;
use App\Services\PdfService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
        return __('report.teacher_revenue_report') ?: 'Revenue Report';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }

    public ?array $data = [];

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $courseId = null;

    /**
     * UI filter: paid|partial|pending|null
     */
    public ?string $paymentStatus = null;

    /**
     * Computed data shown in UI
     */
    public array $summary = [
        'total_sales' => 0.0,
        'total_paid' => 0.0,
        'total_due' => 0.0,
        'count' => 0,
    ];

    public array $rows = [];

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
            'paymentStatus' => null,
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
                ->disabled(fn () => empty($this->rows))
                ->action(fn () => $this->downloadExcel()),

            Action::make('exportPdf')
                ->label(__('exports.pdf') ?: 'PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->disabled(fn () => empty($this->rows))
                ->action(fn (): StreamedResponse => $this->downloadPdf()),
        ];
    }

    public function form(Form $form): Form
    {
        $teacherId = auth('teacher')->id();

        return $form
            ->schema([
                Section::make(__('filters.title') ?: 'Filters')
                    ->icon('heroicon-o-funnel')
                    ->schema([
                        Grid::make(['default' => 12])->schema([
                            DatePicker::make('dateFrom')
                                ->label(__('filters.date_from') ?: 'From')
                                ->required()
                                ->native(false)
                                ->closeOnDateSelection()
                                ->columnSpan(4)
                                ->maxDate(fn (callable $get) => $get('dateTo') ?: now())
                                ->default(now()->startOfMonth()),

                            DatePicker::make('dateTo')
                                ->label(__('filters.date_to') ?: 'To')
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
                                        ->orderByDesc('id')
                                        ->get()
                                        ->mapWithKeys(function ($course) {
                                            $name = $course->name;
                                            $label = is_array($name)
                                                ? ($name[app()->getLocale()] ?? $name['en'] ?? $name['ar'] ?? '')
                                                : (string) $name;

                                            return [$course->id => trim($label)];
                                        })
                                        ->toArray()
                                ),

                            Select::make('paymentStatus')
                                ->label(__('report.payment_status') ?: 'Payment Status')
                                ->placeholder(__('general.all') ?: 'All')
                                ->nullable()
                                ->columnSpan(4)
                                ->options([
                                    'paid' => __('report.status_paid') ?: 'Paid',
                                    'partial' => __('report.status_partial') ?: 'Partial',
                                    'pending' => __('report.status_pending') ?: 'Pending',
                                ]),
                        ]),
                    ])
                    ->footerActions([
                        FormAction::make('generate')
                            ->label(__('report.generate') ?: 'Generate')
                            ->icon('heroicon-o-arrow-path')
                            ->color('primary')
                            ->action(fn () => $this->generate()),

                        FormAction::make('reset')
                            ->label(__('general.reset') ?: 'Reset')
                            ->icon('heroicon-o-arrow-uturn-left')
                            ->color('gray')
                            ->action(function () {
                                $from = now()->startOfMonth();
                                $to = now();

                                $this->form->fill([
                                    'dateFrom' => $from,
                                    'dateTo' => $to,
                                    'courseId' => null,
                                    'paymentStatus' => null,
                                ]);

                                $this->generate();
                            }),
                    ])
                    ->footerActionsAlignment('right'),
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
        $this->paymentStatus = $state['paymentStatus'] ?? null;

        $this->loadReport();

        Notification::make()
            ->title(__('report.updated') ?: 'Report updated')
            ->success()
            ->send();
    }

    protected function loadReport(): void
    {
        $service = App::make(TeacherRevenueReportService::class);

        $result = $service->getReport(
            $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
            $this->dateTo ? Carbon::parse($this->dateTo) : null,
            auth('teacher')->id(),
            $this->courseId,
            $this->paymentStatus
        );

        $this->summary = array_merge($this->summary, $result['summary'] ?? []);
        $this->rows = $result['enrollments'] ?? [];
    }

    protected function downloadExcel()
    {
        $rows = collect($this->rows)->map(function (array $row) {
            return [
                'Reference' => $row['reference'] ?? '',
                'Student' => $row['student_name'] ?? '',
                'Course' => $row['course_name'] ?? '',
                'Total Amount' => number_format((float) ($row['total_amount'] ?? 0), 2),
                'Paid Amount' => number_format((float) ($row['paid_amount'] ?? 0), 2),
                'Due Amount' => number_format((float) ($row['due_amount'] ?? 0), 2),
                'Status' => $row['status'] ?? '',
            ];
        });

        $filename = 'teacher-revenue-report-' . now()->format('Y-m-d_His');
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

        $pdfResponse = $pdfService->report('teacher-revenue-report', [
            'summary' => $this->summary,
            'enrollments' => $this->rows,
            'dateFrom' => $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
            'dateTo' => $this->dateTo ? Carbon::parse($this->dateTo) : null,
        ]);

        $pdfContent = $pdfResponse->getContent();

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, 'teacher-revenue-report-' . now()->format('YmdHis') . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
