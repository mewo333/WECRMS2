<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$db = new Database();
$conn = $db->connect();

$user = getEmployeeData($conn, $_SESSION['employee_id']);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone_number = $_POST['phone_number'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $query = "UPDATE employees SET phone_number = :phone_number, email = :email";
        $params = [
            'phone_number' => $phone_number,
            'email' => $email,
            'employee_id' => $_SESSION['employee_id']
        ];

        if (!empty($password)) {
            $query .= ", password = :password";
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $query .= " WHERE employee_id = :employee_id";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        $success = 'Settings updated successfully!';
        $user = getEmployeeData($conn, $_SESSION['employee_id']);
    } catch (PDOException $e) {
        $error = 'Failed to update settings. ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<style>
/* --- SETTINGS PAGE --- */
.settings-container {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    padding: 40px 50px;
    animation: fadeIn 0.4s ease;
}
.settings-container h2 {
    font-size: 2rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}
.settings-subtitle {
    color: #777;
    margin-bottom: 30px;
}
.settings-section h3 {
    font-size: 1.3rem;
    color: #444;
    border-bottom: 2px solid #f2f2f2;
    padding-bottom: 10px;
    margin-bottom: 25px;
}
.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    border-bottom: 1px solid #eee;
}
.setting-label { flex: 1; }
.setting-label strong { font-size: 1rem; color: #333; }
.setting-label p { font-size: 0.9rem; color: #777; margin-bottom: 0; }
.setting-value {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}
.setting-value input.form-control {
    width: 250px;
    border-radius: 8px;
    border: 1px solid #ccc;
    padding: 8px 12px;
    transition: all 0.3s ease;
}
.setting-value input.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}
.btn.btn-secondary.btn-sm {
    background-color: #f0f0f0;
    color: #333;
    border: none;
    border-radius: 6px;
    padding: 6px 14px;
    transition: all 0.3s ease;
}
.btn.btn-secondary.btn-sm:hover { background-color: #e0e0e0; }
.btn-primary {
    margin-top: 25px;
    border-radius: 8px;
    padding: 10px 20px;
}

/* --- TOGGLE SWITCH --- */
.toggle {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}
.toggle input { opacity: 0; width: 0; height: 0; }
.slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 26px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px; width: 18px;
    left: 4px; bottom: 4px;
    background-color: white;
    border-radius: 50%;
    transition: 0.4s;
}
.toggle input:checked + .slider { background-color: #007bff; }
.toggle input:checked + .slider:before { transform: translateX(24px); }

/* --- BADGE --- */
#password-strength-text {
    transition: all 0.3s ease;
    font-size: 0.8rem;
    padding: 6px 10px;
    border-radius: 6px;
}

/* --- RESPONSIVE --- */
@media (max-width: 768px) {
    .setting-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .setting-value {
        width: 100%;
    }
    .setting-value input.form-control {
        width: 100%;
    }
}

/* --- ANIMATION --- */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<body>
<?php include_once 'includes/sidebar.php'; ?>
<div class="main-content">
<?php include_once 'includes/employee_header.php'; ?>

<div class="settings-container">
<h2>Employee Settings</h2>
<p class="settings-subtitle">Manage your details and personal preferences here.</p>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php elseif ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="settings-section">
<h3>Basics</h3>

<form method="POST" action="">
    <div class="setting-item">
        <div class="setting-label">
            <strong>Password</strong>
            <p>Set a password to protect your account</p>
        </div>
        <div class="setting-value">
            <input type="password" name="password" id="password" class="form-control" placeholder="Enter new password">
            <span id="password-strength-text" class="badge" style="min-width:100px;"></span>
        </div>
    </div>

    <div class="setting-item">
        <div class="setting-label">
            <strong>Two-Step Verification</strong>
            <p>We recommend requiring a verification code in addition to your password</p>
        </div>
        <div class="setting-value">
            <label class="toggle">
                <input type="checkbox" id="two-step-toggle" <?php echo !empty($user['two_step_enabled']) && $user['two_step_enabled'] == 1 ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
            <span id="2fa-status"><?php echo !empty($user['two_step_enabled']) && $user['two_step_enabled'] == 1 ? 'Enabled' : 'Disabled'; ?></span>
        </div>
    </div>

    <div class="setting-item">
        <div class="setting-label">
            <strong>Email</strong>
            <p>Change your email address</p>
        </div>
        <div class="setting-value">
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control" required>
        </div>
    </div>

    <div class="setting-item">
        <div class="setting-label">
            <strong>Phone Number</strong>
            <p>Change your phone number</p>
        </div>
        <div class="setting-value">
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" class="form-control" placeholder="(+63) XXX XXX XXXX">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
</form>
</div>
</div>
</div>

<!-- 2FA Verification Modal -->
<div class="modal fade" id="twoFactorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 2px solid #f2f2f2;">
                <h5 class="modal-title" id="modalTitle">Enable Two-Step Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="step1" class="verification-step">
                    <p class="text-muted">To enable two-step verification, please verify your password first.</p>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current-password" placeholder="Enter your password">
                        <div class="invalid-feedback" id="password-error"></div>
                    </div>
                    <button type="button" class="btn btn-primary w-100" id="verify-password-btn">Verify Password</button>
                </div>

                <div id="step2" class="verification-step" style="display: none;">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> A verification code has been sent to your email address.
                    </div>
                    <p class="text-muted">Please enter the 6-digit code we sent to <strong id="user-email"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Verification Code</label>
                        <input type="text" class="form-control text-center" id="verification-code" maxlength="6" placeholder="000000" style="font-size: 1.5rem; letter-spacing: 0.5rem;">
                        <div class="invalid-feedback" id="code-error"></div>
                    </div>
                    <button type="button" class="btn btn-primary w-100" id="verify-code-btn">Confirm Code</button>
                    <button type="button" class="btn btn-link w-100 mt-2" id="resend-code-btn">Resend Code</button>
                </div>

                <div id="step3" class="verification-step" style="display: none;">
                    <div class="text-center">
                        <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                        <h4 class="mt-3">Two-Step Verification <span id="action-status">Enabled</span>!</h4>
                        <p class="text-muted">Your account is now more secure.</p>
                    </div>
                    <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="modalAction" value="">

<!-- Load Bootstrap JS FIRST -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Password Strength Checker
const passwordInput = document.getElementById('password');
const strengthText = document.getElementById('password-strength-text');

passwordInput.addEventListener('input', () => {
    const val = passwordInput.value;
    const strength = getPasswordStrength(val);
    strengthText.textContent = strength.label;
    strengthText.className = 'badge ' + strength.class;
});

function getPasswordStrength(password) {
    let score = 0;
    if (password.length >= 8) score++;
    if (password.match(/[A-Z]/)) score++;
    if (password.match(/[a-z]/)) score++;
    if (password.match(/[0-9]/)) score++;
    if (password.match(/[^A-Za-z0-9]/)) score++;

    if (password.length === 0) return { label: '', class: '' };
    if (score <= 2) return { label: 'Weak', class: 'bg-danger text-white' };
    else if (score === 3) return { label: 'Moderate', class: 'bg-warning text-dark' };
    else if (score === 4) return { label: 'Strong', class: 'bg-info text-white' };
    else return { label: 'Very Secure', class: 'bg-success text-white' };
}

// 2FA Modal JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded'); // Debug
    
    let currentAction = 'enable';
    const twoStepToggle = document.getElementById('two-step-toggle');
    const twoFactorModalEl = document.getElementById('twoFactorModal');
    const twoFactorModal = new bootstrap.Modal(twoFactorModalEl);
    
    // Toggle Event
    if (twoStepToggle) {
        twoStepToggle.addEventListener('change', function() {
            console.log('Toggle changed:', this.checked); // Debug
            
            if (this.checked) {
                currentAction = 'enable';
                document.getElementById('modalAction').value = 'enable';
                document.getElementById('modalTitle').textContent = 'Enable Two-Step Verification';
                document.getElementById('action-status').textContent = 'Enabled';
            } else {
                currentAction = 'disable';
                document.getElementById('modalAction').value = 'disable';
                document.getElementById('modalTitle').textContent = 'Disable Two-Step Verification';
                document.getElementById('action-status').textContent = 'Disabled';
            }
            
            resetModal();
            twoFactorModal.show();
        });
    }
    
    // Verify Password Button
    const verifyPasswordBtn = document.getElementById('verify-password-btn');
    if (verifyPasswordBtn) {
        verifyPasswordBtn.addEventListener('click', function() {
            console.log('Verify button clicked'); // Debug
            
            const password = document.getElementById('current-password').value;
            const passwordInput = document.getElementById('current-password');
            const passwordError = document.getElementById('password-error');
            
            passwordInput.classList.remove('is-invalid');
            passwordError.textContent = '';
            
            if (!password) {
                passwordInput.classList.add('is-invalid');
                passwordError.textContent = 'Please enter your password';
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verifying...';
            
            const formData = new FormData();
            formData.append('password', password);
            
            fetch('includes/verify_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data); // Debug
                
                if (data.success) {
                    document.getElementById('step1').style.display = 'none';
                    document.getElementById('step2').style.display = 'block';
                    document.getElementById('user-email').textContent = data.email;
                    sendVerificationCode();
                } else {
                    passwordInput.classList.add('is-invalid');
                    passwordError.textContent = data.message || 'Incorrect password';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = 'Verify Password';
            });
        });
    }
    
    // Send Verification Code
    function sendVerificationCode() {
        fetch('includes/send_2fa_code.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Failed to send code: ' + (data.message || ''));
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    // Verify Code Button
    const verifyCodeBtn = document.getElementById('verify-code-btn');
    if (verifyCodeBtn) {
        verifyCodeBtn.addEventListener('click', function() {
            const code = document.getElementById('verification-code').value;
            const codeInput = document.getElementById('verification-code');
            const codeError = document.getElementById('code-error');
            
            codeInput.classList.remove('is-invalid');
            codeError.textContent = '';
            
            if (!code || code.length !== 6) {
                codeInput.classList.add('is-invalid');
                codeError.textContent = 'Please enter a valid 6-digit code';
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verifying...';
            
            const formData = new FormData();
            formData.append('code', code);
            formData.append('action', currentAction);
            
            fetch('includes/verify_2fa_code.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('step2').style.display = 'none';
                    document.getElementById('step3').style.display = 'block';
                    document.getElementById('2fa-status').textContent = currentAction === 'enable' ? 'Enabled' : 'Disabled';
                } else {
                    codeInput.classList.add('is-invalid');
                    codeError.textContent = data.message || 'Invalid or expired code';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = 'Confirm Code';
            });
        });
    }
    
    // Resend Code
    const resendCodeBtn = document.getElementById('resend-code-btn');
    if (resendCodeBtn) {
        resendCodeBtn.addEventListener('click', function() {
            this.disabled = true;
            this.textContent = 'Sending...';
            sendVerificationCode();
            setTimeout(() => {
                this.disabled = false;
                this.textContent = 'Resend Code';
                alert('Code resent!');
            }, 2000);
        });
    }
    
    // Reset Modal
    function resetModal() {
        document.getElementById('step1').style.display = 'block';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'none';
        document.getElementById('current-password').value = '';
        document.getElementById('verification-code').value = '';
        document.getElementById('current-password').classList.remove('is-invalid');
        document.getElementById('verification-code').classList.remove('is-invalid');
    }
    
    // Modal hidden event
    twoFactorModalEl.addEventListener('hidden.bs.modal', function() {
        const isEnabled = document.getElementById('2fa-status').textContent === 'Enabled';
        twoStepToggle.checked = isEnabled;
    });
});
</script>

</body>
</html>
