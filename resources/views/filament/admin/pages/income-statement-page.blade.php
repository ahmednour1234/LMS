<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <x-filament::section>
            <x-slot name="heading">
                {{ __('reports.generate') }}
            </x-slot>
            <x-slot name="description">
                {{ __('reports.income_statement') }}
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
                $result = $reportService->getIncomeStatement(
                    \Carbon\Carbon::parse($this->startDate),
                    \Carbon\Carbon::parse($this->endDate),
                    $this->branchId,
                    auth()->user()
                );
                
                $revenues = $result['revenues'];
                $expenses = $result['expenses'];
                $totalRevenue = $revenues->sum('amount');
                $totalExpenses = $expenses->sum('amount');
                $netIncome = $totalRevenue - $totalExpenses;
            @endphp

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-filament::section class="!p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('pdf.total_revenue') }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                                {{ number_format($totalRevenue, 2) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/20">
                            <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section class="!p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('pdf.total_expenses') }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">
                                {{ number_format($totalExpenses, 2) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/20">
                            <x-heroicon-o-arrow-trending-down class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section class="!p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('pdf.net_income') }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold {{ $netIncome >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ number_format($netIncome, 2) }}
                            </p>
                        </div>
                        <div class="rounded-full {{ $netIncome >= 0 ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20' }} p-3">
                            <x-heroicon-o-chart-bar class="h-6 w-6 {{ $netIncome >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" />
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <!-- Period Info -->
            <x-filament::section class="!p-4">
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('pdf.period') }}: 
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($this->startDate)->format('d/m/Y') }} 
                            - 
                            {{ \Carbon\Carbon::parse($this->endDate)->format('d/m/Y') }}
                        </span>
                    </p>
                </div>
            </x-filament::section>

            <!-- Revenues Section -->
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('pdf.revenue') }}
                </x-slot>
                
                @if($revenues->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-green-50 dark:bg-green-900/20">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            {{ __('accounts.code') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            {{ __('accounts.name') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            {{ __('pdf.amount') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach($revenues as $row)
                                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $row->accountCode }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                {{ $row->accountName }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-green-600 dark:text-green-400">
                                                {{ number_format($row->amount, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-green-50 dark:bg-green-900/20">
                                    <tr class="font-semibold">
                                        <td colspan="2" class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ __('pdf.total_revenue') }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-green-600 dark:text-green-400">
                                            {{ number_format($totalRevenue, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('tables.labels.no_records') }}
                    </div>
                @endif
            </x-filament::section>

            <!-- Expenses Section -->
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('pdf.expenses') }}
                </x-slot>
                
                @if($expenses->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-red-50 dark:bg-red-900/20">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            {{ __('accounts.code') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            {{ __('accounts.name') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            {{ __('pdf.amount') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach($expenses as $row)
                                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $row->accountCode }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                {{ $row->accountName }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-red-600 dark:text-red-400">
                                                {{ number_format($row->amount, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-red-50 dark:bg-red-900/20">
                                    <tr class="font-semibold">
                                        <td colspan="2" class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ __('pdf.total_expenses') }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-red-600 dark:text-red-400">
                                            {{ number_format($totalExpenses, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('tables.labels.no_records') }}
                    </div>
                @endif
            </x-filament::section>

            <!-- Net Income Summary -->
            <x-filament::section class="!p-6">
                <div class="flex items-center justify-between rounded-lg border-2 {{ $netIncome >= 0 ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' }}">
                    <div>
                        <p class="text-sm font-medium {{ $netIncome >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ __('pdf.net_income') }}
                        </p>
                        <p class="mt-1 text-3xl font-bold {{ $netIncome >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                            {{ number_format($netIncome, 2) }}
                        </p>
                    </div>
                    <div class="rounded-full {{ $netIncome >= 0 ? 'bg-green-100 dark:bg-green-900/40' : 'bg-red-100 dark:bg-red-900/40' }} p-4">
                        <x-heroicon-o-chart-bar class="h-8 w-8 {{ $netIncome >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" />
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
