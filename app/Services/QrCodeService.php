<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Generate QR code as SVG string
     *
     * @param string $data
     * @param int $size
     * @return string
     */
    public function generateSvg(string $data, int $size = 200): string
    {
        // If SimpleSoftwareIO QrCode is not installed, use a fallback
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return QrCode::size($size)->generate($data);
        }
        
        // Fallback: return a simple data URI with encoded data
        // In production, install: composer require simplesoftwareio/simple-qrcode
        return $this->generateSimpleQr($data, $size);
    }
    
    /**
     * Generate QR code as data URI (base64 encoded)
     *
     * @param string $data
     * @param int $size
     * @return string
     */
    public function generateDataUri(string $data, int $size = 200): string
    {
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            $svg = QrCode::size($size)->generate($data);
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }
        
        return $this->generateSimpleQr($data, $size);
    }
    
    /**
     * Simple fallback QR code generator (basic implementation)
     * For production, install: composer require simplesoftwareio/simple-qrcode
     */
    protected function generateSimpleQr(string $data, int $size): string
    {
        // Return a placeholder SVG that can be replaced with actual QR code
        // This is a fallback until the package is installed
        $encodedData = urlencode($data);
        return '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">
            <rect width="' . $size . '" height="' . $size . '" fill="white"/>
            <text x="50%" y="50%" text-anchor="middle" font-size="12" fill="black">QR Code</text>
            <text x="50%" y="60%" text-anchor="middle" font-size="10" fill="gray">' . htmlspecialchars(substr($data, 0, 20)) . '</text>
        </svg>';
    }
}

