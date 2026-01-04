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

class IncomeStatementPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.admin.pages.income-statement-page';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?string $navigationLabel = 'reports.income_statement';

    protected static ?int $navigationSort = 12;

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
                ->action(function () {
                    $reportService = App::make(ReportService::class);
                    $pdfService = App::make(PdfService::class);
                    
                    $result = $reportService->getIncomeStatement(
                        Carbon::parse($this->startDate),
                        Carbon::parse($this->endDate),
                        $this->branchId,
                        auth()->user()
                    );
                    
                    $response = $pdfService->report('income-statement', [
                        'revenues' => $result['revenues'],
                        'expenses' => $result['expenses'],
                        'startDate' => Carbon::parse($this->startDate),
                        'endDate' => Carbon::parse($this->endDate),
                    ]);
                    
                    return $response;
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

