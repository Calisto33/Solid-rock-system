<?php
session_start();

// Core configurations and includes
include '../config.php'; // Ensure this path is correct
include './notifications.php'; // Optional, if needed for notifications

// PHPMailer Manual Include (use this if Composer doesn't work)
// Uncomment these lines and comment out the autoload line if using manual installation
/*
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';
*/

// PHPMailer Autoload (use this if Composer is working)
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ------------------------------------------------------------------------------------
// FEE REMINDER EMAIL FUNCTION
// ------------------------------------------------------------------------------------
/**
 * Generates and sends a fee reminder email using an AI prompt.
 */
function sendFeeReminderEmail(
    ?string $parentEmail,
    string $parentName,
    string $studentName,
    float $amountDue,
    string $dueDate,
    string $ollamaApiUrl = 'http://localhost:11434/api/generate',
    string $modelName = 'gemma3:1b',
    string $gmailUsername = 'ronaldbvirinyangwe@gmail.com',
    string $gmailAppPassword = 'bkepemqcdyxxedlr',
    string $senderName = 'Solid Rock Accounts'
): bool {

    if (empty($parentEmail)) {
        error_log("sendFeeReminderEmail was called with an empty email for student {$studentName}. Aborting.");
        return false;
    }

    // Log input parameters for debugging
    error_log("sendFeeReminderEmail called with parameters:");
    error_log("Parent Email: {$parentEmail}");
    error_log("Parent Name: {$parentName}");
    error_log("Student Name: {$studentName}");
    error_log("Amount Due: {$amountDue}");
    error_log("Due Date: {$dueDate}");
    error_log("Ollama API URL: {$ollamaApiUrl}");

    $formattedDueDate = !empty($dueDate) ? date('F j, Y', strtotime($dueDate)) : 'as soon as possible';
    $emailSubject = "Fee Reminder for {$studentName} - Solid Rock Group of Schools ";

    $keyPoints = [
        "This is a friendly reminder regarding an outstanding fee balance for student {$studentName}.",
        "Clearly state the outstanding balance due: $" . number_format($amountDue, 2) . ".",
        "Mention the payment due date was " . $formattedDueDate . ".",
        "Politely request that the payment be settled at their earliest convenience to ensure the student's account remains in good standing.",
        "Briefly mention payment methods (e.g., via the school portal, bank transfer, or at the front office).",
        "Provide contact information for any queries regarding fees (e.g., solidrockgroupofschool48@gmail.com or 0773022249)."
    ];

    $prompt = "You are an AI assistant for Solid Rock's accounts department. Your task is to draft ONLY THE BODY of a professional and courteous fee reminder email.\n\n"
            . "The email is addressed to {$parentName}.\n\n"
            . "The email body must be based on the following key points, ensuring a polite but clear tone:\n";
    foreach ($keyPoints as $point) {
        $prompt .= "- " . $point . "\n";
    }
    $prompt .= "\nMaintain a supportive and professional tone throughout.\n"
             . "Start the email body *directly* with the greeting (e.g., 'Dear {$parentName},').\n"
             . "End the email body with a professional closing from 'Solid Rock Accounts Department'.\n"
             . "Do NOT include the subject line or any conversational text like 'Here is the draft' before the actual email content. Generate only the email body.\n";

    // --- Ollama API Call ---
    $data = ['model' => $modelName, 'prompt' => $prompt, 'stream' => false];
    $jsonData = json_encode($data);
    $ch = curl_init($ollamaApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($jsonData)]);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $responseJson = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch) || $httpCode != 200) {
        error_log("Ollama API/cURL Error for reminder to {$parentEmail}. HTTP: {$httpCode}. Error: " . curl_error($ch) . ". Response: " . $responseJson);
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    $responseData = json_decode($responseJson, true);
    if (isset($responseData['response'])) {
        $generatedEmailBody = $responseData['response'];
        
        // Check if PHPMailer class exists before using it
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log("PHPMailer class not found. Please ensure PHPMailer is properly installed.");
            return false;
        }
        
        $mail = new PHPMailer(true);
        try {
            // --- PHPMailer Server Settings ---
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $gmailUsername;
            $mail->Password   = $gmailAppPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom($gmailUsername, $senderName);

            // ======================================================
            // === EMAIL RECIPIENT IS NOW ACTIVATED ===
            // ======================================================
            // The hardcoded test address has been removed.
            // This now sends the email to the actual parent's address from the database.
            $mail->addAddress($parentEmail, $parentName);
            // ======================================================

            $mail->isHTML(false);
            $mail->Subject = $emailSubject;
            $mail->Body    = $generatedEmailBody;

            $mail->send();
            error_log("Fee reminder email successfully sent to {$parentName} <{$parentEmail}> for student: {$studentName}");
            return true;
        } catch (Exception $e) {
            error_log("Reminder email could not be sent to {$parentEmail}. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    } else {
        error_log("Error: 'response' field not found in Ollama API output for reminder to {$parentEmail}.");
        return false;
    }
}
// --- END OF EMAIL SENDING FUNCTION ---


// --- MAIN SCRIPT LOGIC ---

if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

if (!isset($_GET['student_id'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Student ID not provided.'];
    header("Location: fees.php");
    exit();
}
$student_id = (int)$_GET['student_id'];

$query = "
    SELECT 
        s.username AS student_name,
        p_user.username AS parent_name,
        p_user.email AS parent_email,
        f.due_date,
        (f.total_fee - f.amount_paid) AS amount_due
    FROM students s
    JOIN fees f ON s.student_id = f.student_id
    LEFT JOIN parents p ON s.student_id = p.student_id
    LEFT JOIN users p_user ON p.user_id = p_user.id
    WHERE s.student_id = ? 
    AND (f.total_fee - f.amount_paid) > 0";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();

    if (empty($data['parent_email']) || !filter_var($data['parent_email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Could not send reminder. No valid parent email address is on file for ' . htmlspecialchars($data['student_name']) . '.'];
        header("Location: update_fees.php?student_id=" . urlencode($student_id));
        exit();
    }

    $emailSent = sendFeeReminderEmail(
        $data['parent_email'],
        $data['parent_name'] ?? 'Parent/Guardian',
        $data['student_name'],
        (float)$data['amount_due'],
        $data['due_date']
    );

    if ($emailSent) {
        if (function_exists('trigger_manual_reminder_sent_notification')) {
             trigger_manual_reminder_sent_notification($student_id, $conn);
        }
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Fee reminder sent to ' . htmlspecialchars($data['parent_name']) . ' for student ' . htmlspecialchars($data['student_name']) . '.'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to send the fee reminder email. Please check the server logs for details.'];
    }
} else {
    $_SESSION['message'] = ['type' => 'info', 'text' => 'Could not send reminder. The student either has no outstanding balance or could not be found.'];
}

$stmt->close();
$conn->close();

header("Location: update_fees.php?student_id=" . urlencode($student_id));
exit();