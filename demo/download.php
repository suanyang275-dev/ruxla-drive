<?php
require_once 'auth.php';
require_once '../src/Ruxla/Drive/Security.php';
require_once '../src/Ruxla/Drive/DownloadHandler.php';

$id = (int)$_GET['id'];
$stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$fileData = $stmt->get_result()->fetch_assoc();

if (!$fileData) {
    http_response_code(404);
    echo "<script>
            alert('File tidak ditemukan di sistem!');
            window.history.back();
          </script>";
    exit;
}


$handler = new \Ruxla\Drive\DownloadHandler(getenv('ENC_KEY'));


$handler->download($fileData['file_name'], $userSession, [
    'allowed_roles' => ['admin', 'member'],
    'check_owner'   => true,
    'strict_owner'  => (bool)$fileData['is_private'], 
    'owner_id'      => $fileData['user_id']
]);