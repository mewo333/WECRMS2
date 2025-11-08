<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$db = new Database();
$conn = $db->connect();
$employee_id = $_SESSION['employee_id'];

// Determine which page called this
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$redirect_page = (strpos($referrer, 'hr_settings') !== false) ? 'hr_settings.php' : 'profile.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    $error = $file['error'];

    if ($error === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $upload_dir = 'assets/images/';

        // Create folder if missing
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            header("Location: $redirect_page?upload=error&msg=invalid_type");
            exit();
        }

        // Validate file size
        if ($file['size'] > $max_size) {
            header("Location: $redirect_page?upload=error&msg=file_too_large");
            exit();
        }

        // Generate unique filename
        $unique_name = uniqid('photo_') . '_' . time() . '_' . basename($file['name']);
        $target_path = $upload_dir . $unique_name;

        // Get old photo path for deletion
        $stmt = $conn->prepare("SELECT photo FROM employees WHERE employee_id = :id");
        $stmt->execute(['id' => $employee_id]);
        $old_photo = $stmt->fetchColumn();

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Update database with new photo path
            $stmt = $conn->prepare("UPDATE employees SET photo = :photo WHERE employee_id = :id");
            $stmt->execute(['photo' => $target_path, 'id' => $employee_id]);

            // Delete old photo if it exists and is not default
            if ($old_photo && file_exists($old_photo) && $old_photo !== 'assets/images/default.png') {
                unlink($old_photo);
            }

            // Update session
            $_SESSION['photo'] = $target_path;

            // Redirect with success message
            header("Location: $redirect_page?upload=success");
            exit();
        } else {
            header("Location: $redirect_page?upload=error&msg=move_failed");
            exit();
        }
    } else {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File upload incomplete',
            UPLOAD_ERR_NO_FILE => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporary folder missing',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
        ];
        $msg = $error_messages[$error] ?? 'Unknown error';
        header("Location: $redirect_page?upload=error&msg=" . urlencode($msg));
        exit();
    }
} else {
    header("Location: $redirect_page");
    exit();
}
?>
