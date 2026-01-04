<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Services\ReportService;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneralLedgerPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static string $view = 'filament.admin.pages.general-ledger-page';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?string $navigationLabel = 'reports.general_ledger';

    protected static ?int $navigationSort = 11;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?array $accountIds = null;
    
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
                    
                    $data = $reportService->getGeneralLedger(
                        Carbon::parse($this->startDate),
                        Carbon::parse($this->endDate),
                        $this->accountIds,
                        auth()->user()
                    );
                    
                    $pdfResponse = $pdfService->report('general-ledger', [
                        'data' => $data,
                        'startDate' => Carbon::parse($this->startDate),
                        'endDate' => Carbon::parse($this->endDate),
                    ]);
                    
                    $pdfContent = $pdfResponse->getContent();
                    
                    return response()->streamDownload(function () use ($pdfContent) {
                        echo $pdfContent;
                    }, 'general-ledger-' . now()->format('YmdHis') . '.pdf', [
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
                Select::make('accountIds')
                    ->label(__('accounts.account'))
                    ->multiple()
                    ->options(Account::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $this->form->validate();
        $data = $this->form->getState();
        $this->startDate = $data['startDate'] ?? $this->startDate;
        $this->endDate = $data['endDate'] ?? $this->endDate;
        $this->accountIds = $data['accountIds'] ?? $this->accountIds;
    }
}

