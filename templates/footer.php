</main> <!-- Close main content from header -->

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h4>About</h4>
                <p>The University Magazine showcases outstanding student work across all faculties.</p>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>/guidelines.php">Submission Guidelines</a></li>
                    <li><a href="<?= BASE_URL ?>/archive.php">Past Issues</a></li>
                    <li><a href="<?= BASE_URL ?>/contact.php">Contact Editorial Team</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Connect</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> University Magazine. All rights reserved.</p>
            <div class="footer-links">
                <a href="<?= BASE_URL ?>/privacy.php">Privacy Policy</a>
                <a href="<?= BASE_URL ?>/terms.php">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        fetch('<?= BASE_URL ?>/includes/set_theme.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'theme=' + theme
        });
    }
    
    // Close alert buttons
    document.querySelectorAll('.close-alert').forEach(btn => {
        btn.addEventListener('click', function() {
            this.parentElement.remove();
        });
    });
</script>
</body>
</html>