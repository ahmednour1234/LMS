<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />

        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <a 
                href="{{ route('filament.teacher.pages.register') }}" 
                class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                {{ __('Create account') }}
            </a>
            <a 
                href="{{ route('filament.teacher.auth.password-reset.request') }}" 
                class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                {{ __('Forgot password?') }}
            </a>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page.simple>
