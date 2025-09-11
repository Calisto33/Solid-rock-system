<?php
session_start();
include '../config.php'; // Database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Ensure the connection object exists
if (!isset($conn) || !$conn) {
    die("Database connection failed. Please check your config.php file.");
}

$successMessage = null;
$errorMessage = null;

// Handle event submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $target_audience = $_POST['target_audience'];
    $attachment_type = $_POST['attachment_type'];
    $attachment_link = null;
    $created_by = $_SESSION['user_id']; // Ensure this is an integer

    // Handle file upload
    if (!empty($_FILES['attachment']['name'])) {
        $targetDir = "../uploads/events/";
        // Create directory if it doesn't exist
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = basename($_FILES['attachment']['name']);
        $targetFilePath = $targetDir . $fileName;
        
        // For now, use basename and move
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)) {
            $attachment_link = $targetFilePath;
        } else {
            $errorMessage = "Sorry, there was an error uploading your file.";
        }
    }

    // Only proceed if no upload error or no file uploaded
    if (!$errorMessage) {
        // Insert event into the database
        $stmt = $conn->prepare("INSERT INTO events (title, description, attachment_type, attachment_link, target_audience) VALUES (?, ?, ?, ?, ?)");
        // Check if prepare() failed
        if ($stmt === false) {
            $errorMessage = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("sssss", $title, $description, $attachment_type, $attachment_link, $target_audience);

            if ($stmt->execute()) {
                $successMessage = "Event added successfully!";
            } else {
                $errorMessage = "Error adding event: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// We will close the connection at the very end.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Add Events | Wisetech</title>
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4A90E2;
            --primary-dark: #3771C8;
            --background-color: #F8F9FA;
            --card-background: #FFFFFF;
            --text-color: #333333;
            --text-light: #666666;
            --border-color: #E0E0E0;
            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --border-radius: 12px;
            --success-bg: #EAF7E8;
            --success-text: #4CAF50;
            --error-bg: #F8E8E8;
            --error-text: #F44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            background-color: var(--card-background);
            padding: 1.5rem 2.5rem;
            box-shadow: var(--shadow-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            height: 40px; /* Adjust as needed */
            width: auto;
        }

        .header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .nav-link {
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
        }

        .nav-link:hover {
            background-color: var(--primary-color);
            color: var(--card-background);
        }

        .container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 2.5rem;
            width: 100%;
            max-width: 700px;
            text-align: center;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            font-size: 1.75rem;
            color: var(--primary-dark);
            margin-bottom: 2rem;
            position: relative;
        }

        .card h3::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: left;
        }
        
        .message.success {
            background-color: var(--success-bg);
            color: var(--success-text);
        }
        
        .message.error {
            background-color: var(--error-bg);
            color: var(--error-text);
        }
        
        .form-group {
            text-align: left;
            margin-bottom: 2rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--background-color);
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        select.form-control {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23666666'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
            background-color: var(--card-background);
        }

        .form-file-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--background-color);
            font-family: 'Poppins', sans-serif;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
        }

        .form-file-input::-webkit-file-upload-button {
            padding: 0.5rem 1rem;
            margin-right: 1rem;
            border: none;
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
        }

        .form-file-input::-webkit-file-upload-button:hover {
            background: var(--primary-dark);
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary-color);
            color: var(--card-background);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .footer {
            background-color: var(--card-background);
            padding: 1.5rem;
            text-align: center;
            color: var(--text-light);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
            margin-top: auto;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1.5rem;
            }
            .header-left {
                width: 100%;
                justify-content: center;
                margin-bottom: 1rem;
            }
            .header h2 {
                font-size: 1.2rem;
                text-align: center;
            }
            .nav-link {
                width: 100%;
                text-align: center;
            }
            .container {
                padding: 1rem;
            }
            .card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <img src="../images/logo.jpeg" alt="Wisetech College Logo" class="logo">
            <h2>Wisetech Admin Portal</h2>
        </div>
        <a href="admin_home.php" class="nav-link">Dashboard</a>
    </header>

    <div class="container">
        <div class="card">
            <h3>Add New Event</h3>
            
            <?php if ($successMessage): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php elseif ($errorMessage): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title" class="form-label">Event Title</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Enter event title" required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Event Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" placeholder="Describe the event details"></textarea>
                </div>

                <div class="form-group">
                    <label for="target_audience" class="form-label">Target Audience</label>
                    <select id="target_audience" name="target_audience" class="form-control" required>
                        <option value="" selected disabled>Select audience</option>
                        <option value="parent">Parent</option>
                        <option value="student">Student</option>
                        <option value="staff">Staff</option>
                        <option value="all">All</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="attachment_type" class="form-label">Attachment Type</label>
                    <select id="attachment_type" name="attachment_type" class="form-control" required>
                        <option value="" selected disabled>Select type</option>
                        <option value="text">Text / Link Only</option>
                        <option value="pdf">PDF</option>
                        <option value="image">Image</option>
                        <option value="ppt">PowerPoint</option>
                        <option value="excel">Excel</option>
                        <option value="word">Word Document</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="attachment" class="form-label">Upload File (Optional)</label>
                    <input type="file" id="attachment" name="attachment" class="form-control form-file-input">
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-plus-circle"></i>
                    Add Event
                </button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Wisetech College Portal | Admin System</p>
    </footer>
</body>
</html>

<?php
// Close the database connection at the end
if ($conn) {
    $conn->close();
}
?>