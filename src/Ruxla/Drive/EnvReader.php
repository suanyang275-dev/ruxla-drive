<?php
namespace Ruxla\Drive;

class EnvReader {
    /**
     * Load environment variables from file dengan validasi keamanan
     */
    public static function load($path) {
        if (!file_exists($path)) {
            error_log("EnvReader: File tidak ditemukan: $path");
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            error_log("EnvReader: Gagal membaca file: $path");
            return false;
        }

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') === false) {
                error_log("EnvReader: Format tidak valid pada baris " . ($lineNum + 1));
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!self::isValidVarName($name)) {
                error_log("EnvReader: Nama variable tidak valid: $name");
                continue;
            }

            if (!self::isValidVarValue($value)) {
                error_log("EnvReader: Nilai variable tidak valid: $name");
                continue;
            }

            putenv("$name=$value");
            $_ENV[$name] = $value;
        }

        return true;
    }

    private static function isValidVarName($name) {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) === 1;
    }

    private static function isValidVarValue($value) {
        if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }

        $dangerousPatterns = ['/`/', '/\$\(/', '/\$\{/', '/;\s*/', '/\|\s*/', '/>\s*/', '/<\s*/',];
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) return false;
        }
        return true;
    }
}