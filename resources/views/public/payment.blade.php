<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('payments.payment') }} - #{{ $payment->id }}</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('payments.payment') }} #{{ $payment->id }}</h1>
        </div>

        <table class="info-table">
            <tr>
                <td>{{ __('payments.student') }}</td>
                <td>{{ $payment->enrollment->student->name ?? '' }}</td>
            </tr>
            <tr>
                <td>{{ __('payments.course') }}</td>
                <td>{{ is_array($payment->enrollment->course->name ?? null) ? ($payment->enrollment->course->name[app()->getLocale()] ?? $payment->enrollment->course->name['ar'] ?? '') : ($payment->enrollment->course->name ?? '') }}</td>
            </tr>
            <tr>
                <td>{{ __('payments.amount') }}</td>
                <td>{{ number_format($payment->amount, 2) }} SAR</td>
            </tr>
            <tr>
                <td>{{ __('payments.method') }}</td>
                <td>{{ __('payments.method_options.' . $payment->method) }}</td>
            </tr>
            <tr>
                <td>{{ __('payments.status') }}</td>
                <td>{{ __('payments.status_options.' . $payment->status) }}</td>
            </tr>
            @if($payment->paid_at)
            <tr>
                <td>{{ __('payments.paid_at') }}</td>
                <td>{{ $payment->paid_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            @endif
        </table>
    </div>
</body>
</html>

