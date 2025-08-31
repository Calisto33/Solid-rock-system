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
        
        // Ensure unique filename if needed, or handle overwrites
        // For now, use basename and move
        if(move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)){
            $attachment_link = $targetFilePath;
        } else{
            $errorMessage = "Sorry, there was an error uploading your file.";
        }
    }

    // Only proceed if no upload error or no file uploaded
    if (!$errorMessage) {
        // Insert event into the database
        $stmt = $conn->prepare("INSERT INTO events (title, description, attachment_type, attachment_link, target_audience, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        // Check if prepare() failed
        if ($stmt === false) {
            $errorMessage = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("sssssi", $title, $description, $attachment_type, $attachment_link, $target_audience, $created_by);

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
    <title>Admin Add Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --primary-light: rgba(67, 97, 238, 0.1);
            --secondary-color: #3f37c9;
            --accent-color: #f72585;
            --success-color: #4cc9f0; /* Changed for better contrast */
            --success-bg: rgba(76, 201, 240, 0.1);
            --warning-color: #f8961e;
            --danger-color: #f94144;
            --danger-bg: rgba(249, 65, 68, 0.1);
            --text-color: #2b2d42;
            --text-light: #6c757d;
            --text-muted: #9CA3AF;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-radius-sm: 6px;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
            --shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.1);
            --transition: all 0.2s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Roboto, -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex; /* Kept for potential future use, but not strictly needed now */
            flex-direction: column; /* Changed to column */
        }

        /* Main Content - No more sidebar margin */
        .main-content {
            flex: 1;
            transition: var(--transition);
            width: 100%; /* Ensure it takes full width */
            padding-top: 70px; /* Add padding to prevent header overlap */
        }

        .header {
            background-color: var(--card-bg);
            box-shadow: var(--shadow-sm);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed; /* Fixed header */
            top: 0;
            left: 0;
            right: 0;
            z-index: 999; /* Ensure it's above content */
            height: 70px; /* Fixed height for padding calculation */
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-icon {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }

        .logo-text {
            color: var(--text-color);
            font-weight: 700;
            font-size: 1.2rem;
        }


        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-color);
            background-color: var(--bg-color);
            position: relative;
            transition: var(--transition);
            cursor: pointer;
        }

        .header-icon:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: var(--accent-color);
            color: white;
            font-size: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .container {
            padding: 2rem;
            max-width: 900px; /* Reduced width for better focus */
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-title-wrapper {
            margin-bottom: 0.5rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .breadcrumb i {
            font-size: 0.7rem;
        }

        .breadcrumb a {
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-color);
        }

        /* Card */
        .card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-3px);
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary-color);
            font-size: 1rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Messages */
        .message {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .message.success {
            background-color: var(--success-bg);
            border-left: 4px solid var(--success-color);
            color: var(--text-color);
        }

        .message.error {
            background-color: var(--danger-bg);
            border-left: 4px solid var(--danger-color);
            color: var(--text-color);
        }

        .message i {
            font-size: 1.25rem;
        }

        .message.success i {
            color: var(--success-color);
        }

        .message.error i {
            color: var(--danger-color);
        }

        /* Form elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--text-color);
            background-color: var(--card-bg);
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px; /* Increased height */
        }

        .form-select-wrapper {
            position: relative;
        }

        .form-select {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--text-color);
            background-color: var(--card-bg);
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: var(--border-radius);
            transition: var(--transition);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        .select-arrow {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            color: var(--text-light);
            pointer-events: none;
        }

        .file-input-wrapper {
            position: relative;
        }
        
        .form-control[type="file"] {
            padding: 0.55rem 1rem; /* Adjust padding for file input */
        }

        .form-control[type="file"]::file-selector-button {
            padding: 0.55rem 1rem;
            margin: -0.55rem -1rem;
            margin-right: 1rem;
            border: none;
            background: var(--primary-light);
            color: var(--primary-color);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border-radius: var(--border-radius) 0 0 var(--border-radius);
        }

        .form-control[type="file"]::file-selector-button:hover {
             background: var(--primary-color);
             color: white;
        }


        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.625rem 1.25rem;
            border-radius: var(--border-radius);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
            transform: translateY(-2px);
        }

        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            width: 100%; /* Make button full width */
            margin-top: 1rem; /* Add some space above */
        }

        /* Responsive */
         @media (max-width: 768px) {
            .header {
                padding: 1rem;
                height: 60px; /* Adjust header height */
            }
             .main-content {
                padding-top: 60px; /* Adjust padding */
             }
            .logo-text {
                display: none; /* Hide text on smaller screens */
            }
             .page-title {
                 font-size: 1.1rem;
             }
            .container {
                padding: 1rem;
            }
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        @media (max-width: 576px) {
            .form-group {
                margin-bottom: 1rem;
            }
            .header-actions .header-icon:not(:first-child) {
                display: none; /* Hide extra icons on very small screens */
            }
            .page-title {
                 display: none; /* Hide title on very small screens */
             }
        }
    </style>
</head>
<body>
    <main class="main-content">
        <header class="header">
            <div class="header-left">
                <a href="#" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <span class="logo-text">Wisetech</span>
                </a>
                <h1 class="page-title">Add New Event</h1>
            </div>
               
                 <div class="header-icon"> <i class="fas fa-user"></i>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="page-header">
                <div class="page-title-wrapper">
                    <div class="breadcrumb">
                        <a href="admin_home.php">Dashboard</a>
                        <i class="fas fa-chevron-right"></i>
                        <a href="#">Events</a> <i class="fas fa-chevron-right"></i>
                        <span>Add Event</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-calendar-plus"></i>
                        Create New Event
                    </h2>
                </div>
                <div class="card-body">
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
                            <div class="form-select-wrapper">
                                <select id="target_audience" name="target_audience" class="form-select" required>
                                    <option value="" selected disabled>Select audience</option>
                                    <option value="parent">Parent</option>
                                    <option value="student">Student</option>
                                    <option value="staff">Staff</option>
                                    <option value="all">All</option>
                                </select>
                                <span class="select-arrow">
                                    <i class="fas fa-chevron-down"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="attachment_type" class="form-label">Attachment Type</label>
                            <div class="form-select-wrapper">
                                <select id="attachment_type" name="attachment_type" class="form-select" required>
                                    <option value="" selected disabled>Select type</option>
                                    <option value="text">Text / Link Only</option>
                                    <option value="pdf">PDF</option>
                                    <option value="image">Image</option>
                                    <option value="ppt">PowerPoint</option>
                                    <option value="excel">Excel</option>
                                    <option value="word">Word Document</option>
                                </select>
                                <span class="select-arrow">
                                    <i class="fas fa-chevron-down"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="attachment" class="form-label">Upload File (Optional)</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="attachment" name="attachment" class="form-control">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus-circle"></i>
                            Add Event
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php
// Close the database connection at the end
if ($conn) {
    $conn->close();
}
?>
</body>
</html>