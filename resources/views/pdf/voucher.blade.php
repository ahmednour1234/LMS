<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('vouchers.types.' . $voucher->voucher_type->value) }} - {{ $voucher->voucher_no }}</title>
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
        .amount-section { margin: 20px 0; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; }
        .amount-section h3 { margin: 0 0 10px 0; font-size: 18px; }
        .accounting-effect { margin-top: 15px; padding: 10px; background-color: #e8f4f8; border-left: 4px solid #2196F3; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('vouchers.types.' . $voucher->voucher_type->value) }} - {{ $voucher->voucher_no }}</h1>
    </div>

    <table class="info-table">
        <tr>
            <td>{{ __('vouchers.voucher_no') }}</td>
            <td>{{ $voucher->voucher_no }}</td>
        </tr>
        <tr>
            <td>{{ __('vouchers.voucher_date') }}</td>
            <td>{{ $voucher->voucher_date->format('Y-m-d') }}</td>
        </tr>
        @if($voucher->payee_name)
        <tr>
            <td>{{ __('vouchers.payee_name') }}</td>
            <td>{{ $voucher->payee_name }}</td>
        </tr>
        @endif
        @if($voucher->reference_no)
        <tr>
            <td>{{ __('vouchers.reference_no') }}</td>
            <td>{{ $voucher->reference_no }}</td>
        </tr>
        @endif
        @if($voucher->branch)
        <tr>
            <td>{{ __('vouchers.branch') }}</td>
            <td>{{ $voucher->branch->name }}</td>
        </tr>
        @endif
        @if($voucher->description)
        <tr>
            <td>{{ __('vouchers.description') }}</td>
            <td>{{ $voucher->description }}</td>
        </tr>
        @endif
        <tr>
            <td>{{ __('vouchers.status') }}</td>
            <td>{{ __('vouchers.status_options.' . $voucher->status->value) }}</td>
        </tr>
    </table>

    <div class="amount-section">
        <h3>{{ __('vouchers.voucher_lines') }}</h3>
        <table class="info-table">
            <tr>
                <td>{{ __('vouchers.cash_bank_account') }}</td>
                <td>{{ $voucher->cashBankAccount ? ($voucher->cashBankAccount->code . ' - ' . $voucher->cashBankAccount->name) : '-' }}</td>
            </tr>
            <tr>
                <td>{{ __('vouchers.counterparty_account') }}</td>
                <td>{{ $voucher->counterpartyAccount ? ($voucher->counterpartyAccount->code . ' - ' . $voucher->counterpartyAccount->name) : '-' }}</td>
            </tr>
            @if($voucher->costCenter)
            <tr>
                <td>{{ __('vouchers.cost_center') }}</td>
                <td>{{ $voucher->costCenter->name }}</td>
            </tr>
            @endif
            @if($voucher->line_description)
            <tr>
                <td>{{ __('vouchers.line_description') }}</td>
                <td>{{ $voucher->line_description }}</td>
            </tr>
            @endif
            <tr>
                <td><strong>{{ __('vouchers.amount') }}</strong></td>
                <td><strong>{{ number_format($voucher->amount, config('money.precision', 3)) }}</strong></td>
            </tr>
        </table>

        <div class="accounting-effect">
            <strong>{{ __('vouchers.accounting_effect') }}:</strong><br>
            @if($voucher->voucher_type->value === 'receipt')
                {{ __('vouchers.debit') }}: {{ $voucher->cashBankAccount ? ($voucher->cashBankAccount->code . ' - ' . $voucher->cashBankAccount->name) : '-' }} ({{ number_format($voucher->amount, config('money.precision', 3)) }})<br>
                {{ __('vouchers.credit') }}: {{ $voucher->counterpartyAccount ? ($voucher->counterpartyAccount->code . ' - ' . $voucher->counterpartyAccount->name) : '-' }} ({{ number_format($voucher->amount, config('money.precision', 3)) }})
            @else
                {{ __('vouchers.debit') }}: {{ $voucher->counterpartyAccount ? ($voucher->counterpartyAccount->code . ' - ' . $voucher->counterpartyAccount->name) : '-' }} ({{ number_format($voucher->amount, config('money.precision', 3)) }})<br>
                {{ __('vouchers.credit') }}: {{ $voucher->cashBankAccount ? ($voucher->cashBankAccount->code . ' - ' . $voucher->cashBankAccount->name) : '-' }} ({{ number_format($voucher->amount, config('money.precision', 3)) }})
            @endif
        </div>
    </div>

    <div class="footer">
        <p>{{ __('pdf.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
        @if($voucher->approved_at)
        <p>{{ __('vouchers.approved_at') }}: {{ $voucher->approved_at->format('Y-m-d H:i:s') }}</p>
        @endif
        @if($voucher->approver)
        <p>{{ __('vouchers.approved_by') }}: {{ $voucher->approver->name }}</p>
        @endif
    </div>
</body>
</html>
