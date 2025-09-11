<?php
// *** FIX: Added session_start() at the very top.
session_start();

$pageTitle = "Submit Assignment";
// *** FIX: Moved header and config includes after the security check.
include '../config.php';

// *** FIX: Replaced the old code with the standard, secure header.
// 1. Check for the correct user ID and role.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

// 2. Get the primary key of the logged-in user from the session using the correct key.
$user_id = $_SESSION['user_id'];


// Validate assignment ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='card' style='padding:2rem; color:red; text-align:center;'>Invalid Assignment ID provided.</div>");
}
$assignment_id = (int)$_GET['id']; // Cast to integer for security

// Fetch assignment details to display
$stmt = $conn->prepare("SELECT a.assignment_description, ts.subject_name FROM assignments a JOIN table_subject ts ON a.subject_id = ts.subject_id WHERE a.assignment_id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    die("<div class='card' style='padding:2rem; color:red; text-align:center;'>Assignment could not be found.</div>");
}

// Now include the header, as we are sure the user is authorized.
include 'header.php';
?>

<style>
    /* --- Styles for the Submission Page --- */
    .submission-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 1.5rem;
    }

    /* We will use the shared .card class, but add specific styles for the card body here */
    .card .card-body {
        padding: 2rem;
    }
    .card .card-body h3 {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 2rem;
        color: var(--primary-text);
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: var(--secondary-text);
    }
    .form-group input[type="file"],
    .form-group textarea {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: var(--rounded-lg);
        background-color: var(--bg-color);
        transition: var(--transition);
        font-size: 1rem;
        color: var(--primary-text);
    }
    .form-group textarea {
        min-height: 120px;
        resize: vertical;
    }
    .form-group input[type="file"]:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--accent-purple);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }

    /* Style the buttons to use the shared .btn and .btn-primary classes */
    .submit-btn {
        width: 100%;
        font-size: 1rem;
    }

    /* AI Feedback Section Specific Styles */
    .ai-feedback-card h3 {
        color: var(--accent-pink);
    }
    .ai-feedback-card .btn-primary {
        background-color: var(--accent-pink);
    }
    .ai-feedback-card .btn-primary:hover {
        background-color: #be185d;
    }

    #aiFeedbackResult {
        margin-top: 1.5rem;
        padding: 1.5rem;
        border: 1px dashed var(--border-color);
        border-radius: var(--rounded-lg);
        background-color: var(--bg-color);
        min-height: 150px;
        color: var(--secondary-text);
        white-space: pre-wrap;
        font-family: 'Menlo', 'Courier New', monospace;
        font-size: 0.9rem;
        line-height: 1.7;
    }
    #aiFeedbackResult:empty::before {
        content: "Your AI feedback will appear here...";
        font-style: italic;
    }
    #aiFeedbackResult.loading {
        display: grid;
        place-items: center;
        font-style: italic;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .submission-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<h1 class="page-title">Submit: <?= htmlspecialchars($assignment['subject_name']) ?></h1>
<p style="margin-top:-1.5rem; margin-bottom: 2rem; color: var(--secondary-text); max-width: 80ch;">
    <?= htmlspecialchars($assignment['assignment_description']) ?>
</p>

<div class="submission-grid">

    <!-- Official Submission Card -->
    <div class="card">
        <div class="card-body">
            <h3><i class="fas fa-file-upload"></i> Official Submission</h3>
            <form action="handle_submissions.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">
                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                
                <div class="form-group">
                    <label for="assignmentFile">Upload Your File</label>
                    <input type="file" id="assignmentFile" name="assignment_file" required>
                </div>
                <div class="form-group">
                    <label for="comments">Comments for Teacher (Optional)</label>
                    <textarea id="comments" name="comments" placeholder="e.g., I had a question about part 2..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary submit-btn"><i class="fas fa-paper-plane"></i> Submit for Marking</button>
            </form>
        </div>
    </div>

    <!-- Instant AI Feedback Card -->
    <div class="card ai-feedback-card">
        <div class="card-body">
            <h3><i class="fas fa-robot"></i> Instant AI Feedback (Practice)</h3>
            <div class="form-group">
                <label for="aiAnswer">Paste Your Answer Here</label>
                <textarea id="aiAnswer" placeholder="Paste text here to get instant feedback before you submit officially."></textarea>
            </div>
            <button id="getAiFeedbackBtn" class="btn btn-primary submit-btn"><i class="fas fa-magic"></i> Get Feedback</button>
            <div id="aiFeedbackResult"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('getAiFeedbackBtn').addEventListener('click', async () => {
    const answerText = document.getElementById('aiAnswer').value;
    const feedbackResultEl = document.getElementById('aiFeedbackResult');
    const assignmentDescription = "<?= htmlspecialchars($assignment['assignment_description'], ENT_QUOTES) ?>";
    const subjectName = "<?= htmlspecialchars($assignment['subject_name'], ENT_QUOTES) ?>";

    if (!answerText.trim()) {
        feedbackResultEl.textContent = "Please paste your answer in the text area first.";
        return;
    }

    feedbackResultEl.classList.add('loading');
    feedbackResultEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Thinking...';

    const prompt = `
        You are a friendly and encouraging teaching assistant for Wisetech College.
        A student needs feedback on a practice answer for an assignment.

        The subject is: "${subjectName}"
        The assignment is: "${assignmentDescription}"
        The student's answer is:
        ---
        ${answerText}
        ---

        Please provide constructive feedback in a structured, positive, and easy-to-read format. Use markdown for formatting. Your response should include:
        1.  **Great Start!**: A brief, encouraging opening statement.
        2.  **What You've Done Well**: 2-3 bullet points highlighting specific strengths.
        3.  **Areas to Improve**: 2-3 specific, actionable bullet points for improvement.
        4.  **Final Encouragement**: A concluding sentence.

        Do not give a grade. Keep the feedback focused and helpful.
    `;

    try {
        const payload = { contents: [{ role: "user", parts: [{ text: prompt }] }] };
        const apiKey = ""; // Provided by Canvas
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!response.ok) throw new Error('Network response failed.');

        const result = await response.json();
        
        if (result.candidates && result.candidates[0].content.parts[0].text) {
            feedbackResultEl.textContent = result.candidates[0].content.parts[0].text;
        } else {
            throw new Error("The AI response was empty.");
        }

    } catch (error) {
        console.error("AI Feedback Error:", error);
        feedbackResultEl.textContent = "Sorry, an error occurred while getting feedback. Please try again later.";
    } finally {
        feedbackResultEl.classList.remove('loading');
    }
});
</script>

<?php include 'footer.php'; ?>
