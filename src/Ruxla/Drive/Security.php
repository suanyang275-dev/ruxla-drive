<?php
namespace Ruxla\Drive;

class Security {
    private $key;
    private $cipher = 'AES-256-CBC';

    public function __construct($key) {
        if (empty($key)) {
            throw new \Exception("Encryption key tidak boleh kosong");
        }
        $this->key = hash('sha256', $key, true);
    }

    /**
     * Encrypt data dengan authenticated encryption (format BARU)
     */
    public function encrypt($data) {
        if (empty($data)) {
            throw new \Exception("Data tidak boleh kosong");
        }

        $ivLen = openssl_cipher_iv_length($this->cipher);
        $iv = random_bytes($ivLen);
        
        $encryptedRaw = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($encryptedRaw === false) {
            throw new \Exception("Encryption gagal");
        }

        // Generate HMAC untuk authentication
        $hmac = hash_hmac('sha256', $iv . $encryptedRaw, $this->key, true);

        // Format BARU: IV + HMAC + EncryptedData
        return $iv . $hmac . $encryptedRaw;
    }

    /**
     * Decrypt data - AUTO DETECT format lama atau baru
     * 
     * Format LAMA: IV (16 bytes) + EncryptedData
     * Format BARU: IV (16 bytes) + HMAC (32 bytes) + EncryptedData
     */
    public function decrypt($data) {
        if (empty($data)) {
            throw new \Exception("Data tidak boleh kosong");
        }

        $ivLen = openssl_cipher_iv_length($this->cipher);
        $hmacLen = 32; // SHA256 = 32 bytes
        
        // Minimum size untuk format baru: IV + HMAC + minimal 1 byte encrypted
        $minNewFormat = $ivLen + $hmacLen + 1;

        // Coba format BARU dulu (jika size cukup)
        if (strlen($data) >= $minNewFormat) {
            $result = $this->tryDecryptNew($data, $ivLen, $hmacLen);
            if ($result !== false) {
                return $result;
            }
        }

        // Fallback ke format LAMA
        return $this->decryptLegacy($data, $ivLen);
    }

    /**
     * Coba dekripsi format BARU dengan HMAC verification
     */
    private function tryDecryptNew($data, $ivLen, $hmacLen) {
        if (strlen($data) <= $ivLen + $hmacLen) {
            return false;
        }

        // Extract parts
        $iv = substr($data, 0, $ivLen);
        $hmac = substr($data, $ivLen, $hmacLen);
        $encryptedRaw = substr($data, $ivLen + $hmacLen);

        // Verify HMAC
        $expectedHmac = hash_hmac('sha256', $iv . $encryptedRaw, $this->key, true);
        if (!hash_equals($hmac, $expectedHmac)) {
            // HMAC verification gagal, bukan format baru
            return false;
        }

        // Decrypt
        $decrypted = openssl_decrypt($encryptedRaw, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            error_log("Decryption format baru gagal");
            return false;
        }

        return $decrypted;
    }

    /**
     * Dekripsi format LAMA (IV + EncryptedData)
     */
    private function decryptLegacy($data, $ivLen) {
        if (strlen($data) <= $ivLen) {
            error_log("Data terlalu pendek untuk decryption");
            return false;
        }

        $iv = substr($data, 0, $ivLen);
        $encryptedRaw = substr($data, $ivLen);
        
        $decrypted = openssl_decrypt($encryptedRaw, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            error_log("Decryption format lama gagal");
            return false;
        }

        return $decrypted;
    }

    /**
     * Validasi encrypted data format
     */
    public function isValidEncryptedData($data) {
        $ivLen = openssl_cipher_iv_length($this->cipher);
        return strlen($data) > $ivLen;
    }
}