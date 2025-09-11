<?php
    // This is includes/footer.php
?>
    </main> <!-- Closes the .main-content div -->
</div> <!-- Closes the .dashboard-wrapper div -->

<script>
    // Wait for the document to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Notification Dropdown Script (Existing) ---
        const notificationButton = document.querySelector('.icon-button');
        const dropdown = document.querySelector('.notification-dropdown');

        if (notificationButton && dropdown) {
            // Logic for notification dropdown...
        }

        // --- NEW: Responsive Sidebar Toggle Script ---
        const menuToggleBtn = document.getElementById('menu-toggle-btn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.overlay');

        if (menuToggleBtn && sidebar && overlay) {
            // Function to open the sidebar
            const openSidebar = () => {
                sidebar.classList.add('active');
                overlay.classList.add('active');
            };

            // Function to close the sidebar
            const closeSidebar = () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            };

            // Event listener for the hamburger button
            menuToggleBtn.addEventListener('click', function(event) {
                event.stopPropagation();
                if (sidebar.classList.contains('active')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            // Event listener to close sidebar when clicking the overlay
            overlay.addEventListener('click', closeSidebar);
            
            // Event listener to close sidebar when clicking outside of it on the main document
             document.addEventListener('click', function(event) {
                if (sidebar.classList.contains('active') && !sidebar.contains(event.target)) {
                   closeSidebar();
                }
            });
        }
    });
</script>
</body>
</html>
