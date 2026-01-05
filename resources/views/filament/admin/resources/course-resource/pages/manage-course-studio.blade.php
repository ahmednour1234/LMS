<x-filament-panels::page>
    <div class="grid grid-cols-12 gap-6">
        {{-- Left Panel: Sections & Lessons --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">{{ __('Sections & Lessons') }}</h3>
                    <x-filament::button
                        size="sm"
                        icon="heroicon-o-plus"
                        wire:click="mountAction('createSection')"
                    >
                        {{ __('Add Section') }}
                    </x-filament::button>
                </div>

                <div class="space-y-2">
                    @php
                        $sections = $this->getSections();
                    @endphp

                    @if($sections->isEmpty())
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>{{ __('No sections yet. Create your first section!') }}</p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($sections as $section)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    {{-- Section Header --}}
                                    <div
                                        class="bg-gray-50 dark:bg-gray-900 p-3 flex items-center justify-between cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800"
                                        x-on:click="$dispatch('toggle-section', { id: {{ $section->id }} })"
                                    >
                                        <div class="flex items-center gap-2 flex-1">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                            </svg>
                                            <span class="font-medium">
                                                {{ $section->title[app()->getLocale()] ?? $section->title['en'] ?? 'Untitled' }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                ({{ $section->lessons->count() }} {{ __('lessons') }})
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <x-filament::icon-button
                                                size="sm"
                                                icon="heroicon-o-arrow-up"
                                                wire:click="moveSectionUp({{ $section->id }})"
                                                wire:click.stop
                                                tooltip="{{ __('Move Up') }}"
                                            />
                                            <x-filament::icon-button
                                                size="sm"
                                                icon="heroicon-o-arrow-down"
                                                wire:click="moveSectionDown({{ $section->id }})"
                                                wire:click.stop
                                                tooltip="{{ __('Move Down') }}"
                                            />
                                            <x-filament::icon-button
                                                size="sm"
                                                icon="heroicon-o-pencil"
                                                wire:click="mountAction('editSection', { section: {{ $section->id }} })"
                                                wire:click.stop
                                            />
                                            <x-filament::icon-button
                                                size="sm"
                                                :icon="$section->is_active ? 'heroicon-o-eye' : 'heroicon-o-eye-slash'"
                                                wire:click="toggleSectionActive({{ $section->id }})"
                                                wire:click.stop
                                            />
                                        </div>
                                    </div>

                                    {{-- Lessons List --}}
                                    <div class="bg-white dark:bg-gray-800 p-2 space-y-1" x-show="true">
                                        @if($section->lessons->isEmpty())
                                            <div class="text-sm text-gray-500 dark:text-gray-400 text-center py-2">
                                                {{ __('No lessons') }}
                                            </div>
                                        @else
                                            <div class="space-y-1">
                                                @foreach($section->lessons as $lesson)
                                                    <div
                                                        class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer {{ $this->selectedLessonId === $lesson->id ? 'bg-primary-50 dark:bg-primary-900 border border-primary-200 dark:border-primary-800' : '' }}"
                                                        wire:click="selectLesson({{ $lesson->id }})"
                                                    >
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                                        </svg>
                                                        <span class="flex-1 text-sm">
                                                            {{ $lesson->title[app()->getLocale()] ?? $lesson->title['en'] ?? 'Untitled' }}
                                                        </span>
                                                        <div class="flex items-center gap-1">
                                                            @if($lesson->is_preview)
                                                                <span class="text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-1 rounded">
                                                                    {{ __('Preview') }}
                                                                </span>
                                                            @endif
                                                            <x-filament::icon-button
                                                                size="sm"
                                                                icon="heroicon-o-arrow-up"
                                                                wire:click="moveLessonUp({{ $lesson->id }})"
                                                                wire:click.stop
                                                                tooltip="{{ __('Move Up') }}"
                                                            />
                                                            <x-filament::icon-button
                                                                size="sm"
                                                                icon="heroicon-o-arrow-down"
                                                                wire:click="moveLessonDown({{ $lesson->id }})"
                                                                wire:click.stop
                                                                tooltip="{{ __('Move Down') }}"
                                                            />
                                                            <x-filament::icon-button
                                                                size="sm"
                                                                icon="heroicon-o-pencil"
                                                                wire:click="mountAction('editLesson', { lesson: {{ $lesson->id }} })"
                                                                wire:click.stop
                                                            />
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        <x-filament::button
                                            size="sm"
                                            variant="ghost"
                                            icon="heroicon-o-plus"
                                            wire:click="mountAction('createLesson', { sectionId: {{ $section->id }} })"
                                            wire:click.stop
                                            class="w-full mt-2"
                                        >
                                            {{ __('Add Lesson') }}
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Panel: Lesson Details --}}
        <div class="col-span-12 lg:col-span-8">
            @if($this->getSelectedLesson())
                @php
                    $lesson = $this->getSelectedLesson();
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    {{-- Lesson Header --}}
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold mb-2">
                                    {{ $lesson->title[app()->getLocale()] ?? $lesson->title['en'] ?? 'Untitled' }}
                                </h2>
                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span>
                                        <strong>{{ __('Type') }}:</strong>
                                        {{ match($lesson->lesson_type) {
                                            \App\Domain\Training\Enums\LessonType::RECORDED => __('Recorded'),
                                            \App\Domain\Training\Enums\LessonType::LIVE => __('Live'),
                                            \App\Domain\Training\Enums\LessonType::MIXED => __('Mixed'),
                                            default => '-'
                                        } }}
                                    </span>
                                    @if($lesson->published_at)
                                        <span>
                                            <strong>{{ __('Published') }}:</strong>
                                            {{ $lesson->published_at->format('Y-m-d H:i') }}
                                        </span>
                                    @endif
                                    @if($lesson->estimated_minutes)
                                        <span>
                                            <strong>{{ __('Duration') }}:</strong>
                                            {{ $lesson->estimated_minutes }} {{ __('minutes') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($lesson->is_preview)
                                    <x-filament::badge color="warning">
                                        {{ __('Preview') }}
                                    </x-filament::badge>
                                @endif
                                <x-filament::badge :color="$lesson->is_active ? 'success' : 'danger'">
                                    {{ $lesson->is_active ? __('Active') : __('Inactive') }}
                                </x-filament::badge>
                                <x-filament::button
                                    size="sm"
                                    icon="heroicon-o-pencil"
                                    wire:click="mountAction('editLesson', { lesson: {{ $lesson->id }} })"
                                >
                                    {{ __('Edit') }}
                                </x-filament::button>
                            </div>
                        </div>
                        @if($lesson->description)
                            <p class="text-gray-700 dark:text-gray-300">
                                {{ $lesson->description[app()->getLocale()] ?? $lesson->description['en'] ?? '' }}
                            </p>
                        @endif
                    </div>

                    {{-- Content Tabs --}}
                    <div class="p-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <a href="#" class="border-primary-500 text-primary-600 dark:text-primary-400 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    {{ __('Videos') }}
                                </a>
                                <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    {{ __('Documents') }}
                                </a>
                                <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    {{ __('Tasks') }}
                                </a>
                                <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    {{ __('Quizzes') }}
                                </a>
                                <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    {{ __('Meetings') }}
                                </a>
                                <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    {{ __('Reviews') }}
                                </a>
                            </nav>
                        </div>

                        <div class="mt-6 p-8 text-center text-gray-500 dark:text-gray-400 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                            <p class="text-lg mb-2">{{ __('Content Management') }}</p>
                            <p class="text-sm">{{ __('Content tables will be implemented in Phase 2') }}</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('Select a Lesson') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Choose a lesson from the left panel to view and manage its content') }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

