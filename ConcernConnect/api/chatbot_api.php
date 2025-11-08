<?php
header('Content-Type: application/json');
session_start();

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = new Database();
$conn = $db->connect();

$employee_id = $_SESSION['employee_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');

    if ($message !== '') {
        // Save employee message
        $stmt = $conn->prepare("
            INSERT INTO chat_messages (employee_id, message, is_bot, created_at)
            VALUES (:employee_id, :message, 0, NOW())
        ");
        $stmt->execute([
            'employee_id' => $employee_id,
            'message' => $message
        ]);

        // Predefined answers with keywords
        $responses = [
            'company holidays' => "Our company observes both national and special holidays as declared by the Philippine government.

In addition, employees are entitled to company-declared holidays such as:
• Company Foundation Day
• Christmas Break (December 24–25)
• New Year's Eve (December 31)

A full list of holidays is posted on the HR Dashboard or may be requested from the HR Department.",
            
            'update personal data' => "To update your personal information (e.g., address, contact number, emergency contact):

1. Go to your Profile Settings in the Employee Portal
2. Click Edit Information and make the necessary updates
3. Submit your changes for HR review and approval

Alternatively, you may email the HR Department with supporting documents for verification.",
            
            'sick leave' => "Employees are entitled to up to 15 days of paid sick leave per year (subject to company policy).

To file a sick leave:
1. Submit your Sick Leave Request through the system before or immediately after your absence
2. Attach a medical certificate if the absence is more than two consecutive days
3. Wait for HR approval and confirmation through the portal or email

Unused sick leaves may be converted to cash or carried over depending on company rules.",
            
            'id request' => "To request an ID Card:

1. Go to Tickets and create a new ticket with category 'ID Request'
2. Provide your details and reason for the request
3. Submit for HR processing
4. HR will contact you for photo verification if needed
5. Your ID will be ready for pickup in 3-5 business days

For urgent requests, please contact the HR Department directly.",
            
            'id lost' => "If your ID or Access Card is lost:

1. Report it immediately to the HR Department
2. Create a ticket with category 'ID Lost or Access Card'
3. Provide details of when and where it was lost
4. Pay the replacement fee (if applicable per company policy)
5. A new card will be issued within 3-5 business days

In the meantime, notify your manager and the security team.",
            
            'access card' => "If your ID or Access Card is lost:

1. Report it immediately to the HR Department
2. Create a ticket with category 'ID Lost or Access Card'
3. Provide details of when and where it was lost
4. Pay the replacement fee (if applicable per company policy)
5. A new card will be issued within 3-5 business days

In the meantime, notify your manager and the security team.",
            
            'notary' => "For Notary Services:

1. Submit a ticket with category 'Notary Request'
2. Attach the document that needs to be notarized
3. Specify the purpose and urgency
4. The HR Department will schedule an appointment
5. Service is typically completed within 1-2 business days

Please bring a valid ID when you come for the notarization.",
            
            'benefits' => "For information about employee benefits:

• Health Insurance Coverage
• Retirement Plans (401k equivalent)
• Paid Time Off (PTO)
• Professional Development Fund
• Employee Assistance Program

For detailed information, visit the HR Dashboard or contact the HR Department for a benefits consultation.",
            
            'payroll' => "For payroll-related inquiries:

1. Check your payslip in the Employee Portal under Payroll
2. View tax deductions and gross/net pay
3. For discrepancies, contact the Payroll Department immediately
4. Direct deposit information can be updated in your Profile Settings

Payroll is processed on the 25th of each month.",
            
            'ticket' => "To create or manage support tickets:

1. Go to Tickets section in the Employee Portal
2. Click 'Create New Ticket'
3. Select the appropriate category (ID Request, Concern, etc.)
4. Provide detailed description
5. Track your ticket status in real-time

You can view all your tickets and their status anytime in the Tickets section.",
            
            'leave' => "For leave-related inquiries:

• Sick Leave: Up to 15 days per year
• Vacation Leave: As per company policy
• Emergency Leave: Contact HR immediately
• Unpaid Leave: Requires approval

To file a leave request:
1. Go to Tickets → Create New
2. Select 'Leave Request'
3. Choose leave type and dates
4. Submit for HR approval
5. Receive confirmation via email",
        ];

        // Convert message to lowercase for matching
        $messageLower = strtolower($message);

        // Find matching response
        $bot_response = "I'm here to help! Could you please rephrase your question or select one of the common questions from the sidebar? I can assist with company holidays, personal data updates, sick leave policies, ID requests, lost cards, notary services, benefits, payroll, tickets, and more.";

        foreach ($responses as $keyword => $answer) {
            if (stripos($messageLower, $keyword) !== false) {
                $bot_response = $answer;
                break;
            }
        }

        // Save bot reply
        $stmt = $conn->prepare("
            INSERT INTO chat_messages (employee_id, message, is_bot, created_at)
            VALUES (:employee_id, :message, 1, NOW())
        ");
        $stmt->execute([
            'employee_id' => $employee_id,
            'message' => $bot_response
        ]);

        echo json_encode([
            'success' => true,
            'response' => $bot_response
        ]);
    } else {
        echo json_encode(['error' => 'Empty message']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>
