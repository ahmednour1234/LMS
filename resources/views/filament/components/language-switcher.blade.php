<button
    wire:click="toggleLanguage"
    type="button"
    class="fi-btn fi-btn-size-sm fi-btn-color-gray fi-btn-text-color-gray-700 dark:fi-btn-color-gray dark:fi-btn-text-color-gray-200 fi-btn-icon fi-btn-icon-start flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset transition duration-75 fi-color-gray hover:bg-gray-50 dark:hover:bg-white/5 ring-gray-950/10 dark:ring-white/20"
>
    <svg class="fi-btn-icon-size h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 10.5h13.5M3 10.5l2.25-5.25M3 10.5l2.25 5.25m0 0L7.5 21h4.5m0 0l2.25-5.25m-2.25 5.25L12 21" />
    </svg>
    <span>{{ $switchLabel }}</span>
</button>

