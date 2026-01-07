<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('enrollments.enrollment') }} - {{ $enrollment->reference }}</title>
    <style>
        @if(app()->getLocale() === 'ar')
        @font-face {
            font-family: dejavusanscondensed;
        }
        * {
            font-family: dejavusanscondensed, Arial, sans-serif !important;
        }
        body { 
            direction: rtl; 
            text-align: right; 
            font-family: dejavusanscondensed, Arial, sans-serif !important;
            unicode-bidi: embed;
        }
        @else
        body { direction: ltr; text-align: left; font-family: Arial, sans-serif; }
        @endif
        .header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0 0 5px 0; font-size: 24px; font-weight: bold; }
        .header h2 { margin: 0; font-size: 18px; font-weight: normal; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 8px; border: 1px solid #ddd; }
        .info-table td:first-child { font-weight: bold; width: 30%; background-color: #f5f5f5; }
        @if(app()->getLocale() === 'ar')
        .info-table td {
            font-family: dejavusanscondensed, Arial, sans-serif !important;
            text-align: right;
        }
        @endif
        .amount { font-size: 20px; font-weight: bold; text-align: right; padding: 15px; background-color: #f0f0f0; border: 2px solid #000; margin-top: 20px; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $app_name ?? 'نظام إدارة التعلم' }}</h1>
        <h2>{{ __('enrollments.enrollment') }} - {{ $enrollment->reference }}</h2>
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
            <td>{{ __('courses.delivery_type') }}</td>
            <td>{{ __('courses.delivery_type_options.' . ($enrollment->course->delivery_type->value ?? 'online')) }}</td>
        </tr>
        <tr>
            <td>{{ __('enrollments.delivery_type') }}</td>
            <td>{{ __('enrollments.delivery_type_options.' . ($enrollment->delivery_type ?? 'online')) }}</td>
        </tr>
        <tr>
            <td>{{ __('enrollments.enrollment_mode') }}</td>
            <td>{{ __('enrollments.enrollment_mode_options.' . ($enrollment->enrollment_mode->value ?? 'course_full')) }}</td>
        </tr>
        <tr>
            <td>{{ __('enrollments.status') }}</td>
            <td>{{ __('enrollments.status_options.' . $enrollment->status->value) }}</td>
        </tr>
        <tr>
            <td>{{ __('enrollments.total_amount') }}</td>
            <td>{{ number_format($enrollment->total_amount, config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}</td>
        </tr>
        <tr>
            <td>{{ __('enrollments.paid_amount') }}</td>
            <td>{{ number_format($enrollment->payments()->where('status', 'paid')->sum('amount'), config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}</td>
        </tr>
        <tr>
            <td>{{ __('enrollments.due_amount') }}</td>
            <td>{{ number_format($enrollment->total_amount - $enrollment->payments()->where('status', 'paid')->sum('amount'), config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}</td>
        </tr>
        @if($enrollment->branch)
        <tr>
            <td>{{ __('enrollments.branch') }}</td>
            <td>{{ $enrollment->branch->name }}</td>
        </tr>
        @endif
    </table>

    <div class="footer">
        <p>{{ __('exports.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

