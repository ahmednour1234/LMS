<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('enrollments.enrollment') }} - {{ $enrollment->reference }}</title>
    <style>
        @if(app()->getLocale() === 'ar')
        body { direction: rtl; text-align: right; font-family: 'Amiri', 'DejaVu Sans', sans-serif; }
        @else
        body { direction: ltr; text-align: left; font-family: Arial, sans-serif; }
        @endif
        body { margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 12px; border: 1px solid #ddd; }
        .info-table td:first-child { font-weight: bold; width: 30%; background-color: #f5f5f5; }
        .qr-code { text-align: center; margin: 30px 0; }
        .qr-code svg { border: 2px solid #ddd; padding: 10px; background: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('enrollments.enrollment') }} - {{ $enrollment->reference }}</h1>
        </div>

        <table class="info-table">
            <tr>
                <td>{{ __('enrollments.reference') }}</td>
                <td>{{ $enrollment->reference }}</td>
            </tr>
            <tr>
                <td>{{ __('enrollments.student') }}</td>
                <td>{{ $enrollment->student->name ?? '' }}</td>
            </tr>
            <tr>
                <td>{{ __('enrollments.course') }}</td>
                <td>{{ is_array($enrollment->course->name ?? null) ? ($enrollment->course->name[app()->getLocale()] ?? $enrollment->course->name['ar'] ?? '') : ($enrollment->course->name ?? '') }}</td>
            </tr>
            <tr>
                <td>{{ __('enrollments.status') }}</td>
                <td>{{ __('enrollments.status_options.' . $enrollment->status->value) }}</td>
            </tr>
            <tr>
                <td>{{ __('enrollments.total_amount') }}</td>
                <td>{{ number_format($enrollment->total_amount, 2) }} SAR</td>
            </tr>
            <tr>
                <td>{{ __('enrollments.paid_amount') }}</td>
                <td>{{ number_format($enrollment->payments()->where('status', 'paid')->sum('amount'), 2) }} SAR</td>
            </tr>
            <tr>
                <td>{{ __('enrollments.due_amount') }}</td>
                <td>{{ number_format($enrollment->total_amount - $enrollment->payments()->where('status', 'paid')->sum('amount'), 2) }} SAR</td>
            </tr>
        </table>

        <div class="qr-code">
            <h3>{{ __('enrollments.qr_code') }}</h3>
            {!! $qrCodeSvg !!}
        </div>
    </div>
</body>
</html>

