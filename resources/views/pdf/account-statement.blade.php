<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('pdf.account_statement') }} - {{ $account->code }}</title>
    <style>
        @if(app()->getLocale() === 'ar')
        body { direction: rtl; text-align: right; font-family: 'Amiri', 'DejaVu Sans', sans-serif; }
        @else
        body { direction: ltr; text-align: left; font-family: Arial, sans-serif; }
        @endif
        .header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .account-info { margin-bottom: 20px; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th { background-color: #333; color: #fff; padding: 10px; border: 1px solid #000; text-align: center; }
        .table td { padding: 8px; border: 1px solid #ddd; }
        .table td:nth-child(4), .table td:nth-child(5), .table td:nth-child(6) { text-align: right; }
        .opening-row { font-weight: bold; background-color: #e8e8e8; }
        .closing-row { font-weight: bold; background-color: #f0f0f0; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('pdf.account_statement') }}</h1>
    </div>

    <div class="account-info">
        <p><strong>{{ __('accounts.code') }}:</strong> {{ $account->code }}</p>
        <p><strong>{{ __('accounts.name') }}:</strong> {{ $account->name }}</p>
        <p><strong>{{ __('pdf.period') }}:</strong> {{ $startDate->format('Y-m-d') }} - {{ $endDate->format('Y-m-d') }}</p>
        <p><strong>{{ __('pdf.opening_balance') }}:</strong> {{ number_format($openingBalance, 2) }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>{{ __('pdf.date') }}</th>
                <th>{{ __('journals.reference') }}</th>
                <th>{{ __('pdf.description') }}</th>
                <th>{{ __('pdf.debit') }}</th>
                <th>{{ __('pdf.credit') }}</th>
                <th>{{ __('pdf.balance') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr class="opening-row">
                <td colspan="3">{{ __('pdf.opening_balance') }}</td>
                <td></td>
                <td></td>
                <td>{{ number_format($openingBalance, 2) }}</td>
            </tr>
            @php $runningBalance = $openingBalance; @endphp
            @foreach($data as $line)
                @php
                    $runningBalance += ($line->debit - $line->credit);
                @endphp
                <tr>
                    <td>{{ $line->journalDate->format('Y-m-d') }}</td>
                    <td>{{ $line->journalReference }}</td>
                    <td>{{ $line->lineDescription ?? $line->journalDescription }}</td>
                    <td>{{ number_format($line->debit, 2) }}</td>
                    <td>{{ number_format($line->credit, 2) }}</td>
                    <td>{{ number_format($runningBalance, 2) }}</td>
                </tr>
            @endforeach
            <tr class="closing-row">
                <td colspan="3">{{ __('pdf.closing_balance') }}</td>
                <td>{{ number_format($data->sum('debit'), 2) }}</td>
                <td>{{ number_format($data->sum('credit'), 2) }}</td>
                <td>{{ number_format($runningBalance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('pdf.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

