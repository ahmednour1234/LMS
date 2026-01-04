<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Accounting\Services\PdfService;
use App\Domain\Accounting\Services\ReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Illuminate\Support\Facades\App;

class TrialBalancePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static string $view = 'filament.admin.pages.trial-balance-page';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?string $navigationLabel = 'reports.trial_balance';

    protected static ?int $navigationSort = 10;

    public ?string $reportDate = null;

    public function mount(): void
    {
        $this->reportDate = now()->format('Y-m-d');
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
                    
                    return $pdfService->report('trial-balance', [
                        'data' => $data,
                        'reportDate' => Carbon::parse($this->reportDate),
                    ]);
                }),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('reportDate')
                ->label(__('pdf.report_date'))
                ->required()
                ->default(now()),
        ];
    }
}

