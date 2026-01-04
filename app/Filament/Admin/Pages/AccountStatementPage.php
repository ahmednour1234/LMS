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

class AccountStatementPage extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.account-statement-page';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('reports.account_statement');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.accounting');
    }

    public ?int $accountId = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    
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
                    if (!$this->accountId) {
                        throw new \Exception('Account is required');
                    }
                    
                    $reportService = App::make(ReportService::class);
                    $pdfService = App::make(PdfService::class);
                    
                    $result = $reportService->getAccountStatement(
                        $this->accountId,
                        Carbon::parse($this->startDate),
                        Carbon::parse($this->endDate),
                        auth()->user()
                    );
                    
                    $pdfResponse = $pdfService->report('account-statement', [
                        'account' => $result['account'],
                        'openingBalance' => $result['openingBalance'],
                        'data' => $result['data'],
                        'startDate' => Carbon::parse($this->startDate),
                        'endDate' => Carbon::parse($this->endDate),
                    ]);
                    
                    $pdfContent = $pdfResponse->getContent();
                    
                    return response()->streamDownload(function () use ($pdfContent) {
                        echo $pdfContent;
                    }, 'account-statement-' . now()->format('YmdHis') . '.pdf', [
                        'Content-Type' => 'application/pdf',
                    ]);
                })
                ->disabled(fn () => !$this->accountId)
                ->requiresConfirmation(false),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $this->form->validate();
        $data = $this->form->getState();
        $this->accountId = $data['accountId'] ?? $this->accountId;
        $this->startDate = $data['startDate'] ?? $this->startDate;
        $this->endDate = $data['endDate'] ?? $this->endDate;
    }
}

