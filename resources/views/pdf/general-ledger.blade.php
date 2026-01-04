<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('pdf.general_ledger') }}</title>
    <style>
        @if(app()->getLocale() === 'ar')
        body { direction: rtl; text-align: right; font-family: 'Amiri', 'DejaVu Sans', sans-serif; }
        @else
        body { direction: ltr; text-align: left; font-family: Arial, sans-serif; }
        @endif
        .header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .info { margin-bottom: 20px; }
        .account-section { margin-bottom: 30px; page-break-inside: avoid; }
        .account-header { background-color: #333; color: #fff; padding: 10px; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th { background-color: #555; color: #fff; padding: 8px; border: 1px solid #000; text-align: center; }
        .table td { padding: 6px; border: 1px solid #ddd; }
        .table td:nth-child(4), .table td:nth-child(5), .table td:nth-child(6) { text-align: right; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('pdf.general_ledger') }}</h1>
    </div>

    <div class="info">
        <p><strong>{{ __('pdf.period') }}:</strong> {{ $startDate->format('Y-m-d') }} - {{ $endDate->format('Y-m-d') }}</p>
    </div>

    @php
        $grouped = $data->groupBy('accountId');
    @endphp

    @foreach($grouped as $accountId => $lines)
        @php
            $firstLine = $lines->first();
            $openingBalance = $lines->first()->openingBalance ?? 0;
        @endphp
        <div class="account-section">
            <div class="account-header">
                {{ $firstLine->accountCode }} - {{ $firstLine->accountName }}
                <br>{{ __('pdf.opening_balance') }}: {{ number_format($openingBalance, 2) }}
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
                    @php $runningBalance = $openingBalance; @endphp
                    @foreach($lines as $line)
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
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        <p>{{ __('pdf.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

