<?php
// =========================================================================
// == DEBUGGING ONLY: Show all PHP errors. REMOVE these on a live server! ==
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// =========================================================================

session_start();
header('Content-Type: application/json'); // MUST be before ANY output

// --- Function to send a JSON error and stop ---
function send_json_error($message, $code = 500) {
    http_response_code($code); // Set HTTP status code
    echo json_encode(['error' => $message]);
    exit(); // Stop script execution
}

// --- Include config and check connection ---
$config_path = '../config.php';
if (!file_exists($config_path)) {
    send_json_error("Configuration file not found at: $config_path");
}

include $config_path;

if (!isset($conn) || $conn->connect_error) {
    send_json_error("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
}

// --- Check Admin Role ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    send_json_error('Unauthorized', 401);
}

// --- Get and Validate Student ID ---
$studentId = $_GET['student_id'] ?? null;
if (empty($studentId)) {
    send_json_error('Student ID not provided', 400);
}

// Your screenshot shows student_id as an integer. So we use 'i'.
$studentIdInt = (int)$studentId; 

$conn->set_charset("utf8mb4");

// --- Prepare Response ---
$response = [
    'studentId' => $studentId,
    'stats' => ['present' => 0, 'absent' => 0, 'rate' => 'N/A', 'lastAbsent' => 'N/A'],
    'monthly' => [],
    'recent' => []
];

// --- Start Database Queries with Corrected Column Names ---
try {

    // --- 1. Fetch Stats ---
    $statsQuery = "
        SELECT 
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
            COUNT(*) as total_days,
            MAX(CASE WHEN status = 'Absent' THEN date ELSE NULL END) as last_absent 
        FROM attendance 
        WHERE student_id = ?
    "; // Changed 'attendance_date' to 'date'
    $stmt = $conn->prepare($statsQuery);
    if ($stmt === false) { send_json_error("SQL Prepare Error (Stats): " . $conn->error); }
    
    if (!$stmt->bind_param("i", $studentIdInt)) { send_json_error("SQL Bind Error (Stats): " . $stmt->error); }
    if (!$stmt->execute()) { send_json_error("SQL Execute Error (Stats): " . $stmt->error); }
    
    $statsResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($statsResult) {
        $totalDays = (int)($statsResult['total_days'] ?? 0);
        $presentDays = (int)($statsResult['present_days'] ?? 0);
        $response['stats'] = [
            'present' => $presentDays,
            'absent' => (int)($statsResult['absent_days'] ?? 0),
            'rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100) . '%' : 'N/A',
            'lastAbsent' => $statsResult['last_absent'] ? date('M d, Y', strtotime($statsResult['last_absent'])) : 'N/A'
        ];
    }

    // --- 2. Fetch Monthly Data ---
    $monthlyQuery = "
        SELECT 
            DATE_FORMAT(date, '%b') as month_name, 
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count
        FROM attendance 
        WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date, '%Y-%m'), month_name
        ORDER BY DATE_FORMAT(date, '%Y-%m')
    "; // Changed 'attendance_date' to 'date' (3 times)
    $stmt = $conn->prepare($monthlyQuery);
    if ($stmt === false) { send_json_error("SQL Prepare Error (Monthly): " . $conn->error); }

    if (!$stmt->bind_param("i", $studentIdInt)) { send_json_error("SQL Bind Error (Monthly): " . $stmt->error); }
    if (!$stmt->execute()) { send_json_error("SQL Execute Error (Monthly): " . $stmt->error); }

    $monthlyResult = $stmt->get_result();
    while ($row = $monthlyResult->fetch_assoc()) {
        $response['monthly'][$row['month_name']] = (int)$row['present_count'];
    }
    $stmt->close();

    // --- 3. Fetch Recent Records ---
    $recentQuery = "
        SELECT 
            date, 
            status, 
            updated_by
        FROM attendance 
        WHERE student_id = ? 
        ORDER BY date DESC 
        LIMIT 10
    "; // Changed 'attendance_date' to 'date', 'marked_by' to 'updated_by', removed 'notes'
    $stmt = $conn->prepare($recentQuery);
    if ($stmt === false) { send_json_error("SQL Prepare Error (Recent): " . $conn->error); }

    if (!$stmt->bind_param("i", $studentIdInt)) { send_json_error("SQL Bind Error (Recent): " . $stmt->error); }
    if (!$stmt->execute()) { send_json_error("SQL Execute Error (Recent): " . $stmt->error); }

    $recentResult = $stmt->get_result();
    while ($row = $recentResult->fetch_assoc()) {
        $response['recent'][] = [
            'date' => date('M d, Y', strtotime($row['date'])),
            'status' => $row['status'] ?? 'Unknown',
            'markedBy' => $row['updated_by'] ?? 'N/A', // Using 'updated_by' now
            'notes' => '-' // Setting notes to '-' since the column doesn't exist
        ];
    }
    $stmt->close();

    // --- Send the successful response ---
    echo json_encode($response);

} catch (Exception $e) {
    // Catch any other unexpected errors
    send_json_error('An unexpected error occurred: ' . $e->getMessage());
}

// Close the connection
$conn->close();
?>  