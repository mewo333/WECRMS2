<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/helpers.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$user = getEmployeeData($conn, $_SESSION['employee_id']);
$success = '';
$error = '';

// Fetch categories from database
$stmt = $conn->query("SELECT id, category_name FROM ticket_categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Upload directory with proper path handling
$upload_dir = 'assets/TicketImages/';

// Create directory recursively if it doesn't exist
if (!is_dir('assets')) {
    @mkdir('assets', 0755, true);
}

if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

// Verify directory is writable
if (!is_writable($upload_dir)) {
    chmod($upload_dir, 0755);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = $_POST['category'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $attachment_filename = null;

    if ($category_id && $title && $description) {
        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_error = $file['error'];

            // File validation
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_ext, $allowed_extensions)) {
                $error = '⚠️ Invalid file type. Allowed: JPG, PNG, GIF, PDF, DOC, DOCX';
            } elseif ($file_size > $max_file_size) {
                $error = '⚠️ File size exceeds 5MB limit.';
            } else {
                // Generate unique filename
                $new_filename = 'TKT-' . date('Ymd-His') . '-' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $attachment_filename = $new_filename;
                } else {
                    $error = '❌ Failed to upload file. Please try again.';
                }
            }
        }

        if (!$error) {
            // Generate unique ticket number
            $ticket_number = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
            $employee_id = $_SESSION['employee_id'];

            try {
                $stmt = $conn->prepare("
                    INSERT INTO tickets (ticket_number, employee_id, category_id, title, description, priority, status, attachment, created_at, updated_at)
                    VALUES (:ticket_number, :employee_id, :category_id, :title, :description, :priority, 'pending', :attachment, NOW(), NOW())
                ");
                
                $stmt->execute([
                    ':ticket_number' => $ticket_number,
                    ':employee_id' => $employee_id,
                    ':category_id' => $category_id,
                    ':title' => $title,
                    ':description' => $description,
                    ':priority' => strtolower($priority),
                    ':attachment' => $attachment_filename
                ]);

                // Get the inserted ticket ID
                $ticket_id = $conn->lastInsertId();

                // Add to status history
                $stmt = $conn->prepare("
                    INSERT INTO status_history (ticket_id, old_status, new_status, updated_by, created_at)
                    VALUES (:ticket_id, NULL, 'pending', :updated_by, NOW())
                ");
                $stmt->execute([
                    ':ticket_id' => $ticket_id,
                    ':updated_by' => $employee_id
                ]);

                $success = '✅ Ticket created successfully! Ticket Number: ' . $ticket_number;
                
                // Redirect after 2 seconds
                header("refresh:2;url=tickets.php");

            } catch (PDOException $e) {
                $error = '❌ Failed to create ticket. Error: ' . $e->getMessage();
            }
        }
    } else {
        $error = '⚠️ Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .main-content {
            padding: 20px;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .form-container h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2d3748;
        }

        .form-container p {
            color: #718096;
            margin-bottom: 30px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #4a5568;
            font-size: 14px;
        }

        .form-group label i {
            margin-right: 5px;
            color: #667eea;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 150px;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: #718096;
            font-size: 13px;
        }

        /* File upload styles */
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 15px;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 30px;
            border: 2px dashed #667eea;
            border-radius: 10px;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .file-input-label:hover {
            background: #f0f4ff;
            border-color: #764ba2;
        }

        .file-input-label.dragover {
            background: #e0e7ff;
            border-color: #667eea;
        }

        .file-input-label i {
            font-size: 24px;
            color: #667eea;
        }

        .file-input-label span {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #667eea;
            font-weight: 600;
        }

        .file-input-label small {
            display: block;
            color: #718096;
            font-size: 12px;
            margin-top: 5px;
            font-weight: normal;
        }

        #attachment {
            display: none;
        }

        .file-list {
            margin-top: 15px;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .file-item-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .file-item-name {
            font-weight: 500;
            color: #1e293b;
        }

        .file-item-size {
            color: #718096;
            font-size: 12px;
        }

        .file-remove-btn {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .file-remove-btn:hover {
            background: #fecaca;
        }

        .priority-badges {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .priority-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-high { background: #f8d7da; color: #721c24; }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .btn-back {
            background: white;
            color: #667eea;
            border: 2px solid white;
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.9);
        }

        .required-note {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #718096;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 25px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1><i class="bi bi-plus-circle-fill"></i> Create New Ticket</h1>
            <div class="user-info">
                <a href="dashboard.php" class="btn btn-back"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
        
        <div class="form-container">
            <h2>Submit Your Concern or Request</h2>
            <p>Fill out the form below to create a new support ticket. Our HR team will review and respond as soon as possible.</p>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill" style="font-size: 20px;"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-circle-fill" style="font-size: 20px;"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="required-note">
                <i class="bi bi-info-circle"></i> Fields marked with * are required
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="category"><i class="bi bi-folder-fill"></i> Category *</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Choose the category that best matches your concern or request</small>
                </div>
                
                <div class="form-group">
                    <label for="title"><i class="bi bi-card-heading"></i> Title *</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Brief summary of your concern" maxlength="200" required>
                    <small>Provide a clear, concise title (max 200 characters)</small>
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="bi bi-chat-left-text-fill"></i> Description *</label>
                    <textarea id="description" name="description" class="form-control" rows="6" placeholder="Provide detailed information about your concern or request..." required></textarea>
                    <small>Include all relevant details to help us understand and resolve your issue quickly</small>
                </div>

                <!-- File Upload Section -->
                <div class="form-group">
                    <label><i class="bi bi-file-earmark-arrow-up"></i> Attach File or Photo (Optional)</label>
                    <div class="file-upload-wrapper">
                        <label for="attachment" class="file-input-label" id="dragDropArea">
                            <div>
                                <i class="bi bi-cloud-arrow-up"></i>
                                <span>
                                    Click to upload or drag and drop
                                    <small>Supported formats: JPG, PNG, GIF, PDF, DOC, DOCX (Max 5MB)</small>
                                </span>
                            </div>
                        </label>
                        <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                    </div>
                    <div class="file-list" id="fileList"></div>
                </div>
                
                <div class="form-group">
                    <label for="priority"><i class="bi bi-flag-fill"></i> Priority</label>
                    <select id="priority" name="priority" class="form-control">
                        <option value="low">Low - General inquiry or non-urgent request</option>
                        <option value="medium" selected>Medium - Requires attention within a few days</option>
                        <option value="high">High - Urgent issue requiring immediate attention</option>
                    </select>
                    <div class="priority-badges">
                        <span class="priority-badge priority-low">Low Priority</span>
                        <span class="priority-badge priority-medium">Medium Priority</span>
                        <span class="priority-badge priority-high">High Priority</span>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send-fill"></i> Submit Ticket
                    </button>
                    <a href="tickets.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const attachmentInput = document.getElementById('attachment');
        const dragDropArea = document.getElementById('dragDropArea');
        const fileList = document.getElementById('fileList');

        // Click to upload
        dragDropArea.addEventListener('click', () => {
            attachmentInput.click();
        });

        // Drag and drop
        dragDropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dragDropArea.classList.add('dragover');
        });

        dragDropArea.addEventListener('dragleave', () => {
            dragDropArea.classList.remove('dragover');
        });

        dragDropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dragDropArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            attachmentInput.files = files;
            displayFile();
        });

        // File input change
        attachmentInput.addEventListener('change', displayFile);

        function displayFile() {
            fileList.innerHTML = '';
            if (attachmentInput.files.length > 0) {
                const file = attachmentInput.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                fileList.innerHTML = `
                    <div class="file-item">
                        <div class="file-item-info">
                            <i class="bi bi-file-earmark" style="font-size: 18px; color: #667eea;"></i>
                            <div>
                                <div class="file-item-name">${file.name}</div>
                                <div class="file-item-size">${fileSize} MB</div>
                            </div>
                        </div>
                        <button type="button" class="file-remove-btn" onclick="removeFile()">Remove</button>
                    </div>
                `;
            }
        }

        function removeFile() {
            attachmentInput.value = '';
            fileList.innerHTML = '';
        }
    </script>
</body>
</html>
