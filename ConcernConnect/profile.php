<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$user = getEmployeeData($conn, $_SESSION['employee_id']);
$success = '';

// Handle AJAX update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    header('Content-Type: application/json');
    
    try {
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone_number = $_POST['phone_number'] ?? '';
        $street_address = $_POST['street_address'] ?? '';
        $barangay = $_POST['barangay'] ?? '';
        $city = $_POST['city'] ?? '';
        $province = $_POST['province'] ?? '';
        $postal_code = $_POST['postal_code'] ?? '';
        $location = $_POST['location'] ?? '';
        $language = $_POST['language'] ?? 'English';
        $timezone = $_POST['timezone'] ?? 'GMT+8';
        $nationality = $_POST['nationality'] ?? 'Filipino';

        $stmt = $conn->prepare("
            UPDATE employees 
            SET full_name = :full_name,
                email = :email, 
                phone_number = :phone_number,
                street_address = :street_address,
                barangay = :barangay,
                city = :city,
                province = :province,
                postal_code = :postal_code,
                location = :location, 
                language = :language, 
                timezone = :timezone, 
                nationality = :nationality 
            WHERE employee_id = :id
        ");
        
        $result = $stmt->execute([
            'full_name' => $full_name,
            'email' => $email,
            'phone_number' => $phone_number,
            'street_address' => $street_address,
            'barangay' => $barangay,
            'city' => $city,
            'province' => $province,
            'postal_code' => $postal_code,
            'location' => $location,
            'language' => $language,
            'timezone' => $timezone,
            'nationality' => $nationality,
            'id' => $_SESSION['employee_id']
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
            padding: 0;
            min-height: 100vh;
        }

        .profile-container {
            padding: 2rem 3rem;
        }

        .alert {
            border-radius: 12px;
            margin-bottom: 2rem;
            border: none;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        /* ===== PROFILE HEADER WITH EDIT BUTTON ===== */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 3rem;
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .profile-photo-wrapper {
            position: relative;
            display: inline-block;
        }

        #profilePhotoDisplay {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .photo-placeholder-header {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            border: 4px solid #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .edit-photo-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .edit-photo-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .profile-details {
            flex: 1;
        }

        .profile-details h4 {
            font-size: 22px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .profile-details p {
            color: #718096;
            margin: 0.25rem 0;
            font-size: 15px;
        }

        /* ===== MODAL STYLES ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content-box {
            background: white;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px 20px 0 0;
        }

        .modal-header-custom h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .modal-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body-custom {
            padding: 2rem;
            text-align: center;
        }

        .upload-section-modal {
            background: linear-gradient(135deg, #f8fafc 0%, #f0f4ff 100%);
            padding: 2.5rem;
            border-radius: 16px;
            border: 2px solid #e2e8f0;
            text-align: center;
        }

        .profile-photo-container-modal {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .profile-photo-wrapper-modal {
            position: relative;
            display: inline-block;
        }

        #previewImg {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .photo-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .upload-text {
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .upload-description {
            font-size: 14px;
            color: #718096;
            margin-bottom: 1.5rem;
        }

        .dashed-box {
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 2rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 1.5rem 0;
        }

        .dashed-box:hover {
            border-color: #667eea;
            background: #f8fafc;
        }

        .dashed-box.dragover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .dashed-box-icon {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 0.75rem;
        }

        .dashed-box-text {
            font-size: 15px;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .dashed-box-hint {
            font-size: 13px;
            color: #a0aec0;
        }

        #profilePhoto {
            display: none;
        }

        .btn-upload {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 1rem;
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.25);
            color: white;
            text-decoration: none;
        }

        .file-info {
            font-size: 13px;
            color: #a0aec0;
            margin-top: 1rem;
        }

        #uploadError, #uploadSuccess {
            margin-top: 1rem;
            font-size: 14px;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            display: none;
        }

        #uploadError {
            background: #fed7d7;
            color: #742a2a;
        }

        #uploadSuccess {
            background: #c6f6d5;
            color: #22543d;
        }

        #uploadError.show, #uploadSuccess.show {
            display: block;
        }

        /* ===== PROFILE GRID ===== */
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: none;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .card h3 {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .info-box:hover {
            background: #f0f4ff;
            border-color: #667eea;
        }

        .info-box-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #718096;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .info-box-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .info-box-value {
            font-size: 15px;
            font-weight: 600;
            color: #2d3748;
            flex: 1;
        }

        .info-box-display {
            flex: 1;
        }

        .info-box-input {
            flex: 1;
        }

        .info-box-input .form-control {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 0.6rem 0.75rem;
            font-size: 14px;
        }

        .info-box-input .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .edit-btn {
            padding: 0.5rem 1rem;
            font-size: 13px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #667eea;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .edit-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .edit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        #saveChanges {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }

        #saveChanges:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .d-none {
            display: none !important;
        }

        .address-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 1.5rem;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }

            .info-box-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .edit-btn {
                align-self: flex-start;
            }

            #profilePhotoDisplay,
            .photo-placeholder-header {
                width: 100px;
                height: 100px;
            }

            .address-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include_once 'includes/employee_header.php'; ?>

        <div class="profile-container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Profile Header with Edit Photo Button -->
            <div class="profile-header">
                <div class="profile-photo-wrapper">
                    <img id="profilePhotoDisplay" 
                         src="<?php echo htmlspecialchars($user['photo'] ?? 'assets/images/default.png'); ?>" 
                         alt="Profile Photo"
                         onerror="this.style.display='none'; document.querySelector('.photo-placeholder-header').style.display='flex';">
                    <div class="photo-placeholder-header" id="photoPlaceholderHeader" style="display: <?php echo !empty($user['photo']) && file_exists($user['photo']) ? 'none' : 'flex'; ?>;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <button class="edit-photo-btn" onclick="openEditPhotoModal()">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>

                <div class="profile-details">
                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($user['position'] ?? 'Employee'); ?></p>
                    <p><i class="bi bi-building"></i> <?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></p>
                </div>
            </div>

            <!-- Edit Photo Modal -->
            <div class="modal-overlay" id="editPhotoModal" onclick="closeEditPhotoModal(event)">
                <div class="modal-content-box" onclick="event.stopPropagation()">
                    <div class="modal-header-custom">
                        <h2>
                            <i class="bi bi-image"></i> Edit Profile Photo
                        </h2>
                        <button class="modal-close-btn" onclick="closeEditPhotoModal()">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>

                    <div class="modal-body-custom">
                        <div class="upload-section-modal">
                            <div class="profile-photo-container-modal">
                                <div class="profile-photo-wrapper-modal">
                                    <img id="previewImg" 
                                         src="<?php echo htmlspecialchars($user['photo'] ?? 'assets/images/default.png'); ?>" 
                                         alt="Profile Photo"
                                         onerror="this.style.display='none'; document.querySelector('.photo-placeholder').style.display='flex';">
                                    <div class="photo-placeholder" id="photoPlaceholder" style="display: <?php echo !empty($user['photo']) && file_exists($user['photo']) ? 'none' : 'flex'; ?>;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                </div>
                            </div>

                            <p class="upload-text">Update Profile Photo</p>
                            <p class="upload-description">Choose a new profile picture</p>

                            <form action="upload_photo.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                                <div class="dashed-box" id="dragBox">
                                    <div class="dashed-box-icon">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                    </div>
                                    <div class="dashed-box-text">Click to upload</div>
                                    <div class="dashed-box-hint">Drag and drop or click</div>
                                </div>

                                <input type="file" name="photo" id="profilePhoto" accept="image/*">

                                <br>
                                <button type="submit" class="btn-upload">
                                    <i class="bi bi-cloud-upload"></i> Upload
                                </button>

                                <div id="uploadError"></div>
                                <div id="uploadSuccess"></div>

                                <div class="file-info">
                                    Max 5MB (JPG, PNG, GIF, WebP)
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Information Grid -->
            <div class="profile-grid">
                <!-- Personal Information -->
                <div class="card">
                    <h3><i class="bi bi-person-fill"></i> Personal Information</h3>

                    <!-- Full Name -->
                    <div class="info-box">
                        <div class="info-box-label">Full Name</div>
                        <div class="info-box-content">
                            <div class="info-box-display">
                                <p id="full_name_display" class="info-box-value"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            </div>
                            <button class="edit-btn" onclick="toggleEdit('full_name')" type="button">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                        <div class="info-box-input d-none">
                            <input type="text" id="full_name_input" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="info-box">
                        <div class="info-box-label">Email Address</div>
                        <div class="info-box-content">
                            <div class="info-box-display">
                                <p id="email_display" class="info-box-value"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <button class="edit-btn" onclick="toggleEdit('email')" type="button">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                        <div class="info-box-input d-none">
                            <input type="email" id="email_input" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div class="info-box">
                        <div class="info-box-label">Phone Number</div>
                        <div class="info-box-content">
                            <div class="info-box-display">
                                <p id="phone_number_display" class="info-box-value"><?php echo htmlspecialchars($user['phone_number'] ?? 'Not provided'); ?></p>
                            </div>
                            <button class="edit-btn" onclick="toggleEdit('phone_number')" type="button">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                        <div class="info-box-input d-none">
                            <input type="text" id="phone_number_input" class="form-control" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="+63 912 345 6789">
                        </div>
                    </div>

                    <!-- Nationality -->
                    <div class="info-box">
                        <div class="info-box-label">Nationality</div>
                        <div class="info-box-content">
                            <div class="info-box-display">
                                <p id="nationality_display" class="info-box-value"><?php echo htmlspecialchars($user['nationality'] ?? 'Filipino'); ?></p>
                            </div>
                            <button class="edit-btn" onclick="toggleEdit('nationality')" type="button">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                        <div class="info-box-input d-none">
                            <input type="text" id="nationality_input" class="form-control" value="<?php echo htmlspecialchars($user['nationality'] ?? 'Filipino'); ?>" placeholder="Filipino">
                        </div>
                    </div>

                    <!-- Date Joined -->
                    <div class="info-box">
                        <div class="info-box-label">Date Joined</div>
                        <div class="info-box-content">
                            <p class="info-box-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                            <button class="edit-btn" type="button" disabled>
                                <i class="bi bi-lock"></i> Locked
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="card">
                    <h3><i class="bi bi-geo-alt-fill"></i> Address Information</h3>

                    <!-- Street Address -->
                    <div class="info-box">
                        <div class="info-box-label">Street Address</div>
                        <div class="info-box-content">
                            <div class="info-box-display">
                                <p id="street_address_display" class="info-box-value"><?php echo htmlspecialchars($user['street_address'] ?? 'Not provided'); ?></p>
                            </div>
                            <button class="edit-btn" onclick="toggleEdit('street_address')" type="button">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                        <div class="info-box-input d-none">
                            <input type="text" id="street_address_input" class="form-control" value="<?php echo htmlspecialchars($user['street_address'] ?? ''); ?>" placeholder="123 Bonifacio Street">
                        </div>
                    </div>

                    <!-- Barangay and City -->
                    <div class="address-row">
                        <div class="info-box">
                            <div class="info-box-label">Barangay</div>
                            <div class="info-box-content">
                                <div class="info-box-display">
                                    <p id="barangay_display" class="info-box-value"><?php echo htmlspecialchars($user['barangay'] ?? 'Not provided'); ?></p>
                                </div>
                                <button class="edit-btn" onclick="toggleEdit('barangay')" type="button">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </div>
                            <div class="info-box-input d-none">
                                <input type="text" id="barangay_input" class="form-control" value="<?php echo htmlspecialchars($user['barangay'] ?? ''); ?>" placeholder="San Antonio">
                            </div>
                        </div>

                        <div class="info-box">
                            <div class="info-box-label">City</div>
                            <div class="info-box-content">
                                <div class="info-box-display">
                                    <p id="city_display" class="info-box-value"><?php echo htmlspecialchars($user['city'] ?? 'Not provided'); ?></p>
                                </div>
                                <button class="edit-btn" onclick="toggleEdit('city')" type="button">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </div>
                            <div class="info-box-input d-none">
                                <input type="text" id="city_input" class="form-control" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="Makati City">
                            </div>
                        </div>
                    </div>

                    <!-- Province and Postal Code -->
                    <div class="address-row">
                        <div class="info-box">
                            <div class="info-box-label">Province</div>
                            <div class="info-box-content">
                                <div class="info-box-display">
                                    <p id="province_display" class="info-box-value"><?php echo htmlspecialchars($user['province'] ?? 'Not provided'); ?></p>
                                </div>
                                <button class="edit-btn" onclick="toggleEdit('province')" type="button">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </div>
                            <div class="info-box-input d-none">
                                <input type="text" id="province_input" class="form-control" value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>" placeholder="Metro Manila">
                            </div>
                        </div>

                        <div class="info-box">
                            <div class="info-box-label">Postal Code</div>
                            <div class="info-box-content">
                                <div class="info-box-display">
                                    <p id="postal_code_display" class="info-box-value"><?php echo htmlspecialchars($user['postal_code'] ?? 'Not provided'); ?></p>
                                </div>
                                <button class="edit-btn" onclick="toggleEdit('postal_code')" type="button">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </div>
                            <div class="info-box-input d-none">
                                <input type="text" id="postal_code_input" class="form-control" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" placeholder="1209" maxlength="4">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Details -->
                <div class="card">
                    <h3><i class="bi bi-globe"></i> Account Details</h3>

                    <!-- Location -->
                    <div class="info-box">
                        <div class="info-box-label">Location</div>
                        <div class="info-box-content">
                            <div class="info-box-display">
                                <p id="location_display" class="info-box-value"><?php echo htmlspecialchars($user['location'] ?? 'Not provided'); ?></p>
                            </div>
                            <button class="edit-btn" onclick="toggleEdit('location')" type="button">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                        <div class="info-box-input d-none">
                            <input type="text" id="location_input" class="form-control" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="Manila, Philippines">
                        </div>
                    </div>

                    <!-- Language -->
                    <div class="info-box">
                        <div class="info-box-label">Language</div>
                        <div class="info-box-content">
                            <div class="info-box-display">
                                <p id="language_display" class="info-box-value"><?php echo htmlspecialchars($user['language'] ?? 'English'); ?></p>
                            </div>
                            <button class="edit-btn" onclick="toggleEdit('language')" type="button">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                        <div class="info-box-input d-none">
                            <select id="language_input" class="form-control">
                                <option value="English" <?php echo ($user['language'] ?? 'English') === 'English' ? 'selected' : ''; ?>>English</option>
                                <option value="Filipino" <?php echo ($user['language'] ?? '') === 'Filipino' ? 'selected' : ''; ?>>Filipino</option>
                                <option value="Spanish" <?php echo ($user['language'] ?? '') === 'Spanish' ? 'selected' : ''; ?>>Spanish</option>
                                <option value="French" <?php echo ($user['language'] ?? '') === 'French' ? 'selected' : ''; ?>>French</option>
                                <option value="Mandarin" <?php echo ($user['language'] ?? '') === 'Mandarin' ? 'selected' : ''; ?>>Mandarin</option>
                                <option value="Japanese" <?php echo ($user['language'] ?? '') === 'Japanese' ? 'selected' : ''; ?>>Japanese</option>
                            </select>
                        </div>
                    </div>

                    <!-- Timezone -->
                    <div class="info-box">
                        <div class="info-box-label">Timezone</div>
                        <div class="info-box-content">
                            <div class="info-box-display">
                                <p id="timezone_display" class="info-box-value"><?php echo htmlspecialchars($user['timezone'] ?? 'GMT+8'); ?></p>
                            </div>
                            <button class="edit-btn" onclick="toggleEdit('timezone')" type="button">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                        <div class="info-box-input d-none">
                            <select id="timezone_input" class="form-control">
                                <option value="GMT-5" <?php echo ($user['timezone'] ?? '') === 'GMT-5' ? 'selected' : ''; ?>>GMT-5 (EST)</option>
                                <option value="GMT-6" <?php echo ($user['timezone'] ?? '') === 'GMT-6' ? 'selected' : ''; ?>>GMT-6 (CST)</option>
                                <option value="GMT-8" <?php echo ($user['timezone'] ?? '') === 'GMT-8' ? 'selected' : ''; ?>>GMT-8 (PST)</option>
                                <option value="GMT+0" <?php echo ($user['timezone'] ?? '') === 'GMT+0' ? 'selected' : ''; ?>>GMT+0 (UTC)</option>
                                <option value="GMT+1" <?php echo ($user['timezone'] ?? '') === 'GMT+1' ? 'selected' : ''; ?>>GMT+1 (CET)</option>
                                <option value="GMT+8" <?php echo ($user['timezone'] ?? 'GMT+8') === 'GMT+8' ? 'selected' : ''; ?>>GMT+8 (PST/PHT)</option>
                                <option value="GMT+9" <?php echo ($user['timezone'] ?? '') === 'GMT+9' ? 'selected' : ''; ?>>GMT+9 (JST)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <button id="saveChanges" class="d-none" style="max-width: 400px; margin: 0 auto;">
                <i class="bi bi-check-circle"></i> Save Changes
            </button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // ===== MODAL FUNCTIONS =====
        function openEditPhotoModal() {
            document.getElementById('editPhotoModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeEditPhotoModal(event) {
            if (event && event.target !== document.getElementById('editPhotoModal')) {
                return;
            }
            document.getElementById('editPhotoModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditPhotoModal();
            }
        });

        // ===== DRAG & DROP FILE UPLOAD =====
        const dragBox = document.getElementById('dragBox');
        const profilePhoto = document.getElementById('profilePhoto');
        const previewImg = document.getElementById('previewImg');
        const photoPlaceholder = document.getElementById('photoPlaceholder');
        const uploadError = document.getElementById('uploadError');
        const uploadSuccess = document.getElementById('uploadSuccess');

        dragBox.addEventListener('click', () => profilePhoto.click());

        dragBox.addEventListener('dragover', (e) => {
            e.preventDefault();
            dragBox.classList.add('dragover');
        });

        dragBox.addEventListener('dragleave', () => {
            dragBox.classList.remove('dragover');
        });

        dragBox.addEventListener('drop', (e) => {
            e.preventDefault();
            dragBox.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                profilePhoto.files = files;
                validateAndPreviewFile();
            }
        });

        profilePhoto.addEventListener('change', validateAndPreviewFile);

        function validateAndPreviewFile() {
            const file = profilePhoto.files[0];
            uploadError.classList.remove('show');
            uploadSuccess.classList.remove('show');

            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                uploadError.innerHTML = '❌ Only JPG, PNG, GIF, or WebP allowed.';
                uploadError.classList.add('show');
                profilePhoto.value = '';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                uploadError.innerHTML = '❌ Image must be smaller than 5MB.';
                uploadError.classList.add('show');
                profilePhoto.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                photoPlaceholder.style.display = 'none';
                uploadSuccess.innerHTML = '✅ Image ready to upload!';
                uploadSuccess.classList.add('show');
            };
            reader.readAsDataURL(file);
        }

        // ===== PROFILE EDIT FUNCTIONS =====
        let editedFields = new Set();

        function toggleEdit(fieldName) {
            const displayParent = document.getElementById(fieldName + '_display')?.parentElement;
            const inputParent = document.getElementById(fieldName + '_input')?.parentElement;

            if (displayParent && inputParent) {
                displayParent.classList.toggle('d-none');
                inputParent.classList.toggle('d-none');

                if (inputParent.classList.contains('d-none')) {
                    editedFields.delete(fieldName);
                } else {
                    editedFields.add(fieldName);
                }

                const saveBtn = document.getElementById('saveChanges');
                if (editedFields.size === 0) {
                    saveBtn.classList.add('d-none');
                } else {
                    saveBtn.classList.remove('d-none');
                }
            }
        }

        // Save Changes
        document.getElementById('saveChanges').addEventListener('click', function() {
            const saveBtn = this;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
            
            const formData = new URLSearchParams({
                update_profile: 'true',
                full_name: document.getElementById('full_name_input').value,
                email: document.getElementById('email_input').value,
                phone_number: document.getElementById('phone_number_input').value,
                street_address: document.getElementById('street_address_input').value,
                barangay: document.getElementById('barangay_input').value,
                city: document.getElementById('city_input').value,
                province: document.getElementById('province_input').value,
                postal_code: document.getElementById('postal_code_input').value,
                location: document.getElementById('location_input').value,
                language: document.getElementById('language_input').value,
                timezone: document.getElementById('timezone_input').value,
                nationality: document.getElementById('nationality_input').value
            });

            fetch('profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Profile updated successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Save Changes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Failed to update profile. Please try again.');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Save Changes';
            });
        });
    </script>
</body>
</html>
