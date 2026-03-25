<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/points_system.php';
require_once '../includes/functions.php';

// Check authentication - admin only
$require_admin = true;
require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../admin/returns.php');
    exit;
}

$loan_id = (int)($_POST['loan_id'] ?? 0);
$condition_on_return = sanitize($_POST['condition_on_return'] ?? '');
$return_notes = sanitize($_POST['return_notes'] ?? '');
$admin_id = $_SESSION['user_id'];

// Validate inputs
if (empty($loan_id) || empty($condition_on_return)) {
    $_SESSION['error'] = 'All required fields must be filled';
    header('Location: ../admin/returns.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Minimal loan info (needed to compute days late + points)
    $stmt = $pdo->prepare("
        SELECT due_date, condition_on_checkout, user_id
        FROM loans
        WHERE loan_id = ? AND status IN ('active', 'overdue')
    ");
    $stmt->execute([$loan_id]);
    $base = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$base) {
        throw new Exception('Loan not found or already returned');
    }

    $return_date = date('Y-m-d H:i:s');
    $days_late = getDaysLate($return_date, $base['due_date']);

    // Determine return status
    $return_status = ($days_late > 0) ? 'returned_late' : 'returned';

    // Update loan + equipment in SQL
    $stmt = $pdo->prepare("CALL sp_return_equipment_update(?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $loan_id,
        $admin_id,
        $return_status,
        $days_late,
        $condition_on_return,
        $return_notes
    ]);

    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$loan || (int)$loan['ok'] !== 1) {
        throw new Exception($loan['message'] ?? 'Return failed');
    }

    // Calculate points change (same logic as before)
    $points_result = calculateReturnPoints($days_late, $loan['condition_on_checkout'], $condition_on_return);
    $points_change = $points_result['points_change'];
    $reason = $points_result['reason'];
    $action_type = ($points_change < 0) ? 'penalty' : 'reward';

    // Apply points change (now handled by SQL procedure)
    $new_points = updateUserPoints(
        $pdo,
        $loan['user_id'],
        $points_change,
        $reason,
        $action_type,
        $loan_id,
        $days_late,
        $admin_id
    );

    if ($new_points === false) {
        throw new Exception('Failed to update points');
    }

    $pdo->commit();

    // Prepare success message
    $points_msg = ($points_change > 0) ? "+{$points_change}" : $points_change;
    $_SESSION['success'] = "Equipment returned successfully. Points: {$points_msg}. Student's new balance: {$new_points}";
    // Email is sent ONLY if the item was overdue
    if ($days_late > 0) {


        // Send email notification (same template)
        $email_message = "
        <html>
        <head>
        <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #800000; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .points { font-size: 24px; font-weight: bold; color: " . ($points_change >= 0 ? '#28a745' : '#dc3545') . "; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
        </head>
        <body>
        <div class='container'>
        <div class='header'>
        <h2>Overdue Return Notice - HAU Athletics</h2>
        </div>
        <div class='content'>
        <p>Dear {$loan['first_name']},</p>
        <p>Your equipment <strong>{$loan['name']}</strong> (Code: {$loan['code']}) has been returned.</p>
        <p><strong>Return Status:</strong> " . ucfirst(str_replace('_', ' ', $return_status)) . "</p>
        " . ($days_late > 0 ? "<p><strong>Days Late:</strong> {$days_late}</p>" : "") . "
        <p><strong>Points Change:</strong> <span class='points'>{$points_msg}</span></p>
        <p><strong>Current Points:</strong> {$new_points}</p>
        <p><strong>Reason:</strong> {$reason}</p>
        <p>Thank you for using HAU Athletics Equipment Portal.</p>
        </div>
        <div class='footer'>
        <p>&copy; " . date('Y') . " Holy Angel University - Athletics Department</p>
        </div>
        </div>
        </body>
        </html>
        ";

        sendEmail($loan['email'], 'Overdue Equipment Notice - HAU Athletics', $email_message);


    }
header('Location: ../admin/returns.php');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Return failed: " . $e->getMessage());
    $_SESSION['error'] = 'Return failed: ' . $e->getMessage();
    header('Location: ../admin/returns.php');
}
exit;
?>
