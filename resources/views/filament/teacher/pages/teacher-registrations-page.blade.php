<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            :heading="__('navigation.enrollments') ?? 'Registrations'"
            :description="__('reports.registrations_hint') ?? 'Track student registrations and payment status.'"
            icon="heroicon-o-academic-cap"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-filament::card class="h-full">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">{{ __('reports.total_registrations') ?? 'Total Registrations' }}</div>
                        <x-filament::icon icon="heroicon-o-user-group" class="h-5 w-5 text-gray-400" />
                    </div>
                    <div class="mt-2 text-3xl font-semibold">{{ $this->stats['total'] ?? 0 }}</div>
                </x-filament::card>

                <x-filament::card class="h-full">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">{{ __('reports.total_amount') ?? 'Total Amount' }}</div>
                        <x-filament::icon icon="heroicon-o-banknotes" class="h-5 w-5 text-gray-400" />
                    </div>
                    <div class="mt-2 text-3xl font-semibold">{{ $this->stats['total_amount'] ?? '0.00' }}</div>
                </x-filament::card>

                <x-filament::card class="h-full">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">{{ __('reports.paid_amount') ?? 'Paid' }}</div>
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-gray-400" />
                    </div>
                    <div class="mt-2 text-3xl font-semibold">{{ $this->stats['paid_amount'] ?? '0.00' }}</div>
                </x-filament::card>

                <x-filament::card class="h-full">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">{{ __('reports.due_amount') ?? 'Due' }}</div>
                        <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 text-gray-400" />
                    </div>
                    <div class="mt-2 text-3xl font-semibold">{{ $this->stats['due_amount'] ?? '0.00' }}</div>
                </x-filament::card>
            </div>

            <div class="mt-6">
                {{ $this->table }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
