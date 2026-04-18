<?php
namespace FileHandler;

class SecurityLegacy {
    private $key;
    private $cipher = 'AES-256-CBC';

    public function __construct($key) {
        if (empty($key)) {
            throw new \Exception("Encryption key tidak boleh kosong");
        }
        $this->key = hash('sha256', $key, true);
    }

    /**
     * Decrypt data format LAMA (tanpa HMAC)
     */
    public function decryptLegacy($data) {
        $ivLen = openssl_cipher_iv_length($this->cipher);
        if (strlen($data) <= $ivLen) {
            return false;
        }

        $iv = substr($data, 0, $ivLen);
        $encryptedRaw = substr($data, $ivLen);
        $decrypted = openssl_decrypt($encryptedRaw, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        
        return $decrypted;
    }
}