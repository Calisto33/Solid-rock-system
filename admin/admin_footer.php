<?php
// admin_footer.php
?>
    </div> <script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        if (menuBtn && sidebar) {
            menuBtn.addEventListener('click', () => sidebar.classList.toggle('is-open'));
        }
    });
    </script>
</body>
</html>