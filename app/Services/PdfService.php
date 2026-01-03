<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class PdfService
{
    protected array $defaultConfig;

    public function __construct()
    {
        $this->defaultConfig = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9,
            'orientation' => 'P',
        ];
    }

    /**
     * Render a view to PDF with proper Arabic support
     *
     * @param string $view
     * @param array $data
     * @param array $options
     * @return Response
     */
    public function render(string $view, array $data = [], array $options = []): Response
    {
        $locale = app()->getLocale();
        $isRtl = $locale === 'ar';

        // Merge default config with options
        $config = array_merge($this->defaultConfig, $options);

        // Set direction based on locale
        if ($isRtl) {
            $config['direction'] = 'rtl';
        }

        // Configure font paths
        $defaultConfig = new ConfigVariables();
        $fontDirs = $defaultConfig->getDefaults()['fontDir'];
        $fontDirs[] = resource_path('fonts');

        $defaultFontConfig = new FontVariables();
        $fontData = $defaultFontConfig->getDefaults()['fontdata'];

        // Add Arabic fonts if available
        $arabicFontPath = resource_path('fonts/Amiri-Regular.ttf');
        if (File::exists($arabicFontPath)) {
            $fontData['amiri'] = [
                'R' => 'Amiri-Regular.ttf',
                'B' => 'Amiri-Bold.ttf',
                'I' => 'Amiri-Italic.ttf',
                'BI' => 'Amiri-BoldItalic.ttf',
            ];
        }

        // Fallback to DejaVu if Amiri not available
        if (empty($fontData['amiri'])) {
            $fontData['dejavusans'] = [
                'R' => 'DejaVuSans.ttf',
                'B' => 'DejaVuSans-Bold.ttf',
                'I' => 'DejaVuSans-Oblique.ttf',
                'BI' => 'DejaVuSans-BoldOblique.ttf',
            ];
        }

        $config['fontDir'] = $fontDirs;
        $config['fontdata'] = $fontData;

        // Set default font for Arabic
        if ($isRtl && isset($fontData['amiri'])) {
            $config['default_font'] = 'amiri';
        } elseif ($isRtl) {
            $config['default_font'] = 'dejavusans';
        }

        // Create mPDF instance
        $mpdf = new Mpdf($config);

        // Render the view
        $html = view($view, $data)->render();

        // Add RTL CSS if Arabic
        if ($isRtl) {
            $rtlCss = '
                <style>
                    body {
                        direction: rtl;
                        text-align: right;
                        font-family: ' . ($config['default_font'] ?? 'dejavusans') . ', sans-serif;
                    }
                    table {
                        direction: rtl;
                    }
                    th, td {
                        text-align: right;
                    }
                </style>
            ';
            $html = $rtlCss . $html;
        }

        $mpdf->WriteHTML($html);
        
        $pdfContent = $mpdf->Output('', 'S');

        return response()->make($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Generate PDF from HTML string
     *
     * @param string $html
     * @param array $options
     * @return Response
     */
    public function renderFromHtml(string $html, array $options = []): Response
    {
        $locale = app()->getLocale();
        $isRtl = $locale === 'ar';

        $config = array_merge($this->defaultConfig, $options);

        if ($isRtl) {
            $config['direction'] = 'rtl';
        }

        // Configure fonts
        $defaultConfig = new ConfigVariables();
        $fontDirs = $defaultConfig->getDefaults()['fontDir'];
        $fontDirs[] = resource_path('fonts');

        $defaultFontConfig = new FontVariables();
        $fontData = $defaultFontConfig->getDefaults()['fontdata'];

        $arabicFontPath = resource_path('fonts/Amiri-Regular.ttf');
        if (File::exists($arabicFontPath)) {
            $fontData['amiri'] = [
                'R' => 'Amiri-Regular.ttf',
                'B' => 'Amiri-Bold.ttf',
                'I' => 'Amiri-Italic.ttf',
                'BI' => 'Amiri-BoldItalic.ttf',
            ];
        }

        if (empty($fontData['amiri'])) {
            $fontData['dejavusans'] = [
                'R' => 'DejaVuSans.ttf',
                'B' => 'DejaVuSans-Bold.ttf',
                'I' => 'DejaVuSans-Oblique.ttf',
                'BI' => 'DejaVuSans-BoldOblique.ttf',
            ];
        }

        $config['fontDir'] = $fontDirs;
        $config['fontdata'] = $fontData;

        if ($isRtl && isset($fontData['amiri'])) {
            $config['default_font'] = 'amiri';
        } elseif ($isRtl) {
            $config['default_font'] = 'dejavusans';
        }

        $mpdf = new Mpdf($config);

        if ($isRtl) {
            $rtlCss = '
                <style>
                    body {
                        direction: rtl;
                        text-align: right;
                        font-family: ' . ($config['default_font'] ?? 'dejavusans') . ', sans-serif;
                    }
                    table {
                        direction: rtl;
                    }
                    th, td {
                        text-align: right;
                    }
                </style>
            ';
            $html = $rtlCss . $html;
        }

        $mpdf->WriteHTML($html);

        return response()->make($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}

