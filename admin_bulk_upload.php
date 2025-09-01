<?php
// admin_bulk_upload.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication (modify according to your auth system)
/*
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
*/

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk User Upload - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --text-color: #1f2937;
            --white: #ffffff;
            --gray-light: #f3f4f6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0fd 100%);
            color: var(--text-color);
            min-height: 100vh;
        }

        .admin-header {
            background: var(--white);
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo h1 {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .upload-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-light);
        }

        .card-header h2 {
            color: var(--text-color);
            font-size: 1.5rem;
        }

        .upload-area {
            border: 3px dashed var(--primary-color);
            border-radius: var(--radius);
            padding: 3rem;
            text-align: center;
            background: #fafbff;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .upload-area:hover {
            border-color: var(--primary-dark);
            background: #f0f7ff;
        }

        .upload-area.dragover {
            border-color: var(--success-color);
            background: #f0fdf4;
        }

        .upload-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .file-input {
            display: none;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem;
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-success { background: var(--success-color); }
        .btn-warning { background: var(--warning-color); }
        .btn-secondary { background: #6b7280; }

        .progress-container {
            margin: 1.5rem 0;
            display: none;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: var(--gray-light);
            border-radius: 6px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
            width: 0%;
            transition: width 0.3s ease;
        }

        .file-info {
            display: none;
            background: var(--gray-light);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .template-section {
            background: var(--white);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .template-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .requirements {
            background: #fef3c7;
            border-left: 4px solid var(--warning-color);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .requirements ul {
            margin-left: 2rem;
            margin-top: 0.5rem;
        }

        .requirements li {
            margin-bottom: 0.5rem;
        }

        .results-section {
            display: none;
            background: var(--white);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .stat-card {
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-card.success { background: #dcfce7; color: #166534; }
        .stat-card.error { background: #fee2e2; color: #991b1b; }
        .stat-card.warning { background: #fef3c7; color: #92400e; }
        .stat-card.info { background: #dbeafe; color: #1e40af; }

        .results-list {
            max-height: 300px;
            overflow-y: auto;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .success-list {
            background: #dcfce7;
            border-left: 4px solid var(--success-color);
        }

        .error-list {
            background: #fee2e2;
            border-left: 4px solid var(--error-color);
        }

        @media (max-width: 768px) {
            .admin-header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 0 0.5rem;
            }

            .template-buttons {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="logo">
            <i class="fas fa-graduation-cap" style="font-size: 2rem; color: var(--primary-color);"></i>
            <h1>Admin Panel - Bulk User Upload</h1>
        </div>
        <div class="user-info">
            <span>Welcome, Admin</span>
            <a href="admin_home.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </header>

    <div class="container">
        <!-- Upload Section -->
        <div class="upload-card">
            <div class="card-header">
                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--primary-color);"></i>
                <h2>Upload Users File</h2>
            </div>

            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h3>Drop your CSV/Excel file here or click to browse</h3>
                <p style="margin: 1rem 0; color: #6b7280;">Maximum file size: 5MB | Maximum users: 1000</p>
                <input type="file" id="fileInput" class="file-input" accept=".csv,.xlsx,.xls">
                <button class="btn" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-folder-open"></i> Choose File
                </button>
            </div>

            <div class="file-info" id="fileInfo">
                <h4><i class="fas fa-file"></i> Selected File:</h4>
                <p id="fileName"></p>
                <p id="fileSize"></p>
                <p id="fileType"></p>
            </div>

            <div class="progress-container" id="progressContainer">
                <h4>Processing...</h4>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <p id="progressText">Preparing upload...</p>
            </div>

            <div style="text-align: center; margin-top: 1.5rem;">
                <button class="btn btn-success" id="uploadBtn" disabled>
                    <i class="fas fa-upload"></i> Process Upload
                </button>
                <button class="btn btn-secondary" id="clearBtn">
                    <i class="fas fa-times"></i> Clear Selection
                </button>
            </div>
        </div>

        <!-- Template Section -->
        <div class="template-section">
            <div class="card-header">
                <i class="fas fa-download" style="font-size: 2rem; color: var(--success-color);"></i>
                <h2>Download Templates</h2>
            </div>

            <p>Download a template file to see the required format for bulk uploads:</p>

            <div class="template-buttons">
                <button class="btn btn-success" onclick="downloadTemplate('csv')">
                    <i class="fas fa-file-csv"></i> Download CSV Template
                </button>
                <button class="btn btn-warning" onclick="downloadTemplate('excel')">
                    <i class="fas fa-file-excel"></i> Download Excel Template
                </button>
            </div>

            <div class="requirements">
                <h4><i class="fas fa-exclamation-triangle"></i> File Requirements:</h4>
                <ul>
                    <li><strong>Required columns:</strong> username, email, password, role</li>
                    <li><strong>Optional columns:</strong> status (defaults to 'pending')</li>
                    <li><strong>Valid roles:</strong> student, staff, admin, parent</li>
                    <li><strong>Valid statuses:</strong> active, pending, suspended</li>
                    <li><strong>Email addresses must be unique</strong></li>
                    <li><strong>Usernames must be unique</strong></li>
                    <li><strong>Passwords will be automatically hashed</strong></li>
                    <li><strong>Student IDs will be auto-generated for students</strong></li>
                    <li><strong>Maximum 1000 users per upload</strong></li>
                </ul>
            </div>
        </div>

        <!-- Results Section -->
        <div class="results-section" id="resultsSection">
            <div class="card-header">
                <i class="fas fa-chart-bar" style="font-size: 2rem; color: var(--primary-color);"></i>
                <h2>Upload Results</h2>
            </div>

            <div class="stats-grid" id="statsGrid">
                <!-- Stats will be populated by JavaScript -->
            </div>

            <div id="successList" class="results-list success-list" style="display: none;">
                <h4><i class="fas fa-check-circle"></i> Successfully Created Users:</h4>
                <ul id="successItems"></ul>
            </div>

            <div id="errorList" class="results-list error-list" style="display: none;">
                <h4><i class="fas fa-exclamation-circle"></i> Errors:</h4>
                <ul id="errorItems"></ul>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <button class="btn" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Upload Another File
                </button>
                <button class="btn btn-secondary" onclick="exportResults()">
                    <i class="fas fa-download"></i> Export Results
                </button>
            </div>
        </div>
    </div>

    <script>
        let selectedFile = null;
        let uploadResults = null;

        // DOM elements
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const uploadBtn = document.getElementById('uploadBtn');
        const progressContainer = document.getElementById('progressContainer');
        const resultsSection = document.getElementById('resultsSection');

        // File upload handling
        setupFileUpload();

        function setupFileUpload() {
            // Drag and drop
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelect(files[0]);
                }
            });

            // File input change
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleFileSelect(e.target.files[0]);
                }
            });

            // Upload button
            uploadBtn.addEventListener('click', processUpload);
            document.getElementById('clearBtn').addEventListener('click', clearSelection);
        }

        function handleFileSelect(file) {
            selectedFile = file;
            
            // Validate file
            const validTypes = ['text/csv', 'application/csv', 'application/vnd.ms-excel', 
                               'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (file.size > maxSize) {
                alert('File too large. Maximum size is 5MB.');
                return;
            }

            // Show file info
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = `Size: ${(file.size / 1024).toFixed(2)} KB`;
            document.getElementById('fileType').textContent = `Type: ${file.type}`;
            fileInfo.style.display = 'block';
            uploadBtn.disabled = false;
        }

        function processUpload() {
            if (!selectedFile) return;

            const formData = new FormData();
            formData.append('bulk_file', selectedFile);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

            // Show progress
            progressContainer.style.display = 'block';
            uploadBtn.disabled = true;
            
            // Start upload
            fetch('bulk_upload_users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                uploadResults = data;
                displayResults(data);
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert('Upload failed: ' + error.message);
            })
            .finally(() => {
                progressContainer.style.display = 'none';
                uploadBtn.disabled = false;
            });

            // Simulate progress (for visual feedback)
            simulateProgress();
        }

        function simulateProgress() {
            let progress = 0;
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                
                progressFill.style.width = progress + '%';
                
                if (progress < 30) {
                    progressText.textContent = 'Reading file...';
                } else if (progress < 60) {
                    progressText.textContent = 'Validating data...';
                } else {
                    progressText.textContent = 'Creating users...';
                }
                
                if (uploadResults) {
                    progressFill.style.width = '100%';
                    progressText.textContent = 'Complete!';
                    clearInterval(interval);
                }
            }, 200);
        }

        function displayResults(results) {
            const statsGrid = document.getElementById('statsGrid');
            
            // Create stats cards
            statsGrid.innerHTML = `
                <div class="stat-card success">
                    <h3>${results.successful}</h3>
                    <p><i class="fas fa-check-circle"></i> Users Created</p>
                </div>
                <div class="stat-card error">
                    <h3>${results.failed}</h3>
                    <p><i class="fas fa-times-circle"></i> Failed</p>
                </div>
                <div class="stat-card warning">
                    <h3>${results.warnings || 0}</h3>
                    <p><i class="fas fa-exclamation-triangle"></i> Warnings</p>
                </div>
                <div class="stat-card info">
                    <h3>${results.total}</h3>
                    <p><i class="fas fa-list"></i> Total Processed</p>
                </div>
            `;

            // Show success list
            if (results.success_list && results.success_list.length > 0) {
                const successList = document.getElementById('successList');
                const successItems = document.getElementById('successItems');
                successItems.innerHTML = results.success_list.map(item => `<li>${item}</li>`).join('');
                successList.style.display = 'block';
            }

            // Show error list
            if (results.errors && results.errors.length > 0) {
                const errorList = document.getElementById('errorList');
                const errorItems = document.getElementById('errorItems');
                errorItems.innerHTML = results.errors.map(error => `<li>${error}</li>`).join('');
                errorList.style.display = 'block';
            }

            resultsSection.style.display = 'block';
            resultsSection.scrollIntoView({ behavior: 'smooth' });
        }

        function clearSelection() {
            selectedFile = null;
            uploadResults = null;
            fileInput.value = '';
            fileInfo.style.display = 'none';
            progressContainer.style.display = 'none';
            document.getElementById('progressFill').style.width = '0%';
            uploadBtn.disabled = true;
            resultsSection.style.display = 'none';
            
            // Clear results
            document.getElementById('successList').style.display = 'none';
            document.getElementById('errorList').style.display = 'none';
        }

        function downloadTemplate(type) {
            if (type === 'csv') {
                // Create CSV content
                const headers = ['username', 'email', 'password', 'role', 'status'];
                const sampleData = [
                    ['John Doe', 'john.doe@example.com', 'password123', 'student', 'active'],
                    ['Jane Smith', 'jane.smith@example.com', 'securepass456', 'staff', 'pending'],
                    ['Mike Johnson', 'mike.j@example.com', 'mypassword789', 'student', 'active'],
                    ['Sarah Wilson', 'sarah.w@example.com', 'adminpass000', 'admin', 'active'],
                    ['Tom Brown', 'tom.brown@example.com', 'parentpass111', 'parent', 'pending']
                ];

                let csvContent = headers.join(',') + '\n';
                sampleData.forEach(row => {
                    csvContent += row.map(field => `"${field}"`).join(',') + '\n';
                });

                // Download CSV
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'bulk_users_template.csv';
                link.click();
                URL.revokeObjectURL(link.href);
                
            } else if (type === 'excel') {
                // For Excel, you could either:
                // 1. Use a library like SheetJS to generate real Excel files
                // 2. Or redirect to a PHP script that generates Excel
                window.open('bulk_upload_users.php?action=download_template&format=excel', '_blank');
            }
        }

        function exportResults() {
            if (!uploadResults) {
                alert('No results to export');
                return;
            }

            // Create CSV content with results
            let csvContent = 'Upload Results Summary\n';
            csvContent += `Total Processed,${uploadResults.total}\n`;
            csvContent += `Successful,${uploadResults.successful}\n`;
            csvContent += `Failed,${uploadResults.failed}\n`;
            csvContent += `Warnings,${uploadResults.warnings || 0}\n\n`;

            if (uploadResults.success_list && uploadResults.success_list.length > 0) {
                csvContent += 'Successfully Created Users\n';
                uploadResults.success_list.forEach(user => {
                    csvContent += `"${user}"\n`;
                });
                csvContent += '\n';
            }

            if (uploadResults.errors && uploadResults.errors.length > 0) {
                csvContent += 'Errors\n';
                uploadResults.errors.forEach(error => {
                    csvContent += `"${error}"\n`;
                });
            }

            // Download results
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `bulk_upload_results_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        }

        // Auto-refresh CSRF token periodically (optional security enhancement)
        setInterval(() => {
            fetch('refresh_csrf.php')
                .then(response => response.json())
                .then(data => {
                    if (data.csrf_token) {
                        // Update any forms with new token if needed
                        console.log('CSRF token refreshed');
                    }
                })
                .catch(error => console.log('CSRF refresh failed:', error));
        }, 300000); // Refresh every 5 minutes
    </script>
</body>
</html>