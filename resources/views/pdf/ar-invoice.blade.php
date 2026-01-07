<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('ar_invoices.ar_invoice') }} - #{{ $invoice->id }}</title>
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
        
        .company-info { 
            margin-bottom: 25px; 
            font-size: 13px;
            color: #6b7280;
            line-height: 1.8;
            padding: 15px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 6px;
            border-left: 4px solid #3b82f6;
        }
        
        .company-info p {
            margin: 5px 0;
        }
        
        .company-info strong {
            color: #1e40af;
            font-weight: 600;
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
        
        .installments-section {
            margin-top: 30px;
        }
        
        .installments-title {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .installments-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            overflow: hidden;
        }
        
        .installments-table th {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 12px 15px;
            font-weight: 600;
            font-size: 13px;
        }
        
        [dir="rtl"] .installments-table th {
            text-align: right;
        }
        
        [dir="ltr"] .installments-table th {
            text-align: left;
        }
        
        .installments-table td {
            padding: 10px 15px;
            border: 1px solid #e5e7eb;
            background: white;
        }
        
        .installments-table tr:nth-child(even) td {
            background: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
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
                <h1 class="platform-name">
                    {{ isset($settings['app_name']) && isset($settings['app_name'][app()->getLocale()]) ? $settings['app_name'][app()->getLocale()] : (isset($settings['app_name']['en']) ? $settings['app_name']['en'] : 'LMS') }}
                </h1>
            </div>
            <div>
                <h2 class="invoice-title">{{ __('ar_invoices.ar_invoice') }} #{{ $invoice->id }}</h2>
            </div>
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

        <table class="info-table">
            <tr>
                <td>{{ __('ar_invoices.student') }}</td>
                <td>{{ $invoice->enrollment->student->name ?? '' }}</td>
            </tr>
            <tr>
                <td>{{ __('ar_invoices.enrollment') }}</td>
                <td><strong>{{ $invoice->enrollment->reference ?? '' }}</strong></td>
            </tr>
            @if($invoice->enrollment->course)
            <tr>
                <td>{{ __('enrollments.course') }}</td>
                <td>{{ is_array($invoice->enrollment->course->name) ? ($invoice->enrollment->course->name[app()->getLocale()] ?? $invoice->enrollment->course->name['ar'] ?? '') : $invoice->enrollment->course->name }}</td>
            </tr>
            @endif
            <tr>
                <td>{{ __('ar_invoices.status') }}</td>
                <td>
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ __('ar_invoices.status_options.' . $invoice->status) }}
                    </span>
                </td>
            </tr>
            @if($invoice->issued_at)
            <tr>
                <td>{{ __('ar_invoices.issued_at') }}</td>
                <td>{{ $invoice->issued_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            @endif
        </table>

        @if($invoice->arInstallments->count() > 0)
        <div class="installments-section">
            <h3 class="installments-title">{{ __('installments.installments') }}</h3>
            <table class="installments-table">
                <thead>
                    <tr>
                        <th>{{ __('installments.installment_no') }}</th>
                        <th>{{ __('installments.due_date') }}</th>
                        <th>{{ __('installments.amount') }}</th>
                        <th>{{ __('installments.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->arInstallments as $installment)
                    <tr>
                        <td><strong>#{{ $installment->installment_no }}</strong></td>
                        <td>{{ $installment->due_date->format('Y-m-d') }}</td>
                        <td><strong>{{ number_format($installment->amount, config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}</strong></td>
                        <td>
                            <span class="status-badge status-{{ $installment->status }}">
                                {{ __('installments.status_options.' . $installment->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="amount-section">
            <div class="amount-label">{{ __('ar_invoices.total_amount') }}</div>
            <div class="amount-value">{{ number_format($invoice->total_amount, config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}</div>
            @if($invoice->due_amount > 0)
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #93c5fd;">
                <div style="font-size: 14px; color: #1e40af; font-weight: 600; margin-bottom: 5px;">{{ __('ar_invoices.due_amount') }}</div>
                <div style="font-size: 22px; font-weight: 700; color: #dc2626;">{{ number_format($invoice->due_amount, config('money.precision', 3)) }} {{ config('money.symbol', 'ر.ع') }}</div>
            </div>
            @endif
        </div>

        <div class="footer">
            <p>{{ __('exports.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
            <p style="margin-top: 5px; font-size: 11px; color: #9ca3af;">
                {{ isset($settings['app_name']) && isset($settings['app_name'][app()->getLocale()]) ? $settings['app_name'][app()->getLocale()] : (isset($settings['app_name']['en']) ? $settings['app_name']['en'] : 'LMS') }} - {{ __('exports.all_rights_reserved') ?? 'All rights reserved' }}
            </p>
        </div>
    </div>
</body>
</html>

