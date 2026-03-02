<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            :heading="__('navigation.enrollments') ?? 'Registrations'"
            :description="__('reports.registrations_hint') ?? 'Track student registrations and payment status.'"
            icon="heroicon-o-academic-cap"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-1 xl:grid-cols-1">
                <x-filament::card class="h-full">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">{{ __('reports.due_amount') ?? 'Due Amount' }}</div>
                        <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 text-gray-400" />
                    </div>
                    <div class="mt-2 text-3xl font-semibold">{{ $this->stats['due_amount'] ?? '0.00' }} OMR</div>
                </x-filament::card>
            </div>

            <div class="mt-6">
                {{ $this->table }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
