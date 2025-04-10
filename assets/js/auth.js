document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            
            this.innerHTML = type === 'password' 
                ? '<i class="fas fa-eye"></i>' 
                : '<i class="fas fa-eye-slash"></i>';
                
            this.setAttribute('aria-label', 
                type === 'password' ? 'Show password' : 'Hide password');
        });
    });

    // Form validation
    const form = document.querySelector('.auth-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = form.querySelector('#password');
            const confirmPassword = form.querySelector('#confirm_password');
            
            if (password.value.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters');
                return;
            }
            
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    }
});