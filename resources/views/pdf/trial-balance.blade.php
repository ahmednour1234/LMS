<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('pdf.trial_balance') }}</title>
    <style>
        @if(app()->getLocale() === 'ar')
        body { direction: rtl; text-align: right; font-family: 'Amiri', 'DejaVu Sans', sans-serif; }
        @else
        body { direction: ltr; text-align: left; font-family: Arial, sans-serif; }
        @endif
        .header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .info { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th { background-color: #333; color: #fff; padding: 10px; border: 1px solid #000; text-align: center; }
        .table td { padding: 8px; border: 1px solid #ddd; }
        .table td:nth-child(3), .table td:nth-child(4), .table td:nth-child(5) { text-align: right; }
        .total-row { font-weight: bold; background-color: #f0f0f0; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('pdf.trial_balance') }}</h1>
    </div>

    <div class="info">
        <p><strong>{{ __('pdf.report_date') }}:</strong> {{ $reportDate->format('Y-m-d') }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>{{ __('accounts.code') }}</th>
                <th>{{ __('accounts.name') }}</th>
                <th>{{ __('pdf.opening_balance') }}</th>
                <th>{{ __('pdf.debit') }}</th>
                <th>{{ __('pdf.credit') }}</th>
                <th>{{ __('pdf.closing_balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            <tr>
                <td>{{ $row->accountCode }}</td>
                <td>{{ $row->accountName }}</td>
                <td>{{ number_format($row->openingBalance, 2) }}</td>
                <td>{{ number_format($row->totalDebit, 2) }}</td>
                <td>{{ number_format($row->totalCredit, 2) }}</td>
                <td>{{ number_format($row->closingBalance, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">{{ __('pdf.total') }}</td>
                <td>{{ number_format($data->sum('openingBalance'), 2) }}</td>
                <td>{{ number_format($data->sum('totalDebit'), 2) }}</td>
                <td>{{ number_format($data->sum('totalCredit'), 2) }}</td>
                <td>{{ number_format($data->sum('closingBalance'), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('pdf.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

