# Arabic Fonts for PDF Export

This directory should contain Arabic font files for proper PDF rendering.

## Recommended Fonts

### Amiri (Recommended)
- Download from: https://fonts.google.com/specimen/Amiri
- Required files:
  - `Amiri-Regular.ttf`
  - `Amiri-Bold.ttf`
  - `Amiri-Italic.ttf`
  - `Amiri-BoldItalic.ttf`

### Cairo (Alternative)
- Download from: https://fonts.google.com/specimen/Cairo
- Required files:
  - `Cairo-Regular.ttf`
  - `Cairo-Bold.ttf`

### DejaVu Sans (Fallback)
- Already included with mPDF
- Works for basic Arabic support

## Installation

1. Download the font files from Google Fonts or another source
2. Place the TTF files in this directory
3. The PdfService will automatically detect and use available fonts

## Font Configuration

Fonts are configured in `app/Services/PdfService.php`. The service will:
- Auto-detect Arabic fonts (Amiri preferred)
- Fall back to DejaVu Sans if Arabic fonts are not available
- Apply RTL direction when locale is 'ar'

## License

Ensure you have proper licensing for any fonts you use. Google Fonts are typically open source and free to use.

