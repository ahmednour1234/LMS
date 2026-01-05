<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('installments.installment') }} - #{{ $installment->id }}</title>
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
        <h1>{{ __('installments.installment') }} #{{ $installment->installment_no }}</h1>
    </div>

    <table class="info-table">
        <tr>
            <td>{{ __('installments.student') }}</td>
            <td>{{ $installment->arInvoice->enrollment->student->name ?? '' }}</td>
        </tr>
        <tr>
            <td>{{ __('installments.invoice_id') }}</td>
            <td>#{{ $installment->arInvoice->id }}</td>
        </tr>
        <tr>
            <td>{{ __('installments.installment_no') }}</td>
            <td>{{ $installment->installment_no }}</td>
        </tr>
        <tr>
            <td>{{ __('installments.due_date') }}</td>
            <td>{{ $installment->due_date->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <td>{{ __('installments.amount') }}</td>
            <td>{{ number_format($installment->amount, 2) }} SAR</td>
        </tr>
        <tr>
            <td>{{ __('installments.paid_amount') }}</td>
            <td>{{ number_format($installment->paid_amount, 2) }} SAR</td>
        </tr>
        <tr>
            <td>{{ __('installments.status') }}</td>
            <td>{{ __('installments.status_options.' . $installment->status) }}</td>
        </tr>
        @if($installment->paid_at)
        <tr>
            <td>{{ __('installments.paid_at') }}</td>
            <td>{{ $installment->paid_at->format('Y-m-d H:i:s') }}</td>
        </tr>
        @endif
    </table>

    <div class="footer">
        <p>{{ __('exports.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

