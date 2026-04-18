<?php
namespace Ruxla\Drive;

class DownloadHandler {
    private $security;
    private $allowedDir = 'storage/secure/';

    public function __construct($key) {
        if (empty($key)) {
            throw new \Exception("Encryption key tidak boleh kosong");
        }
        $this->security = new Security($key);
    }

    /**
     * Download file dengan access control validation
     */
    public function download($fileName, $userData, $config) {
        // 1. Validasi input
        if (!$this->isValidUserData($userData)) {
            http_response_code(401);
            die("Unauthorized: Data user tidak valid");
        }

        $allowedRoles = $config['allowed_roles'] ?? ['admin'];
        $checkOwner = $config['check_owner'] ?? false;
        $strictOwner = $config['strict_owner'] ?? false;
        $ownerId = $config['owner_id'] ?? null;

        // 2. Cek Role Dasar
        if (!in_array($userData['role'], $allowedRoles, true)) {
            http_response_code(403);
            die("Forbidden: Role tidak diizinkan");
        }

        // 3. Logika Akses Owner
        if ($checkOwner) {
            if ($strictOwner) {
                if ($userData['id'] !== $ownerId) {
                    http_response_code(403);
                    die("Forbidden: File ini bersifat Private. Hanya pemilik yang bisa akses.");
                }
            } else {
                if ($userData['role'] !== 'admin' && $userData['id'] !== $ownerId) {
                    http_response_code(403);
                    die("Forbidden: File ini bukan milik Anda.");
                }
            }
        }

        // 4. Validasi & sanitize filename
        if (!$this->isValidFileName($fileName)) {
            http_response_code(400);
            die("Bad Request: Filename tidak valid");
        }

        // 5. Path traversal protection
        $safeFileName = basename($fileName);
        if ($safeFileName !== $fileName) {
            http_response_code(400);
            die("Bad Request: Invalid file path");
        }

        $path = $this->allowedDir . $safeFileName;

        // 6. Double check path is within allowed directory
        $realPath = realpath($path);
        $realAllowedDir = realpath($this->allowedDir);
        if ($realPath === false || $realAllowedDir === false || 
            strpos($realPath, $realAllowedDir) !== 0) {
            http_response_code(403);
            die("Forbidden: Path traversal detected");
        }

        // 7. File existence check
        if (!file_exists($path)) {
            http_response_code(404);
            die("File tidak ditemukan");
        }

        // 8. File readability check
        if (!is_readable($path)) {
            http_response_code(403);
            die("Forbidden: File tidak dapat dibaca");
        }

        // 9. Validate encrypted data format
        $data = file_get_contents($path);
        if ($data === false) {
            http_response_code(500);
            die("Server error: Gagal membaca file");
        }

        if (!$this->security->isValidEncryptedData($data)) {
            error_log("Invalid encrypted data format: $safeFileName");
            http_response_code(400);
            die("Bad Request: Format file tidak valid");
        }

        // 10. Decrypt file
        $decrypted = $this->security->decrypt($data);
        if ($decrypted === false) {
            error_log("Decryption failed for user " . $userData['id'] . ": $safeFileName");
            http_response_code(500);
            die("Server error: Gagal dekripsi file");
        }

        // 11. Prepare safe filename for download
        $originalName = str_replace('.enc', '', $safeFileName);
        $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName); // Sanitize

        // 12. Send file
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . addslashes($originalName) . '"');
        header('Content-Length: ' . strlen($decrypted));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $decrypted;
        exit;
    }

    /**
     * Validasi data user
     */
    private function isValidUserData($userData) {
        return isset($userData['id'], $userData['role']) && 
               is_numeric($userData['id']) && 
               is_string($userData['role']);
    }

    /**
     * Validasi filename
     */
    private function isValidFileName($fileName) {
        // Only allow alphanumeric, dot, and dash
        return preg_match('/^[a-zA-Z0-9._-]+$/', $fileName) === 1 &&
               strlen($fileName) <= 255 &&
               strpos($fileName, '..') === false;
    }
}