<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $stats = $this->getKpiStats();
            $activeTab = $this->activeTab ?? 'exams_list';
        @endphp

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('exam_center.kpi.total_exams') ?? 'Total Exams' }}
                </x-slot>
                <div class="text-3xl font-bold">
                    {{ $stats['total_exams'] }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('exam_center.kpi.total_attempts') ?? 'Total Attempts' }}
                </x-slot>
                <div class="text-3xl font-bold">
                    {{ $stats['total_attempts'] }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('exam_center.kpi.pending_grading') ?? 'Pending Grading' }}
                </x-slot>
                <div class="text-3xl font-bold text-warning-600 dark:text-warning-400">
                    {{ $stats['pending_grading'] }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('exam_center.kpi.avg_score') ?? 'Average Score' }}
                </x-slot>
                <div class="text-3xl font-bold">
                    {{ $stats['avg_score'] }}%
                </div>
            </x-filament::section>
        </div>

        <div>
            <x-filament::tabs>
                <x-filament::tabs.item
                    wire:click="$set('activeTab', 'exams_list')"
                    :active="$activeTab === 'exams_list'"
                >
                    <x-slot name="icon">
                        <x-heroicon-o-list-bullet class="w-5 h-5" />
                    </x-slot>
                    {{ __('exam_center.tabs.exams_list') ?? 'Exams List' }}
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    wire:click="$set('activeTab', 'exam_builder')"
                    :active="$activeTab === 'exam_builder'"
                >
                    <x-slot name="icon">
                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                    </x-slot>
                    {{ __('exam_center.tabs.exam_builder') ?? 'Exam Builder' }}
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    wire:click="$set('activeTab', 'attempts_grading')"
                    :active="$activeTab === 'attempts_grading'"
                >
                    <x-slot name="icon">
                        <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
                    </x-slot>
                    {{ __('exam_center.tabs.attempts_grading') ?? 'Attempts & Grading' }}
                </x-filament::tabs.item>

                @if($this->selectedAttempt)
                    <x-filament::tabs.item
                        wire:click="$set('activeTab', 'grading')"
                        :active="$activeTab === 'grading'"
                    >
                        <x-slot name="icon">
                            <x-heroicon-o-star class="w-5 h-5" />
                        </x-slot>
                        {{ __('exam_center.tabs.grading') ?? 'Grading' }}
                    </x-filament::tabs.item>
                @endif
            </x-filament::tabs>

            <div class="mt-6">
                @if($activeTab === 'exams_list')
                    {{ $this->examsListTable }}
                @elseif($activeTab === 'exam_builder')
                    <div class="space-y-6">
                        @if($this->selectedExam)
                            <x-filament::section>
                                <x-slot name="heading">
                                    {{ $this->selectedExam->exists ? __('exam_center.edit_exam') : __('exam_center.create_exam') }}
                                </x-slot>
                                <x-slot name="description">
                                    {{ __('exam_center.exam_builder_description') }}
                                </x-slot>
                                
                                <form wire:submit="saveExam">
                                    {{ $this->examBuilderForm }}
                                    
                                    <div class="mt-6 flex gap-4">
                                        <x-filament::button type="submit" color="success">
                                            {{ __('exam_center.save_exam') }}
                                        </x-filament::button>
                                        
                                        <x-filament::button 
                                            type="button" 
                                            color="gray" 
                                            wire:click="$set('activeTab', 'exams_list')"
                                        >
                                            {{ __('exam_center.cancel') }}
                                        </x-filament::button>
                                    </div>
                                </form>
                            </x-filament::section>
                        @else
                            <x-filament::section>
                                <x-slot name="heading">
                                    {{ __('exam_center.select_exam_to_edit') }}
                                </x-slot>
                                <x-slot name="description">
                                    {{ __('exam_center.select_exam_description') }}
                                </x-slot>
                                <p class="text-gray-600 dark:text-gray-400">
                                    {{ __('exam_center.go_to_exams_list') }}
                                </p>
                            </x-filament::section>
                        @endif
                    </div>
                @elseif($activeTab === 'attempts_grading')
                    {{ $this->attemptsTable }}
                @elseif($activeTab === 'grading')
                    @if($this->selectedAttempt)
                        <div class="space-y-6">
                            <x-filament::section>
                                <x-slot name="heading">
                                    {{ __('exam_center.grade_attempt') }}
                                </x-slot>
                                <x-slot name="description">
                                    {{ __('exam_center.student') }}: {{ $this->selectedAttempt->student->name ?? 'N/A' }} | 
                                    {{ __('exam_center.exam') }}: {{ \App\Support\Helpers\MultilingualHelper::formatMultilingualField($this->selectedAttempt->exam->title ?? []) }}
                                </x-slot>
                                
                                <form wire:submit="saveGrading">
                                    {{ $this->gradingForm }}
                                    
                                    <div class="mt-6 flex gap-4">
                                        <x-filament::button type="submit" color="success">
                                            {{ __('grading.finalize_grade') }}
                                        </x-filament::button>
                                        
                                        <x-filament::button 
                                            type="button" 
                                            color="gray" 
                                            wire:click="$set('activeTab', 'attempts_grading')"
                                        >
                                            {{ __('exam_center.cancel') }}
                                        </x-filament::button>
                                    </div>
                                </form>
                            </x-filament::section>
                        </div>
                    @else
                        <x-filament::section>
                            <x-slot name="heading">
                                {{ __('exam_center.select_attempt_to_grade') }}
                            </x-slot>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ __('exam_center.go_to_attempts_tab') }}
                            </p>
                        </x-filament::section>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
