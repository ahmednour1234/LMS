<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Accounting\Services\ReportService;
use App\Services\PdfService;
use App\Domain\Branch\Models\Branch;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncomeStatementPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.admin.pages.income-statement-page';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('reports.income_statement');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.accounting');
    }

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $branchId = null;
    
    public ?array $data = [];

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->form->fill([
            'startDate' => now()->startOfMonth(),
            'endDate' => now(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('pdf.print'))
                ->icon('heroicon-o-printer')
                ->action(function (): StreamedResponse {
                    $reportService = App::make(ReportService::class);
                    $pdfService = App::make(PdfService::class);
                    
                    $result = $reportService->getIncomeStatement(
                        Carbon::parse($this->startDate),
                        Carbon::parse($this->endDate),
                        $this->branchId,
                        auth()->user()
                    );
                    
                    $pdfResponse = $pdfService->report('income-statement', [
                        'revenues' => $result['revenues'],
                        'expenses' => $result['expenses'],
                        'startDate' => Carbon::parse($this->startDate),
                        'endDate' => Carbon::parse($this->endDate),
                    ]);
                    
                    $pdfContent = $pdfResponse->getContent();
                    
                    return response()->streamDownload(function () use ($pdfContent) {
                        echo $pdfContent;
                    }, 'income-statement-' . now()->format('YmdHis') . '.pdf', [
                        'Content-Type' => 'application/pdf',
                    ]);
                })
                ->requiresConfirmation(false),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('startDate')
                    ->label(__('filters.date_from'))
                    ->required()
                    ->default(now()->startOfMonth()),
                DatePicker::make('endDate')
                    ->label(__('filters.date_to'))
                    ->required()
                    ->default(now()),
                Select::make('branchId')
                    ->label(__('journals.branch'))
                    ->options(Branch::pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $this->form->validate();
        $data = $this->form->getState();
        $this->startDate = $data['startDate'] ?? $this->startDate;
        $this->endDate = $data['endDate'] ?? $this->endDate;
        $this->branchId = $data['branchId'] ?? $this->branchId;
    }
}

