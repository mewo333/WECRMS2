<?php
/**
 * Helper functions for HR Notification System
 * File: includes/helpers.php
 * Sends notifications based on ROLE, not employee_id
 */

/**
 * Send notification to ALL users with role = 'hr_admin'
 */
function notifyHRNewTicket($conn, $ticket_id, $employee_name, $ticket_number, $ticket_title) {
    try {
        // Get ALL users with role = 'hr_admin'
        $stmt = $conn->prepare("SELECT employee_id FROM employees WHERE role = 'hr_admin'");
        $stmt->execute();
        $hr_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($hr_admins)) {
            error_log("ERROR: No users found with role = 'hr_admin'");
            return false;
        }
        
        error_log("DEBUG: Found " . count($hr_admins) . " hr_admin users");
        
        // Prepare notification message
        $message = "$employee_name submitted a new ticket: $ticket_title";
        
        // Insert notification for EACH HR admin user
        $stmt = $conn->prepare("
            INSERT INTO hr_notifications (hr_id, ticket_id, notification_type, message, is_read, created_at)
            VALUES (?, ?, 'new_ticket', ?, 0, NOW())
        ");
        
        $conn->beginTransaction();
        $inserted_count = 0;
        
        foreach ($hr_admins as $admin) {
            $hr_employee_id = trim($admin['employee_id']);
            try {
                $result = $stmt->execute([$hr_employee_id, $ticket_id, $message]);
                if ($result) {
                    $inserted_count++;
                    error_log("SUCCESS: Notification created for $hr_employee_id for ticket #$ticket_number");
                }
            } catch (Exception $e) {
                error_log("ERROR: Failed to insert notification for $hr_employee_id: " . $e->getMessage());
            }
        }
        
        $conn->commit();
        
        if ($inserted_count > 0) {
            error_log("SUCCESS: New ticket notification sent to $inserted_count hr_admin user(s)");
            return true;
        } else {
            error_log("ERROR: No notifications were inserted");
            return false;
        }
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("ERROR creating HR notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify ALL users with role = 'hr_admin' about urgent/high priority tickets
 */
function notifyHRUrgentTicket($conn, $ticket_id, $employee_name, $ticket_number, $ticket_title, $priority) {
    try {
        // Get ALL users with role = 'hr_admin'
        $stmt = $conn->prepare("SELECT employee_id FROM employees WHERE role = 'hr_admin'");
        $stmt->execute();
        $hr_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($hr_admins)) {
            error_log("ERROR: No users found with role = 'hr_admin'");
            return false;
        }
        
        error_log("DEBUG: Found " . count($hr_admins) . " hr_admin users");
        
        // Add urgent emoji to message
        $message = "🚨 URGENT: $employee_name submitted a $priority priority ticket: $ticket_title";
        
        // Insert urgent notification for EACH HR admin user
        $stmt = $conn->prepare("
            INSERT INTO hr_notifications (hr_id, ticket_id, notification_type, message, is_read, created_at)
            VALUES (?, ?, 'urgent_ticket', ?, 0, NOW())
        ");
        
        $conn->beginTransaction();
        $inserted_count = 0;
        
        foreach ($hr_admins as $admin) {
            $hr_employee_id = trim($admin['employee_id']);
            try {
                $result = $stmt->execute([$hr_employee_id, $ticket_id, $message]);
                if ($result) {
                    $inserted_count++;
                    error_log("SUCCESS: Urgent notification created for $hr_employee_id for ticket #$ticket_number");
                }
            } catch (Exception $e) {
                error_log("ERROR: Failed to insert urgent notification for $hr_employee_id: " . $e->getMessage());
            }
        }
        
        $conn->commit();
        
        if ($inserted_count > 0) {
            error_log("SUCCESS: Urgent ticket notification sent to $inserted_count hr_admin user(s)");
            return true;
        } else {
            error_log("ERROR: No urgent notifications were inserted");
            return false;
        }
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("ERROR creating urgent notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification to employee when HR responds to their ticket
 */
function notifyEmployeeTicketResponse($conn, $ticket_id, $employee_id, $ticket_number, $response_text) {
    try {
        $message = "HR responded to your ticket #$ticket_number";
        
        $stmt = $conn->prepare("
            INSERT INTO notifications (employee_id, ticket_id, notification_type, message, is_read, created_at)
            VALUES (?, ?, 'ticket_response', ?, 0, NOW())
        ");
        
        $stmt->execute([$employee_id, $ticket_id, $message]);
        
        error_log("SUCCESS: Employee notification sent for ticket #$ticket_number");
        return true;
        
    } catch (Exception $e) {
        error_log("ERROR creating employee notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification when ticket status changes
 */
function notifyTicketStatusChange($conn, $ticket_id, $employee_id, $ticket_number, $new_status) {
    try {
        $message = "Your ticket #$ticket_number status changed to: $new_status";
        
        $stmt = $conn->prepare("
            INSERT INTO notifications (employee_id, ticket_id, notification_type, message, is_read, created_at)
            VALUES (?, ?, 'status_change', ?, 0, NOW())
        ");
        
        $stmt->execute([$employee_id, $ticket_id, $message]);
        
        error_log("SUCCESS: Status change notification sent for ticket #$ticket_number");
        return true;
        
    } catch (Exception $e) {
        error_log("ERROR creating status notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count for user
 */
function getHRUnreadCount($conn, $employee_id) {
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as unread 
            FROM hr_notifications 
            WHERE hr_id = ? AND is_read = 0
        ");
        $stmt->execute([$employee_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['unread'] ?? 0;
    } catch (Exception $e) {
        error_log("ERROR getting unread count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark a single notification as read
 */
function markNotificationRead($conn, $notification_id, $employee_id) {
    try {
        $stmt = $conn->prepare("
            UPDATE hr_notifications 
            SET is_read = 1 
            WHERE id = ? AND hr_id = ?
        ");
        $stmt->execute([$notification_id, $employee_id]);
        return true;
    } catch (Exception $e) {
        error_log("ERROR marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for user
 */
function markAllNotificationsRead($conn, $employee_id) {
    try {
        $stmt = $conn->prepare("
            UPDATE hr_notifications 
            SET is_read = 1 
            WHERE hr_id = ? AND is_read = 0
        ");
        $stmt->execute([$employee_id]);
        return true;
    } catch (Exception $e) {
        error_log("ERROR marking all notifications as read: " . $e->getMessage());
        return false;
    }
}
?>
