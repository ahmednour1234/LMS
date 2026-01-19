<div class="space-y-4">
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('exam_center.student_name') }}</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $attempt->student->name ?? 'N/A' }}</p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('exam_center.enrollment_ref') }}</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $attempt->enrollment->reference ?? 'N/A' }}</p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('exams.title') }}</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
            {{ \App\Support\Helpers\MultilingualHelper::formatMultilingualField($attempt->exam->title ?? []) }}
        </p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('exam_center.started_at') }}</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
            {{ $attempt->started_at?->format('Y-m-d H:i') ?? 'N/A' }}
        </p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('exam_center.submitted_at') }}</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
            {{ $attempt->submitted_at?->format('Y-m-d H:i') ?? 'N/A' }}
        </p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('attempts.status_label') }}</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
            {{ __('attempts.status.' . $attempt->status) }}
        </p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('exam_center.score') }}</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
            {{ number_format($attempt->score ?? 0, 2) }} / {{ number_format($attempt->max_score ?? 0, 2) }}
        </p>
    </div>
    
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('exam_center.percentage') }}</label>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
            {{ number_format($attempt->percentage ?? 0, 1) }}%
        </p>
    </div>
    
    @if($attempt->answers->count() > 0)
        <div class="mt-6">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">{{ __('exam_center.answers') }}</label>
            <div class="space-y-3">
                @foreach($attempt->answers as $index => $answer)
                    @php
                        $question = $answer->question;
                    @endphp
                    <div class="border rounded-lg p-3">
                        <div class="font-medium text-sm mb-2">
                            {{ $index + 1 }}. {{ \App\Support\Helpers\MultilingualHelper::formatMultilingualField($question->question ?? []) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <strong>{{ __('exam_center.student_answer') }}:</strong>
                            @if(in_array($question->type, ['mcq', 'true_false']))
                                @php
                                    $options = $question->options ?? [];
                                    $selectedOption = collect($options)->firstWhere('order', $answer->answer);
                                @endphp
                                @if($selectedOption)
                                    {{ \App\Support\Helpers\MultilingualHelper::formatMultilingualField(['ar' => $selectedOption['text_ar'] ?? '', 'en' => $selectedOption['text_en'] ?? '']) }}
                                @else
                                    {{ $answer->answer ?? __('exam_center.no_answer') }}
                                @endif
                            @else
                                {{ $answer->answer ?? __('exam_center.no_answer') }}
                            @endif
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            <strong>{{ __('exam_center.points_earned') }}:</strong> 
                            {{ number_format($answer->points_earned ?? 0, 2) }} / {{ number_format($answer->points_possible ?? 0, 2) }}
                        </div>
                        @if($answer->feedback)
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <strong>{{ __('grading.feedback') }}:</strong> {{ $answer->feedback }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
