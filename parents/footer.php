<?php
    // This is includes/footer.php
?>
    </div> <script>
        // Example: Script to toggle notification dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const notificationButton = document.querySelector('.icon-button');
            const dropdown = document.querySelector('.notification-dropdown');

            if (notificationButton && dropdown) {
                notificationButton.addEventListener('click', function(event) {
                    event.stopPropagation();
                    dropdown.classList.toggle('active');
                });

                document.addEventListener('click', function() {
                    dropdown.classList.remove('active');
                });
                
                dropdown.addEventListener('click', function(event) {
                    event.stopPropagation();
                });
            }
        });
    </script>
</body>
</html>