<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <x-filament::section>
            <x-slot name="heading">
                {{ __('reports.generate') }}
            </x-slot>
            <x-slot name="description">
                {{ __('reports.general_ledger') }}
            </x-slot>
            <form wire:submit="generate" class="space-y-4">
                {{ $this->form }}
                
                <x-filament::button type="submit" class="w-full sm:w-auto">
                    <x-slot name="icon">
                        <x-heroicon-o-arrow-path class="w-5 h-5" />
                    </x-slot>
                    {{ __('reports.generate') }}
                </x-filament::button>
            </form>
        </x-filament::section>

        @if($this->startDate && $this->endDate)
            @php
                $reportService = app(\App\Domain\Accounting\Services\ReportService::class);
                $data = $reportService->getGeneralLedger(
                    \Carbon\Carbon::parse($this->startDate),
                    \Carbon\Carbon::parse($this->endDate),
                    $this->accountIds,
                    auth()->user()
                );
                
                $groupedData = $data->groupBy('accountId');
                $totalDebit = $data->sum('debit');
                $totalCredit = $data->sum('credit');
            @endphp

            @if($data->isNotEmpty())
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-filament::section class="!p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ __('pdf.total') }} {{ __('pdf.debit') }}
                                </p>
                                <p class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                                    {{ number_format($totalDebit, 2) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/20">
                                <x-heroicon-o-arrow-down class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </x-filament::section>

                    <x-filament::section class="!p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ __('pdf.total') }} {{ __('pdf.credit') }}
                                </p>
                                <p class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">
                                    {{ number_format($totalCredit, 2) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/20">
                                <x-heroicon-o-arrow-up class="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                        </div>
                    </x-filament::section>
                </div>

                <!-- Report by Account -->
                @foreach($groupedData as $accountId => $accountData)
                    @php
                        $firstRow = $accountData->first();
                        $openingBalance = $accountData->first()->runningBalance - $accountData->first()->debit + $accountData->first()->credit;
                        $closingBalance = $accountData->last()->runningBalance;
                    @endphp
                    
                    <x-filament::section>
                        <x-slot name="heading">
                            {{ $firstRow->accountCode }} - {{ $firstRow->accountName }}
                        </x-slot>
                        <x-slot name="description">
                            <div class="flex flex-wrap gap-4 text-sm">
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ __('pdf.opening_balance') }}: 
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($openingBalance, 2) }}</span>
                                </span>
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ __('pdf.closing_balance') }}: 
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($closingBalance, 2) }}</span>
                                </span>
                            </div>
                        </x-slot>
                        
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('pdf.date') }}
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('journals.reference') }}
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('pdf.description') }}
                                            </th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('pdf.debit') }}
                                            </th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('pdf.credit') }}
                                            </th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('pdf.balance') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                        @foreach($accountData as $row)
                                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $row->journalDate->format('d/m/Y') }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $row->journalReference }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $row->lineDescription ?: $row->journalDescription }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-green-600 dark:text-green-400">
                                                    {{ $row->debit > 0 ? number_format($row->debit, 2) : '-' }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-red-600 dark:text-red-400">
                                                    {{ $row->credit > 0 ? number_format($row->credit, 2) : '-' }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ number_format($row->runningBalance, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </x-filament::section>
                @endforeach
            @else
                <x-filament::section>
                    <div class="py-12 text-center">
                        <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ __('tables.labels.no_records') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('reports.no_data_for_period') }}
                        </p>
                    </div>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
