</main>
    </div> <script>
    document.addEventListener('DOMContentLoaded', function() {
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const body = document.body;

        if (hamburgerMenu) {
            hamburgerMenu.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                // The body class is not needed if the sidebar is fixed and transforms
                // body.classList.toggle('sidebar-collapsed');
                overlay.classList.toggle('active');
                
                // Adjust main wrapper margin based on sidebar state
                const mainWrapper = document.querySelector('.main-wrapper');
                if (sidebar.classList.contains('active') || window.innerWidth > 992) {
                     if(window.innerWidth > 992) {
                        mainWrapper.style.marginLeft = '260px';
                     } else {
                        mainWrapper.style.marginLeft = '0';
                     }
                } else {
                    mainWrapper.style.marginLeft = '0';
                }
            });
        }
        
        if (overlay) {
             overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
             });
        }

        function adjustLayout() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
                document.querySelector('.main-wrapper').style.marginLeft = '0';
            } else {
                document.querySelector('.main-wrapper').style.marginLeft = '260px';
            }
        }

        window.addEventListener('resize', adjustLayout);

        // Initial check
        adjustLayout();
        
        // You can add page-specific Chart.js code on the actual pages
        // Or keep it here if it's on many pages
    });

document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
});
    </script>
</body>
</html>
<?php if(isset($conn)) { $conn->close(); } ?>