<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('ar_invoices.ar_invoice') }} - #{{ $invoice->id }}</title>
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
            <h1>{{ __('ar_invoices.ar_invoice') }} #{{ $invoice->id }}</h1>
        </div>

        <table class="info-table">
            <tr>
                <td>{{ __('ar_invoices.student') }}</td>
                <td>{{ $invoice->enrollment->student->name ?? '' }}</td>
            </tr>
            <tr>
                <td>{{ __('ar_invoices.enrollment') }}</td>
                <td>{{ $invoice->enrollment->reference ?? '' }}</td>
            </tr>
            <tr>
                <td>{{ __('ar_invoices.total_amount') }}</td>
                <td>{{ number_format($invoice->total_amount, 2) }} SAR</td>
            </tr>
            <tr>
                <td>{{ __('ar_invoices.due_amount') }}</td>
                <td>{{ number_format($invoice->due_amount, 2) }} SAR</td>
            </tr>
            <tr>
                <td>{{ __('ar_invoices.status') }}</td>
                <td>{{ __('ar_invoices.status_options.' . $invoice->status) }}</td>
            </tr>
            @if($invoice->issued_at)
            <tr>
                <td>{{ __('ar_invoices.issued_at') }}</td>
                <td>{{ $invoice->issued_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            @endif
        </table>

        @if($invoice->arInstallments->count() > 0)
        <h3>{{ __('installments.installments') }}</h3>
        <table class="info-table">
            <tr>
                <td>{{ __('installments.installment_no') }}</td>
                <td>{{ __('installments.due_date') }}</td>
                <td>{{ __('installments.amount') }}</td>
                <td>{{ __('installments.status') }}</td>
            </tr>
            @foreach($invoice->arInstallments as $installment)
            <tr>
                <td>{{ $installment->installment_no }}</td>
                <td>{{ $installment->due_date->format('Y-m-d') }}</td>
                <td>{{ number_format($installment->amount, 2) }} SAR</td>
                <td>{{ __('installments.status_options.' . $installment->status) }}</td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
</body>
</html>

