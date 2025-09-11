<?php
session_start();

// Core configurations and includes
include '../config.php';
include './notifications.php';

// PHPMailer Autoload
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ------------------------------------------------------------------------------------
// FUNCTION TO ENSURE INDIVIDUAL FEE RECORD EXISTS FOR STUDENT
// ------------------------------------------------------------------------------------
function ensureIndividualFeeRecord($student_id, $conn) {
    // Check if student has their own fee record
    $checkQuery = "SELECT fee_id FROM fees WHERE student_id = ?";
    $stmt = $conn->prepare($checkQuery);
    if ($stmt === false) {
        error_log("Failed to prepare check query: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Create individual fee record for this student
        $insertQuery = "INSERT INTO fees (student_id, total_fee, amount_paid, due_date, payment_plan, status) 
                       VALUES (?, 500.00, 0.00, DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'One-Time', 'Pending')";
        $insertStmt = $conn->prepare($insertQuery);
        if ($insertStmt === false) {
            error_log("Failed to prepare insert query: " . $conn->error);
            $stmt->close();
            return false;
        }
        
        $insertStmt->bind_param("s", $student_id);
        if (!$insertStmt->execute()) {
            error_log("Failed to create individual fee record for student {$student_id}: " . $insertStmt->error);
            $insertStmt->close();
            $stmt->close();
            return false;
        }
        $insertStmt->close();
        error_log("Created individual fee record for student {$student_id}");
    }
    
    $stmt->close();
    return true;
}

// ------------------------------------------------------------------------------------
// SAFE STRING TRUNCATION FUNCTION
// ------------------------------------------------------------------------------------
function safeTruncateString($string, $maxLength = 20) {
    $string = trim($string);
    if (strlen($string) > $maxLength) {
        return substr($string, 0, $maxLength);
    }
    return $string;
}

// ------------------------------------------------------------------------------------
// PAYMENT PLAN VALIDATOR
// ------------------------------------------------------------------------------------
function validateAndFixPaymentPlan($paymentPlan) {
    $planMappings = [
        'Monthly Installments' => 'Monthly',
        'Quarterly Payment Plan' => 'Quarterly',
        'Semi-Annual Payment' => 'Semi-Annual',
        'Annual Payment Plan' => 'Annual',
        'One-Time Payment' => 'One-Time',
        'Weekly Payments' => 'Weekly',
        'Bi-Weekly Payments' => 'Bi-Weekly'
    ];
    
    $plan = trim($paymentPlan);
    
    if (isset($planMappings[$plan])) {
        return $planMappings[$plan];
    }
    
    return safeTruncateString($plan, 20);
}

// Function to get formatted student name
function getFormattedStudentName($first_name, $last_name, $username, $student_id) {
    $first_name = trim($first_name ?? '');
    $last_name = trim($last_name ?? '');
    $username = trim($username ?? '');
    
    // If we have both first and last name
    if (!empty($first_name) && !empty($last_name)) {
        return $first_name . ' ' . $last_name;
    }
    // If we have only first name
    elseif (!empty($first_name)) {
        return $first_name;
    }
    // If we have only last name
    elseif (!empty($last_name)) {
        return $last_name;
    }
    // If we have username
    elseif (!empty($username)) {
        return $username;
    }
    // Fallback to student ID
    else {
        return 'Student ' . $student_id;
    }
}

// ------------------------------------------------------------------------------------
// EMAIL SENDING FUNCTION (Your existing function - unchanged)
// ------------------------------------------------------------------------------------
function sendPaymentConfirmationEmail(
    string $parentEmail,
    string $parentName,
    string $studentName,
    float $paymentAmount,
    float $totalFee,
    float $amountPaidSoFar,
    string $paymentMethod,
    mysqli $dbConnection,
    string $ollamaApiUrl = 'http://localhost:11434/api/generate',
    string $modelName = 'gemma3:1b',
    string $gmailUsername = 'ronaldbvirinyangwe@gmail.com',
    string $gmailAppPassword = 'bkepemqcdyxxedlr',
    string $senderName = 'Solid Rock Group Of Schools Accounts'
): bool {

    $balanceDue = $totalFee - $amountPaidSoFar;
    $emailSubject = "Payment Confirmation for {$studentName} - Solid Rock ";

    $keyPoints = [
        "Acknowledge the recent payment of $" . number_format($paymentAmount, 2) . " for {$studentName} via {$paymentMethod}.",
        "Confirm the payment has been successfully processed.",
        "State the total fee: $" . number_format($totalFee, 2) . ".",
        "State the total amount paid so far: $" . number_format($amountPaidSoFar, 2) . ".",
        "Clearly state the remaining balance: $" . number_format($balanceDue, 2) . ".",
        ($balanceDue <= 0 ? "Mention that the fees are fully paid up. Thank them." : "If there's a balance, gently remind them of it and mention the due date if available."),
        "Provide contact information for any queries regarding fees (e.g., solidrockgroupofschool48@gmail.com or 0773022249)."
    ];

    $promptParentNameForLLM = $parentName;

    $prompt = "You are an AI assistant for Solid Rock Group Of Schools. Your task is to draft ONLY the BODY of a professional and friendly payment confirmation email.\n\n";
    $prompt .= "The email is addressed to {$promptParentNameForLLM}.\n";
    $prompt .= "The subject line for this email will be: \"{$emailSubject}\". Do NOT include the subject line itself in the email body you generate.\n\n";

    $prompt .= "The email body must cover the following key points, ensuring all monetary values are clear:\n";
    foreach ($keyPoints as $point) {
        $prompt .= "- " . $point . "\n";
    }
    $prompt .= "\nMaintain a courteous and professional tone.\n";
    $prompt .= "Start the email body *directly* with the greeting (e.g., 'Dear {$promptParentNameForLLM},').\n";
    $prompt .= "End the email body with a professional closing from 'Solid Rock  Accounts Department'.\n";
    $prompt .= "Do NOT include any introductory phrases like 'Here is the draft,' 'Okay, here is the email body,' or any similar conversational text before the actual email content.\n";
    $prompt .= "Generate only the email body content.\n";

    // Prepare data for Ollama API
    $data = [
        'model' => $modelName,
        'prompt' => $prompt,
        'stream' => false
    ];
    $jsonData = json_encode($data);

    $ch = curl_init($ollamaApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $responseJson = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log("Ollama cURL Error for {$parentEmail}: " . curl_error($ch));
        curl_close($ch);
        return false;
    }

    if ($httpCode != 200) {
        error_log("Ollama API Error for {$parentEmail}. HTTP Code: {$httpCode}. Response: {$responseJson}");
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    $responseData = json_decode($responseJson, true);

    if (isset($responseData['response'])) {
        $generatedEmailBody = $responseData['response'];
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $gmailUsername;
            $mail->Password   = $gmailAppPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($gmailUsername, $senderName);
            $mail->addAddress($parentEmail, $parentName);

            $mail->isHTML(false);
            $mail->Subject = $emailSubject;
            $mail->Body    = $generatedEmailBody;

            $mail->send();
            error_log("Payment confirmation email successfully sent to {$parentName} <{$parentEmail}>. Student: {$studentName}");
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent to {$parentEmail}. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    } else {
        error_log("Error: 'response' field not found in Ollama API output for {$parentEmail}. Full Response: " . print_r($responseData, true));
        return false;
    }
}

// ------------------------------------------------------------------------------------
// ADD AJAX HANDLING HERE - THIS IS THE KEY FIX
// ------------------------------------------------------------------------------------
if (isset($_POST['ajax_action'])) {
    $action = $_POST['ajax_action'];
    
    if ($action === 'get_student_data' && isset($_POST['student_id'])) {
        $student_id = $_POST['student_id'];
        
        // Ensure student has fee record
        ensureIndividualFeeRecord($student_id, $conn);
        
        // Get student data with proper name handling
        $query = "SELECT s.student_id, 
                         s.first_name,
                         s.last_name,
                         s.username,
                         s.class,
                         COALESCE(f.total_fee, 0.00) AS total_fee,
                         COALESCE(f.amount_paid, 0.00) AS amount_paid,
                         COALESCE(f.status, 'No Fee Assigned') AS status,
                         f.due_date,
                         f.payment_plan
                  FROM students s
                  LEFT JOIN fees f ON s.student_id = f.student_id
                  WHERE s.student_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student_data = $result->fetch_assoc();
        
        if ($student_data) {
            // Format the student name
            $student_data['student_name'] = getFormattedStudentName(
                $student_data['first_name'], 
                $student_data['last_name'], 
                $student_data['username'], 
                $student_data['student_id']
            );
        }
        
        // Get payment history
        $paymentsQuery = "SELECT payment_date, amount, payment_method, received_by, created_at
                          FROM payments 
                          WHERE student_id = ? 
                          ORDER BY payment_date DESC";
        $stmt_payments = $conn->prepare($paymentsQuery);
        $stmt_payments->bind_param("s", $student_id);
        $stmt_payments->execute();
        $paymentsResult = $stmt_payments->get_result();
        $payments = $paymentsResult->fetch_all(MYSQLI_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'student_data' => $student_data,
            'payments' => $payments
        ]);
        exit();
    }
    
    if ($action === 'update_fee_structure' && isset($_POST['student_id'])) {
        $student_id = $_POST['student_id'];
        
        $total_fee_post = filter_input(INPUT_POST, 'total_fee', FILTER_VALIDATE_FLOAT);
        $amount_paid_post = filter_input(INPUT_POST, 'amount_paid', FILTER_VALIDATE_FLOAT);
        $due_date_post = trim($_POST['due_date'] ?? '');
        $payment_plan_raw = trim($_POST['payment_plan'] ?? '');
        
        $payment_plan_post = validateAndFixPaymentPlan($payment_plan_raw);
        
        if ($total_fee_post === false || $amount_paid_post === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid fee or amount values.']);
            exit();
        } elseif ($total_fee_post < 0 || $amount_paid_post < 0) {
            echo json_encode(['success' => false, 'message' => 'Fee and amount paid values cannot be negative.']);
            exit();
        } elseif ($amount_paid_post > $total_fee_post) {
            echo json_encode(['success' => false, 'message' => 'Amount paid cannot be greater than total fee.']);
            exit();
        }
        
        // ENHANCED STATUS LOGIC WITH PROPER OVERDUE CHECKING
        $new_status_general_update = 'Pending';
        if ($total_fee_post > 0) {
            if ($amount_paid_post >= $total_fee_post) {
                $new_status_general_update = 'Cleared';
            } elseif ($amount_paid_post < $total_fee_post) {
                // Check if due date has passed for overdue status
                $due_date_obj = new DateTime($due_date_post);
                $today = new DateTime();
                if ($due_date_obj < $today) {
                    $new_status_general_update = 'Overdue';
                } else {
                    $new_status_general_update = 'Pending';
                }
            }
        }

        // Check for existing fee record
        $check_fee_exists_query = "SELECT fee_id FROM fees WHERE student_id = ? LIMIT 1";
        $stmt_check_fee = $conn->prepare($check_fee_exists_query);
        $stmt_check_fee->bind_param("s", $student_id);
        $stmt_check_fee->execute();
        $result_check_fee = $stmt_check_fee->get_result();
        $existing_fee_data = $result_check_fee->fetch_assoc();
        $fee_exists = (bool)$existing_fee_data;
        $fee_id_to_update = $existing_fee_data['fee_id'] ?? null;
        $stmt_check_fee->close();

        if ($fee_exists) {
            $updateQuery = "UPDATE fees 
                           SET total_fee = ?, amount_paid = ?, due_date = ?, payment_plan = ?, status = ? 
                           WHERE student_id = ? AND fee_id = ?";
            $stmt_update_details = $conn->prepare($updateQuery);
            $stmt_update_details->bind_param("ddssssi", $total_fee_post, $amount_paid_post, $due_date_post, $payment_plan_post, $new_status_general_update, $student_id, $fee_id_to_update);
        } else {
            $insertQuery = "INSERT INTO fees (student_id, total_fee, amount_paid, due_date, payment_plan, status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_update_details = $conn->prepare($insertQuery);
            $stmt_update_details->bind_param("sddsss", $student_id, $total_fee_post, $amount_paid_post, $due_date_post, $payment_plan_post, $new_status_general_update);
        }

        if ($stmt_update_details->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Fee details updated successfully!',
                'updated_data' => [
                    'total_fee' => $total_fee_post,
                    'amount_paid' => $amount_paid_post,
                    'status' => $new_status_general_update,
                    'due_date' => $due_date_post
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating fee details: ' . $stmt_update_details->error]);
        }
        $stmt_update_details->close();
        exit();
    }
    
    if ($action === 'record_payment' && isset($_POST['student_id'])) {
        $student_id = $_POST['student_id'];
        
        $payment_amount = filter_input(INPUT_POST, 'new_payment_amount', FILTER_VALIDATE_FLOAT);
        $payment_method = trim($_POST['payment_method'] ?? '');
        $received_by = $_SESSION['username'] ?? ($_SESSION['super_admin_username'] ?? 'Super Admin');

        if ($payment_amount === false || $payment_amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Payment amount must be a positive number.']);
            exit();
        }
        
        // Get current fee data
        $current_fee_query = "SELECT fee_id, total_fee, amount_paid, status 
                             FROM fees 
                             WHERE student_id = ? 
                             LIMIT 1";
        $stmt_current = $conn->prepare($current_fee_query);
        $stmt_current->bind_param("s", $student_id);
        $stmt_current->execute();
        $result_current = $stmt_current->get_result();
        $current_fee_data = $result_current->fetch_assoc();
        $stmt_current->close();

        if (!$current_fee_data) {
            echo json_encode(['success' => false, 'message' => 'No existing fee record found for this student.']);
            exit();
        }
        
        $fee_id_for_payment = $current_fee_data['fee_id'];
        $total_fee_current = $current_fee_data['total_fee'] ?? 0;
        $amount_paid_current = $current_fee_data['amount_paid'] ?? 0;
        $updated_amount_paid = $amount_paid_current + $payment_amount;

        // ENHANCED STATUS LOGIC WITH PROPER OVERDUE CHECKING
        $new_status_after_payment = 'Pending';
        if ($total_fee_current > 0) {
            if ($updated_amount_paid >= $total_fee_current) {
                $new_status_after_payment = 'Cleared';
            } else {
                // Check if due date has passed for overdue status
                $due_date_query = "SELECT due_date FROM fees WHERE student_id = ? AND fee_id = ?";
                $stmt_due_date = $conn->prepare($due_date_query);
                $stmt_due_date->bind_param("si", $student_id, $fee_id_for_payment);
                $stmt_due_date->execute();
                $due_date_result = $stmt_due_date->get_result();
                $due_date_data = $due_date_result->fetch_assoc();
                $stmt_due_date->close();
                
                if ($due_date_data && $due_date_data['due_date']) {
                    $due_date_obj = new DateTime($due_date_data['due_date']);
                    $today = new DateTime();
                    if ($due_date_obj < $today) {
                        $new_status_after_payment = 'Overdue';
                    } else {
                        $new_status_after_payment = 'Pending';
                    }
                } else {
                    $new_status_after_payment = 'Pending';
                }
            }
        }

        $insertPaymentQuery = "INSERT INTO payments (student_id, fee_id, payment_date, amount, payment_method, received_by) VALUES (?, ?, CURDATE(), ?, ?, ?)";
        $stmt_payment = $conn->prepare($insertPaymentQuery);
        $stmt_payment->bind_param("sidss", $student_id, $fee_id_for_payment, $payment_amount, $payment_method, $received_by);

        if ($stmt_payment->execute()) {
            $updateFeeAmountQuery = "UPDATE fees 
                                   SET amount_paid = ?, status = ? 
                                   WHERE student_id = ? AND fee_id = ?";
            $stmt_fee_amount = $conn->prepare($updateFeeAmountQuery);
            $stmt_fee_amount->bind_param("dssi", $updated_amount_paid, $new_status_after_payment, $student_id, $fee_id_for_payment);

            if ($stmt_fee_amount->execute()) {
                // Send email (your existing logic)
                $parentInfoQuery = "SELECT u.email AS parent_email, u.username AS parent_name, s.username AS student_name
                                    FROM students s
                                    LEFT JOIN parents p ON s.student_id = p.student_id
                                    LEFT JOIN users u ON p.user_id = u.id
                                    WHERE s.student_id = ?";
                $stmt_parent_info = $conn->prepare($parentInfoQuery);
                if ($stmt_parent_info) {
                    $stmt_parent_info->bind_param("s", $student_id);
                    $stmt_parent_info->execute();
                    $result_parent_info = $stmt_parent_info->get_result();

                    if ($parent_data = $result_parent_info->fetch_assoc()) {
                        if (!empty($parent_data['parent_email'])) {
                            sendPaymentConfirmationEmail(
                                $parent_data['parent_email'],
                                $parent_data['parent_name'] ?? 'Parent/Guardian',
                                $parent_data['student_name'] ?? 'N/A',
                                $payment_amount,
                                $total_fee_current,
                                $updated_amount_paid,
                                $payment_method,
                                $conn
                            );
                        }
                    }
                    $stmt_parent_info->close();
                }

                if (function_exists('trigger_payment_received_notification')) {
                    trigger_payment_received_notification($student_id, $payment_amount, $conn);
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Payment recorded successfully!',
                    'payment_amount' => $payment_amount,
                    'updated_data' => [
                        'total_fee' => $total_fee_current,
                        'amount_paid' => $updated_amount_paid,
                        'status' => $new_status_after_payment
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating fee amount: ' . $stmt_fee_amount->error]);
            }
            $stmt_fee_amount->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Error recording payment: ' . $stmt_payment->error]);
        }
        $stmt_payment->close();
        exit();
    }
}

// Check if user is a super admin
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

// Ensure the student_id is provided
if (!isset($_GET['student_id']) && !isset($_POST['student_id'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Student ID is missing. Cannot update fees.'];
    header("Location: fees.php");
    exit();
}

$student_id = $_GET['student_id'] ?? $_POST['student_id'];

// CRITICAL FIX: Ensure this student has their own individual fee record
if (!ensureIndividualFeeRecord($student_id, $conn)) {
    die("Failed to ensure individual fee record for student. Please check error logs.");
}

// Initialize $fee array with default values
$fee = [
    'fee_id' => null,
    'total_fee' => 0.00,
    'amount_paid' => 0.00,
    'due_date' => date('Y-m-d', strtotime('+1 month')),
    'payment_plan' => 'One-Time',
    'status' => 'Pending'
];

// Fetch current fee details for THIS SPECIFIC STUDENT ONLY
$query_initial_fee = "SELECT fee_id, total_fee, amount_paid, due_date, payment_plan, status 
                      FROM fees 
                      WHERE student_id = ? 
                      LIMIT 1";
$stmt_initial_fee = $conn->prepare($query_initial_fee);
if ($stmt_initial_fee === false) {
    die("Prepare failed (initial fee fetch): (" . $conn->errno . ") " . $conn->error);
}
$stmt_initial_fee->bind_param("s", $student_id);
if (!$stmt_initial_fee->execute()) {
    die("Execute failed (initial fee fetch): (" . $stmt_initial_fee->errno . ") " . $stmt_initial_fee->error);
}
$result_initial_fee = $stmt_initial_fee->get_result();
if ($result_initial_fee->num_rows > 0) {
    $fee = array_merge($fee, $result_initial_fee->fetch_assoc());
}
$stmt_initial_fee->close();

// Initialize payments array and fetch payment history for THIS STUDENT ONLY
$payments = [];
$paymentsQuery = "SELECT payment_date, amount, payment_method, received_by, created_at
                  FROM payments 
                  WHERE student_id = ? 
                  ORDER BY payment_date DESC";
$stmt_payments_history = $conn->prepare($paymentsQuery);
if ($stmt_payments_history === false) {
    die("Prepare failed (payments history fetch): (" . $conn->errno . ") " . $conn->error);
}
$stmt_payments_history->bind_param("s", $student_id);
if (!$stmt_payments_history->execute()) {
    die("Execute failed (payments history fetch): (" . $stmt_payments_history->errno . ") " . $stmt_payments_history->error);
}
$paymentsResult = $stmt_payments_history->get_result();
while ($row = $paymentsResult->fetch_assoc()) {
    $payments[] = $row;
}

// Store messages for display
$page_messages = [];

// Handle POST requests (ONLY if not AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
    $is_payment_record = isset($_POST['is_payment']) && $_POST['is_payment'] == 'true';

    if ($is_payment_record) {
        // --- Handle New Payment Record ---
        $payment_amount = filter_input(INPUT_POST, 'new_payment_amount', FILTER_VALIDATE_FLOAT);
        $payment_method = trim($_POST['payment_method'] ?? '');
        $received_by = $_SESSION['username'] ?? ($_SESSION['super_admin_username'] ?? 'Super Admin');

        if ($payment_amount === false || $payment_amount <= 0) {
            $page_messages[] = ['type' => 'error', 'text' => 'Payment amount must be a positive number.'];
        } elseif (empty($payment_method)) {
            $page_messages[] = ['type' => 'error', 'text' => 'Payment method is required.'];
        } elseif (empty($received_by)) {
            $page_messages[] = ['type' => 'error', 'text' => 'Received by field is required.'];
        } else {
            // CRITICAL: Fetch fee record for THIS SPECIFIC STUDENT ONLY
            $current_fee_query = "SELECT fee_id, total_fee, amount_paid, status 
                                 FROM fees 
                                 WHERE student_id = ? 
                                 LIMIT 1";
            $stmt_current = $conn->prepare($current_fee_query);
            
            if ($stmt_current === false) {
                error_log("Prepare failed (fetch current fee): " . $conn->error);
                $page_messages[] = ['type' => 'error', 'text' => 'Database error occurred. Please try again.'];
            } else {
                $stmt_current->bind_param("s", $student_id);
                
                if (!$stmt_current->execute()) {
                    error_log("Execute failed (fetch current fee): " . $stmt_current->error);
                    $page_messages[] = ['type' => 'error', 'text' => 'Database error occurred. Please try again.'];
                    $stmt_current->close();
                } else {
                    $result_current = $stmt_current->get_result();
                    $current_fee_data = $result_current->fetch_assoc();
                    $stmt_current->close();

                    if (!$current_fee_data) {
                        $page_messages[] = ['type' => 'error', 'text' => 'No existing fee record found for this student. Please create a fee structure first.'];
                    } else {
                        $fee_id_for_payment = $current_fee_data['fee_id'];
                        $total_fee_current = $current_fee_data['total_fee'] ?? 0;
                        $amount_paid_current = $current_fee_data['amount_paid'] ?? 0;
                        $updated_amount_paid = $amount_paid_current + $payment_amount;

                        // Validate fee_id is numeric
                        if (!is_numeric($fee_id_for_payment) || $fee_id_for_payment <= 0) {
                            error_log("Invalid fee_id: {$fee_id_for_payment} for student: {$student_id}");
                            $page_messages[] = ['type' => 'error', 'text' => 'Invalid fee record. Please contact administrator.'];
                        } else {
                            // Determine new status
                            $new_status_after_payment = 'Pending';
                            if ($total_fee_current > 0) {
                                if ($updated_amount_paid >= $total_fee_current) {
                                    $new_status_after_payment = 'Cleared';
                                } elseif ($updated_amount_paid > 0) {
                                    $new_status_after_payment = 'Partial';
                                }
                            }

                            // Log the values being inserted
                            error_log("Inserting payment - Student: {$student_id}, Fee ID: {$fee_id_for_payment}, Amount: {$payment_amount}, Method: {$payment_method}, Received by: {$received_by}");

                            $insertPaymentQuery = "INSERT INTO payments (student_id, fee_id, payment_date, amount, payment_method, received_by) VALUES (?, ?, CURDATE(), ?, ?, ?)";
                            $stmt_payment = $conn->prepare($insertPaymentQuery);
                            
                            if ($stmt_payment === false) {
                                error_log("Prepare failed (insert payment): " . $conn->error);
                                $page_messages[] = ['type' => 'error', 'text' => 'Database error preparing payment insertion.'];
                            } else {
                                // FIXED: Correct parameter types - fee_id is integer, so use "siiss" not "sidss"
                                $stmt_payment->bind_param("siiss", $student_id, $fee_id_for_payment, $payment_amount, $payment_method, $received_by);

                                if ($stmt_payment->execute()) {
                                    error_log("Payment recorded successfully for student: {$student_id}");
                                    
                                    // Update fee amount
                                    $updateFeeAmountQuery = "UPDATE fees 
                                                           SET amount_paid = ?, status = ? 
                                                           WHERE student_id = ? AND fee_id = ?";
                                    $stmt_fee_amount = $conn->prepare($updateFeeAmountQuery);
                                    
                                    if ($stmt_fee_amount === false) {
                                        error_log("Prepare failed (update fee amount): " . $conn->error);
                                        $page_messages[] = ['type' => 'error', 'text' => 'Payment recorded but failed to update fee totals.'];
                                    } else {
                                        $stmt_fee_amount->bind_param("dssi", $updated_amount_paid, $new_status_after_payment, $student_id, $fee_id_for_payment);

                                        if ($stmt_fee_amount->execute()) {
                                            // EMAIL SENDING LOGIC
                                            $parentInfoQuery = "SELECT u.email AS parent_email, u.username AS parent_name, s.username AS student_name
                                                                FROM students s
                                                                LEFT JOIN parents p ON s.student_id = p.student_id
                                                                LEFT JOIN users u ON p.user_id = u.id
                                                                WHERE s.student_id = ?";
                                            $stmt_parent_info = $conn->prepare($parentInfoQuery);
                                            if ($stmt_parent_info) {
                                                $stmt_parent_info->bind_param("s", $student_id);
                                                $stmt_parent_info->execute();
                                                $result_parent_info = $stmt_parent_info->get_result();

                                                if ($parent_data = $result_parent_info->fetch_assoc()) {
                                                    if (!empty($parent_data['parent_email'])) {
                                                        $emailSent = sendPaymentConfirmationEmail(
                                                            $parent_data['parent_email'],
                                                            $parent_data['parent_name'] ?? 'Parent/Guardian',
                                                            $parent_data['student_name'] ?? 'N/A',
                                                            $payment_amount,
                                                            $total_fee_current,
                                                            $updated_amount_paid,
                                                            $payment_method,
                                                            $conn
                                                        );
                                                        $_SESSION['email_status_message'] = $emailSent ?
                                                            "Payment confirmation email sent to " . htmlspecialchars($parent_data['parent_name'] ?? 'Parent') . "." :
                                                            "Failed to send payment confirmation email. Please check server logs.";
                                                    } else {
                                                         $_SESSION['email_status_message'] = "Parent email not found for student ID: {$student_id}. Email not sent.";
                                                         error_log("Parent email not found for student ID: {$student_id} for payment confirmation email.");
                                                    }
                                                } else {
                                                    $_SESSION['email_status_message'] = "Could not find parent/student details for student ID: {$student_id}. Email not sent.";
                                                    error_log("Parent/student details not found for student ID: {$student_id} for payment confirmation email.");
                                                }
                                                $stmt_parent_info->close();
                                            } else {
                                                $_SESSION['email_status_message'] = "Database error fetching parent details for email. Email not sent.";
                                                error_log("Failed to prepare parent_info_query: " . $conn->error);
                                            }

                                            if (function_exists('trigger_payment_received_notification')) {
                                                 trigger_payment_received_notification($student_id, $payment_amount, $conn);
                                            }
                                            header("Location: update_fees.php?student_id=" . urlencode($student_id) . "&status=payment_success");
                                            exit();
                                        } else {
                                            error_log("Failed to update fee amount - Student: {$student_id}, Error: " . $stmt_fee_amount->error);
                                            $page_messages[] = ['type' => 'error', 'text' => 'Payment recorded but failed to update fee totals: ' . $stmt_fee_amount->error];
                                        }
                                        $stmt_fee_amount->close();
                                    }
                                } else {
                                    error_log("Payment insertion failed - Student: {$student_id}, Fee ID: {$fee_id_for_payment}, Amount: {$payment_amount}, Error: " . $stmt_payment->error);
                                    $page_messages[] = ['type' => 'error', 'text' => 'Error recording payment: ' . $stmt_payment->error];
                                }
                                $stmt_payment->close();
                            }
                        }
                    }
                }
            }
        }
    } else {
        // --- Handle General Fee Details Update ---
        $total_fee_post = filter_input(INPUT_POST, 'total_fee', FILTER_VALIDATE_FLOAT);
        $amount_paid_post = filter_input(INPUT_POST, 'amount_paid', FILTER_VALIDATE_FLOAT);
        $due_date_post = trim($_POST['due_date'] ?? '');
        $payment_plan_raw = trim($_POST['payment_plan'] ?? '');
        
        $payment_plan_post = validateAndFixPaymentPlan($payment_plan_raw);
        
        if ($payment_plan_raw !== $payment_plan_post && !empty($payment_plan_raw)) {
            $page_messages[] = ['type' => 'info', 'text' => "Payment plan was shortened from '{$payment_plan_raw}' to '{$payment_plan_post}' to fit database constraints."];
        }
        
        if ($total_fee_post === false || $amount_paid_post === false) {
            $page_messages[] = ['type' => 'error', 'text' => 'Invalid fee or amount values.'];
        } elseif ($total_fee_post < 0 || $amount_paid_post < 0) {
            $page_messages[] = ['type' => 'error', 'text' => 'Fee and amount paid values cannot be negative.'];
        } elseif ($amount_paid_post > $total_fee_post) {
            $page_messages[] = ['type' => 'error', 'text' => 'Amount paid cannot be greater than total fee.'];
        } else {
            $new_status_general_update = 'Pending';
            if ($total_fee_post > 0) {
                 if ($amount_paid_post >= $total_fee_post) {
                    $new_status_general_update = 'Cleared';
                } elseif ($amount_paid_post > 0) {
                    $new_status_general_update = 'Partial';
                }
            }

            // CRITICAL: Check for existing fee record for THIS SPECIFIC STUDENT
            $check_fee_exists_query = "SELECT fee_id FROM fees WHERE student_id = ? LIMIT 1";
            $stmt_check_fee = $conn->prepare($check_fee_exists_query);
            if ($stmt_check_fee === false) { die("Prepare failed (check fee exists): " . $conn->error); }
            $stmt_check_fee->bind_param("s", $student_id);
            $stmt_check_fee->execute();
            $result_check_fee = $stmt_check_fee->get_result();
            $existing_fee_data = $result_check_fee->fetch_assoc();
            $fee_exists = (bool)$existing_fee_data;
            $fee_id_to_update = $existing_fee_data['fee_id'] ?? null;
            $stmt_check_fee->close();

            if ($fee_exists) {
                // CRITICAL: Update ONLY this student's record using BOTH student_id AND fee_id
                $updateQuery = "UPDATE fees 
                               SET total_fee = ?, amount_paid = ?, due_date = ?, payment_plan = ?, status = ? 
                               WHERE student_id = ? AND fee_id = ?";
                $stmt_update_details = $conn->prepare($updateQuery);
                if ($stmt_update_details === false) { die("Prepare failed (update fee details): " . $conn->error); }
                $stmt_update_details->bind_param("ddssssi", $total_fee_post, $amount_paid_post, $due_date_post, $payment_plan_post, $new_status_general_update, $student_id, $fee_id_to_update);
            } else {
                // Create new record for this specific student
                $insertQuery = "INSERT INTO fees (student_id, total_fee, amount_paid, due_date, payment_plan, status) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_update_details = $conn->prepare($insertQuery);
                if ($stmt_update_details === false) { die("Prepare failed (insert fee details): " . $conn->error); }
                $stmt_update_details->bind_param("sddsss", $student_id, $total_fee_post, $amount_paid_post, $due_date_post, $payment_plan_post, $new_status_general_update);
            }

            if ($stmt_update_details->execute()) {
                header("Location: update_fees.php?student_id=" . urlencode($student_id) . "&status=details_success");
                exit();
            } else {
                $page_messages[] = ['type' => 'error', 'text' => "Error " . ($fee_exists ? "updating" : "creating") . " fee details: " . $stmt_update_details->error];
            }
            $stmt_update_details->close();
        }
    }

    // Re-fetch data if there was a POST and it didn't redirect
    $query_refetch_fee = "SELECT fee_id, total_fee, amount_paid, due_date, payment_plan, status 
                          FROM fees 
                          WHERE student_id = ? 
                          LIMIT 1";
    $stmt_refetch_fee = $conn->prepare($query_refetch_fee);
    $stmt_refetch_fee->bind_param("s", $student_id);
    $stmt_refetch_fee->execute();
    $result_refetch_fee = $stmt_refetch_fee->get_result();
    if ($result_refetch_fee->num_rows > 0) {
        $fee = array_merge($fee, $result_refetch_fee->fetch_assoc());
    }
    $stmt_refetch_fee->close();

    $payments = [];
    $stmt_payments_history_refetch = $conn->prepare($paymentsQuery);
    $stmt_payments_history_refetch->bind_param("s", $student_id);
    $stmt_payments_history_refetch->execute();
    $paymentsResult = $stmt_payments_history_refetch->get_result();
    while ($row = $paymentsResult->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt_payments_history_refetch->close();
}

// Handle GET status messages for display
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status === 'payment_success') {
        $page_messages[] = ['type' => 'success', 'text' => 'Payment recorded and fees updated successfully!'];
    } elseif ($status === 'details_success') {
        $page_messages[] = ['type' => 'success', 'text' => 'Fee details updated successfully!'];
    }
}

// Display session-based email status message
if (isset($_SESSION['email_status_message'])) {
    $email_msg_type = (strpos(strtolower($_SESSION['email_status_message']), 'fail') !== false || strpos(strtolower($_SESSION['email_status_message']), 'error') !== false) ? 'error' : 'info';
    $page_messages[] = ['type' => $email_msg_type, 'text' => $_SESSION['email_status_message']];
    unset($_SESSION['email_status_message']);
}

// Close the payments history statement
$stmt_payments_history->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Fees (Student ID: <?= htmlspecialchars($student_id) ?>) | Solid Rock </title>
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --secondary: #0f172a;
            --accent: #38bdf8; --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --info: #3b82f6; /* For info messages like email sent */
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb; --gray-300: #d1d5db;
            --gray-400: #9ca3af; --gray-500: #6b7280; --gray-600: #4b5563;
            --gray-700: #374151; --gray-800: #1f2937; --gray-900: #111827;
            --white: #ffffff; --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            --border-radius: 0.5rem;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--gray-100); color: var(--gray-800); line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column; }
        .header { background-color: var(--white); padding: 1rem 2rem; box-shadow: var(--shadow); position: sticky; top: 0; z-index: 1000; display: flex; align-items: center; justify-content: space-between; }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .logo { height: 40px; width: auto; transition: transform 0.3s ease; }
        .logo:hover { transform: scale(1.05); }
        .header-title { font-size: 1.5rem; font-weight: 600; color: var(--gray-900); margin: 0; }
        .header-actions { display: flex; gap: 1rem; align-items: center; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1.25rem; border-radius: var(--border-radius); font-weight: 500; transition: all 0.3s ease; cursor: pointer; text-decoration: none; border: none; gap: 0.5rem; }
        .btn-primary { background-color: var(--primary); color: var(--white); }
        .btn-primary:hover { background-color: var(--primary-dark); transform: translateY(-2px); box-shadow: var(--shadow); }
        .btn-secondary { background-color: var(--white); color: var(--gray-700); border: 1px solid var(--gray-300); }
        .btn-secondary:hover { background-color: var(--gray-100); transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .btn-danger { background-color: var(--danger); color: var(--white); }
        .btn-danger:hover { background-color: #dc2626; transform: translateY(-2px); box-shadow: var(--shadow); }
        .mobile-menu-btn { display: none; background: none; border: none; color: var(--gray-700); font-size: 1.5rem; cursor: pointer; }
        .main-content { flex: 1; padding: 2rem; display: flex; justify-content: center; align-items: flex-start; }
        .card { background-color: var(--white); border-radius: var(--border-radius); box-shadow: var(--shadow-md); overflow: hidden; width: 100%; max-width: 700px; /* Increased width */ }
        .card-header { background-color: var(--primary); color: var(--white); padding: 1.5rem; text-align: center; }
        .card-title { font-size: 1.75rem; font-weight: 600; margin: 0; }
        .card-body { padding: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--gray-700); }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: var(--border-radius); font-size: 1rem; transition: border-color 0.3s ease, box-shadow 0.3s ease; background-color: var(--gray-50); } /* Changed background */
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .form-control:hover { border-color: var(--gray-400); }
        .form-actions { margin-top: 2rem; }
        .form-submit { width: 100%; padding: 0.75rem; font-size: 1rem; font-weight: 600; }
        .footer { background-color: var(--secondary); color: var(--white); padding: 1.5rem; text-align: center; margin-top: auto; }
        .footer-content { display: flex; justify-content: center; align-items: center; gap: 1rem; max-width: 1200px; margin: 0 auto; }
        .footer-logo { height: 30px; filter: brightness(0) invert(1); }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500; margin-left: 0.5rem; text-transform: capitalize; }
        .status-pending { background-color: var(--warning); color: var(--gray-800); } /* Adjusted color for better readability */
        .status-cleared { background-color: var(--success); color: var(--white); }
        .status-overdue { background-color: var(--danger); color: var(--white); }
        .status-partial { background-color: var(--accent); color: var(--white); }
        .status-n-a, .status-not-set { background-color: var(--gray-300); color: var(--gray-700); }
        .animate-fade-in { animation: fadeIn 0.5s ease forwards; } @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fee-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; } /* Responsive grid */
        .fee-stat { background-color: var(--gray-100); border-radius: var(--border-radius); padding: 1rem; text-align: center; transition: all 0.3s ease; border: 1px solid var(--gray-200); }
        .fee-stat:hover { background-color: var(--gray-200); transform: translateY(-3px); }
        .fee-stat-label { font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.25rem; }
        .fee-stat-value { font-size: 1.25rem; font-weight: 600; color: var(--gray-900); }
        .fee-stat-balance.positive { color: var(--success); } .fee-stat-balance.negative { color: var(--danger); }
        .payment-history-table table { width: 100%; border-collapse: collapse; margin-top: 1rem; box-shadow: var(--shadow-sm); border-radius: var(--border-radius); overflow: hidden; }
        .payment-history-table th, .payment-history-table td { padding: 0.75rem; border: 1px solid var(--gray-200); text-align: left; font-size: 0.9rem; }
        .payment-history-table th { background-color: var(--gray-200); font-weight: 600; color: var(--gray-700); text-transform: uppercase; letter-spacing: 0.05em; }
        .payment-history-table tbody tr:nth-child(even) { background-color: var(--gray-50); }
        .payment-history-table tbody tr:hover { background-color: var(--gray-100); transition: background-color 0.2s ease; }
        .btn-success { background-color: var(--success); color: var(--white); }
        .btn-success:hover { background-color: #059669; transform: translateY(-2px); box-shadow: var(--shadow); }
        .page-message { padding: 1rem; margin-bottom: 1.5rem; border-radius: var(--border-radius); font-weight: 500; border-left-width: 5px; border-left-style: solid; }
        .page-message.success { background-color: #d1fae5; color: #065f46; border-left-color: var(--success); }
        .page-message.error { background-color: #fee2e2; color: #991b1b; border-left-color: var(--danger); }
        .page-message.info { background-color: #dbeafe; color: #1e40af; border-left-color: var(--info); }
        .mobile-nav { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: var(--secondary); z-index: 1010; transform: translateX(-100%); transition: transform 0.3s ease-in-out; display: flex; flex-direction: column; padding: 2rem; }
        .mobile-nav.active { transform: translateX(0); }
        .mobile-nav-close { align-self: flex-end; background: none; border: none; color: var(--white); font-size: 1.5rem; margin-bottom: 2rem; cursor: pointer; }
        .mobile-nav-links { display: flex; flex-direction: column; gap: 1rem; }
        .mobile-nav-link { color: var(--white); text-decoration: none; font-size: 1.25rem; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        @media (max-width: 768px) {
            .header { padding: 1rem; } .header-title { font-size: 1.25rem; }
            .main-content { padding: 1.5rem 1rem; } .card-body { padding: 1.5rem; }
            .mobile-menu-btn { display: block; }
            .header-actions .btn-secondary, .header-actions .btn-danger:not(:first-child) { display: none; } /* Keep logout on mobile if needed */
        }
         @media (max-width: 640px) {
            .header-title { display: none; }
            .fee-summary { grid-template-columns: 1fr; } /* Stack summary items */
         }
    </style>
</head>
<body>
    <div class="mobile-nav" id="mobileNav">
        <button class="mobile-nav-close" id="closeMobileNav"><i class="fas fa-times"></i></button>
        <div class="mobile-nav-links">
            <a href="super_admin_dashboard.php" class="mobile-nav-link"><i class="fas fa-home"></i> Dashboard</a>
            <a href="fees.php" class="mobile-nav-link"><i class="fas fa-money-bill-wave"></i> Fees Overview</a>
            <a href="../logout.php" class="mobile-nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <header class="header">
        <div class="header-left">
            <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
            <img src="../images/logo.jpeg" alt="Solid Rock  Logo" class="logo"> <h1 class="header-title">Student Fee Management</h1>
        </div>
        <div class="header-actions">
            <a href="fees.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Fees Overview</a>
            <a href="super_admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <main class="main-content">
        <div class="card animate-fade-in">
            <div class="card-header">
                <h2 class="card-title">Update Student Fees (ID: <?= htmlspecialchars($student_id) ?>)</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($page_messages)): ?>
                    <?php foreach ($page_messages as $msg): ?>
                        <div class="page-message <?= htmlspecialchars($msg['type']) ?>">
                            <i class="fas <?= $msg['type'] === 'success' ? 'fa-check-circle' : ($msg['type'] === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle') ?>"></i>
                            <?= htmlspecialchars($msg['text']) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="fee-summary">
                    <div class="fee-stat">
                        <div class="fee-stat-label">Total Fee</div>
                        <div class="fee-stat-value" id="summaryTotalFee">$<?= htmlspecialchars(number_format((float)($fee['total_fee'] ?? 0), 2)) ?></div>
                    </div>
                    <div class="fee-stat">
                        <div class="fee-stat-label">Amount Paid</div>
                        <div class="fee-stat-value" id="summaryAmountPaid">$<?= htmlspecialchars(number_format((float)($fee['amount_paid'] ?? 0), 2)) ?></div>
                    </div>
                    <div class="fee-stat">
                        <div class="fee-stat-label">Balance</div>
                        <div class="fee-stat-value fee-stat-balance <?=(($fee['total_fee'] ?? 0) - ($fee['amount_paid'] ?? 0)) <= 0 ? 'positive' : 'negative'?>" id="summaryBalance">
                            $<?= htmlspecialchars(number_format(max(0, (float)($fee['total_fee'] ?? 0) - (float)($fee['amount_paid'] ?? 0)), 2)) ?>
                        </div>
                    </div>
                    <div class="fee-stat">
                        <div class="fee-stat-label">Current Status</div>
                        <div class="fee-stat-value">
                            <span id="summaryStatus" class="status-badge status-<?= strtolower(htmlspecialchars($fee['status'] ?? 'n-a')) ?>">
                                <?= htmlspecialchars($fee['status'] ?? 'N/A') ?>
                            </span>
                        </div>
                    </div>
                    <div class="fee-stat">
                        <div class="fee-stat-label">Due Date</div>
                        <div class="fee-stat-value" id="summaryDueDate">
                            <?= !empty($fee['due_date']) && $fee['due_date'] !== '0000-00-00' ? htmlspecialchars(date('M j, Y', strtotime($fee['due_date']))) : '--/--/----' ?>
                        </div>
                    </div>
                </div>

                <form method="POST" action="update_fees.php?student_id=<?= htmlspecialchars($student_id) ?>" id="feeUpdateForm">
                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
                    <h3 style="margin-bottom: 1rem; margin-top:1.5rem; color: var(--gray-700); font-size: 1.25rem; border-bottom: 1px solid var(--gray-300); padding-bottom: 0.5rem;">Update Fee Structure</h3>
                    <div class="form-group">
                        <label for="total_fee" class="form-label"><i class="fas fa-file-invoice-dollar"></i> Total Fee:</label>
                        <input type="number" step="0.01" min="0" name="total_fee" id="total_fee" class="form-control" value="<?= htmlspecialchars(number_format((float)($fee['total_fee'] ?? 0), 2, '.', '')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="amount_paid" class="form-label"><i class="fas fa-coins"></i> Amount Paid (Overall):</label>
                        <input type="number" step="0.01" min="0" name="amount_paid" id="amount_paid" class="form-control" value="<?= htmlspecialchars(number_format((float)($fee['amount_paid'] ?? 0), 2, '.', '')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="due_date" class="form-label"><i class="fas fa-calendar-alt"></i> Due Date:</label>
                        <input type="date" name="due_date" id="due_date" class="form-control" value="<?= htmlspecialchars($fee['due_date'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="payment_plan" class="form-label"><i class="fas fa-tasks"></i> Payment Plan:</label>
                        <select name="payment_plan" id="payment_plan" class="form-control" required>
                            <option value="One-Time" <?= ($fee['payment_plan'] ?? '') == 'One-Time' ? 'selected' : '' ?>>One-Time Payment</option>
                            <option value="Monthly" <?= ($fee['payment_plan'] ?? '') == 'Monthly' ? 'selected' : '' ?>>Monthly Installments</option>
                            <option value="Quarterly" <?= ($fee['payment_plan'] ?? '') == 'Quarterly' ? 'selected' : '' ?>>Quarterly Installments</option>
                            <option value="Term" <?= ($fee['payment_plan'] ?? '') == 'Term' ? 'selected' : '' ?>>Per Term</option>
                            <option value="Custom" <?= ($fee['payment_plan'] ?? '') == 'Custom' ? 'selected' : '' ?>>Custom</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary form-submit"><i class="fas fa-save"></i> Update Fee Details</button>
                    </div>
                </form>

                <hr style="margin: 2.5rem 0; border-color: var(--gray-300); border-style: dashed;">

                <h3 style="margin-bottom: 1rem; color: var(--gray-700); font-size: 1.25rem; border-bottom: 1px solid var(--gray-300); padding-bottom: 0.5rem;">Record New Payment</h3>
                <form method="POST" action="update_fees.php?student_id=<?= htmlspecialchars($student_id) ?>" id="recordPaymentForm">
                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
                    <input type="hidden" name="is_payment" value="true">
                    <div class="form-group">
                        <label for="new_payment_amount" class="form-label"><i class="fas fa-dollar-sign"></i> Payment Amount:</label>
                        <input type="number" step="0.01" name="new_payment_amount" id="new_payment_amount" class="form-control" required min="0.01">
                    </div>
                    <div class="form-group">
                        <label for="payment_method" class="form-label"><i class="fas fa-credit-card"></i> Payment Method:</label>
                        <select name="payment_method" id="payment_method" class="form-control" required>
                            <option value="">Select Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Card Payment">Card Payment</option>
                            <option value="Online Payment">Online Payment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success form-submit"><i class="fas fa-plus-circle"></i> Record Payment</button>
                    </div>
                </form>

                <hr style="margin: 2.5rem 0; border-color: var(--gray-300); border-style: dashed;">

                <h3 style="margin-bottom: 1rem; color: var(--gray-700); font-size: 1.25rem; border-bottom: 1px solid var(--gray-300); padding-bottom: 0.5rem;">Payment History</h3>
                <?php if (!empty($payments)): ?>
                    <div class="payment-history-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th><th>Amount</th><th>Method</th><th>Recorded By</th><th>Recorded At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment_item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('M j, Y', strtotime($payment_item['payment_date']))) ?></td>
                                        <td>$<?= htmlspecialchars(number_format((float)$payment_item['amount'], 2)) ?></td>
                                        <td><?= htmlspecialchars($payment_item['payment_method']) ?></td>
                                        <td><?= htmlspecialchars($payment_item['received_by']) ?></td>
                                        <td><?= !empty($payment_item['created_at']) ? htmlspecialchars(date('M j, Y H:i', strtotime($payment_item['created_at']))) : 'N/A' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--gray-500); padding: 1rem; background-color: var(--gray-50); border-radius: var(--border-radius);">No payment history found for this student.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <img src="../images/logo.jpg" alt="Solid Rock  Logo" class="footer-logo"> <p>&copy; <?php echo date("Y"); ?> Mirilax-Scales Portal. All rights reserved.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMobileNavBtn = document.getElementById('closeMobileNav');
        const mobileNav = document.getElementById('mobileNav');

        if (mobileMenuBtn && mobileNav) {
            mobileMenuBtn.addEventListener('click', function() { mobileNav.classList.add('active'); });
        }
        if (closeMobileNavBtn && mobileNav) {
            closeMobileNavBtn.addEventListener('click', function() { mobileNav.classList.remove('active'); });
        }

        const totalFeeInput = document.getElementById('total_fee');
        const amountPaidInput = document.getElementById('amount_paid');
        const dueDateInput = document.getElementById('due_date');

        function formatCurrency(value) {
            return parseFloat(value).toFixed(2);
        }
        
        function updateFeeSummaryDisplay() {
            const totalFee = parseFloat(totalFeeInput.value) || 0;
            const amountPaid = parseFloat(amountPaidInput.value) || 0;
            const dueDateVal = dueDateInput.value;
            const balance = Math.max(0, totalFee - amountPaid);

            document.getElementById('summaryTotalFee').textContent = ' + formatCurrency(totalFee);
            document.getElementById('summaryAmountPaid').textContent = ' + formatCurrency(amountPaid);
            
            const summaryBalanceEl = document.getElementById('summaryBalance');
            summaryBalanceEl.textContent = ' + formatCurrency(balance);
            summaryBalanceEl.className = 'fee-stat-value fee-stat-balance ' + (balance <= 0 && totalFee > 0 ? 'positive' : (totalFee > 0 ? 'negative' : ''));


            if (dueDateVal && dueDateVal !== '0000-00-00') {
                try {
                    const dateObj = new Date(dueDateVal + 'T00:00:00Z'); // Treat as UTC to avoid timezone issues with just date
                    document.getElementById('summaryDueDate').textContent = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' });
                } catch (e) { document.getElementById('summaryDueDate').textContent = '--/--/----'; }
            } else {
                document.getElementById('summaryDueDate').textContent = '--/--/----';
            }

            let currentStatusText = 'Pending';
            let currentStatusClass = 'pending';

            if (totalFee > 0) {
                if (amountPaid >= totalFee) { currentStatusText = 'Cleared'; currentStatusClass = 'cleared'; }
                else if (amountPaid > 0) { currentStatusText = 'Partial'; currentStatusClass = 'partial'; }
            } else if (totalFee === 0 && amountPaid === 0) {
                 currentStatusText = 'N/A'; currentStatusClass = 'n-a';
            }
            
            const today = new Date(); today.setHours(0,0,0,0);
            const dueDateObj = dueDateVal ? new Date(dueDateVal + 'T00:00:00Z') : null;

            if (dueDateObj && dueDateObj < today && balance > 0 && currentStatusText !== 'Cleared') {
                currentStatusText = 'Overdue'; currentStatusClass = 'overdue';
            }

            const summaryStatusElement = document.getElementById('summaryStatus');
            summaryStatusElement.textContent = currentStatusText;
            summaryStatusElement.className = `status-badge status-${currentStatusClass}`;
        }

        if (totalFeeInput && amountPaidInput && dueDateInput) {
            updateFeeSummaryDisplay(); // Initial update
            totalFeeInput.addEventListener('input', updateFeeSummaryDisplay);
            amountPaidInput.addEventListener('input', updateFeeSummaryDisplay);
            dueDateInput.addEventListener('change', updateFeeSummaryDisplay);
        }

        const feeUpdateForm = document.getElementById('feeUpdateForm');
        if (feeUpdateForm) {
            feeUpdateForm.addEventListener('submit', function(e) {
                const totalFee = parseFloat(document.getElementById('total_fee').value) || 0;
                const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
                if (amountPaid > totalFee) {
                    e.preventDefault();
                    alert('Amount paid cannot be greater than total fee.');
                }
                if (totalFee < 0 || amountPaid < 0) {
                    e.preventDefault();
                    alert('Fee amounts cannot be negative.');
                }
            });
        }

        const recordPaymentForm = document.getElementById('recordPaymentForm');
        if (recordPaymentForm) {
            recordPaymentForm.addEventListener('submit', function(e) {
                const paymentAmount = parseFloat(document.getElementById('new_payment_amount').value) || 0;
                if (paymentAmount <= 0) {
                    e.preventDefault();
                    alert('Payment amount must be greater than zero.');
                    return;
                }
                const currentTotalFee = parseFloat(document.getElementById('total_fee').value) || 0;
                const currentAmountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
                const currentBalance = Math.max(0, currentTotalFee - currentAmountPaid);

                if (paymentAmount > currentBalance && currentBalance > 0) {
                    if (!confirm('The payment amount ( + formatCurrency(paymentAmount) + ') exceeds the current balance ( + formatCurrency(currentBalance) + '). This will result in an overpayment or a credit. Do you want to proceed?')) {
                        e.preventDefault();
                    }
                }
            });
        }
    });
    </script>
</body>
</html>

<?php
if (isset($conn)) $conn->close();
?>