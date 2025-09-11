<?php
// You can add your session and security checks here later
?>
<h1>My Assignments</h1>

<?php
// Check if the success message is in the URL
if (isset($_GET['success']) && $_GET['success'] == 'submitted') {
    echo "<p style='color: green;'>Your assignment was submitted successfully!</p>";
}
?>

<p>The list of all assignments will go here.</p>