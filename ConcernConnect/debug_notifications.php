<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h1>Debug: Notifications Table</h1>";

// Check what's in the notifications table
echo "<h2>Raw notifications data:</h2>";
$stmt = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 3");
$raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($raw);
echo "</pre>";

// Check employees table
echo "<h2>Employees data:</h2>";
$stmt = $conn->query("SELECT id, employee_id, full_name FROM employees LIMIT 5");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($employees);
echo "</pre>";

// Try the JOIN
echo "<h2>JOIN test (employee_id):</h2>";
$stmt = $conn->prepare("
    SELECT n.employee_id, e.employee_id as emp_id_check, e.full_name, e.id
    FROM notifications n 
    LEFT JOIN employees e ON n.employee_id = e.employee_id
    LIMIT 3
");
$stmt->execute();
$join_test = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($join_test);
echo "</pre>";

// Try JOIN with id
echo "<h2>JOIN test (id):</h2>";
$stmt = $conn->prepare("
    SELECT n.employee_id, e.employee_id as emp_id_check, e.full_name, e.id
    FROM notifications n 
    LEFT JOIN employees e ON CAST(n.employee_id AS UNSIGNED) = e.id
    LIMIT 3
");
$stmt->execute();
$join_test2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($join_test2);
echo "</pre>";
?>
