    </div>

    <!-- Footer -->
    <footer class="footer text-center py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2">
                        <i class="bi bi-calendar-check"></i> FMS
                    </p>
                    <small class="text-white-50">
                        Faculty Management System © <?php echo date('Y'); ?>
                    </small>
                </div>
                <div class="col-md-6">
                    <p class="mb-2">
                        <i class="bi bi-shield-check"></i> Secure Admin Access
                    </p>
                    <small class="text-white-50">
                        Powered by <i class="bi bi-google"></i> Firebase Authentication
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Initialize Bootstrap dropdowns -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>
</body>
</html>
