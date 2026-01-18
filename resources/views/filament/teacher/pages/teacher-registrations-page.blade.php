<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            :heading="__('navigation.enrollments') ?? 'Registrations'"
            :description="__('reports.registrations_hint') ?? 'Track student registrations and payment status.'"
            icon="heroicon-o-academic-cap"
        >
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <x-filament::card>
                    <div class="text-sm text-gray-500">{{ __('reports.total_registrations') ?? 'Total Registrations' }}</div>
                    <div class="mt-1 text-2xl font-semibold">{{ $this->stats['total'] ?? 0 }}</div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500">{{ __('reports.total_amount') ?? 'Total Amount' }}</div>
                    <div class="mt-1 text-2xl font-semibold">{{ $this->stats['total_amount'] ?? '0' }}</div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500">{{ __('reports.paid_amount') ?? 'Paid' }}</div>
                    <div class="mt-1 text-2xl font-semibold">{{ $this->stats['paid_amount'] ?? '0' }}</div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500">{{ __('reports.due_amount') ?? 'Due' }}</div>
                    <div class="mt-1 text-2xl font-semibold">{{ $this->stats['due_amount'] ?? '0' }}</div>
                </x-filament::card>
            </div>

            <div class="mt-6">
                {{ $this->table }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
