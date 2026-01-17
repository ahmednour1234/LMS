<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        <x-slot name="description">
            {{ $this->getSubHeading() }}
        </x-slot>

        <form wire:submit="resetPassword" class="space-y-6">
            {{ $this->form }}

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('filament.teacher.auth.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                    Back to login
                </a>
                <x-filament::button type="submit" class="w-full sm:w-auto">
                    Reset Password
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
