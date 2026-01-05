<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? __('exports.pdf') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: {{ $isRtl ? 'amiri, "Arabic Typesetting", "Traditional Arabic", sans-serif' : 'Arial, sans-serif' }};
            direction: {{ $isRtl ? 'rtl' : 'ltr' }};
            text-align: {{ $isRtl ? 'right' : 'left' }};
            padding: 10px;
            font-size: 10px;
            line-height: 1.5;
        }

        .header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .header .meta {
            font-size: 9px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            direction: {{ $isRtl ? 'rtl' : 'ltr' }};
            font-size: 9px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 5px;
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
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title ?? __('exports.pdf') }}</h1>
        <div class="meta">
            {{ __('exports.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}
        </div>
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
                                        } elseif (is_array($value)) {
                                            // Handle multilingual arrays (e.g., course names)
                                            $locale = app()->getLocale();
                                            $value = $value[$locale] ?? $value['ar'] ?? $value['en'] ?? json_encode($value);
                                        } elseif ($value === null) {
                                            $value = '-';
                                        } elseif (is_object($value) && !($value instanceof \DateTimeInterface)) {
                                            // Handle objects (convert to string representation)
                                            $value = method_exists($value, '__toString') ? $value->__toString() : json_encode($value);
                                        }
                                    }
                                @endphp
                                {{ is_string($value) || is_numeric($value) ? $value : json_encode($value) }}
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
        <p>{{ __('exports.generated_at') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>

