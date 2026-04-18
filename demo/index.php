<?php
require_once 'auth.php';
require_once '../src/Ruxla/Drive/Security.php';
require_once '../src/Ruxla/Drive/Uploader.php';

$msg = '';
$statusCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Security Error: CSRF token tidak valid.");
    }

    $mode = $_POST['mode'];
    $isPublic = ($mode === 'public');
    
    $uploader = new \Ruxla\Drive\Uploader($isPublic, getenv('ENC_KEY'));
    $result = $uploader->upload($_FILES['my_file'], !$isPublic);

    if (is_array($result) && $result['status'] === 'success') {
        // Tentukan Flag Enkripsi & Private
        $isEnc = !$isPublic ? 1 : 0;
        $isPriv = ($mode === 'private') ? 1 : 0;

        $stmt = $db->prepare("INSERT INTO files (user_id, file_name, original_name, is_encrypted, is_private) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $userSession['id'], $result['file'], $_FILES['my_file']['name'], $isEnc, $isPriv);
        
        if ($stmt->execute()) {
            // ✅ REDIRECT SETELAH UPLOAD BERHASIL (POST-Redirect-GET pattern)
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit;
        } else {
            $statusCode = 'error';
            $msg = "❌ Database error saat menyimpan file.";
        }
    } else {
        $statusCode = 'error';
        $msg = is_string($result) ? $result : "Upload gagal.";
    }

    // Jika ada error, redirect dengan error status (opsional)
    if ($statusCode === 'error') {
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=error&msg=" . urlencode($msg));
        exit;
    }
}

// Tampilkan pesan dari redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $msg = "<div class='alert success'>✅ File berhasil diamankan ke server!</div>";
    } elseif ($_GET['status'] === 'error') {
        $msg = "<div class='alert error'>❌ " . htmlspecialchars($_GET['msg'] ?? "Upload gagal.") . "</div>";
    }
}

$res = $db->query("SELECT files.*, users.username FROM files JOIN users ON files.user_id = users.id ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - File Shield Pro</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #334155; margin: 0; padding: 0; }
        .navbar { background: #ffffff; padding: 16px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); }
        .navbar .brand { font-weight: 700; font-size: 18px; color: #0f172a; }
        .navbar .user-info { font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 15px;}
        .navbar .user-role { background: #e2e8f0; padding: 4px 10px; border-radius: 20px; font-size: 12px; text-transform: uppercase; }
        .btn-logout { background: #ef4444; color: white; text-decoration: none; padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; }
        .btn-logout:hover { background: #dc2626; }
        
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 30px; }
        @media (max-width: 800px) { .container { grid-template-columns: 1fr; } }
        
        .card { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .card h3 { margin-top: 0; margin-bottom: 20px; font-size: 18px; color: #0f172a; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; font-family: 'Inter', sans-serif;}
        .btn-upload { width: 100%; padding: 12px; background: #0ea5e9; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px;}
        .btn-upload:hover { background: #0284c7; }
        
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th { padding: 14px; border-bottom: 2px solid #e2e8f0; color: #64748b; font-weight: 600; }
        td { padding: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover { background-color: #f8fafc; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge.public { background: #dcfce7; color: #166534; }
        .badge.secure { background: #fef08a; color: #854d0e; }
        .badge.private { background: #fee2e2; color: #991b1b; }
        
        .action-link { text-decoration: none; color: #4f46e5; font-weight: 600; font-size: 13px; background: #e0e7ff; padding: 6px 12px; border-radius: 6px; transition: 0.2s;}
        .action-link:hover { background: #c7d2fe; }
        
        .alert { padding: 15px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; font-weight: 500; }
        .alert.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">File Shield Pro</div>
        <div class="user-info">
            <span>Hi, <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
            <span class="user-role"><?= htmlspecialchars($_SESSION['role']) ?></span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </div>

    <div class="container">
        <div>
            <?= $msg ?>
            <div class="card">
                <h3>Secure Upload</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="form-group">
                        <label>Pilih File</label>
                        <input type="file" name="my_file" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Level Akses & Keamanan</label>
                        <select name="mode" class="form-control">
                            <option value="public">🌍 Publik (Tanpa Enkripsi)</option>
                            <option value="secure">🛡️ Secure (Admin & Pemilik)</option>
                            <option value="private">🔒 Private (Hanya Pemilik)</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="upload" class="btn-upload">Upload Dokumen</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h3>Document Repository</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nama File</th>
                            <th>Uploader</th>
                            <th>Akses</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($f = $res->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight: 500; color: #0f172a;">
                                <?= htmlspecialchars($f['original_name']) ?>
                            </td>
                            <td><?= htmlspecialchars($f['username']) ?></td>
                            <td>
                                <?php if($f['is_private']): ?>
                                    <span class="badge private">Private</span>
                                <?php elseif($f['is_encrypted']): ?>
                                    <span class="badge secure">Secure</span>
                                <?php else: ?>
                                    <span class="badge public">Public</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($f['is_encrypted']): ?>
                                    <a href="download.php?id=<?= $f['id'] ?>" class="action-link">Download</a>
                                <?php else: ?>
                                    <a href="storage/public/<?= htmlspecialchars($f['file_name']) ?>" target="_blank" class="action-link">Open Link</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>