<script>
        // You can put any shared JavaScript here, like the sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggle-sidebar');
            const sidebar = document.getElementById('sidebar');
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('sidebar-active'); // A class for mobile view toggle
                });
            }
        });
    </script>
</body>
</html>