<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? __('exports.print') }}</title>
    <style>
        @media print {
            @page {
                margin: 1cm;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: {{ $isRtl ? 'Arial, "Arabic Typesetting", "Traditional Arabic", sans-serif' : 'Arial, sans-serif' }};
            direction: {{ $isRtl ? 'rtl' : 'ltr' }};
            text-align: {{ $isRtl ? 'right' : 'left' }};
            padding: 20px;
            font-size: 12px;
            line-height: 1.6;
        }

        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header .meta {
            font-size: 12px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            direction: {{ $isRtl ? 'rtl' : 'ltr' }};
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: {{ $isRtl ? 'right' : 'left' }};
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }

        .no-print {
            text-align: center;
            margin: 20px 0;
        }

        .no-print button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }

        .no-print button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title ?? __('exports.print') }}</h1>
        <div class="meta">
            {{ __('exports.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>

    <div class="no-print">
        <button onclick="window.print()">{{ __('exports.print') }}</button>
    </div>

    @if($records->count() > 0)
        <table>
            <thead>
                <tr>
                    @foreach($columns as $column)
                        @php
                            $label = property_exists($column, 'label') ? $column->label : (is_callable([$column, 'getLabel']) ? $column->getLabel() : ($column->label ?? (property_exists($column, 'name') ? $column->name : ($column->name ?? ''))));
                        @endphp
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                    <tr>
                        @foreach($columns as $column)
                            <td>
                                @php
                                    // Handle both object properties and method calls
                                    $name = property_exists($column, 'name') ? $column->name : (is_callable([$column, 'getName']) ? $column->getName() : ($column->name ?? ''));
                                    $value = null;
                                    
                                    if (empty($name)) {
                                        $value = '-';
                                    } else {
                                        // Handle relationship columns
                                        if (str_contains($name, '.')) {
                                            $parts = explode('.', $name);
                                            $value = $record;
                                            foreach ($parts as $part) {
                                                if ($value === null) break;
                                                $value = is_object($value) ? $value->getAttribute($part) : ($value[$part] ?? null);
                                            }
                                        } else {
                                            $value = $record->getAttribute($name) ?? $record->{$name} ?? null;
                                        }
                                        
                                        // Format value
                                        if ($value instanceof \DateTimeInterface) {
                                            $value = $value->format('Y-m-d H:i:s');
                                        } elseif (is_bool($value)) {
                                            $value = $value ? __('Yes') : __('No');
                                        } elseif ($value === null) {
                                            $value = '-';
                                        }
                                    }
                                @endphp
                                {{ $value }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; margin: 40px 0; color: #666;">
            {{ __('exports.no_records') }}
        </p>
    @endif

    <div class="footer">
        <p>{{ __('exports.printed_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

