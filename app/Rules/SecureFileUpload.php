<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class SecureFileUpload implements Rule
{
    protected string $failureReason = '';
    protected array $allowedMimeTypes;
    protected array $allowedExtensions;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->allowedMimeTypes = config('facility-document.allowed_mime_types', []);
        $this->allowedExtensions = config('facility-document.allowed_extensions', []);
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (!$value instanceof UploadedFile) {
            $this->failureReason = 'not_file';
            return false;
        }

        // Check if file was uploaded successfully
        if (!$value->isValid()) {
            $this->failureReason = 'upload_error';
            return false;
        }

        // Check file extension
        $extension = strtolower($value->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->failureReason = 'invalid_extension';
            return false;
        }

        // Check MIME type
        $mimeType = $value->getMimeType();
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            $this->failureReason = 'invalid_mime_type';
            return false;
        }

        // Check for dangerous file names
        $originalName = $value->getClientOriginalName();
        if ($this->isDangerousFileName($originalName)) {
            $this->failureReason = 'dangerous_filename';
            return false;
        }

        // Check file size against configuration
        $maxSize = config('facility-document.max_file_size', 10240) * 1024; // Convert KB to bytes
        if ($value->getSize() > $maxSize) {
            $this->failureReason = 'file_too_large';
            return false;
        }

        // Additional security checks
        if ($this->hasExecutableContent($value)) {
            $this->failureReason = 'executable_content';
            return false;
        }

        // Enhanced MIME type validation with content verification
        if (!$this->validateMimeTypeWithContent($value)) {
            $this->failureReason = 'mime_content_mismatch';
            return false;
        }

        // Check for double extensions (e.g., file.pdf.exe)
        if ($this->hasDoubleExtension($originalName)) {
            $this->failureReason = 'double_extension';
            return false;
        }

        // Check for null bytes in filename
        if (strpos($originalName, "\0") !== false) {
            $this->failureReason = 'null_byte_filename';
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        switch ($this->failureReason) {
            case 'not_file':
                return 'アップロードされたデータが有効なファイルではありません。';
            case 'upload_error':
                return 'ファイルのアップロードに失敗しました。再度お試しください。';
            case 'invalid_extension':
                $extensions = implode('、', $this->allowedExtensions);
                return "サポートされていないファイル形式です。利用可能な形式: {$extensions}";
            case 'invalid_mime_type':
                return 'ファイルの実際の形式が拡張子と一致しません。';
            case 'dangerous_filename':
                return 'ファイル名に危険な文字が含まれています。';
            case 'file_too_large':
                $maxSizeMB = config('facility-document.max_file_size', 10240) / 1024;
                return "ファイルサイズが制限を超えています。最大サイズ: {$maxSizeMB}MB";
            case 'executable_content':
                return 'セキュリティ上の理由により、このファイルはアップロードできません。';
            case 'mime_content_mismatch':
                return 'ファイルの内容と拡張子が一致しません。ファイルが破損している可能性があります。';
            case 'double_extension':
                return 'ファイル名に複数の拡張子が含まれています。';
            case 'null_byte_filename':
                return 'ファイル名に無効な文字が含まれています。';
            default:
                return 'ファイルの検証に失敗しました。';
        }
    }

    /**
     * Check if filename contains dangerous patterns.
     */
    protected function isDangerousFileName(string $filename): bool
    {
        // Check for path traversal attempts
        if (strpos($filename, '..') !== false) {
            return true;
        }

        // Check for dangerous characters
        $dangerousChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|'];
        foreach ($dangerousChars as $char) {
            if (strpos($filename, $char) !== false) {
                return true;
            }
        }

        // Check for Windows reserved names
        $reservedNames = [
            'CON', 'PRN', 'AUX', 'NUL',
            'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
            'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9',
        ];

        $nameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        if (in_array(strtoupper($nameWithoutExtension), $reservedNames)) {
            return true;
        }

        // Check for control characters
        if (preg_match('/[\x00-\x1F\x7F]/', $filename)) {
            return true;
        }

        // Check for absolute paths
        if (preg_match('/^[\/\\\\]/', $filename)) {
            return true;
        }

        return false;
    }

    /**
     * Check if file contains executable content.
     * Enhanced security check for malicious files.
     * 
     * Requirements: 7.2, 7.3 - 悪意のあるファイル検出
     */
    protected function hasExecutableContent(UploadedFile $file): bool
    {
        // Read first few bytes to check for executable signatures
        $handle = fopen($file->getPathname(), 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 2048); // Read more bytes for better detection
        fclose($handle);

        // Check for common executable signatures
        $executableSignatures = [
            "\x4D\x5A", // PE executable (Windows .exe)
            "\x7F\x45\x4C\x46", // ELF executable (Linux)
            "\xFE\xED\xFA\xCE", // Mach-O executable (macOS)
            "\xFE\xED\xFA\xCF", // Mach-O executable (macOS 64-bit)
            "\xCA\xFE\xBA\xBE", // Java class file
            "\xFE\xED\xFA\xCF", // Mach-O 64-bit
            "\xCE\xFA\xED\xFE", // Mach-O reverse byte order
            "\xCF\xFA\xED\xFE", // Mach-O 64-bit reverse byte order
            "#!/bin/", // Shell script
            "#!/usr/bin/", // Shell script
            "#!/usr/local/bin/", // Shell script
            "<?php", // PHP script
            "<%", // ASP script
            "<script", // JavaScript/HTML
            "<html", // HTML with potential scripts
            "PK\x03\x04", // ZIP file (could contain executables)
        ];

        foreach ($executableSignatures as $signature) {
            if (strpos($header, $signature) === 0 || strpos($header, $signature) !== false) {
                return true;
            }
        }

        // Additional checks for specific file types
        if ($this->hasScriptContent($header)) {
            return true;
        }

        if ($this->hasSuspiciousArchiveContent($file)) {
            return true;
        }

        return false;
    }

    /**
     * Check for script content in file header.
     */
    protected function hasScriptContent(string $header): bool
    {
        $scriptPatterns = [
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/base64_decode\s*\(/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i', // Event handlers like onclick=
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
        ];

        foreach ($scriptPatterns as $pattern) {
            if (preg_match($pattern, $header)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for suspicious content in archive files.
     */
    protected function hasSuspiciousArchiveContent(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Only check archive files
        if (!in_array($extension, ['zip', 'rar', '7z'])) {
            return false;
        }

        // For ZIP files, we can do basic checks
        if ($extension === 'zip') {
            return $this->checkZipContent($file);
        }

        return false;
    }

    /**
     * Check ZIP file content for suspicious files.
     */
    protected function checkZipContent(UploadedFile $file): bool
    {
        try {
            $zip = new \ZipArchive();
            $result = $zip->open($file->getPathname());
            
            if ($result !== true) {
                return false;
            }

            $dangerousExtensions = [
                'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
                'php', 'asp', 'aspx', 'jsp', 'py', 'rb', 'pl', 'sh', 'bash'
            ];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($extension, $dangerousExtensions)) {
                    $zip->close();
                    return true;
                }

                // Check for suspicious file names
                if ($this->isDangerousFileName($filename)) {
                    $zip->close();
                    return true;
                }
            }

            $zip->close();
        } catch (\Exception $e) {
            // If we can't read the ZIP, consider it suspicious
            return true;
        }

        return false;
    }

    /**
     * Enhanced MIME type validation with content verification.
     */
    protected function validateMimeTypeWithContent(UploadedFile $file): bool
    {
        $declaredMime = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Read file header to verify actual content type
        $handle = fopen($file->getPathname(), 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 512);
        fclose($handle);

        // Verify common file types by their magic bytes
        $magicBytes = [
            'pdf' => ['%PDF', 'application/pdf'],
            'jpg' => ["\xFF\xD8\xFF", 'image/jpeg'],
            'png' => ["\x89PNG\r\n\x1A\n", 'image/png'],
            'gif' => ['GIF87a', 'GIF89a', 'image/gif'],
            'zip' => ['PK\x03\x04', 'application/zip'],
            'docx' => ['PK\x03\x04', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xlsx' => ['PK\x03\x04', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];

        if (isset($magicBytes[$extension])) {
            $expectedSignatures = is_array($magicBytes[$extension][0]) ? $magicBytes[$extension] : [$magicBytes[$extension][0]];
            $expectedMime = end($magicBytes[$extension]);

            $signatureFound = false;
            foreach ($expectedSignatures as $signature) {
                if (strpos($header, $signature) === 0) {
                    $signatureFound = true;
                    break;
                }
            }

            // If signature doesn't match or MIME type doesn't match, it's suspicious
            if (!$signatureFound || $declaredMime !== $expectedMime) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check for double extensions in filename.
     */
    protected function hasDoubleExtension(string $filename): bool
    {
        // Count dots in filename (excluding the main extension)
        $parts = explode('.', $filename);
        
        // If there are more than 2 parts and the second-to-last part looks like an extension
        if (count($parts) > 2) {
            $secondExtension = strtolower($parts[count($parts) - 2]);
            $commonExtensions = [
                'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
                'php', 'asp', 'aspx', 'jsp', 'py', 'rb', 'pl', 'sh', 'bash',
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt',
                'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'zip', 'rar', '7z'
            ];
            
            return in_array($secondExtension, $commonExtensions);
        }
        
        return false;
    }
}