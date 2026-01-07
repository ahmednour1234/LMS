<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('pdf.income_statement') }}</title>
    <style>
        @if(app()->getLocale() === 'ar')
        body { direction: rtl; text-align: right; font-family: 'Amiri', 'DejaVu Sans', sans-serif; }
        @else
        body { direction: ltr; text-align: left; font-family: Arial, sans-serif; }
        @endif
        .header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .info { margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        .section-header { background-color: #333; color: #fff; padding: 10px; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th { background-color: #555; color: #fff; padding: 8px; border: 1px solid #000; text-align: center; }
        .table td { padding: 6px; border: 1px solid #ddd; }
        .table td:last-child { text-align: right; }
        .total-row { font-weight: bold; background-color: #f0f0f0; }
        .net-income { font-size: 18px; font-weight: bold; text-align: right; padding: 15px; background-color: #e0e0e0; border: 2px solid #000; margin-top: 20px; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('pdf.income_statement') }}</h1>
    </div>

    <div class="info">
        <p><strong>{{ __('pdf.period') }}:</strong> {{ $startDate->format('Y-m-d') }} - {{ $endDate->format('Y-m-d') }}</p>
    </div>

    <div class="section">
        <div class="section-header">{{ __('pdf.revenue') }}</div>
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('accounts.code') }}</th>
                    <th>{{ __('accounts.name') }}</th>
                    <th>{{ __('pdf.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($revenues as $item)
                <tr>
                    <td>{{ $item->accountCode }}</td>
                    <td>{{ $item->accountName }}</td>
                    <td>{{ number_format($item->amount, config('money.precision', 3)) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">{{ __('pdf.total_revenue') }}</td>
                    <td>{{ number_format($revenues->sum('amount'), config('money.precision', 3)) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-header">{{ __('pdf.expenses') }}</div>
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('accounts.code') }}</th>
                    <th>{{ __('accounts.name') }}</th>
                    <th>{{ __('pdf.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $item)
                <tr>
                    <td>{{ $item->accountCode }}</td>
                    <td>{{ $item->accountName }}</td>
                    <td>{{ number_format($item->amount, config('money.precision', 3)) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">{{ __('pdf.total_expenses') }}</td>
                    <td>{{ number_format($expenses->sum('amount'), config('money.precision', 3)) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @php
        $totalRevenue = $revenues->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $netIncome = $totalRevenue - $totalExpenses;
    @endphp

    <div class="net-income">
        {{ __('pdf.net_income') }}: {{ number_format($netIncome, config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}
    </div>

    <div class="footer">
        <p>{{ __('pdf.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

