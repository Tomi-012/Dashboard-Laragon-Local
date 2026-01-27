<?php
/**
 * API Handler untuk LaragonDash
 * Menangani operasi Create, Rename, dan Delete untuk file/folder
 */

// Suppress PHP errors/warnings from appearing in output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Method tidak diizinkan. Gunakan POST.'
    ]);
    exit;
}

// Ambil data dari request
$action = $_POST['action'] ?? '';
$item = $_POST['item'] ?? '';
$newName = $_POST['newName'] ?? '';

// Validasi input
if (empty($action)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Action tidak boleh kosong.'
    ]);
    exit;
}

// Sanitize input - mencegah path traversal attack
function sanitizeName($name) {
    // Hapus path traversal
    $name = basename($name);
    // Hanya hapus karakter yang benar-benar berbahaya untuk filesystem Windows
    // Karakter terlarang di Windows: < > : " / \ | ? *
    $name = preg_replace('/[<>:"\/\\\\|?*]/', '', $name);
    $name = trim($name);
    return $name;
}

// Response helper
function respond($status, $message) {
    echo json_encode([
        'status' => $status,
        'message' => $message
    ]);
    exit;
}

// Cek apakah item adalah file sistem yang dilindungi
function isProtected($name) {
    $protected = ['index.php', 'api.php', '.htaccess'];
    return in_array(strtolower($name), array_map('strtolower', $protected));
}

// Handle actions
switch ($action) {
    
    // ========== CREATE NEW FOLDER ==========
    case 'create':
        $folderName = sanitizeName($item);
        
        if (empty($folderName)) {
            respond('error', 'Nama folder tidak valid.');
        }
        
        if (file_exists($folderName)) {
            respond('error', 'Folder atau file dengan nama tersebut sudah ada.');
        }
        
        if (mkdir($folderName, 0755)) {
            respond('success', "Folder '$folderName' berhasil dibuat!");
        } else {
            respond('error', 'Gagal membuat folder. Periksa permission direktori.');
        }
        break;
    
    // ========== RENAME FILE/FOLDER ==========
    case 'rename':
        $oldName = sanitizeName($item);
        $newNameSanitized = sanitizeName($newName);
        
        if (empty($oldName) || empty($newNameSanitized)) {
            respond('error', 'Nama lama atau nama baru tidak valid.');
        }
        
        if (isProtected($oldName)) {
            respond('error', 'File sistem tidak dapat diubah namanya.');
        }
        
        if (!file_exists($oldName)) {
            respond('error', "Item '$oldName' tidak ditemukan.");
        }
        
        if (file_exists($newNameSanitized)) {
            respond('error', "Nama '$newNameSanitized' sudah digunakan.");
        }
        
        // Clear stat cache
        clearstatcache(true, $oldName);
        
        // On Windows, try to release any locks by closing handles
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Kill any git processes that might be locking the folder
            @exec("taskkill /F /IM git.exe 2>nul");
            @exec("taskkill /F /IM node.exe 2>nul");
            usleep(100000); // Wait 100ms
        }
        
        // Try rename with retry mechanism
        $success = false;
        $lastError = '';
        
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            clearstatcache(true);
            
            $result = @rename($oldName, $newNameSanitized);
            
            if ($result) {
                $success = true;
                break;
            }
            
            $error = error_get_last();
            $lastError = $error['message'] ?? 'Unknown error';
            
            usleep($attempt * 300000); // 300ms, 600ms, etc.
        }
        
        if ($success) {
            respond('success', "Berhasil mengubah nama menjadi '$newNameSanitized'.");
        } else {
            // Try alternative method using shell command on Windows
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $oldPath = __DIR__ . DIRECTORY_SEPARATOR . $oldName;
                $newPath = __DIR__ . DIRECTORY_SEPARATOR . $newNameSanitized;
                
                // Use robocopy to move (works better with locked files)
                $cmd = "move /Y \"$oldPath\" \"$newPath\" 2>&1";
                $output = shell_exec($cmd);
                
                if (file_exists($newPath) && !file_exists($oldPath)) {
                    respond('success', "Berhasil mengubah nama menjadi '$newNameSanitized'.");
                }
            }
            
            respond('error', "Gagal mengubah nama '$oldName'. Kemungkinan folder sedang digunakan oleh proses background (Git, Node, Windows Search Indexer). Coba restart Laragon atau komputer.");
        }
        break;
    
    // ========== DELETE FILE/FOLDER ==========
    case 'delete':
        $targetName = sanitizeName($item);
        
        if (empty($targetName)) {
            respond('error', 'Nama item tidak valid.');
        }
        
        if (isProtected($targetName)) {
            respond('error', 'File sistem tidak dapat dihapus.');
        }
        
        if (!file_exists($targetName)) {
            respond('error', "Item '$targetName' tidak ditemukan.");
        }
        
        // Fungsi untuk menghapus folder beserta isinya (recursive) - Robust Version
        function deleteRecursive($path) {
            if (!file_exists($path)) {
                return true; // Already deleted
            }
            
            if (is_file($path) || is_link($path)) {
                // Try to remove read-only attribute on Windows
                @chmod($path, 0777);
                
                // Retry up to 3 times for locked files
                for ($i = 0; $i < 3; $i++) {
                    if (@unlink($path)) {
                        return true;
                    }
                    usleep(100000); // Wait 100ms before retry
                }
                return false;
            }
            
            if (is_dir($path)) {
                $items = @scandir($path);
                if ($items === false) {
                    return false; // Can't read directory
                }
                
                $allDeleted = true;
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    
                    $fullPath = $path . DIRECTORY_SEPARATOR . $item;
                    if (!deleteRecursive($fullPath)) {
                        $allDeleted = false;
                    }
                }
                
                // Only try to remove directory if all contents were deleted
                if ($allDeleted) {
                    @chmod($path, 0777);
                    
                    // Retry rmdir
                    for ($i = 0; $i < 3; $i++) {
                        if (@rmdir($path)) {
                            return true;
                        }
                        usleep(100000); // Wait 100ms
                    }
                }
                return false;
            }
            
            return false;
        }
        
        // Clear any file stat cache before deleting
        clearstatcache(true, $targetName);
        
        if (deleteRecursive($targetName)) {
            respond('success', "'$targetName' berhasil dihapus.");
        } else {
            // Check if folder still exists to give specific error
            if (file_exists($targetName)) {
                if (is_dir($targetName)) {
                    $remaining = count(array_diff(scandir($targetName), ['.', '..']));
                    if ($remaining > 0) {
                        respond('error', "Gagal menghapus '$targetName'. Masih ada $remaining file/folder yang terkunci.");
                    } else {
                        respond('error', "Folder '$targetName' kosong tapi tidak bisa dihapus. Mungkin sedang digunakan proses lain.");
                    }
                } else {
                    respond('error', "File '$targetName' tidak bisa dihapus. Mungkin sedang dibuka aplikasi lain.");
                }
            } else {
                respond('success', "'$targetName' berhasil dihapus.");
            }
        }
        break;
    
    // ========== OPEN IN IDE ==========
    case 'open_ide':
        $targetName = sanitizeName($item);
        $app = $_POST['app'] ?? 'code'; // Default VS Code
        
        if (empty($targetName)) respond('error', 'Nama item tidak valid.');
        
        $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $targetName;
        
        if (file_exists($fullPath)) {
            $cmd = '';
            $appName = '';
            
            switch ($app) {
                case 'kiro':
                    $cmd = "start kiro \"$fullPath\"";
                    $appName = "Kiro IDE";
                    break;
                case 'antigravity':
                    $cmd = "start antigravity \"$fullPath\""; // Asumsi command-nya 'antigravity'
                    $appName = "Antigravity";
                    break;
                case 'vscode':
                default:
                    $cmd = "start code \"$fullPath\"";
                    $appName = "Visual Studio Code";
                    break;
            }
            
            pclose(popen($cmd, "r"));
            respond('success', "Membuka $appName untuk '$targetName'...");
        } else {
            respond('error', 'Folder/File tidak ditemukan.');
        }
        break;

    // ========== OPEN IN EXPLORER ==========
    case 'open_explorer':
        $targetName = sanitizeName($item);
        if (empty($targetName)) respond('error', 'Nama item tidak valid.');
        
        $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $targetName;
        $fullPath = str_replace('/', '\\', $fullPath); // Fix slash for Windows Explorer

        if (file_exists($fullPath)) {
            pclose(popen("explorer \"$fullPath\"", "r"));
            respond('success', "Membuka Explorer untuk '$targetName'...");
        } else {
            respond('error', 'Folder/File tidak ditemukan.');
        }
        break;

    // ========== UNKNOWN ACTION ==========
    default:
        respond('error', "Action '$action' tidak dikenali.");
        break;
}
