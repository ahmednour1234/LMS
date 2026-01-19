<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('exam_center.results_summary') }} - {{ \App\Support\Helpers\MultilingualHelper::formatMultilingualField($exam->title ?? []) }}</title>
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
    <h1>{{ __('exam_center.results_summary') }}</h1>
    
    <div class="info">
        <div class="info-item"><strong>{{ __('exams.title') }}:</strong> {{ \App\Support\Helpers\MultilingualHelper::formatMultilingualField($exam->title ?? []) }}</div>
        <div class="info-item"><strong>{{ __('exams.total_score') }}:</strong> {{ number_format($exam->total_score ?? 0, 2) }}</div>
        <div class="info-item"><strong>{{ __('exam_center.total_attempts') }}:</strong> {{ $attempts->count() }}</div>
        <div class="info-item"><strong>{{ __('exam_center.graded_attempts') }}:</strong> {{ $attempts->where('status', 'graded')->count() }}</div>
    </div>
    
    <h2>{{ __('exam_center.attempts') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('exam_center.student_name') }}</th>
                <th>{{ __('exam_center.enrollment_ref') }}</th>
                <th>{{ __('exam_center.started_at') }}</th>
                <th>{{ __('exam_center.submitted_at') }}</th>
                <th>{{ __('attempts.status_label') }}</th>
                <th>{{ __('exam_center.score') }}</th>
                <th>{{ __('exam_center.percentage') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attempts as $attempt)
                <tr>
                    <td>{{ $attempt->student->name ?? 'N/A' }}</td>
                    <td>{{ $attempt->enrollment->reference ?? 'N/A' }}</td>
                    <td>{{ $attempt->started_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                    <td>{{ $attempt->submitted_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                    <td>{{ __('attempts.status.' . $attempt->status) }}</td>
                    <td>{{ number_format($attempt->score ?? 0, 2) }} / {{ number_format($attempt->max_score ?? 0, 2) }}</td>
                    <td>{{ number_format($attempt->percentage ?? 0, 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    @if($attempts->where('status', 'graded')->count() > 0)
        <h2>{{ __('exam_center.statistics') }}</h2>
        <div class="info">
            @php
                $gradedAttempts = $attempts->where('status', 'graded');
                $avgScore = $gradedAttempts->avg('score');
                $avgPercentage = $gradedAttempts->avg('percentage');
                $highestScore = $gradedAttempts->max('score');
                $lowestScore = $gradedAttempts->min('score');
            @endphp
            <div class="info-item"><strong>{{ __('exam_center.avg_score') }}:</strong> {{ number_format($avgScore ?? 0, 2) }}</div>
            <div class="info-item"><strong>{{ __('exam_center.avg_percentage') }}:</strong> {{ number_format($avgPercentage ?? 0, 1) }}%</div>
            <div class="info-item"><strong>{{ __('exam_center.highest_score') }}:</strong> {{ number_format($highestScore ?? 0, 2) }}</div>
            <div class="info-item"><strong>{{ __('exam_center.lowest_score') }}:</strong> {{ number_format($lowestScore ?? 0, 2) }}</div>
        </div>
    @endif
</body>
</html>
