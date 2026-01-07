<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('pdf.journal_voucher') }} - {{ $journal->reference }}</title>
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
        .lines-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .lines-table th { background-color: #333; color: #fff; padding: 10px; border: 1px solid #000; text-align: center; }
        .lines-table td { padding: 8px; border: 1px solid #ddd; }
        .lines-table td:nth-child(3), .lines-table td:nth-child(4) { text-align: right; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .total-row { font-weight: bold; background-color: #f0f0f0; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('pdf.journal_voucher') }}</h1>
    </div>

    <table class="info-table">
        <tr>
            <td>{{ __('journals.reference') }}</td>
            <td>{{ $journal->reference }}</td>
        </tr>
        <tr>
            <td>{{ __('journals.date') }}</td>
            <td>{{ $journal->journal_date->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <td>{{ __('journals.status') }}</td>
            <td>{{ __('journals.status_options.' . $journal->status->value) }}</td>
        </tr>
        @if($journal->branch)
        <tr>
            <td>{{ __('journals.branch') }}</td>
            <td>{{ $journal->branch->name }}</td>
        </tr>
        @endif
        @if($journal->description)
        <tr>
            <td>{{ __('journals.description') }}</td>
            <td>{{ $journal->description }}</td>
        </tr>
        @endif
    </table>

    <table class="lines-table">
        <thead>
            <tr>
                <th>{{ __('journal_lines.account') }}</th>
                <th>{{ __('journal_lines.memo') }}</th>
                <th>{{ __('journal_lines.debit') }}</th>
                <th>{{ __('journal_lines.credit') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($journal->journalLines as $line)
            <tr>
                <td>{{ $line->account->code }} - {{ $line->account->name }}</td>
                <td>{{ $line->memo ?? '-' }}</td>
                <td class="text-right">{{ number_format($line->debit, config('money.precision', 3)) }}</td>
                <td class="text-right">{{ number_format($line->credit, config('money.precision', 3)) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">{{ __('pdf.total') }}</td>
                <td class="text-right">{{ number_format($journal->journalLines->sum('debit'), config('money.precision', 3)) }}</td>
                <td class="text-right">{{ number_format($journal->journalLines->sum('credit'), config('money.precision', 3)) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('pdf.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
        @if($journal->posted_at)
        <p>{{ __('journals.posted_at') }}: {{ $journal->posted_at->format('Y-m-d H:i:s') }}</p>
        @endif
    </div>
</body>
</html>

