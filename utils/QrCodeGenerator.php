<?php
/**
 * QR Code Generator Utility Class
 * Uses Endroid QR Code library v5 to generate and save QR codes
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeGenerator {
    /**
     * Generate and save a QR code image
     *
     * @param string $data Data to encode in the QR code
     * @param string $filename Filename to save the QR code (without extension)
     * @param string $directory Directory to save QR code images
     * @param int $size Size of QR code in pixels
     * @param string $label Optional label text below the QR code
     * @return string Path to the saved QR code image
     */
    public static function generate($data, $filename, $directory = 'uploads/qrcodes', $size = 300, $label = '') {
        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        
        // Create QR code
        $qrCode = QrCode::create($data)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize($size)
            ->setMargin(10)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));
        
        // Create label if provided
        $qrLabel = null;
        if (!empty($label)) {
            $qrLabel = Label::create($label)
                ->setTextColor(new Color(0, 0, 0));
        }
        
        // Create writer and write QR code
        $writer = new PngWriter();
        $result = $writer->write($qrCode, null, $qrLabel);
        
        // Generate a unique filename if not provided
        if (empty($filename)) {
            $filename = 'qr_' . uniqid();
        }
        
        // Ensure the filename has no spaces and unwanted characters
        $filename = self::sanitizeFilename($filename);
        
        // Add .png extension if not already included
        if (!str_ends_with(strtolower($filename), '.png')) {
            $filename .= '.png';
        }
        
        // Full path to save the QR code
        $fullpath = $directory . '/' . $filename;
        
        // Save QR code image
        if (!file_put_contents($fullpath, $result->getString())) {
            throw new Exception("Failed to save QR code image to '{$fullpath}'");
        }
        
        return $fullpath;
    }
    
    /**
     * Sanitize a filename to be safe for the filesystem
     *
     * @param string $filename The filename to sanitize
     * @return string Sanitized filename
     */
    private static function sanitizeFilename($filename) {
        // Remove unwanted characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);
        
        return $filename;
    }
}
?> 