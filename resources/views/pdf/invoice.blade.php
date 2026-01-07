<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('pdf.invoice') }} - {{ $payment->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @if(app()->getLocale() === 'ar')
        body { 
            direction: rtl; 
            text-align: right; 
            font-family: 'Amiri', 'DejaVu Sans', 'Tajawal', sans-serif; 
            background: #f8f9fa;
            padding: 20px;
        }
        @else
        body { 
            direction: ltr; 
            text-align: left; 
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif; 
            background: #f8f9fa;
            padding: 20px;
        }
        @endif
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        
        .header { 
            margin-bottom: 30px; 
            padding-bottom: 20px; 
            border-bottom: 3px solid #3b82f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .platform-name {
            font-size: 32px;
            font-weight: 700;
            color: #1e40af;
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .invoice-title {
            font-size: 24px;
            font-weight: 600;
            color: #374151;
            margin: 0;
        }
        
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            overflow: hidden;
        }
        
        .info-table td { 
            padding: 12px 15px; 
            border: 1px solid #e5e7eb;
        }
        
        .info-table tr:first-child td {
            border-top: none;
        }
        
        .info-table tr:last-child td {
            border-bottom: none;
        }
        
        .info-table td:first-child { 
            font-weight: 600; 
            width: 35%; 
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            color: #374151;
        }
        
        .info-table td:last-child {
            color: #1f2937;
        }
        
        .amount-section {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 8px;
            border: 2px solid #3b82f6;
        }
        
        .amount-label {
            font-size: 14px;
            color: #1e40af;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .amount-value {
            font-size: 28px;
            font-weight: 700;
            color: #1e3a8a;
        }
        
        .footer { 
            margin-top: 40px; 
            padding-top: 20px; 
            border-top: 2px solid #e5e7eb; 
            font-size: 12px; 
            color: #6b7280;
            text-align: center;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div>
                @php
                    $appName = \App\Models\Setting::where('key', 'app_name')->first();
                    $platformName = 'LMS';
                    if ($appName && isset($appName->value[app()->getLocale()])) {
                        $platformName = $appName->value[app()->getLocale()];
                    } elseif ($appName && isset($appName->value['en'])) {
                        $platformName = $appName->value['en'];
                    }
                @endphp
                <h1 class="platform-name">{{ $platformName }}</h1>
            </div>
            <div>
                <h2 class="invoice-title">{{ __('pdf.invoice') }}</h2>
            </div>
        </div>

        <table class="info-table">
            <tr>
                <td>{{ __('payments.reference') }}</td>
                <td><strong>{{ $payment->reference }}</strong></td>
            </tr>
            <tr>
                <td>{{ __('payments.payment_method') }}</td>
                <td>{{ $payment->paymentMethod->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>{{ __('payments.status') }}</td>
                <td>
                    <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #d1fae5; color: #065f46;">
                        {{ __('payments.status_options.' . $payment->status->value) }}
                    </span>
                </td>
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

        <div class="amount-section">
            <div class="amount-label">{{ __('pdf.total_amount') }}</div>
            <div class="amount-value">{{ number_format($payment->amount, config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}</div>
        </div>

        <div class="footer">
            <p>{{ __('pdf.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
            <p style="margin-top: 5px; font-size: 11px; color: #9ca3af;">
                @php
                    $appName = \App\Models\Setting::where('key', 'app_name')->first();
                    $platformName = 'LMS';
                    if ($appName && isset($appName->value[app()->getLocale()])) {
                        $platformName = $appName->value[app()->getLocale()];
                    } elseif ($appName && isset($appName->value['en'])) {
                        $platformName = $appName->value['en'];
                    }
                @endphp
                {{ $platformName }} - {{ __('exports.all_rights_reserved') ?? 'All rights reserved' }}
            </p>
        </div>
    </div>
</body>
</html>

