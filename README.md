# Ruxla Drive - Secure File Management Library

A powerful PHP library for secure file encryption, upload, and access control with role-based permissions.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP: 7.4+](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://www.php.net)
[![Version: 1.0.0](https://img.shields.io/badge/Version-1.0.0-brightgreen.svg)](https://github.com/your-username/ruxla-drive)

## 🚀 Features

- 🔐 **AES-256-CBC Encryption** - Industry-standard encryption with HMAC-SHA256 authentication
- 📤 **Secure File Upload** - MIME type validation, magic bytes verification, file size restrictions
- 👥 **Role-Based Access Control** - Admin, member, and owner-based permissions
- 🔄 **Backward Compatible** - Seamlessly handle both legacy and new encryption formats
- ⚡ **Easy Integration** - Simple API with minimal dependencies
- 🛡️ **Security First** - Path traversal protection, CSRF ready, automatic cleanup

## 📦 Installation

### Via Composer (Recommended)

```bash
composer require ruxla/drive
```

### Manual Installation

1. Download from [GitHub Releases](https://github.com/your-username/ruxla-drive/releases)
2. Extract to your project:

```php
require_once 'vendor/ruxla/drive/src/Ruxla/Drive/Security.php';
require_once 'vendor/ruxla/drive/src/Ruxla/Drive/Uploader.php';
require_once 'vendor/ruxla/drive/src/Ruxla/Drive/DownloadHandler.php';
```

## ⚡ Quick Start

### 1. Encryption & Decryption

```php
<?php
use Ruxla\Drive\Security;

$security = new Security('your-secret-key-here');

// Encrypt file
$data = file_get_contents('document.pdf');
$encrypted = $security->encrypt($data);
file_put_contents('document.pdf.enc', $encrypted);

// Decrypt file (auto-detects format)
$encrypted = file_get_contents('document.pdf.enc');
$decrypted = $security->decrypt($encrypted);
echo $decrypted;
?>
```

### 2. Secure File Upload

```php
<?php
use Ruxla\Drive\Uploader;

$uploader = new Uploader(
    $isPublic = false,              // Encrypt file
    $encryptionKey = 'your-key',    // Encryption key
    $maxFileSize = 52428800         // 50MB limit
);

$result = $uploader->upload(
    $_FILES['document'],            // File from form
    $shouldEncrypt = true           // Encrypt before save
);

if ($result && $result['status'] === 'success') {
    echo "File saved as: " . $result['file'];
} else {
    echo "Upload failed";
}
?>
```

### 3. Secure Download with Access Control

```php
<?php
use Ruxla\Drive\DownloadHandler;

$handler = new DownloadHandler('your-encryption-key');

$userData = [
    'id' => 1,
    'role' => 'admin'
];

$config = [
    'allowed_roles' => ['admin', 'member'],
    'check_owner' => true,
    'strict_owner' => false,  // Only owner can access
    'owner_id' => 1
];

$handler->download('filename.pdf.enc', $userData, $config);
?>
```

## 📚 Full Documentation

### Security Class

#### Constructor
```php
$security = new Security($encryptionKey);
```

#### Methods

**encrypt($data)**
- Encrypts data with AES-256-CBC + HMAC authentication
- Returns: Encrypted data (IV + HMAC + Encrypted)

**decrypt($encrypted)**
- Decrypts data with auto-format detection
- Supports both legacy (IV + Data) and new format (IV + HMAC + Data)
- Returns: Decrypted data or false on error

**isValidEncryptedData($data)**
- Validates if data is in correct encrypted format
- Returns: true/false

### Uploader Class

#### Constructor
```php
$uploader = new Uploader(
    $isPublic = true,           // true = public storage, false = secure/encrypted
    $encryptionKey = null,      // Optional encryption key
    $maxFileSize = 52428800     // Max file size in bytes (50MB default)
);
```

#### Methods

**upload($file, $shouldEncrypt = false)**
- Uploads file with comprehensive validation
- Validates: MIME type, magic bytes, file size, extension
- Generates secure random filename
- Returns: ['status' => 'success', 'file' => 'filename'] or false

**setMaxFileSize($bytes)**
- Customize max file size limit
- Example: $uploader->setMaxFileSize(104857600); // 100MB

#### Allowed File Types
- jpg, jpeg, png, pdf, zip, docx

### DownloadHandler Class

#### Constructor
```php
$handler = new DownloadHandler($encryptionKey);
```

#### Methods

**download($fileName, $userData, $config)**
- Serves file with access control validation
- Auto-decrypts if encrypted
- Sends appropriate headers

#### Config Options
```php
$config = [
    'allowed_roles' => ['admin', 'member'],  // Allowed user roles
    'check_owner' => true,                   // Check file ownership
    'strict_owner' => false,                 // If true, only owner can access (even admins)
    'owner_id' => 1                          // File owner user ID
];
```

## 🔐 Security Features

### Encryption & Authentication
- **AES-256-CBC Encryption** - Industry-standard symmetric encryption
- **HMAC-SHA256 Authentication** - Prevents data tampering and modification
- **Random IV Generation** - Fresh initialization vector per encryption
- **Backward Compatibility** - Seamlessly decrypt files encrypted in legacy format

### File Validation
- **MIME Type Checking** - Validates file type against whitelist
- **Magic Bytes Verification** - Deep content validation beyond file extension
- **File Size Restrictions** - Configurable upload limits prevent storage exhaustion
- **Filename Sanitization** - Secure random filename generation

### Access Control
- **Role-Based Permissions** - Admin, member, owner-based access levels
- **Path Traversal Protection** - realpath() validation prevents directory traversal
- **Ownership Verification** - Verify file owner before download
- **Strict Mode Support** - Owner-only access (bypasses admin override)

### Automatic Safety
- **Exception Handling** - Graceful error handling with logging
- **Resource Cleanup** - Automatic cleanup of resources on error
- **Session Validation** - Optional CSRF token support
- **Header Injection Prevention** - Safe HTTP header handling

## 📂 Project Structure

```
ruxla-drive/
├── src/
│   └── Ruxla/
│       └── Drive/
│           ├── Security.php
│           ├── Uploader.php
│           ├── DownloadHandler.php
│           └── EnvReader.php
├── demo/
│   ├── index.php
│   ├── download.php
│   ├── auth.php
│   └── example.sql
├── composer.json
├── README.md
├── CHANGELOG.md
├── LICENSE
└── .gitignore
```

## 🗄️ Database Schema (Optional)

If using with database backend:

```sql
CREATE TABLE files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    file_name VARCHAR(255),
    original_name VARCHAR(255),
    file_type VARCHAR(50),
    is_encrypted TINYINT(1) DEFAULT 0,
    is_private TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## ⚙️ Error Handling

```php
<?php
try {
    $security = new Security('');  // Empty key throws exception
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
```

## 🛠️ Development

```bash
# Clone repository
git clone https://github.com/your-username/ruxla-drive.git
cd ruxla-drive

# Install dependencies
composer install

# Run demo (requires PHP 7.4+)
cd demo
php -S localhost:8000
```

Then visit: http://localhost:8000

## 📝 Examples

Check the `demo/` folder for:
- Basic file upload example
- Secure file download with access control
- Role-based permission examples
- Database integration pattern

## 📜 License

MIT License - see [LICENSE](LICENSE) file for details

## 🤝 Contributing

Contributions welcome! Please ensure:
- Code follows PSR-12 standards
- All methods are properly documented
- Tests included for new features

## 📧 Support

Questions or found a bug?
- Open an issue on [GitHub](https://github.com/your-username/ruxla-drive/issues)
- Email: your.email@example.com

---

**Made with ❤️ for secure file management**