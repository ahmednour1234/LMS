<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('exam_center.attempt_report') }} - {{ $attempt->student->name ?? 'N/A' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 20px; }
        h2 { font-size: 14px; margin-top: 20px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .info { margin-bottom: 15px; }
        .info-item { margin: 5px 0; }
    </style>
</head>
<body>
    <h1>{{ __('exam_center.attempt_report') }}</h1>
    
    <div class="info">
        <div class="info-item"><strong>{{ __('exam_center.student_name') }}:</strong> {{ $attempt->student->name ?? 'N/A' }}</div>
        <div class="info-item"><strong>{{ __('exam_center.enrollment_ref') }}:</strong> {{ $attempt->enrollment->reference ?? 'N/A' }}</div>
        <div class="info-item"><strong>{{ __('exams.title') }}:</strong> {{ \App\Support\Helpers\MultilingualHelper::formatMultilingualField($attempt->exam->title ?? []) }}</div>
        <div class="info-item"><strong>{{ __('exam_center.started_at') }}:</strong> {{ $attempt->started_at?->format('Y-m-d H:i') ?? 'N/A' }}</div>
        <div class="info-item"><strong>{{ __('exam_center.submitted_at') }}:</strong> {{ $attempt->submitted_at?->format('Y-m-d H:i') ?? 'N/A' }}</div>
        <div class="info-item"><strong>{{ __('attempts.status_label') }}:</strong> {{ __('attempts.status.' . $attempt->status) }}</div>
        <div class="info-item"><strong>{{ __('exam_center.score') }}:</strong> {{ number_format($attempt->score ?? 0, 2) }} / {{ number_format($attempt->max_score ?? 0, 2) }}</div>
        <div class="info-item"><strong>{{ __('exam_center.percentage') }}:</strong> {{ number_format($attempt->percentage ?? 0, 1) }}%</div>
    </div>
    
    <h2>{{ __('exam_center.answers') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('exam_center.question_number') }}</th>
                <th>{{ __('exam_questions.question') }}</th>
                <th>{{ __('exam_center.student_answer') }}</th>
                <th>{{ __('exam_center.points_earned') }}</th>
                <th>{{ __('exam_center.points_possible') }}</th>
                <th>{{ __('grading.feedback') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attempt->answers as $index => $answer)
                @php
                    $question = $answer->question;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \App\Support\Helpers\MultilingualHelper::formatMultilingualField($question->question ?? []) }}</td>
                    <td>
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
                    </td>
                    <td>{{ number_format($answer->points_earned ?? 0, 2) }}</td>
                    <td>{{ number_format($answer->points_possible ?? 0, 2) }}</td>
                    <td>{{ $answer->feedback ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
