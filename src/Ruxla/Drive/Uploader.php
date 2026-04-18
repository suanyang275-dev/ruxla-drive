<?php
namespace Ruxla\Drive;

class Uploader {
    private $targetDir;
    private $isPublic;
    private $security;
    private $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'zip', 'docx'];
    private $maxFileSize = 52428800; // 50MB
    private $allowedMimes = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ];

    public function __construct($isPublic = true, $encryptionKey = null, $maxFileSize = null) {
        $this->isPublic = $isPublic;
        $this->targetDir = $isPublic ? 'storage/public/' : 'storage/secure/';
        
        if ($maxFileSize) {
            $this->maxFileSize = $maxFileSize;
        }

        if ($encryptionKey) {
            $this->security = new Security($encryptionKey);
        }

        if (!is_dir($this->targetDir)) {
            mkdir($this->targetDir, 0755, true);
        }
    }

    /**
     * Upload file dengan validasi keamanan lengkap
     */
    public function upload($file, $shouldEncrypt = false) {
        // 1. Validasi error upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("Upload error: " . $file['error']);
            return false;
        }

        // 2. Validasi file size
        if ($file['size'] > $this->maxFileSize) {
            error_log("File terlalu besar: " . $file['size']);
            return false;
        }

        if ($file['size'] <= 0) {
            error_log("File size invalid");
            return false;
        }

        // 3. Validasi extension
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $this->allowedTypes, true)) {
            error_log("Extension tidak diizinkan: $fileExt");
            return false;
        }

        // 4. Validasi MIME type (tanpa finfo_close yang deprecated)
        $mimeType = $this->getMimeType($file['tmp_name']);
        if ($mimeType === false) {
            error_log("Gagal detect MIME type");
            return false;
        }

        $allowedMimes = $this->allowedMimes[$fileExt] ?? [];
        if (!in_array($mimeType, $allowedMimes, true)) {
            error_log("MIME type tidak sesuai: $mimeType (diharapkan: " . implode(', ', $allowedMimes) . ")");
            return false;
        }

        // 5. Validasi file content (magic bytes check)
        if (!$this->validateFileContent($file['tmp_name'], $fileExt)) {
            error_log("File content tidak valid");
            return false;
        }

        // 6. Generate secure filename
        $newFileName = bin2hex(random_bytes(10)) . '.' . $fileExt;
        $fullPath = $this->targetDir . $newFileName;

        // 7. Ensure file permissions are secure
        $umask = umask(0077);

        // 8. Upload dengan encryption jika diperlukan
        if ($shouldEncrypt && $this->security) {
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                umask($umask);
                return false;
            }

            $encryptedContent = $this->security->encrypt($content);
            $result = file_put_contents($fullPath . '.enc', $encryptedContent, LOCK_EX);
            umask($umask);

            if ($result) {
                chmod($fullPath . '.enc', 0600);
                return ["status" => "success", "file" => $newFileName . '.enc'];
            }
        } else {
            $result = move_uploaded_file($file['tmp_name'], $fullPath);
            umask($umask);

            if ($result) {
                chmod($fullPath, 0600);
                return ["status" => "success", "file" => $newFileName];
            }
        }

        return false;
    }

    /**
     * Get MIME type tanpa deprecated finfo_close
     */
    private function getMimeType($filePath) {
        // Method 1: Gunakan finfo_file tanpa close (auto cleanup)
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                return false;
            }
            $mimeType = finfo_file($finfo, $filePath);
            // Don't call finfo_close - deprecated and auto-cleanup works
            return ($mimeType !== false) ? $mimeType : false;
        }

        // Method 2: Fallback ke mime_content_type (jika tersedia)
        if (function_exists('mime_content_type')) {
            return mime_content_type($filePath);
        }

        return false;
    }

    /**
     * Validasi magic bytes file
     */
    private function validateFileContent($filePath, $fileExt) {
        $handle = fopen($filePath, 'rb');
        if (!$handle) return false;

        $header = fread($handle, 12);
        fclose($handle);

        $magicBytes = [
            'jpg' => [0xFF, 0xD8, 0xFF],
            'jpeg' => [0xFF, 0xD8, 0xFF],
            'png' => [0x89, 0x50, 0x4E, 0x47],
            'pdf' => [0x25, 0x50, 0x44, 0x46],
            'zip' => [0x50, 0x4B, 0x03, 0x04],
        ];

        if (!isset($magicBytes[$fileExt])) {
            return true; // Skip validation untuk file types tanpa magic bytes
        }

        $expected = $magicBytes[$fileExt];
        foreach ($expected as $i => $byte) {
            if (ord($header[$i]) !== $byte) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set custom max file size
     */
    public function setMaxFileSize($bytes) {
        if ($bytes > 0) {
            $this->maxFileSize = $bytes;
        }
    }
}