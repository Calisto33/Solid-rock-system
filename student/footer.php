    </main> <!-- This closes the .main-content div opened in header.php -->

    <script>
        // Simple JavaScript for the mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');

            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });

                // Optional: Close sidebar when clicking outside of it on mobile
                document.addEventListener('click', function(event) {
                    if (sidebar.classList.contains('active') && !sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                        sidebar.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>
