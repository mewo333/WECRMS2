<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$employee = getEmployeeData($conn, $_SESSION['employee_id']);

if ($employee['role'] !== 'hr_admin') {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Fetch all positions from the database
$stmt = $conn->prepare("SELECT id, position_name, department FROM positions WHERE is_active = 1 ORDER BY department, position_name");
$stmt->execute();
$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $department = $_POST['department'];
    $position_id = $_POST['position_id'] ?? null;
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
    $salary = $_POST['salary'] ?? null;
    $hire_date = $_POST['hire_date'] ?? date('Y-m-d');
    
    // Get position name from position_id
    $position = '';
    if ($position_id) {
        $stmt = $conn->prepare("SELECT position_name FROM positions WHERE id = :id");
        $stmt->execute(['id' => $position_id]);
        $pos = $stmt->fetch(PDO::FETCH_ASSOC);
        $position = $pos['position_name'] ?? '';
    }
    
    // Check if employee ID already exists
    $stmt = $conn->prepare("SELECT employee_id FROM employees WHERE employee_id = :id");
    $stmt->execute(['id' => $employee_id]);
    
    if ($stmt->fetch()) {
        $error_message = "Employee ID already exists!";
    } else {
        // Insert new employee
        $stmt = $conn->prepare("
            INSERT INTO employees (
                employee_id, full_name, email, password, department, position, position_id,
                phone_number, street_address, barangay, city, province, postal_code, 
                location, language, timezone, nationality, salary, hire_date, role
            )
            VALUES (
                :employee_id, :full_name, :email, :password, :department, :position, :position_id,
                :phone_number, :street_address, :barangay, :city, :province, :postal_code,
                :location, :language, :timezone, :nationality, :salary, :hire_date, 'employee'
            )
        ");
        
        $result = $stmt->execute([
            'employee_id' => $employee_id,
            'full_name' => $full_name,
            'email' => $email,
            'password' => $password,
            'department' => $department,
            'position' => $position,
            'position_id' => $position_id,
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
            'salary' => $salary,
            'hire_date' => $hire_date
        ]);
        
        if ($result) {
            header('Location: hr_employees.php?success=added');
            exit();
        } else {
            $error_message = "Failed to add employee. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fb;
            color: #1e293b;
        }

        .main-content {
            padding: 2rem;
            min-height: 100vh;
        }

        .form-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .form-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
        }

        .btn-back {
            background: #f1f5f9;
            color: #64748b;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #e2e8f0;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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
        }

        .info-box-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #718096;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background: #dcfce7;
            color: #15803d;
        }

        .info-box-notice {
            background: #fff7ed;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .info-box-notice p {
            margin: 0;
            color: #92400e;
            font-size: 0.9rem;
        }

        .address-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .address-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/hr_sidebar.php'; ?>

<div class="main-content">
    <?php include 'includes/hr_header.php'; ?>

    <div class="form-container">
        <div class="form-header">
            <button class="btn-back" onclick="window.location.href='hr_employees.php'">
                <i class="bi bi-arrow-left"></i> Back
            </button>
            <h2><i class="bi bi-person-plus-fill"></i> Add New Employee</h2>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="info-box-notice">
            <p><strong>Note:</strong> All fields marked with * are required. Employee will receive login credentials via email.</p>
        </div>

        <form method="POST" action="">
            <div class="profile-grid">
                <!-- Personal Information -->
                <div class="card">
                    <h3><i class="bi bi-person-fill"></i> Personal Information</h3>

                    <div class="info-box">
                        <div class="info-box-label">Employee ID *</div>
                        <input type="text" class="form-control" name="employee_id" required placeholder="e.g., EMP-2025-001">
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Full Name *</div>
                        <input type="text" class="form-control" name="full_name" required placeholder="Juan Dela Cruz">
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Email Address *</div>
                        <input type="email" class="form-control" name="email" required placeholder="juan.delacruz@trustingsocial.com">
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Password *</div>
                        <input type="password" class="form-control" name="password" required placeholder="Minimum 6 characters">
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Phone Number</div>
                        <input type="tel" class="form-control" name="phone_number" placeholder="+63 912 345 6789">
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Nationality</div>
                        <input type="text" class="form-control" name="nationality" value="Filipino" placeholder="Filipino">
                    </div>
                </div>

                <!-- Job Information -->
                <div class="card">
                    <h3><i class="bi bi-briefcase-fill"></i> Job Information</h3>

                    <div class="info-box">
                        <div class="info-box-label">Department *</div>
                        <select class="form-control" name="department" required>
                            <option value="">Select Department</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Data Science">Data Science</option>
                            <option value="Data Engineering">Data Engineering</option>
                            <option value="DevOps">DevOps</option>
                            <option value="Quality Assurance">Quality Assurance</option>
                            <option value="Product Management">Product Management</option>
                            <option value="Project Management Office">Project Management Office</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Security & Infrastructure">Security & Infrastructure</option>
                            <option value="Business Intelligence">Business Intelligence</option>
                        </select>
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Position *</div>
                        <select class="form-control" name="position_id" required>
                            <option value="">Select Position</option>
                            <?php
                            $current_dept = '';
                            foreach ($positions as $pos):
                                if ($current_dept != $pos['department']):
                                    if ($current_dept != '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($pos['department']) . '">';
                                    $current_dept = $pos['department'];
                                endif;
                            ?>
                                <option value="<?php echo $pos['id']; ?>">
                                    <?php echo htmlspecialchars($pos['position_name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($current_dept != '') echo '</optgroup>'; ?>
                        </select>
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Salary (₱)</div>
                        <input type="number" class="form-control" name="salary" step="0.01" placeholder="700000.00">
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Hire Date *</div>
                        <input type="date" class="form-control" name="hire_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Location</div>
                        <input type="text" class="form-control" name="location" value="Manila, Philippines" placeholder="Manila, Philippines">
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3><i class="bi bi-geo-alt-fill"></i> Address Information</h3>

                <div class="info-box">
                    <div class="info-box-label">Street Address</div>
                    <input type="text" class="form-control" name="street_address" placeholder="123 Bonifacio Street">
                </div>

                <div class="address-row">
                    <div class="info-box">
                        <div class="info-box-label">Barangay</div>
                        <input type="text" class="form-control" name="barangay" placeholder="San Antonio">
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">City</div>
                        <input type="text" class="form-control" name="city" placeholder="Makati City">
                    </div>
                </div>

                <div class="address-row">
                    <div class="info-box">
                        <div class="info-box-label">Province</div>
                        <input type="text" class="form-control" name="province" placeholder="Metro Manila">
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Postal Code</div>
                        <input type="text" class="form-control" name="postal_code" maxlength="4" placeholder="1209">
                    </div>
                </div>
            </div>

            <!-- Account Settings -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3><i class="bi bi-gear-fill"></i> Account Settings</h3>

                <div class="address-row">
                    <div class="info-box">
                        <div class="info-box-label">Language</div>
                        <select class="form-control" name="language">
                            <option value="English" selected>English</option>
                            <option value="Filipino">Filipino</option>
                            <option value="Spanish">Spanish</option>
                            <option value="Mandarin">Mandarin</option>
                            <option value="Japanese">Japanese</option>
                        </select>
                    </div>

                    <div class="info-box">
                        <div class="info-box-label">Timezone</div>
                        <select class="form-control" name="timezone">
                            <option value="GMT-5">GMT-5 (EST)</option>
                            <option value="GMT-6">GMT-6 (CST)</option>
                            <option value="GMT-8">GMT-8 (PST)</option>
                            <option value="GMT+0">GMT+0 (UTC)</option>
                            <option value="GMT+1">GMT+1 (CET)</option>
                            <option value="GMT+8" selected>GMT+8 (PST/PHT)</option>
                            <option value="GMT+9">GMT+9 (JST)</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="bi bi-save"></i> Add Employee
            </button>
        </form>
    </div>
</div>

</body>
</html>
