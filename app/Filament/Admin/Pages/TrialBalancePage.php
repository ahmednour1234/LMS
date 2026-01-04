<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Accounting\Services\ReportService;
use App\Services\PdfService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\App;

class TrialBalancePage extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static string $view = 'filament.admin.pages.trial-balance-page';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?string $navigationLabel = 'reports.trial_balance';

    protected static ?int $navigationSort = 10;

    public ?string $reportDate = null;
    
    public ?array $data = [];

    public function mount(): void
    {
        $this->reportDate = now()->format('Y-m-d');
        $this->form->fill([
            'reportDate' => now(),
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
                    
                    $data = $reportService->getTrialBalance(
                        Carbon::parse($this->reportDate),
                        auth()->user()
                    );
                    
                    $response = $pdfService->report('trial-balance', [
                        'data' => $data,
                        'reportDate' => Carbon::parse($this->reportDate),
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
                DatePicker::make('reportDate')
                    ->label(__('pdf.report_date'))
                    ->required()
                    ->default(now()),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data = $this->form->getState();
        $this->reportDate = $data['reportDate'] ?? $this->reportDate;
    }
}

