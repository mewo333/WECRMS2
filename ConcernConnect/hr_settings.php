<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$user = getEmployeeData($conn, $_SESSION['employee_id']);

if ($user['role'] !== 'hr_admin') {
    header('Location: dashboard.php');
    exit();
}

$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone_number'] ?? '';

    $stmt = $conn->prepare("UPDATE employees SET email = :email, phone_number = :phone WHERE employee_id = :id");
    $stmt->execute([
        'email' => $email,
        'phone' => $phone,
        'id' => $_SESSION['employee_id']
    ]);
    $success = 'Profile updated successfully!';
    $user = getEmployeeData($conn, $_SESSION['employee_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }

        .settings-container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }

        .settings-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin: 0;
        }

        .settings-header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
            font-size: 15px;
        }

        .settings-content {
            padding: 40px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .profile-photo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .profile-photo-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        #previewImg {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        #previewImg:hover {
            transform: scale(1.05);
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            width: 100%;
            max-width: 400px;
        }

        .file-input-wrapper {
            position: relative;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            display: none;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 20px;
            background: white;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #4a5568;
        }

        .file-input-label:hover {
            border-color: #667eea;
            background: #f8f9fa;
            color: #667eea;
        }

        .upload-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        #uploadError {
            font-size: 13px;
            min-height: 20px;
        }

        .settings-section {
            margin-top: 30px;
        }

        .settings-section h3 {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
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

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .form-control:disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }

        .btn-primary {
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            font-size: 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .info-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #e0e7ff;
            color: #5a67d8;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-top: 5px;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .settings-content {
                padding: 25px;
            }

            .settings-header {
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            #previewImg {
                width: 120px;
                height: 120px;
            }

            .settings-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/hr_sidebar.php'; ?>

    <div class="main-content">
            <?php include 'includes/hr_header.php'; ?>
        <div class="settings-container">
            <div class="settings-header">
                <h2><i class="bi bi-gear-fill"></i>Settings</h2>
                <p>Manage your profile information and account settings</p>
            </div>

            <div class="settings-content">
                <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        Profile photo updated successfully!
                    </div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Photo Section -->
                <div class="profile-photo-section">
                    <div class="profile-photo-wrapper">
                        <img id="previewImg" 
                             src="<?php echo htmlspecialchars($user['photo'] ?? 'assets/images/default.png'); ?>" 
                             alt="Profile Photo">
                    </div>

                    <form action="upload_photo.php" method="POST" enctype="multipart/form-data" class="upload-form">
                        <div class="file-input-wrapper">
                            <input type="file" name="photo" id="profilePhoto" accept="image/*" required>
                            <label for="profilePhoto" class="file-input-label">
                                <i class="bi bi-cloud-upload-fill"></i>
                                <span>Choose Profile Photo</span>
                            </label>
                        </div>
                        <div id="uploadError" class="text-danger"></div>
                        <button type="submit" class="upload-btn">
                            <i class="bi bi-upload"></i> Upload Photo
                        </button>
                    </form>
                    <small style="color: #718096; margin-top: 10px;">Max file size: 2MB (JPG, PNG, GIF)</small>
                </div>

                <!-- Profile Information Section -->
                <div class="settings-section">
                    <h3><i class="bi bi-person-circle"></i> Profile Information</h3>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label><i class="bi bi-hash"></i> Employee ID</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['employee_id']); ?>" disabled>
                            <span class="info-badge"><i class="bi bi-info-circle"></i> Cannot be changed</span>
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-person-fill"></i> Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>
                            <span class="info-badge"><i class="bi bi-info-circle"></i> Cannot be changed</span>
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-envelope-fill"></i> Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required placeholder="your.email@company.com">
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-telephone-fill"></i> Phone Number</label>
                            <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="Enter your phone number">
                        </div>

                        <button type="submit" name="update_profile" class="btn-primary">
                            <i class="bi bi-check-circle-fill"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
document.getElementById('profilePhoto').addEventListener('change', function(event) {
    const fileInput = event.target;
    const file = fileInput.files[0];
    const errorDiv = document.getElementById('uploadError');
    const preview = document.getElementById('previewImg');
    const label = document.querySelector('.file-input-label span');
    errorDiv.innerText = "";

    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        errorDiv.innerText = "⚠️ Only JPG, PNG, or GIF allowed.";
        fileInput.value = "";
        label.innerText = "Choose Profile Photo";
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        errorDiv.innerText = "⚠️ Image must be smaller than 2MB.";
        fileInput.value = "";
        label.innerText = "Choose Profile Photo";
        return;
    }

    label.innerText = file.name;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
    }
    reader.readAsDataURL(file);
});
</script>
</body>
</html>
