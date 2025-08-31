// Handle student_id column if it exists
            if (in_array('student_id', $parentsColumns)) {
                // Check if student_id is required (NOT NULL)
                $studentIdInfo = null;
                $studentIdColumnsInfo = $conn->query("SHOW COLUMNS FROM parents WHERE Field = 'student_id'");
                if ($studentIdColumnsInfo && $studentIdColumnsInfo->num_rows > 0) {
                    $studentIdInfo = $studentIdColumnsInfo->fetch_assoc();
                }
                
                if ($studentIdInfo && $studentIdInfo['Null'] === 'NO') {
                    // student_id is required
                    if (!empty($student_ids)) {
                        // Use the first selected student
                        $parentColumns[] = 'student_id';
                        $parentValues[] = '?';
                        $parentParams[] = $student_ids[0];
                        $parentTypes .= 's'; // varchar
                    } else {
                        // No student selected but field is required
                        // Try to use a placeholder value or get any existing student
                        $anyStudentResult = $conn->query("SELECT student_id FROM students LIMIT 1");
                        if ($anyStudentResult && $anyStudentResult->num_rows > 0) {
                            $anyStudent = $anyStudentResult->fetch_assoc();
                            $parentColumns[] = 'student_id';
                            $parentValues[] = '?';
                            $parentParams[] = $anyStudent['student_id'];
                            $parentTypes .= 's';
                            
                            // Note: This creates a temporary assignment that should be updated later
                            error_log("Warning: Assigned parent to student {$anyStudent['student_id']} temporarily due to required student_id field");
                        } else {
                            throw new Exception("Cannot create parent: student_id is required but no students exist in the database");
                        }
                    }
                } else {
                    // student_id is optional, only add if student is selected
                    if (!empty($student_ids)) {
                        $parentColumns[] = 'student_id';
                        $parentValues[] = '?';
                        $parentParams[] = $student_ids[0];
                        $parentTypes .= 's';
                    }
                }
            }<?php
session_start();
include '../config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check what tables and columns exist
    $usersTableExists = $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0;
    $parentsTableExists = $conn->query("SHOW TABLES LIKE 'parents'")->num_rows > 0;
    
    $hasUsersTable = $_POST['has_users_table'] == '1';
    $canCreateParent = $_POST['can_create_parent'] == '1';
    
    // Get what columns exist in each table
    $usersColumns = [];
    $parentsColumns = [];
    
    if ($usersTableExists) {
        $usersColumnsResult = $conn->query("SHOW COLUMNS FROM users");
        while ($row = $usersColumnsResult->fetch_assoc()) {
            $usersColumns[] = $row['Field'];
        }
    }
    
    if ($parentsTableExists) {
        $parentsColumnsResult = $conn->query("SHOW COLUMNS FROM parents");
        while ($row = $parentsColumnsResult->fetch_assoc()) {
            $parentsColumns[] = $row['Field'];
        }
    }
    
    // Get form data (only if fields were actually in the form)
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $relationship = isset($_POST['relationship']) ? $_POST['relationship'] : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $role = 'parent'; // Fixed role for parents
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];

    // Validate required fields based on what columns exist
    $requiredFields = [];
    $missingFields = [];
    
    if (in_array('username', $usersColumns) && empty($username)) $missingFields[] = 'Username';
    if (in_array('email', $usersColumns) && empty($email)) $missingFields[] = 'Email';
    if (in_array('password', $usersColumns) && empty($password)) $missingFields[] = 'Password';
    if (in_array('phone_number', $parentsColumns) && empty($phone_number)) $missingFields[] = 'Phone Number';
    if (in_array('relationship', $parentsColumns) && empty($relationship)) $missingFields[] = 'Relationship';
    if (in_array('address', $parentsColumns) && empty($address)) $missingFields[] = 'Address';
    
    if (!empty($missingFields)) {
        $_SESSION['error_message'] = "Required fields missing: " . implode(', ', $missingFields);
        header("Location: add_parent.php");
        exit();
    }

    // Validate email format if email field exists
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        header("Location: add_parent.php");
        exit();
    }

    // Validate password length if password field exists
    if (!empty($password) && strlen($password) < 6) {
        $_SESSION['error_message'] = "Password must be at least 6 characters long.";
        header("Location: add_parent.php");
        exit();
    }

<<<<<<< HEAD
    // Check if username already exists (using correct column name: id)
    $checkUsernameQuery = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($checkUsernameQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "Username already exists. Please choose a different username.";
=======
    // Check if username already exists (if users table exists)
    if ($usersTableExists && !empty($username)) {
        $userIdColumn = in_array('id', $usersColumns) ? 'id' : 'user_id';
        $checkUsernameQuery = "SELECT $userIdColumn FROM users WHERE username = ?";
        $stmt = $conn->prepare($checkUsernameQuery);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "Username already exists. Please choose a different username.";
            $stmt->close();
            header("Location: add_parent.php");
            exit();
        }
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
        $stmt->close();
    }

<<<<<<< HEAD
    // Check if email already exists (using correct column name: id)
    $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "Email already exists. Please use a different email address.";
=======
    // Check if email already exists (if users table exists)
    if ($usersTableExists && !empty($email)) {
        $userIdColumn = in_array('id', $usersColumns) ? 'id' : 'user_id';
        $checkEmailQuery = "SELECT $userIdColumn FROM users WHERE email = ?";
        $stmt = $conn->prepare($checkEmailQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "Email already exists. Please use a different email address.";
            $stmt->close();
            header("Location: add_parent.php");
            exit();
        }
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
        $stmt->close();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        $user_id = null;
        
        // Insert into users table if it exists and we have user data
        if ($usersTableExists && !empty($username)) {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Build dynamic insert query for users table
            $userColumns = [];
            $userValues = [];
            $userParams = [];
            $userTypes = '';
            
            if (in_array('username', $usersColumns)) {
                $userColumns[] = 'username';
                $userValues[] = '?';
                $userParams[] = $username;
                $userTypes .= 's';
            }
            
            if (in_array('email', $usersColumns)) {
                $userColumns[] = 'email';
                $userValues[] = '?';
                $userParams[] = $email;
                $userTypes .= 's';
            }
            
            if (in_array('password', $usersColumns)) {
                $userColumns[] = 'password';
                $userValues[] = '?';
                $userParams[] = $hashedPassword;
                $userTypes .= 's';
            }
            
            if (in_array('role', $usersColumns)) {
                $userColumns[] = 'role';
                $userValues[] = '?';
                $userParams[] = $role;
                $userTypes .= 's';
            }
            
            if (in_array('first_name', $usersColumns) && !empty($first_name)) {
                $userColumns[] = 'first_name';
                $userValues[] = '?';
                $userParams[] = $first_name;
                $userTypes .= 's';
            }
            
            if (in_array('last_name', $usersColumns) && !empty($last_name)) {
                $userColumns[] = 'last_name';
                $userValues[] = '?';
                $userParams[] = $last_name;
                $userTypes .= 's';
            }
            
            if (in_array('created_at', $usersColumns)) {
                $userColumns[] = 'created_at';
                $userValues[] = 'NOW()';
            }
            
            $userQuery = "INSERT INTO users (" . implode(', ', $userColumns) . ") VALUES (" . implode(', ', $userValues) . ")";
            $stmt = $conn->prepare($userQuery);
            
            if (!$stmt) {
                throw new Exception("Failed to prepare user query: " . $conn->error);
            }
            
            if (!empty($userParams)) {
                $stmt->bind_param($userTypes, ...$userParams);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create user account: " . $stmt->error);
            }
            
            $user_id = $conn->insert_id;
            $stmt->close();
        }

<<<<<<< HEAD
        // Insert into users table (with correct column structure)
        $userQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($userQuery);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare user query: " . $conn->error);
        }
        
        $stmt->bind_param("ssssss", $username, $email, $hashedPassword, $first_name, $last_name, $role);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user account: " . $stmt->error);
        }
        
        $user_id = $conn->insert_id;
        $stmt->close();

        // Insert into parents table (based on your actual structure)
        // Assuming parents table doesn't have first_name, last_name (they're in users table)
        $parentQuery = "INSERT INTO parents (user_id, phone_number, relationship, address, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($parentQuery);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare parent query: " . $conn->error);
        }
        
        $stmt->bind_param("isss", $user_id, $phone_number, $relationship, $address);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create parent record: " . $stmt->error);
        }
        
        $parent_id = $conn->insert_id;
        $stmt->close();

        // Insert student relationships if any students were selected
        $successful_assignments = 0;
        $failed_assignments = 0;
        
        if (!empty($student_ids)) {
            // Use correct table name: student_parent_relationships (not parent_student_relationships)
            // Based on your database structure shown earlier
            $relationshipQuery = "INSERT INTO student_parent_relationships (student_id, parent_id, relationship_type, is_primary_contact, is_emergency_contact, can_pick_up, created_at, created_by) VALUES (?, ?, ?, 0, 0, 1, NOW(), ?)";
            $stmt = $conn->prepare($relationshipQuery);
=======
        // Insert into parents table
        if ($parentsTableExists) {
            // Check if parent_id needs a value (not auto-increment)
            $parentIdInfo = null;
            $parentColumnsInfo = $conn->query("SHOW COLUMNS FROM parents WHERE Field = 'parent_id'");
            if ($parentColumnsInfo && $parentColumnsInfo->num_rows > 0) {
                $parentIdInfo = $parentColumnsInfo->fetch_assoc();
            }
            
            // Build dynamic insert query for parents table
            $parentColumns = [];
            $parentValues = [];
            $parentParams = [];
            $parentTypes = '';
            
            // Add parent_id if it's not auto-increment and doesn't have a default
            if ($parentIdInfo && 
                strpos($parentIdInfo['Extra'], 'auto_increment') === false && 
                $parentIdInfo['Default'] === null && 
                $parentIdInfo['Null'] === 'NO') {
                
                // Generate a new parent_id
                $maxIdResult = $conn->query("SELECT COALESCE(MAX(parent_id), 0) + 1 as next_id FROM parents");
                if ($maxIdResult) {
                    $nextId = $maxIdResult->fetch_assoc()['next_id'];
                    $parentColumns[] = 'parent_id';
                    $parentValues[] = '?';
                    $parentParams[] = $nextId;
                    $parentTypes .= 'i';
                }
            }
            
            // Add user_id if we created a user and the column exists
            if ($user_id && in_array('user_id', $parentsColumns)) {
                $parentColumns[] = 'user_id';
                $parentValues[] = '?';
                $parentParams[] = $user_id;
                $parentTypes .= 'i';
            }
            
            if (in_array('phone_number', $parentsColumns) && !empty($phone_number)) {
                $parentColumns[] = 'phone_number';
                $parentValues[] = '?';
                $parentParams[] = $phone_number;
                $parentTypes .= 's';
            }
            
            if (in_array('relationship', $parentsColumns) && !empty($relationship)) {
                $parentColumns[] = 'relationship';
                $parentValues[] = '?';
                $parentParams[] = $relationship;
                $parentTypes .= 's';
            }
            
            if (in_array('address', $parentsColumns) && !empty($address)) {
                $parentColumns[] = 'address';
                $parentValues[] = '?';
                $parentParams[] = $address;
                $parentTypes .= 's';
            }
            
            if (in_array('created_at', $parentsColumns)) {
                $parentColumns[] = 'created_at';
                $parentValues[] = 'NOW()';
            }
            
            // Only insert if we have at least one column to insert
            if (!empty($parentColumns)) {
                $parentQuery = "INSERT INTO parents (" . implode(', ', $parentColumns) . ") VALUES (" . implode(', ', $parentValues) . ")";
                $stmt = $conn->prepare($parentQuery);
                
                if (!$stmt) {
                    throw new Exception("Failed to prepare parent query: " . $conn->error);
                }
                
                if (!empty($parentParams)) {
                    $stmt->bind_param($parentTypes, ...$parentParams);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create parent record: " . $stmt->error);
                }
                
                $parent_id = $conn->insert_id;
                $stmt->close();
            } else {
                throw new Exception("No valid columns found for parents table insertion");
            }
        } else {
            throw new Exception("Parents table does not exist");
        }

        // Handle student assignments if any students were selected
        $successful_assignments = 0;
        $failed_assignments = 0;
        
        if (!empty($student_ids) && isset($parent_id)) {
            // Method 1: Try to use student_parent_relationships table if it exists
            $relationshipTableExists = $conn->query("SHOW TABLES LIKE 'student_parent_relationships'")->num_rows > 0;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
            
            if ($relationshipTableExists) {
                // Check what columns exist in the relationship table
                $relColumns = [];
                $relColumnsResult = $conn->query("SHOW COLUMNS FROM student_parent_relationships");
                while ($col = $relColumnsResult->fetch_assoc()) {
                    $relColumns[] = $col['Field'];
                }
                
<<<<<<< HEAD
                // Validate that the student exists (using correct column name from your students table)
                $checkStudentQuery = "SELECT student_id FROM students WHERE student_id = ?";
                $checkStmt = $conn->prepare($checkStudentQuery);
                $checkStmt->bind_param("i", $student_id);
                $checkStmt->execute();
                $studentResult = $checkStmt->get_result();
                
                if ($studentResult->num_rows > 0) {
                    $stmt->bind_param("sisi", $student_id, $parent_id, $relationship, $_SESSION['user_id']);
                    if ($stmt->execute()) {
                        $successful_assignments++;
=======
                foreach ($student_ids as $student_id) {
                    $student_id = trim($student_id);
                    
                    // Validate that the student exists
                    $checkStudentQuery = "SELECT student_id FROM students WHERE student_id = ?";
                    $checkStmt = $conn->prepare($checkStudentQuery);
                    $checkStmt->bind_param("s", $student_id);
                    $checkStmt->execute();
                    $studentResult = $checkStmt->get_result();
                    
                    if ($studentResult->num_rows > 0) {
                        // Build dynamic insert for relationship table
                        $relInsertColumns = [];
                        $relInsertValues = [];
                        $relInsertParams = [];
                        $relParamTypes = '';
                        
                        // Handle ID column if it needs manual generation
                        $hasIdColumn = in_array('id', $relColumns);
                        if ($hasIdColumn) {
                            $idInfo = $conn->query("SHOW COLUMNS FROM student_parent_relationships WHERE Field = 'id'")->fetch_assoc();
                            if ($idInfo && strpos($idInfo['Extra'], 'auto_increment') === false) {
                                $maxIdResult = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM student_parent_relationships");
                                if ($maxIdResult) {
                                    $nextId = $maxIdResult->fetch_assoc()['next_id'];
                                    $relInsertColumns[] = 'id';
                                    $relInsertValues[] = '?';
                                    $relInsertParams[] = $nextId;
                                    $relParamTypes .= 'i';
                                }
                            }
                        }
                        
                        if (in_array('student_id', $relColumns)) {
                            $relInsertColumns[] = 'student_id';
                            $relInsertValues[] = '?';
                            $relInsertParams[] = $student_id;
                            $relParamTypes .= 's';
                        }
                        
                        if (in_array('parent_id', $relColumns)) {
                            $relInsertColumns[] = 'parent_id';
                            $relInsertValues[] = '?';
                            $relInsertParams[] = $parent_id;
                            $relParamTypes .= 'i';
                        }
                        
                        if (in_array('relationship_type', $relColumns)) {
                            $relInsertColumns[] = 'relationship_type';
                            $relInsertValues[] = '?';
                            $relInsertParams[] = $relationship; // Use the relationship from form
                            $relParamTypes .= 's';
                        }
                        
                        if (in_array('created_at', $relColumns)) {
                            $relInsertColumns[] = 'created_at';
                            $relInsertValues[] = 'NOW()';
                        }
                        
                        if (!empty($relInsertColumns)) {
                            $relInsertQuery = "INSERT INTO student_parent_relationships (" . implode(', ', $relInsertColumns) . ") VALUES (" . implode(', ', $relInsertValues) . ")";
                            $relStmt = $conn->prepare($relInsertQuery);
                            
                            if ($relStmt) {
                                if (!empty($relInsertParams)) {
                                    $relStmt->bind_param($relParamTypes, ...$relInsertParams);
                                }
                                
                                if ($relStmt->execute()) {
                                    $successful_assignments++;
                                } else {
                                    $failed_assignments++;
                                    error_log("Failed to assign student ID $student_id to parent ID $parent_id in relationship table: " . $relStmt->error);
                                }
                                $relStmt->close();
                            } else {
                                $failed_assignments++;
                                error_log("Failed to prepare relationship query: " . $conn->error);
                            }
                        }
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                    } else {
                        $failed_assignments++;
                        error_log("Student ID $student_id does not exist");
                    }
                    $checkStmt->close();
                }
            }
            
            // Method 2: Also update the parents table directly if it has student_id column
            if (in_array('student_id', $parentsColumns) && !empty($student_ids)) {
                $firstStudentId = $student_ids[0]; // Use first selected student for direct assignment
                $updateParentQuery = "UPDATE parents SET student_id = ? WHERE parent_id = ?";
                $updateStmt = $conn->prepare($updateParentQuery);
                
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $firstStudentId, $parent_id);
                    if (!$updateStmt->execute()) {
                        error_log("Failed to update parent student_id: " . $updateStmt->error);
                    }
                    $updateStmt->close();
                }
            }
        }

        // Commit transaction
        $conn->commit();

        // Set success message
        $student_count = $successful_assignments;
        if ($student_count > 0) {
            if ($failed_assignments > 0) {
                $_SESSION['success_message'] = "Parent account created successfully! $student_count student(s) assigned ($failed_assignments failed).";
            } else {
                $_SESSION['success_message'] = "Parent account created successfully with $student_count student(s) assigned!";
            }
        } else {
            $_SESSION['success_message'] = "Parent account created successfully! You can assign students from the parent management page.";
        }
        
        header("Location: parents.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Log the error
        error_log("Parent registration failed: " . $e->getMessage());
        
        $_SESSION['error_message'] = "Failed to create parent account. Error: " . $e->getMessage();
        header("Location: add_parent.php");
        exit();
    }

} else {
    // If not a POST request, redirect to add parent page
    header("Location: add_parent.php");
    exit();
}
?>