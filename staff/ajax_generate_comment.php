<?php
// ajax_generate_comment.php

// Prevent any output before JSON response
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output

// Set the header to return JSON immediately
header('Content-Type: application/json');

try {
    // Basic includes
    include '../config.php'; // Your DB connection
    
    // Clean any output that might have been generated
    ob_clean();

    /**
     * Generates a report card comment using an AI model.
     */
    function generateReportComment(
        string $studentName,
        string $subjectName,
        ?float $finalMark,
        ?string $finalGrade,
        ?string $targetGrade,
        ?string $attitude
    ): string {

        // --- 1. Prompt Engineering ---
        $prompt = "You are a professional teaching assistant AI. Your task is to draft a concise, constructive, and personalized report card comment.\n\n";
        $prompt .= "Student Details:\n";
        $prompt .= "- Name: {$studentName}\n";
        $prompt .= "- Subject: {$subjectName}\n";
        if ($finalMark !== null) $prompt .= "- Final Mark: " . number_format($finalMark, 2) . "%\n";
        if ($finalGrade) $prompt .= "- Final Grade: {$finalGrade}\n";
        if ($targetGrade) $prompt .= "- Target Grade: {$targetGrade}\n";
        if ($attitude) $prompt .= "- Attitude/Effort: {$attitude}\n\n";

        $prompt .= "Guidelines for the comment:\n";
        $prompt .= "- As a teacher write a professional, encouraging, and parent-friendly tone.\n";
        $prompt .= "- Start with a positive observation about the student's strength or attitude.\n";
        $prompt .= "- Based on the mark and grades, provide a constructive suggestion for improvement if necessary.\n";
        
        // Add specific logic based on data
        if ($finalMark !== null && $finalMark < 50) {
            $prompt .= "- Since the mark is below passing, focus the suggestion on a key area for improvement.\n";
        } elseif ($finalGrade && $targetGrade && $finalGrade < $targetGrade) {
            $prompt .= "- Acknowledge their effort but note they are just below their target. Suggest one specific action to bridge the gap.\n";
        } elseif ($finalGrade && $targetGrade && $finalGrade >= $targetGrade) {
            $prompt .= "- Commend them for meeting or exceeding their target and encourage them to continue their excellent work.\n";
        }

        $prompt .= "- The comment must be concise and detailed, just like a teacher would write the comment.\n";
        $prompt .= "- Generate ONLY the comment text itself, without any introductory phrases like 'Here is the comment:'.\n";

        // --- 2. Ollama API Call ---
        $ollamaApiUrl = 'http://localhost:11434/api/generate';
        $modelName = 'gemma3:1b'; // Changed from gemma3:1b to a more common model
        $data = ['model' => $modelName, 'prompt' => $prompt, 'stream' => false];
        $jsonData = json_encode($data);

        $ch = curl_init($ollamaApiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 90,
        ]);

        $responseJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return "Error connecting to AI service: " . $error;
        }
        if ($httpCode != 200) {
            return "AI service returned an error (Code: {$httpCode}). Please check server logs.";
        }

        $responseData = json_decode($responseJson, true);
        return trim($responseData['response'] ?? 'Failed to generate a valid comment.');
    }

    // Get the POSTed data from the JavaScript fetch call
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input received');
    }

    // Sanitize inputs (using modern PHP-compatible methods)
    $student_name = htmlspecialchars(trim($input['student_name'] ?? 'The student'), ENT_QUOTES, 'UTF-8');
    $subject_id = filter_var($input['subject_id'] ?? 0, FILTER_VALIDATE_INT);
    $final_mark = filter_var($input['final_mark'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $final_grade = htmlspecialchars(trim($input['final_grade'] ?? ''), ENT_QUOTES, 'UTF-8');
    $target_grade = htmlspecialchars(trim($input['target_grade'] ?? ''), ENT_QUOTES, 'UTF-8');
    $attitude = htmlspecialchars(trim($input['attitude_to_learning'] ?? ''), ENT_QUOTES, 'UTF-8');

    // The form gives us subject_id, but the AI prompt needs the name. Let's fetch it.
    $subject_name = 'the subject';
    if ($subject_id && isset($conn)) {
        $stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $subject_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $subject_name = $row['subject_name'];
            }
            $stmt->close();
        }
    }

    // Call the agent function
    $generated_comment = generateReportComment(
        $student_name,
        $subject_name,
        $final_mark,
        $final_grade,
        $target_grade,
        $attitude
    );

    // Return the result as JSON
    echo json_encode(['comment' => $generated_comment, 'success' => true]);

} catch (Exception $e) {
    // Clean any previous output
    ob_clean();
    
    // Return error as JSON
    echo json_encode([
        'error' => true, 
        'message' => $e->getMessage(),
        'comment' => 'Failed to generate comment due to an error.'
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    ob_end_flush();
}
?>