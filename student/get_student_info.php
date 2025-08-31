<?php
// --- 1. SETUP & DATABASE CONNECTION ---
header('Content-Type: application/json');
session_start(); 

// --- !!! IMPORTANT: UPDATE YOUR DATABASE DETAILS HERE !!! ---
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'wisetech'; // Make sure this is your database name

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// --- 2. SECURITY CHECK & DATA FETCHING ---
if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated. Please log in.']);
    exit;
}

$student_id_from_session = $_SESSION['student_id'];

// --- MODIFIED: The SQL query now matches your table structure ---
$stmt = $conn->prepare("SELECT first_name, last_name, username, course, year FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id_from_session);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

$stmt->close();
$conn->close();

if (!$student) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student with the given ID was not found.']);
    exit;
}

// --- MODIFIED: Logic to build the full name from your columns ---
// It combines first_name and last_name, and uses username as a fallback if they are empty
$fullName = trim($student['first_name'] . ' ' . $student['last_name']);
if (empty($fullName)) {
    $fullName = $student['username']; // Use username if first/last name is not available
}

// --- MODIFIED: Logic to build the class description from your columns ---
$classInfo = ucwords($student['course'] . ' ' . $student['year']);


// --- 3. HELPER FUNCTION TO GENERATE INITIALS (No changes needed here) ---
function getInitials($name) {
    $words = explode(" ", $name);
    $initials = "";
    if (count($words) >= 2) {
        $initials .= strtoupper(substr($words[0], 0, 1));
        $initials .= strtoupper(substr(end($words), 0, 1));
    } else if (count($words) == 1 && strlen($words[0]) > 1) {
        $initials .= strtoupper(substr($words[0], 0, 2));
    } else if (count($words) == 1) {
        $initials .= strtoupper(substr($words[0], 0, 1));
    }
    return $initials;
}

// --- 4. PREPARE & SEND JSON RESPONSE ---
$response = [
    'success'  => true,
    'fullName' => $fullName,
    'class'    => $classInfo,
    'initials' => getInitials($fullName)
];

echo json_encode($response);

?>