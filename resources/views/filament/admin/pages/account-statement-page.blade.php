<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <x-filament::section>
            <x-slot name="heading">
                {{ __('reports.generate') }}
            </x-slot>
            <x-slot name="description">
                {{ __('reports.account_statement') }}
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

        @if($this->accountId && $this->startDate && $this->endDate)
            @php
                $reportService = app(\App\Domain\Accounting\Services\ReportService::class);
                $result = $reportService->getAccountStatement(
                    $this->accountId,
                    \Carbon\Carbon::parse($this->startDate),
                    \Carbon\Carbon::parse($this->endDate),
                    auth()->user()
                );
                
                $account = $result['account'];
                $openingBalance = $result['openingBalance'];
                $data = $result['data'];
                $closingBalance = $data->isNotEmpty() ? $data->last()->runningBalance : $openingBalance;
                $totalDebit = $data->sum('debit');
                $totalCredit = $data->sum('credit');
            @endphp

            @if($account)
                <!-- Account Info Card -->
                <x-filament::section class="!p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $account->code }} - {{ $account->name }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('filters.date_from') }}: {{ \Carbon\Carbon::parse($this->startDate)->format('d/m/Y') }} 
                                - 
                                {{ __('filters.date_to') }}: {{ \Carbon\Carbon::parse($this->endDate)->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-primary-100 p-4 dark:bg-primary-900/20">
                            <x-heroicon-o-document-text class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>
                </x-filament::section>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-filament::section class="!p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ __('pdf.opening_balance') }}
                                </p>
                                <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($openingBalance, 2) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/20">
                                <x-heroicon-o-arrow-trending-up class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </x-filament::section>

                    <x-filament::section class="!p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ __('pdf.total') }} {{ __('pdf.debit') }}
                                </p>
                                <p class="mt-1 text-xl font-semibold text-green-600 dark:text-green-400">
                                    {{ number_format($totalDebit, 2) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/20">
                                <x-heroicon-o-arrow-down class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </x-filament::section>

                    <x-filament::section class="!p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ __('pdf.total') }} {{ __('pdf.credit') }}
                                </p>
                                <p class="mt-1 text-xl font-semibold text-red-600 dark:text-red-400">
                                    {{ number_format($totalCredit, 2) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/20">
                                <x-heroicon-o-arrow-up class="h-5 w-5 text-red-600 dark:text-red-400" />
                            </div>
                        </div>
                    </x-filament::section>

                    <x-filament::section class="!p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ __('pdf.closing_balance') }}
                                </p>
                                <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($closingBalance, 2) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-amber-100 p-3 dark:bg-amber-900/20">
                                <x-heroicon-o-calculator class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            </div>
                        </div>
                    </x-filament::section>
                </div>

                <!-- Transactions Table -->
                <x-filament::section>
                    <x-slot name="heading">
                        {{ __('reports.account_statement') }}
                    </x-slot>
                    
                    @if($data->isNotEmpty())
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('pdf.date') }}
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('journals.reference') }}
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('pdf.description') }}
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('pdf.debit') }}
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('pdf.credit') }}
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                {{ __('pdf.balance') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                        @foreach($data as $row)
                                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $row->journalDate->format('d/m/Y') }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $row->journalReference }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $row->lineDescription ?: $row->journalDescription }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-green-600 dark:text-green-400">
                                                    {{ $row->debit > 0 ? number_format($row->debit, 2) : '-' }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-red-600 dark:text-red-400">
                                                    {{ $row->credit > 0 ? number_format($row->credit, 2) : '-' }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ number_format($row->runningBalance, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50 dark:bg-gray-800">
                                        <tr class="font-semibold">
                                            <td colspan="3" class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ __('pdf.total') }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-green-600 dark:text-green-400">
                                                {{ number_format($totalDebit, 2) }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-red-600 dark:text-red-400">
                                                {{ number_format($totalCredit, 2) }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-900 dark:text-white">
                                                {{ number_format($closingBalance, 2) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="py-12 text-center">
                            <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ __('tables.labels.no_records') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('reports.no_data_for_period') }}
                            </p>
                        </div>
                    @endif
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
