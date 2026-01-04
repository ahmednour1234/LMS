<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('pdf.invoice') }} - {{ $payment->reference }}</title>
    <style>
        @if(app()->getLocale() === 'ar')
        body { direction: rtl; text-align: right; font-family: 'Amiri', 'DejaVu Sans', sans-serif; }
        @else
        body { direction: ltr; text-align: left; font-family: Arial, sans-serif; }
        @endif
        .header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 8px; border: 1px solid #ddd; }
        .info-table td:first-child { font-weight: bold; width: 30%; background-color: #f5f5f5; }
        .amount { font-size: 20px; font-weight: bold; text-align: right; padding: 15px; background-color: #f0f0f0; border: 2px solid #000; margin-top: 20px; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('pdf.invoice') }}</h1>
    </div>

    <table class="info-table">
        <tr>
            <td>{{ __('payments.reference') }}</td>
            <td>{{ $payment->reference }}</td>
        </tr>
        <tr>
            <td>{{ __('payments.amount') }}</td>
            <td>{{ number_format($payment->amount, 2) }}</td>
        </tr>
        <tr>
            <td>{{ __('payments.payment_method') }}</td>
            <td>{{ $payment->paymentMethod->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>{{ __('payments.status') }}</td>
            <td>{{ __('payments.status_options.' . $payment->status->value) }}</td>
        </tr>
        @if($payment->branch)
        <tr>
            <td>{{ __('payments.branch') }}</td>
            <td>{{ $payment->branch->name }}</td>
        </tr>
        @endif
        @if($payment->paid_at)
        <tr>
            <td>{{ __('payments.paid_at') }}</td>
            <td>{{ $payment->paid_at->format('Y-m-d H:i:s') }}</td>
        </tr>
        @endif
        @if($payment->notes)
        <tr>
            <td>{{ __('payments.notes') }}</td>
            <td>{{ $payment->notes }}</td>
        </tr>
        @endif
    </table>

    <div class="amount">
        {{ __('pdf.total_amount') }}: {{ number_format($payment->amount, 2) }}
    </div>

    <div class="footer">
        <p>{{ __('pdf.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

