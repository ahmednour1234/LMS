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

        // Use XBRiyaz as built-in Arabic font (comes with mPDF)
        // This is the best Arabic font available in mPDF by default
        $config['fontDir'] = $fontDirs;
        $config['fontdata'] = $fontData;

        // Set default font for Arabic
        if ($isRtl) {
            // Use DejaVu Sans Condensed which has good Arabic support in mPDF
            // Enable auto script to font for automatic Arabic font selection
            $config['default_font'] = 'dejavusanscondensed';
            $config['autoScriptToLang'] = true;
            $config['autoLangToFont'] = true;
            // Set Arabic as secondary language
            $config['lang'] = 'ar';
        }

        // Create mPDF instance
        $mpdf = new Mpdf($config);

        // Render the view
        $html = view($view, $data)->render();

        // Add RTL CSS if Arabic
        if ($isRtl) {
            $rtlCss = '
                <style>
                    @font-face {
                        font-family: dejavusanscondensed;
                    }
                    * {
                        font-family: dejavusanscondensed, Arial, sans-serif;
                    }
                    body {
                        direction: rtl;
                        text-align: right;
                        font-family: dejavusanscondensed, Arial, sans-serif;
                        unicode-bidi: embed;
                    }
                    table {
                        direction: rtl;
                    }
                    th, td {
                        text-align: right;
                        font-family: dejavusanscondensed, Arial, sans-serif;
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

        // Use XBRiyaz as built-in Arabic font (comes with mPDF)
        $config['fontDir'] = $fontDirs;
        $config['fontdata'] = $fontData;

        // Set default font for Arabic
        if ($isRtl) {
            $config['default_font'] = 'dejavusanscondensed';
            $config['autoScriptToLang'] = true;
            $config['autoLangToFont'] = true;
            $config['lang'] = 'ar';
        }

        $mpdf = new Mpdf($config);

        if ($isRtl) {
            $rtlCss = '
                <style>
                    @font-face {
                        font-family: dejavusanscondensed;
                    }
                    * {
                        font-family: dejavusanscondensed, Arial, sans-serif;
                    }
                    body {
                        direction: rtl;
                        text-align: right;
                        font-family: dejavusanscondensed, Arial, sans-serif;
                        unicode-bidi: embed;
                    }
                    table {
                        direction: rtl;
                    }
                    th, td {
                        text-align: right;
                        font-family: dejavusanscondensed, Arial, sans-serif;
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

    /**
     * Generate journal voucher PDF
     */
    public function journalVoucher(\App\Domain\Accounting\Models\Journal $journal): Response
    {
        return $this->render('pdf.journal-voucher', [
            'journal' => $journal->load(['journalLines.account', 'branch', 'poster']),
        ]);
    }

    /**
     * Generate invoice PDF
     */
    public function invoice(\App\Domain\Accounting\Models\Payment $payment): Response
    {
        return $this->render('pdf.invoice', [
            'payment' => $payment->load(['paymentMethod', 'branch']),
        ]);
    }

    /**
     * Generate report PDF
     */
    public function report(string $template, array $data): Response
    {
        return $this->render("pdf.{$template}", $data);
    }
}

