<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}
        
        <x-filament::button type="submit" class="mt-4">
            {{ __('reports.generate') }}
        </x-filament::button>
    </form>
</x-filament-panels::page>

