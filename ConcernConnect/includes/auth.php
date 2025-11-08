<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function login($conn, $email_or_employee_id, $password) {
    try {
        // Check if input is email or employee_id
        if (filter_var($email_or_employee_id, FILTER_VALIDATE_EMAIL)) {
            // Login with email
            $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ? LIMIT 1");
        } else {
            // Login with employee_id
            $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ? LIMIT 1");
        }
        
        $stmt->execute([$email_or_employee_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Check if password is hashed or plain text
            if (password_verify($password, $user['password'])) {
                // Hashed password - correct way
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'] ?? 'employee';
                return true;
            } elseif ($password === $user['password']) {
                // Plain text password - for backward compatibility
                // TODO: Hash this password after login
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'] ?? 'employee';
                
                // Update to hashed password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE employees SET password = ? WHERE employee_id = ?");
                $update_stmt->execute([$hashed, $user['employee_id']]);
                
                return true;
            }
        }

        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

function getEmployeeData($conn, $employee_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ? LIMIT 1");
        $stmt->execute([$employee_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get employee data error: " . $e->getMessage());
        return null;
    }
}

function isLoggedIn() {
    return isset($_SESSION['employee_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
