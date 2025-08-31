<?php
// session_start() MUST be the very first thing in your file.
session_start();

// Define variables for THIS page before including the header.
$pageTitle = "Fees Management";
$currentPage = "fees";

// Include the database configuration.
include '../config.php';
include './notifications.php';

// PHPMailer Autoload
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

<<<<<<< HEAD
// Helper function to safely handle null values with htmlspecialchars
function safeHtmlspecialchars($value, $default = '') {
    if ($value === null || $value === '') {
        return htmlspecialchars($default);
    }
    return htmlspecialchars((string)$value);
}

// Include all your existing functions from update_fees.php
=======
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if students exist
$debug_students = $conn->query("SELECT COUNT(*) as student_count FROM students");
$student_count = $debug_students ? $debug_students->fetch_assoc()['student_count'] : 0;

// Enhanced ensureIndividualFeeRecord function with better error handling
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
function ensureIndividualFeeRecord($student_id, $conn) {
    error_log("🔍 ensureIndividualFeeRecord: Starting for student $student_id");
    
    // Check if student has their own fee record
    $checkQuery = "SELECT fee_id FROM fees WHERE student_id = ?";
    $stmt = $conn->prepare($checkQuery);
    if ($stmt === false) {
        error_log("❌ ensureIndividualFeeRecord: Failed to prepare check query: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("s", $student_id);
    if (!$stmt->execute()) {
        error_log("❌ ensureIndividualFeeRecord: Failed to execute check query: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        error_log("🔍 ensureIndividualFeeRecord: No fee record found, creating one for student $student_id");
        
        // Create individual fee record for this student
        $insertQuery = "INSERT INTO fees (student_id, total_fee, amount_paid, due_date, payment_plan, status) 
                       VALUES (?, 1000.00, 0.00, DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'One-Time', 'Pending')";
        $insertStmt = $conn->prepare($insertQuery);
        if ($insertStmt === false) {
            error_log("❌ ensureIndividualFeeRecord: Failed to prepare insert query: " . $conn->error);
            $stmt->close();
            return false;
        }
        
        $insertStmt->bind_param("s", $student_id);
        if (!$insertStmt->execute()) {
            error_log("❌ ensureIndividualFeeRecord: Failed to create fee record for student {$student_id}: " . $insertStmt->error);
            $insertStmt->close();
            $stmt->close();
            return false;
        }
        
        $new_fee_id = $conn->insert_id;
        error_log("✅ ensureIndividualFeeRecord: Created fee record for student {$student_id} with fee_id: {$new_fee_id}");
        $insertStmt->close();
    } else {
        $existing_fee = $result->fetch_assoc();
        error_log("✅ ensureIndividualFeeRecord: Fee record already exists for student {$student_id}: fee_id {$existing_fee['fee_id']}");
    }
    
    $stmt->close();
    return true;
}

function safeTruncateString($string, $maxLength = 20) {
    $string = trim($string);
    if (strlen($string) > $maxLength) {
        return substr($string, 0, $maxLength);
    }
    return $string;
}

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
    string $senderName = 'Solid Rock Group of Schools Accounts'
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

    $prompt = "You are an AI assistant for Solid Rock Group of schools. Your task is to draft ONLY the BODY of a professional and friendly payment confirmation email.\n\n";
    $prompt .= "The email is addressed to {$promptParentNameForLLM}.\n";
    $prompt .= "The subject line for this email will be: \"{$emailSubject}\". Do NOT include the subject line itself in the email body you generate.\n\n";

    $prompt .= "The email body must cover the following key points, ensuring all monetary values are clear:\n";
    foreach ($keyPoints as $point) {
        $prompt .= "- " . $point . "\n";
    }
    $prompt .= "\nMaintain a courteous and professional tone.\n";
    $prompt .= "Start the email body *directly* with the greeting (e.g., 'Dear {$promptParentNameForLLM},').\n";
    $prompt .= "End the email body with a professional closing from 'Solid Rock Group of schools Accounts Department'.\n";
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

// --- HANDLE AJAX REQUESTS FOR MODAL ---
if (isset($_POST['ajax_action'])) {
    $action = $_POST['ajax_action'];
    
    // Enhanced get_student_data handler with comprehensive debugging
    if ($action === 'get_student_data' && isset($_POST['student_id'])) {
        $student_id = $_POST['student_id'];
        
        // Enhanced debugging
        error_log("🔍 DEBUG: get_student_data called for student_id: $student_id");
        
        // 1. First check if student exists
        $student_check_query = "SELECT student_id, first_name, last_name, username, class FROM students WHERE student_id = ?";
        $stmt_check = $conn->prepare($student_check_query);
        
        if (!$stmt_check) {
            error_log("❌ ERROR: Failed to prepare student check query: " . $conn->error);
            echo json_encode([
                'success' => false, 
                'message' => 'Database preparation error: ' . $conn->error
            ]);
            exit();
        }
        
        $stmt_check->bind_param("s", $student_id);
        $stmt_check->execute();
        $student_check_result = $stmt_check->get_result();
        
        if ($student_check_result->num_rows === 0) {
            error_log("❌ ERROR: Student not found: $student_id");
            echo json_encode([
                'success' => false, 
                'message' => "Student with ID $student_id not found in database"
            ]);
            $stmt_check->close();
            exit();
        }
        
        $basic_student_data = $student_check_result->fetch_assoc();
        error_log("✅ SUCCESS: Student found: " . print_r($basic_student_data, true));
        $stmt_check->close();
        
        // 2. Try to ensure fee record exists
        error_log("🔍 DEBUG: Calling ensureIndividualFeeRecord for student: $student_id");
        $fee_record_result = ensureIndividualFeeRecord($student_id, $conn);
        
        if (!$fee_record_result) {
            error_log("❌ ERROR: Failed to ensure fee record for student: $student_id");
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to create/verify fee record for student'
            ]);
            exit();
        }
        
        error_log("✅ SUCCESS: Fee record ensured for student: $student_id");
        
        // 3. Get student data with fee information
        $query = "SELECT s.student_id, 
                         s.first_name,
                         s.last_name,
                         s.username,
                         s.class,
                         COALESCE(f.total_fee, 0.00) AS total_fee,
                         COALESCE(f.amount_paid, 0.00) AS amount_paid,
                         COALESCE(f.status, 'Pending') AS status,
                         f.due_date,
                         f.payment_plan
                  FROM students s
                  LEFT JOIN fees f ON s.student_id = f.student_id
                  WHERE s.student_id = ?";
        
        error_log("🔍 DEBUG: Executing main query for student: $student_id");
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("❌ ERROR: Failed to prepare main query: " . $conn->error);
            echo json_encode([
                'success' => false, 
                'message' => 'Database query preparation error: ' . $conn->error
            ]);
            exit();
        }
        
        $stmt->bind_param("s", $student_id);
        if (!$stmt->execute()) {
            error_log("❌ ERROR: Failed to execute main query: " . $stmt->error);
            echo json_encode([
                'success' => false, 
                'message' => 'Database query execution error: ' . $stmt->error
            ]);
            $stmt->close();
            exit();
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("❌ ERROR: No data returned from main query for student: $student_id");
            echo json_encode([
                'success' => false, 
                'message' => 'No student data found after fee record creation'
            ]);
            $stmt->close();
            exit();
        }
        
        $student_data = $result->fetch_assoc();
        error_log("✅ SUCCESS: Main query data: " . print_r($student_data, true));
        $stmt->close();
        
        // Format the student name
        if ($student_data) {
            $student_data['student_name'] = getFormattedStudentName(
                $student_data['first_name'], 
                $student_data['last_name'], 
                $student_data['username'], 
                $student_data['student_id']
            );
        } else {
            error_log("❌ ERROR: student_data is null");
            echo json_encode([
                'success' => false, 
                'message' => 'Student data is null after query'
            ]);
            exit();
        }
        
        // 4. Get payment history
        error_log("🔍 DEBUG: Getting payment history for student: $student_id");
        
        $paymentsQuery = "SELECT payment_date, amount, payment_method, received_by, created_at
                          FROM payments 
                          WHERE student_id = ? 
                          ORDER BY payment_date DESC";
        $stmt_payments = $conn->prepare($paymentsQuery);
        
        if (!$stmt_payments) {
            error_log("❌ WARNING: Failed to prepare payments query: " . $conn->error);
            $payments = []; // Continue without payment history
        } else {
            $stmt_payments->bind_param("s", $student_id);
            $stmt_payments->execute();
            $paymentsResult = $stmt_payments->get_result();
            $payments = $paymentsResult->fetch_all(MYSQLI_ASSOC);
            $stmt_payments->close();
            error_log("✅ SUCCESS: Found " . count($payments) . " payment records");
        }
        
        // 5. Return successful response
        $response_data = [
            'success' => true,
            'student_data' => $student_data,
            'payments' => $payments
        ];
        
        error_log("✅ SUCCESS: Sending response for student $student_id");
        
        header('Content-Type: application/json');
        echo json_encode($response_data);
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

// --- YOUR ORIGINAL FEES.PHP LOGIC CONTINUES HERE ---

// --- REMOVED THE PROBLEMATIC GLOBAL STATUS UPDATE ---
// The following global update was causing all student fees to update when one student was modified:
// This has been removed to fix the issue where updating one student affected all students

// --- Message Handling ---
$fees_overview_message = '';
$fees_overview_message_type = 'info';
if (isset($_SESSION['message'])) {
    if (is_array($_SESSION['message']) && isset($_SESSION['message']['text'])) {
        $fees_overview_message = $_SESSION['message']['text'];
        $fees_overview_message_type = $_SESSION['message']['type'] ?? 'info';
    } else {
        $fees_overview_message = (string) $_SESSION['message'];
    }
    unset($_SESSION['message']);
}

// --- HANDLE SORTING AND FILTERING PARAMETERS ---
$sortable_columns = [
    'student_name' => 'student_name_computed',
    'class' => 's.class', 
    'total_fee' => 'COALESCE(f.total_fee, 0)',
    'amount_due' => '(COALESCE(f.total_fee, 0) - COALESCE(f.amount_paid, 0))',
    'due_date' => 'f.due_date'
];

$sort_col_param = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortable_columns) ? $_GET['sort'] : 'class';
$sort_col_sql = $sortable_columns[$sort_col_param];
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) == 'desc' ? 'DESC' : 'ASC';

// FIXED: Properly handle filter parameters
$filter_class = isset($_GET['filter_class']) && !empty(trim($_GET['filter_class'])) ? trim($_GET['filter_class']) : '';
$filter_status = isset($_GET['filter_status']) && !empty(trim($_GET['filter_status'])) ? trim($_GET['filter_status']) : '';

// --- DATA FETCHING AND PAGINATION ---
$items_per_page = 15;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);

// FIXED: Build WHERE clause more carefully
$where_clauses = [];
$bind_params = [];
$bind_types = '';

if (!empty($filter_class)) {
    $where_clauses[] = "s.class = ?";
    $bind_params[] = $filter_class;
    $bind_types .= 's';
}

if (!empty($filter_status)) {
    if ($filter_status === 'No Fee Assigned') {
        $where_clauses[] = "(f.fee_id IS NULL OR f.status IS NULL OR f.status = 'No Fee Assigned')";
    } else {
        $where_clauses[] = "f.status = ?";
        $bind_params[] = $filter_status;
        $bind_types .= 's';
    }
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// FIXED: Count query to count students, not fee records
$count_query_sql = "SELECT COUNT(DISTINCT s.student_id) AS total_items 
                    FROM students s 
                    LEFT JOIN fees f ON s.student_id = f.student_id" . $where_sql;

$stmt_count = $conn->prepare($count_query_sql);
if ($stmt_count && !empty($bind_types)) {
    $stmt_count->bind_param($bind_types, ...$bind_params);
}
if ($stmt_count) {
    $stmt_count->execute();
    $total_items_result = $stmt_count->get_result();
    $total_items = $total_items_result ? (int)$total_items_result->fetch_assoc()['total_items'] : 0;
    $stmt_count->close();
} else {
    $total_items = 0;
    error_log("Failed to prepare count query: " . $conn->error);
}

$total_pages = ($total_items > 0) ? ceil($total_items / $items_per_page) : 1;
$current_page = min($current_page, $total_pages);
$offset = ($current_page - 1) * $items_per_page;

// UPDATED MAIN QUERY - Better handling of student names
$query_fees_overview = "
    SELECT
        s.student_id, 
        s.first_name,
        s.last_name,
        s.username,
        CASE 
            WHEN TRIM(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, ''))) != '' 
            THEN TRIM(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')))
            WHEN s.username IS NOT NULL AND s.username != '' 
            THEN s.username
            ELSE CONCAT('Student ', s.student_id)
        END AS student_name_computed,
        COALESCE(s.class, 'N/A') as class,
        (SELECT GROUP_CONCAT(CONCAT(u.username, ' (', p.relationship, ')') ORDER BY p.parent_id SEPARATOR ', ') 
         FROM parents p 
         INNER JOIN users u ON p.user_id = u.id 
         WHERE p.student_id = s.student_id) AS parent_name,
        COALESCE(f.total_fee, 0.00) AS total_fee,
        COALESCE(f.amount_paid, 0.00) AS amount_paid,
        (COALESCE(f.total_fee, 0) - COALESCE(f.amount_paid, 0)) as amount_due,
        COALESCE(f.status, 'No Fee Assigned') AS status,
        f.due_date
    FROM students s
    LEFT JOIN fees f ON s.student_id = f.student_id
    {$where_sql}
    ORDER BY {$sort_col_sql} {$sort_order}, s.student_id ASC
    LIMIT ? OFFSET ?";

$final_bind_params = array_merge($bind_params, [$items_per_page, $offset]);
$final_bind_types = $bind_types . 'ii';

$stmt = $conn->prepare($query_fees_overview);
$fees_overview_result = null;

if ($stmt) {
    if (!empty($final_bind_types)) {
        $stmt->bind_param($final_bind_types, ...$final_bind_params);
    }
    if ($stmt->execute()) {
        $fees_overview_result = $stmt->get_result();
    } else {
        $fees_overview_message = "Error executing query: " . htmlspecialchars($stmt->error);
        $fees_overview_message_type = 'error';
        error_log("Query execution failed: " . $stmt->error);
    }
    $stmt->close();
} else {
    $fees_overview_message = "Error preparing data query: " . htmlspecialchars($conn->error);
    $fees_overview_message_type = 'error';
    $fees_overview_result = null;
    error_log("Failed to prepare main query: " . $conn->error);
}

// Get classes for filter dropdown - FIXED to handle empty results
$classes_result = $conn->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
$classes = [];
if ($classes_result) {
    $classes = $classes_result->fetch_all(MYSQLI_ASSOC);
}

$statuses = ['Cleared', 'Overdue', 'Pending', 'No Fee Assigned'];

// Include header AFTER all logic, before any HTML output.
include 'sa_header.php';
?>

<!-- Add the modal HTML and JavaScript -->
<div id="studentModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Update Student Fee Details</h2>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modalStudentInfo">
                <!-- Student info will be loaded here -->
            </div>
            
            <div class="fee-summary" id="feeSummary">
                <!-- Fee summary will be loaded here -->
            </div>
            
            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-edit"></i>
                    Update Fee Structure
                </h3>
                <form id="updateFeeForm">
                    <input type="hidden" id="modalStudentId" name="student_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="totalFee">Total Fee ($):</label>
                            <input type="number" id="totalFee" name="total_fee" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="amountPaid">Amount Paid ($):</label>
                            <input type="number" id="amountPaid" name="amount_paid" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="dueDate">Due Date:</label>
                            <input type="date" id="dueDate" name="due_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="paymentPlan">Payment Plan:</label>
                            <select id="paymentPlan" name="payment_plan" class="form-control" required>
                                <option value="One-Time">One-Time</option>
                                <option value="Monthly">Monthly</option>
                                <option value="Quarterly">Quarterly</option>
                                <option value="Semi-Annual">Semi-Annual</option>
                                <option value="Annual">Annual</option>
                                <option value="Weekly">Weekly</option>
                                <option value="Bi-Weekly">Bi-Weekly</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Fee Structure
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-credit-card"></i>
                    Record New Payment
                </h3>
                <form id="recordPaymentForm">
                    <input type="hidden" id="paymentStudentId" name="student_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="paymentAmount">Payment Amount ($):</label>
                            <input type="number" id="paymentAmount" name="new_payment_amount" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="paymentMethod">Payment Method:</label>
                            <select id="paymentMethod" name="payment_method" class="form-control" required>
                                <option value="">Select Payment Method</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Mobile Money">Mobile Money</option>
                                <option value="Check">Check</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Record Payment
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="payment-history">
                <h3 class="form-section-title">
                    <i class="fas fa-history"></i>
                    Payment History
                </h3>
                <div id="paymentHistoryTable">
                    <!-- Payment history will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* =================================================================== */
/* === A) GLOBAL & LAYOUT STYLING ==================================== */
/* =================================================================== */
body {
    background-color: #f4f7fa;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.card {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(0, 70, 150, 0.08);
    border: none;
    overflow: hidden;
}
.content-header .title {
    font-weight: 700;
    color: #2c3e50;
}

/* =================================================================== */
/* === B) TABLE STYLING ============================================== */
/* =================================================================== */
.fees-table-container {
    width: 100%;
    overflow-x: auto;
}
.fees-table {
    width: 100%;
    border-collapse: collapse;
}
.fees-table th, .fees-table td {
    padding: 16px 20px;
    text-align: left;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
}
.fees-table thead th {
    background-color: #34495e;
    color: #ffffff;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}
.fees-table thead th a {
    color: #ffffff;
    text-decoration: none;
    transition: color 0.2s ease;
}
.fees-table thead th a:hover {
    color: #4fc3f7;
}
.fees-table tbody tr {
    transition: background-color 0.25s ease;
}
.fees-table tbody tr:last-of-type td {
    border-bottom: none;
}
.fees-table tbody tr:hover {
    background-color: #f8faff;
}
.amount-due {
    color: #e74c3c;
    font-weight: 600;
    font-size: 1.05em;
}
.date-overdue {
    color: #c0392b;
    font-weight: 600;
}

/* =================================================================== */
/* === C) EYE-CATCHING STATUS PILLS ================================== */
/* =================================================================== */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
    text-transform: capitalize;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.status-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}
.status-pill .fa, .status-pill .fas {
    font-size: 13px;
}
.status-cleared { background-image: linear-gradient(45deg, #2ed573, #219d55); }
.status-overdue { background-image: linear-gradient(45deg, #ff4757, #c0392b); }
.status-pending { background-image: linear-gradient(45deg, #ffc107, #ff8c00); }
.status-no-fee-assigned { background-image: linear-gradient(45deg, #747d8c, #57606f); }

/* =================================================================== */
/* === D) CONTROLS & PAGINATION ====================================== */
/* =================================================================== */
.table-controls, .bulk-actions-container {
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #eef2f7;
}
.bulk-actions-container {
    border-top: 1px solid #eef2f7;
    padding-top: 20px;
}
.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
}
.filter-form select, .bulk-actions-container select {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #dcdfe6;
    background-color: #fff;
    font-size: 14px;
}
.filter-form select:focus, .bulk-actions-container select:focus {
    outline: none;
    border-color: #409eff;
    box-shadow: 0 0 0 2px rgba(64, 158, 255, 0.2);
}
.btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-primary { 
    background-color: #409eff; 
    color: white; 
    box-shadow: 0 4px 10px rgba(64, 158, 255, 0.2);
}
.btn-primary:hover {
    background-color: #3a8ee6;
    box-shadow: 0 6px 15px rgba(64, 158, 255, 0.3);
}
.btn-secondary {
    background-color: #f0f2f5;
    color: #57606f;
    border: 1px solid #dcdfe6;
}
.btn-secondary:hover {
    background-color: #e4e7ed;
    border-color: #c8cdd8;
}
.btn-warning {
    background-color: #f59e0b;
    color: white;
    box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2);
}
.btn-warning:hover {
    background-color: #d97706;
    box-shadow: 0 6px 15px rgba(245, 158, 11, 0.3);
}
.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 500;
}
.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
}
.pagination {
    padding: 20px;
    text-align: right;
}
.pagination a, .pagination span {
    margin: 0 5px;
    padding: 8px 15px;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
}
.pagination a {
    color: #57606f;
    background-color: #fff;
    border: 1px solid #dcdfe6;
}
.pagination a:hover {
    background-color: #409eff;
    color: #fff;
    border-color: #409eff;
}
.pagination span.disabled {
    color: #c0c4cc;
}
.pagination span.current-page {
    background-image: linear-gradient(45deg, #409eff, #3a8ee6);
    color: white;
    border: none;
    box-shadow: 0 4px 10px rgba(64, 158, 255, 0.3);
}

/* =================================================================== */
/* === E) NOTIFICATION STYLING ======================================= */
/* =================================================================== */
.notification {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 500;
}
.notification.info {
    background-color: #e8f4fd;
    color: #1890ff;
    border-left: 4px solid #1890ff;
}
.notification.success {
    background-color: #f6ffed;
    color: #52c41a;
    border-left: 4px solid #52c41a;
}
.notification.error {
    background-color: #fff2f0;
    color: #ff4d4f;
    border-left: 4px solid #ff4d4f;
}
.notification.warning {
    background-color: #fffbe6;
    color: #faad14;
    border-left: 4px solid #faad14;
}

/* =================================================================== */
/* === F) MODAL STYLING ============================================== */
/* =================================================================== */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform: scale(0.7);
    opacity: 0;
    transition: all 0.3s ease;
}

.modal-overlay.active .modal-content {
    transform: scale(1);
    opacity: 1;
}

.modal-header {
    background: linear-gradient(135deg, #409eff, #3a8ee6);
    color: white;
    padding: 20px 30px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: background-color 0.2s ease;
}

.modal-close:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.modal-body {
    padding: 30px;
}

/* Fee Summary in Modal */
.fee-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.fee-stat {
    text-align: center;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.fee-stat-label {
    font-size: 0.8rem;
    color: #6b7280;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.fee-stat-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
}

.fee-stat-balance.positive { color: #10b981; }
.fee-stat-balance.negative { color: #ef4444; }

/* Form Styling in Modal */
.form-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid #409eff;
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group {
    margin-bottom: 15px;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #374151;
    font-size: 0.9rem;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #409eff;
    box-shadow: 0 0 0 3px rgba(64, 158, 255, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

/* Payment History */
.payment-history {
    margin-top: 25px;
}

.payment-history-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    font-size: 0.85rem;
}

.payment-history-table th,
.payment-history-table td {
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    text-align: left;
}

.payment-history-table th {
    background-color: #f3f4f6;
    font-weight: 600;
    color: #374151;
}

.payment-history-table tbody tr:nth-child(even) {
    background-color: #f9fafb;
}

/* Action buttons in modal */
.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.btn-success {
    background-color: #10b981;
    color: white;
}

.btn-success:hover {
    background-color: #059669;
}

/* Loading state */
.loading {
    opacity: 0.7;
    pointer-events: none;
}

.spinner {
    border: 2px solid #f3f3f3;
    border-top: 2px solid #409eff;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    animation: spin 1s linear infinite;
    display: inline-block;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 10px;
    }

    .modal-body {
        padding: 20px;
    }

    .fee-summary {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="content-header">
    <h1 class="title"><?= safeHtmlspecialchars($pageTitle); ?></h1>
</div>

<div class="card">
    <?php if (!empty($fees_overview_message)): ?>
        <div class="notification <?= safeHtmlspecialchars($fees_overview_message_type); ?>">
            <?= safeHtmlspecialchars($fees_overview_message); ?>
        </div>
    <?php endif; ?>

    <div class="table-controls">
        <form action="" method="GET" class="filter-form">
            <div class="filter-group">
                <label for="filter_class">Filter by Class:</label>
                <select name="filter_class" id="filter_class" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= safeHtmlspecialchars($class['class']); ?>" <?= ($filter_class == $class['class']) ? 'selected' : ''; ?>>
                            <?= safeHtmlspecialchars($class['class']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter_status">Filter by Status:</label>
                <select name="filter_status" id="filter_status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                     <?php foreach ($statuses as $status): ?>
                        <option value="<?= safeHtmlspecialchars($status); ?>" <?= ($filter_status == $status) ? 'selected' : ''; ?>>
                            <?= safeHtmlspecialchars($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <a href="fees.php" class="btn btn-secondary">Reset Filters</a>
        </form>
    </div>

    <form action="process_bulk_fees.php" method="POST">
        <div class="fees-table-container">
            <table class="fees-table sortable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-checkbox"></th>
                        <?php
                        function sortableHeader($title, $column, $current_sort, $current_order) {
                            $order = ($current_sort == $column && $current_order == 'ASC') ? 'desc' : 'asc';
                            $arrow = $current_sort == $column ? ($current_order == 'ASC' ? ' &uarr;' : ' &darr;') : '';
                            $url_params = http_build_query(array_merge($_GET, ['sort' => $column, 'order' => $order, 'page' => 1]));
                            return "<th><a href=\"?{$url_params}\">{$title}{$arrow}</a></th>";
                        }
                        echo sortableHeader('Student Name', 'student_name', $sort_col_param, $sort_order);
                        echo sortableHeader('Class', 'class', $sort_col_param, $sort_order);
                        ?>
                        <th>Parent Name</th>
                        <?php
                        echo sortableHeader('Total Fee', 'total_fee', $sort_col_param, $sort_order);
                        ?>
                        <th>Amount Paid</th>
                        <?php
                        echo sortableHeader('Amount Due', 'amount_due', $sort_col_param, $sort_order);
                        ?>
                        <th>Status</th>
                        <?php
                        echo sortableHeader('Due Date', 'due_date', $sort_col_param, $sort_order);
                        ?>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($fees_overview_result && $fees_overview_result->num_rows > 0): ?>
                        <?php while ($row = $fees_overview_result->fetch_assoc()): ?>
                            <?php
                                $amount_due = floatval($row['amount_due']);
                                $status_class = strtolower(str_replace(' ', '-', $row['status']));
                                $due_date_class = ($row['status'] === 'Overdue') ? 'date-overdue' : '';
                                $status_icons = [
                                    'Cleared' => 'fas fa-check-circle',
                                    'Overdue' => 'fas fa-exclamation-triangle',
                                    'Pending' => 'fas fa-hourglass-half',
                                    'No Fee Assigned' => 'fas fa-minus-circle'
                                ];
                                $icon_class = $status_icons[$row['status']] ?? 'fas fa-question-circle';
                                
                                // Use the computed student name from the query
                                $student_display_name = $row['student_name_computed'];
                            ?>
<<<<<<< HEAD
                            <tr data-student-id="<?= safeHtmlspecialchars($row['student_id']); ?>">
                                <td><input type="checkbox" name="selected_ids[]" value="<?= safeHtmlspecialchars($row['student_id']); ?>" class="row-checkbox"></td>
                                <td><?= safeHtmlspecialchars(trim($row['student_name'])); ?></td>
                                <td><?= safeHtmlspecialchars($row['class']); ?></td>
                                <td><?= safeHtmlspecialchars($row['parent_name'], 'N/A'); ?></td>
=======
                            <tr data-student-id="<?= htmlspecialchars($row['student_id']); ?>">
                                <td><input type="checkbox" name="selected_ids[]" value="<?= htmlspecialchars($row['student_id']); ?>" class="row-checkbox"></td>
                                <td><?= htmlspecialchars($student_display_name); ?></td>
                                <td><?= htmlspecialchars($row['class']); ?></td>
                                <td><?= htmlspecialchars($row['parent_name'] ?? 'N/A'); ?></td>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                <td class="total-fee-cell">$<?= htmlspecialchars(number_format(floatval($row['total_fee']), 2)); ?></td>
                                <td class="amount-paid-cell">$<?= htmlspecialchars(number_format(floatval($row['amount_paid']), 2)); ?></td>
                                <td class="amount-due-cell <?= $amount_due > 0 ? 'amount-due' : ''; ?>">$<?= htmlspecialchars(number_format($amount_due, 2)); ?></td>
                                <td class="status-cell">
                                    <span class="status-pill status-<?= safeHtmlspecialchars($status_class); ?>">
                                        <i class="<?= $icon_class; ?>"></i>
                                        <span><?= safeHtmlspecialchars($row['status']); ?></span>
                                    </span>
                                </td>
                                <td class="due-date-cell <?= $due_date_class; ?>">
                                    <?= (!empty($row['due_date']) && $row['due_date'] !== '0000-00-00') ? htmlspecialchars(date('M j, Y', strtotime($row['due_date']))) : 'N/A'; ?>
                                </td>
                                
                                <td class="action-cell">
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-primary btn-sm update-fee-btn" 
<<<<<<< HEAD
                                                data-student-id="<?= safeHtmlspecialchars($row['student_id']); ?>" 
                                                data-student-name="<?= safeHtmlspecialchars(trim($row['student_name'])); ?>"
=======
                                                data-student-id="<?= htmlspecialchars($row['student_id']); ?>" 
                                                data-student-name="<?= htmlspecialchars($student_display_name); ?>"
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                                title="Update Fee Details">
                                            <i class="fas fa-edit"></i> Update
                                        </button>
                                        
                                        <?php // Show reminder button if there is a balance due and status is Pending or Overdue ?>
                                        <?php if (($row['status'] === 'Pending' || $row['status'] === 'Overdue') && floatval($row['amount_due']) > 0): ?>
                                            <a href="send_reminder.php?student_id=<?= safeHtmlspecialchars($row['student_id']); ?>" 
                                               class="btn btn-warning btn-sm" 
                                               title="Send Reminder Email" 
<<<<<<< HEAD
                                               onclick="return confirm('Are you sure you want to send a fee reminder email for <?= safeHtmlspecialchars(trim($row['student_name'])); ?>?');">
=======
                                               onclick="return confirm('Are you sure you want to send a fee reminder email for <?= htmlspecialchars($student_display_name); ?>?');">
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                                <i class="fas fa-envelope"></i> Reminder
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align:center; padding: 3rem; font-size: 1.1em; color: #777;">
                                <?php if ($student_count == 0): ?>
                                    No students found in the database. Please add students first.
                                <?php elseif (!empty($filter_class) || !empty($filter_status)): ?>
                                    No students match the selected filters. <a href="fees.php">Reset filters</a> to see all students.
                                <?php else: ?>
                                    No student records found.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="bulk-actions-container">
            <select name="bulk_action" required>
                <option value="">-- Select Bulk Action --</option>
                <option value="export_csv">Export Selected to CSV</option>
            </select>
            <button type="submit" class="btn btn-primary">Apply</button>
        </div>
    </form>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
           <?php
                $pagination_params = $_GET;
                function paginationLink($page, $params, $text, $disabled = false) {
                    if ($disabled) return "<span class=\"disabled\">{$text}</span>";
                    $params['page'] = $page;
                    return "<a href=\"?" . http_build_query($params) . "\">{$text}</a>";
                }
                echo paginationLink($current_page - 1, $pagination_params, '&laquo; Prev', $current_page <= 1);
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == $current_page) {
                        echo "<span class=\"current-page\">{$i}</span>";
                    } else {
                        echo paginationLink($i, $pagination_params, $i);
                    }
                }
                echo paginationLink($current_page + 1, $pagination_params, 'Next &raquo;', $current_page >= $total_pages);
           ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Modal functionality
function closeModal() {
    document.getElementById('studentModal').classList.remove('active');
}

// Open modal when update button is clicked
document.addEventListener('DOMContentLoaded', function() {
    // Handle Update Fee buttons
    document.querySelectorAll('.update-fee-btn').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            const studentName = this.dataset.studentName;
            
            // Update modal title
            document.querySelector('.modal-title').textContent = `Update Fee Details - ${studentName}`;
            
            // Show loading state
            document.getElementById('studentModal').classList.add('active');
            document.getElementById('modalStudentInfo').innerHTML = '<div class="spinner"></div> Loading student data...';
            
            // Fetch student data
            fetchStudentData(studentId);
        });
    });
    
    // Handle select all checkbox
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    
    selectAllCheckbox.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Handle individual checkboxes
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(rowCheckboxes).every(cb => cb.checked);
            const noneChecked = Array.from(rowCheckboxes).every(cb => !cb.checked);
            
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && !noneChecked;
        });
    });
    
    // Handle form submissions
    document.getElementById('updateFeeForm').addEventListener('submit', handleUpdateFeeForm);
    document.getElementById('recordPaymentForm').addEventListener('submit', handleRecordPaymentForm);
    
    // Close modal when clicking outside
    document.getElementById('studentModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
});

function fetchStudentData(studentId) {
    const formData = new FormData();
    formData.append('ajax_action', 'get_student_data');
    formData.append('student_id', studentId);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateModal(data.student_data, data.payments);
        } else {
            alert('Error loading student data: ' + (data.message || 'Unknown error'));
            closeModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading student data. Please try again.');
        closeModal();
    });
}

function populateModal(studentData, payments) {
    // Populate student info
    document.getElementById('modalStudentInfo').innerHTML = `
        <h3>Student: ${studentData.student_name}</h3>
        <p><strong>Student ID:</strong> ${studentData.student_id}</p>
        <p><strong>Class:</strong> ${studentData.class || 'N/A'}</p>
    `;
    
    // Populate fee summary
    const totalFee = parseFloat(studentData.total_fee) || 0;
    const amountPaid = parseFloat(studentData.amount_paid) || 0;
    const balance = totalFee - amountPaid;
    
    document.getElementById('feeSummary').innerHTML = `
        <div class="fee-stat">
            <div class="fee-stat-label">Total Fee</div>
            <div class="fee-stat-value">${totalFee.toFixed(2)}</div>
        </div>
        <div class="fee-stat">
            <div class="fee-stat-label">Amount Paid</div>
            <div class="fee-stat-value">${amountPaid.toFixed(2)}</div>
        </div>
        <div class="fee-stat">
            <div class="fee-stat-label">Balance</div>
            <div class="fee-stat-value fee-stat-balance ${balance > 0 ? 'negative' : 'positive'}">${balance.toFixed(2)}</div>
        </div>
        <div class="fee-stat">
            <div class="fee-stat-label">Status</div>
            <div class="fee-stat-value">${studentData.status}</div>
        </div>
    `;
    
    // Populate form fields
    document.getElementById('modalStudentId').value = studentData.student_id;
    document.getElementById('paymentStudentId').value = studentData.student_id;
    document.getElementById('totalFee').value = totalFee.toFixed(2);
    document.getElementById('amountPaid').value = amountPaid.toFixed(2);
    document.getElementById('dueDate').value = studentData.due_date || '';
    document.getElementById('paymentPlan').value = studentData.payment_plan || 'One-Time';
    
    // Populate payment history
    let paymentHistoryHtml = '';
    if (payments && payments.length > 0) {
        paymentHistoryHtml = `
            <table class="payment-history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        payments.forEach(payment => {
            paymentHistoryHtml += `
                <tr>
                    <td>${payment.payment_date}</td>
                    <td>${parseFloat(payment.amount).toFixed(2)}</td>
                    <td>${payment.payment_method}</td>
                    <td>${payment.received_by}</td>
                </tr>
            `;
        });
        
        paymentHistoryHtml += '</tbody></table>';
    } else {
        paymentHistoryHtml = '<p>No payment history found.</p>';
    }
    
    document.getElementById('paymentHistoryTable').innerHTML = paymentHistoryHtml;
}

function handleUpdateFeeForm(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('ajax_action', 'update_fee_structure');
    
    // Disable form while processing
    e.target.classList.add('loading');
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        e.target.classList.remove('loading');
        
        if (data.success) {
            alert(data.message);
            
            // Update the table row with new data
            const studentId = formData.get('student_id');
            updateTableRow(studentId, data.updated_data);
            
            // Refresh modal data
            fetchStudentData(studentId);
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        e.target.classList.remove('loading');
        console.error('Error:', error);
        alert('Error updating fee structure. Please try again.');
    });
}

function handleRecordPaymentForm(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('ajax_action', 'record_payment');
    
    // Disable form while processing
    e.target.classList.add('loading');
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        e.target.classList.remove('loading');
        
        if (data.success) {
            alert(data.message);
            
            // Clear the payment form
            e.target.reset();
            document.getElementById('paymentStudentId').value = formData.get('student_id');
            
            // Update the table row with new data
            const studentId = formData.get('student_id');
            updateTableRow(studentId, data.updated_data);
            
            // Refresh modal data
            fetchStudentData(studentId);
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        e.target.classList.remove('loading');
        console.error('Error:', error);
        alert('Error recording payment. Please try again.');
    });
}

function updateTableRow(studentId, updatedData) {
    const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
    if (!row) return;
    
    // Update total fee
    const totalFeeCell = row.querySelector('.total-fee-cell');
    if (totalFeeCell && updatedData.total_fee !== undefined) {
        totalFeeCell.textContent = `${parseFloat(updatedData.total_fee).toFixed(2)}`;
    }
    
    // Update amount paid
    const amountPaidCell = row.querySelector('.amount-paid-cell');
    if (amountPaidCell && updatedData.amount_paid !== undefined) {
        amountPaidCell.textContent = `${parseFloat(updatedData.amount_paid).toFixed(2)}`;
    }
    
    // Update amount due
    const amountDueCell = row.querySelector('.amount-due-cell');
    if (amountDueCell && updatedData.total_fee !== undefined && updatedData.amount_paid !== undefined) {
        const amountDue = parseFloat(updatedData.total_fee) - parseFloat(updatedData.amount_paid);
        amountDueCell.textContent = `${amountDue.toFixed(2)}`;
        amountDueCell.className = `amount-due-cell ${amountDue > 0 ? 'amount-due' : ''}`;
    }
    
    // Update status
    const statusCell = row.querySelector('.status-cell');
    if (statusCell && updatedData.status) {
        const statusClass = updatedData.status.toLowerCase().replace(/\s+/g, '-');
        const statusIcons = {
            'cleared': 'fas fa-check-circle',
            'overdue': 'fas fa-exclamation-triangle',
            'pending': 'fas fa-hourglass-half',
            'no-fee-assigned': 'fas fa-minus-circle'
        };
        const iconClass = statusIcons[statusClass] || 'fas fa-question-circle';
        
        statusCell.innerHTML = `
            <span class="status-pill status-${statusClass}">
                <i class="${iconClass}"></i>
                <span>${updatedData.status}</span>
            </span>
        `;
    }
}
</script>

<?php
// Include footer if you have one
// include 'sa_footer.php';
?>