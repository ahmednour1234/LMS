<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}
        
        <x-filament::button type="submit" class="mt-4">
            {{ __('reports.generate') }}
        </x-filament::button>
    </form>

    @if($this->reportDate)
        @php
            $reportService = app(\App\Domain\Accounting\Services\ReportService::class);
            $data = $reportService->getTrialBalance(
                \Carbon\Carbon::parse($this->reportDate),
                auth()->user()
            );
        @endphp

        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">{{ __('reports.trial_balance') }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('accounts.code') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('accounts.name') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('pdf.opening_balance') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('pdf.debit') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('pdf.credit') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('pdf.closing_balance') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($data as $row)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $row->accountCode }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $row->accountName }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">{{ number_format($row->openingBalance, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">{{ number_format($row->totalDebit, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">{{ number_format($row->totalCredit, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">{{ number_format($row->closingBalance, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>

