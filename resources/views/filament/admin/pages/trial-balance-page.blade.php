<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <x-filament::section>
            <x-slot name="heading">
                {{ __('reports.generate') }}
            </x-slot>
            <x-slot name="description">
                {{ __('reports.trial_balance') }}
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

        @if($this->reportDate)
            @php
                $reportService = app(\App\Domain\Accounting\Services\ReportService::class);
                $data = $reportService->getTrialBalance(
                    \Carbon\Carbon::parse($this->reportDate),
                    auth()->user()
                );
                
                $totalDebit = $data->sum('totalDebit');
                $totalCredit = $data->sum('totalCredit');
                $totalOpening = $data->sum('openingBalance');
                $totalClosing = $data->sum('closingBalance');
            @endphp

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-filament::section class="!p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('pdf.opening_balance') }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($totalOpening, 2) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/20">
                            <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section class="!p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('pdf.debit') }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
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
                                {{ __('pdf.credit') }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($totalCredit, 2) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/20">
                            <x-heroicon-o-arrow-up class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section class="!p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('pdf.closing_balance') }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($totalClosing, 2) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-amber-100 p-3 dark:bg-amber-900/20">
                            <x-heroicon-o-calculator class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <!-- Report Table -->
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('reports.trial_balance') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('pdf.report_date') }}: {{ \Carbon\Carbon::parse($this->reportDate)->format('d/m/Y') }}
                </x-slot>
                
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        {{ __('accounts.code') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        {{ __('accounts.name') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        {{ __('pdf.opening_balance') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        {{ __('pdf.debit') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        {{ __('pdf.credit') }}
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        {{ __('pdf.closing_balance') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                @forelse($data as $row)
                                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $row->accountCode }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $row->accountName }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700 dark:text-gray-300">
                                            {{ number_format($row->openingBalance, 2) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-green-600 dark:text-green-400">
                                            {{ number_format($row->totalDebit, 2) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-red-600 dark:text-red-400">
                                            {{ number_format($row->totalCredit, 2) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ number_format($row->closingBalance, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('tables.labels.no_records') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($data->isNotEmpty())
                                <tfoot class="bg-gray-50 dark:bg-gray-800">
                                    <tr class="font-semibold">
                                        <td colspan="2" class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ __('pdf.total') }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-900 dark:text-white">
                                            {{ number_format($totalOpening, 2) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-green-600 dark:text-green-400">
                                            {{ number_format($totalDebit, 2) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-red-600 dark:text-red-400">
                                            {{ number_format($totalCredit, 2) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-900 dark:text-white">
                                            {{ number_format($totalClosing, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
