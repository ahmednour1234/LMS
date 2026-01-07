<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('ar_invoices.ar_invoice') }} - #{{ $invoice->id }}</title>
    <style>
        @if(app()->getLocale() === 'ar')
        body { direction: rtl; text-align: right; font-family: 'Amiri', 'DejaVu Sans', sans-serif; }
        @else
        body { direction: ltr; text-align: left; font-family: Arial, sans-serif; }
        @endif
        .header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .company-info { margin-bottom: 20px; font-size: 12px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 8px; border: 1px solid #ddd; }
        .info-table td:first-child { font-weight: bold; width: 30%; background-color: #f5f5f5; }
        .amount { font-size: 20px; font-weight: bold; text-align: right; padding: 15px; background-color: #f0f0f0; border: 2px solid #000; margin-top: 20px; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ isset($settings['app_name']) && isset($settings['app_name'][app()->getLocale()]) ? $settings['app_name'][app()->getLocale()] : 'LMS' }}</h1>
    </div>

    @if(isset($settings))
    <div class="company-info">
        @if(isset($settings['app_phone']['phone']))
            <p><strong>{{ __('settings.phone') }}:</strong> {{ $settings['app_phone']['phone'] }}</p>
        @endif
        @if(isset($settings['app_whatsapp']['phone']))
            <p><strong>{{ __('settings.whatsapp') }}:</strong> {{ $settings['app_whatsapp']['phone'] }}</p>
        @endif
        @if(isset($settings['tax_registration_number']['number']) && !empty($settings['tax_registration_number']['number']))
            <p><strong>{{ __('settings.tax_registration_number') }}:</strong> {{ $settings['tax_registration_number']['number'] }}</p>
        @endif
        @if(isset($settings['commercial_registration_number']['number']) && !empty($settings['commercial_registration_number']['number']))
            <p><strong>{{ __('settings.commercial_registration_number') }}:</strong> {{ $settings['commercial_registration_number']['number'] }}</p>
        @endif
    </div>
    @endif

    <div class="header">
        <h2>{{ __('ar_invoices.ar_invoice') }} #{{ $invoice->id }}</h2>
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
            <td>{{ number_format($invoice->total_amount, config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}</td>
        </tr>
        <tr>
            <td>{{ __('ar_invoices.due_amount') }}</td>
            <td>{{ number_format($invoice->due_amount, config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}</td>
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
            <td>{{ number_format($installment->amount, config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}</td>
            <td>{{ __('installments.status_options.' . $installment->status) }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <div class="footer">
        <p>{{ __('exports.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

