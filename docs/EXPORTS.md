# Table Export System Documentation

This document explains how to enable and customize table exports in Filament resources.

## Overview

The export system provides three export options for Filament tables:
1. **Excel Export (XLSX)** - Export filtered table data to Excel
2. **PDF Export** - Export filtered table data to PDF with Arabic support
3. **Print View** - Clean printable HTML view with RTL support

## Enabling Exports on a Resource

To enable exports on any Filament Resource, follow these steps:

### 1. Add the Trait

Add the `HasTableExports` trait to your Resource class:

```php
use App\Filament\Concerns\HasTableExports;

class YourResource extends Resource
{
    use HasTableExports;
    
    // ... rest of your resource code
}
```

### 2. Add Export Actions to Table

In your Resource's `table()` method, add the export actions to `headerActions()`:

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            // ... your columns
        ])
        ->headerActions(static::getExportActions()) // Add this line
        ->actions([
            // ... your actions
        ]);
}
```

That's it! Your table now has Export Excel, Export PDF, and Print buttons in the header.

## How It Works

### Query Building

The export system automatically:
- Respects current table filters
- Applies current search query
- Maintains current sorting
- Only exports visible columns
- Only exports filtered/visible records

### Excel Export

- Uses FastExcel for memory-efficient streaming
- Exports only visible columns with translated headers
- Formats dates, money, and boolean values appropriately
- Filename format: `{ResourceName}_{Y-m-d_H-i-s}.xlsx`

### PDF Export

- Uses mPDF with Arabic font support
- Auto-detects locale and applies RTL for Arabic
- Landscape orientation for better table display
- Filename format: `{ResourceName}_{Y-m-d_H-i-s}.pdf`

### Print View

- Clean HTML template optimized for printing
- RTL support for Arabic locale
- Auto-opens browser print dialog
- No PDF generation required

## Customizing Exported Columns

By default, all visible columns are exported. To customize:

### Option 1: Hide Columns from Export

Hide columns in the table, and they won't be exported:

```php
Tables\Columns\TextColumn::make('internal_id')
    ->toggleable(isToggledHiddenByDefault: true) // Hidden by default
```

### Option 2: Override Export Actions (Advanced)

You can override the `getExportActions()` method in your Resource to customize:

```php
public static function getExportActions(): array
{
    $actions = parent::getExportActions();
    
    // Customize actions here
    // For example, modify the filename or add custom logic
    
    return $actions;
}
```

## Arabic Font Configuration

### Adding Arabic Fonts

1. Download Arabic fonts (recommended: Amiri or Cairo from Google Fonts)
2. Place TTF files in `resources/fonts/`:
   - `Amiri-Regular.ttf`
   - `Amiri-Bold.ttf`
   - `Amiri-Italic.ttf`
   - `Amiri-BoldItalic.ttf`

3. The `PdfService` will automatically detect and use these fonts

### Font Priority

1. **Amiri** (if available) - Best for Arabic text
2. **Cairo** (if available) - Good alternative
3. **DejaVu Sans** (fallback) - Basic Arabic support

### Configuring Fonts

Font configuration is in `app/Services/PdfService.php`. The service:
- Auto-detects available Arabic fonts
- Falls back to DejaVu Sans if Arabic fonts are missing
- Applies RTL direction when locale is 'ar'

## Performance Considerations

### Large Datasets

- Exports respect current filters - only filtered data is exported
- Excel exports use streaming to handle large datasets
- PDF generation processes records in batches

### Memory Usage

- FastExcel uses streaming for Excel exports
- PDF generation loads records in chunks
- Consider adding pagination limits for very large exports

## Testing

Run the export tests:

```bash
php artisan test --filter ExportTest
```

Tests verify:
- PDF generation returns correct content type
- Arabic text renders without errors
- Excel export returns file response
- Print view respects locale RTL

## Troubleshooting

### Arabic Text Not Rendering Correctly

1. Ensure Arabic fonts are in `resources/fonts/`
2. Check that locale is set to 'ar' when exporting
3. Verify font files are valid TTF format

### Export Actions Not Appearing

1. Ensure trait is added: `use HasTableExports;`
2. Check that `headerActions(static::getExportActions())` is called
3. Clear cache: `php artisan optimize:clear`

### Excel Export Fails

1. Check `storage/app/temp/` directory is writable
2. Verify FastExcel is installed: `composer show rap2hpoutre/fast-excel`
3. Check PHP memory limit for large exports

### PDF Export Fails

1. Verify mPDF is installed: `composer show mpdf/mpdf`
2. Check that font directory exists: `resources/fonts/`
3. Review server logs for specific error messages

## Examples

### Example: Journal Resource

```php
use App\Filament\Concerns\HasTableExports;

class JournalResource extends Resource
{
    use HasTableExports;
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ... columns
            ])
            ->headerActions(static::getExportActions())
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
```

### Example: Custom Export Filename

Override in your Resource:

```php
public static function getExportActions(): array
{
    $actions = parent::getExportActions();
    
    // Modify Excel action
    $actions[0]->action(function (HasTable $livewire) {
        // Custom export logic with custom filename
        $filename = 'custom_name_' . now()->format('Y-m-d');
        // ... export logic
    });
    
    return $actions;
}
```

## Support

For issues or questions:
1. Check this documentation
2. Review test files in `tests/Feature/ExportTest.php`
3. Check service implementations in `app/Services/`

