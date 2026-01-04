<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Services\ReportService;
use App\Services\PdfService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Facades\App;

class AccountStatementPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.account-statement-page';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?string $navigationLabel = 'reports.account_statement';

    protected static ?int $navigationSort = 13;

    public ?int $accountId = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('pdf.print'))
                ->icon('heroicon-o-printer')
                ->action(function () {
                    if (!$this->accountId) {
                        return;
                    }
                    
                    $reportService = App::make(ReportService::class);
                    $pdfService = App::make(PdfService::class);
                    
                    $result = $reportService->getAccountStatement(
                        $this->accountId,
                        Carbon::parse($this->startDate),
                        Carbon::parse($this->endDate),
                        auth()->user()
                    );
                    
                    $response = $pdfService->report('account-statement', [
                        'account' => $result['account'],
                        'openingBalance' => $result['openingBalance'],
                        'data' => $result['data'],
                        'startDate' => Carbon::parse($this->startDate),
                        'endDate' => Carbon::parse($this->endDate),
                    ]);
                    
                    return $response;
                })
                ->disabled(fn () => !$this->accountId)
                ->requiresConfirmation(false),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('accountId')
                ->label(__('accounts.account'))
                ->required()
                ->options(Account::where('is_active', true)->pluck('name', 'id'))
                ->searchable(),
            DatePicker::make('startDate')
                ->label(__('filters.date_from'))
                ->required()
                ->default(now()->startOfMonth()),
            DatePicker::make('endDate')
                ->label(__('filters.date_to'))
                ->required()
                ->default(now()),
        ];
    }

    public function generate(): void
    {
        $data = $this->form->getState();
        $this->accountId = $data['accountId'] ?? $this->accountId;
        $this->startDate = $data['startDate'] ?? $this->startDate;
        $this->endDate = $data['endDate'] ?? $this->endDate;
    }
}

