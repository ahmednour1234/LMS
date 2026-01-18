<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('course_dashboard.title') }} - {{ $course->code }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 20px; }
        h2 { font-size: 14px; margin-top: 20px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>{{ __('course_dashboard.title') }} - {{ $course->code }}</h1>
    
    <h2>{{ __('course_dashboard.stats.total_enrolled') }}: {{ $stats['total_enrolled'] }}</h2>
    <h2>{{ __('course_dashboard.stats.total_paid') }}: {{ number_format($stats['total_paid'], 3) }} OMR</h2>
    <h2>{{ __('course_dashboard.stats.total_due') }}: {{ number_format($stats['total_due'], 3) }} OMR</h2>
    
    <h2>{{ __('course_dashboard.tabs.registrations') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('course_dashboard.reference') }}</th>
                <th>{{ __('course_dashboard.student_name') }}</th>
                <th>{{ __('course_dashboard.total_amount') }}</th>
                <th>{{ __('course_dashboard.paid_amount') }}</th>
                <th>{{ __('course_dashboard.due_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $enrollment)
                @php
                    $paid = $enrollment->payments()->where('status', 'completed')->sum('amount');
                    $due = max(0, $enrollment->total_amount - $paid);
                @endphp
                <tr>
                    <td>{{ $enrollment->reference }}</td>
                    <td>{{ $enrollment->student->name ?? 'N/A' }}</td>
                    <td>{{ number_format($enrollment->total_amount, 3) }}</td>
                    <td>{{ number_format($paid, 3) }}</td>
                    <td>{{ number_format($due, 3) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
