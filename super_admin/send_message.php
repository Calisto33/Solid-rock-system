
<?php

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// --- Configuration ---
$ollamaApiUrl = 'http://localhost:11434/api/generate';
$modelName = 'deepseek-r1:7b'; 

// --- Gmail Configuration (FILL THESE IN!) ---
$gmailUsername = 'ronaldbvirinyangwe@gmail.com';
$gmailAppPassword = 'bkepemqcdyxxedlr';   
$senderName = 'Ronald';     
$recipientEmail = 'scaleszw@gmail.com';     
$recipientName = 'Scales';            


$emailSubject = "Meeting Follow-up";
$keyPoints = [
    "Thank them for their time.",
    "Reiterate the main decisions: A, B, C.",
    "Mention next steps: X, Y, Z.",
    "Ask if they have any further questions."
];

// --- Construct the Prompt for the LLM ---
$prompt = "You are an AI assistant helping to draft professional emails.\n\n";
$prompt .= "Please draft an email with the subject: \"{$emailSubject}\"\n\n";
$prompt .= "The email should cover the following key points:\n";
foreach ($keyPoints as $point) {
    $prompt .= "- " . $point . "\n";
}
$prompt .= "\nKeep the tone professional and concise. Start with a suitable greeting and end with a professional closing.\n\nEmail Draft:\n";

echo "--- Sending Prompt to Ollama --- \n";
echo "Model: {$modelName}\n";
echo "Prompt:\n{$prompt}\n\n";

// --- Prepare data for Ollama API ---
$data = [
    'model' => $modelName,
    'prompt' => $prompt,
    'stream' => false
];
$jsonData = json_encode($data);

// --- Initialize cURL ---
$ch = curl_init($ollamaApiUrl);

// --- Set cURL options ---
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 90);

// --- Execute cURL request ---
$responseJson = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// --- Error Handling for cURL ---
if (curl_errno($ch)) {
    echo "--- cURL Error ---\n";
    echo 'Error:' . curl_error($ch) . "\n";
} elseif ($httpCode != 200) {
    echo "--- Ollama API Error ---\n";
    echo "HTTP Status Code: {$httpCode}\n";
    echo "Response: {$responseJson}\n";
} else {
    // --- Process the Ollama response ---
    $responseData = json_decode($responseJson, true);

    if (isset($responseData['response'])) {
        $generatedEmailBody = $responseData['response'];
        echo "--- LLM Generated Email Draft ---\n";
        echo $generatedEmailBody . "\n\n";

        // --- Send the email using PHPMailer ---
        $mail = new PHPMailer(true); // Passing `true` enables exceptions

        try {
            $mail->isSMTP();                              
            $mail->Host       = 'smtp.gmail.com';          
            $mail->SMTPAuth   = true;                    
            $mail->Username   = $gmailUsername;           
            $mail->Password   = $gmailAppPassword;        
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port       = 587;                     

            $mail->setFrom($gmailUsername, $senderName); 
            $mail->addAddress($recipientEmail, $recipientName); 
          
            $mail->isHTML(false); 
            $mail->Subject = $emailSubject; 
            $mail->Body    = $generatedEmailBody;

            $mail->send();
            echo "--- Email successfully sent via Gmail! ---\n";
        } catch (Exception $e) {
            echo "--- Email could not be sent. Mailer Error: {$mail->ErrorInfo} ---\n";
        }

    } else {
        echo "--- Error: 'response' field not found in Ollama API output. ---\n";
        echo "Full Response:\n";
        print_r($responseData);
    }
}

curl_close($ch);

?>
