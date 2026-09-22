/**
 * Finance Dashboard - Login & Registration JS
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- Toggle Password Visibility ---
    const togglePasswordBtns = document.querySelectorAll('.password-toggle');
    
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const input = btn.previousElementSibling;
            const icon = btn.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // --- Message Box Helper ---
    const messageBox = document.getElementById('messageBox');
    
    function showMessage(message, type = 'error') {
        if (!messageBox) return;
        
        messageBox.className = `message-box ${type}`;
        
        // Setup inner content with icon
        const iconClass = type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check';
        messageBox.innerHTML = `<i class="fa-solid ${iconClass}"></i><span>${message}</span>`;
        
        messageBox.style.display = 'flex';
        messageBox.style.opacity = '0';
        messageBox.style.transform = 'translateY(10px)';
        
        // Trigger reflow
        messageBox.offsetHeight;
        
        messageBox.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        messageBox.style.opacity = '1';
        messageBox.style.transform = 'translateY(0)';
    }

    function hideMessage() {
        if (!messageBox) return;
        messageBox.style.display = 'none';
    }

    // --- Login AJAX Form Submission ---
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideMessage();
            
            const email = loginForm.email.value.trim();
            const password = loginForm.password.value;
            const submitBtn = loginForm.querySelector('.btn-submit');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner');

            // Basic Validation
            if (!email || !password) {
                showMessage('Please fill in all fields.');
                return;
            }

            if (!validateEmail(email)) {
                showMessage('Please enter a valid email address.');
                return;
            }

            // Set loading state
            setLoading(true, submitBtn, btnText, spinner);

            try {
                const formData = new FormData(loginForm);
                const response = await fetch('auth/login.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1200);
                } else {
                    showMessage(data.message || 'An error occurred.');
                    setLoading(false, submitBtn, btnText, spinner);
                }
            } catch (err) {
                console.error(err);
                showMessage('Connection error. Please try again.');
                setLoading(false, submitBtn, btnText, spinner);
            }
        });
    }

    // --- Register AJAX Form Submission ---
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideMessage();

            const fullName = registerForm.full_name.value.trim();
            const email = registerForm.email.value.trim();
            const password = registerForm.password.value;
            const confirmPassword = registerForm.confirm_password.value;
            const submitBtn = registerForm.querySelector('.btn-submit');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner');

            // Validation checks
            if (!fullName || !email || !password || !confirmPassword) {
                showMessage('Please fill in all fields.');
                return;
            }

            if (fullName.length < 2) {
                showMessage('Name must be at least 2 characters.');
                return;
            }

            if (!validateEmail(email)) {
                showMessage('Please enter a valid email address.');
                return;
            }

            if (password.length < 6) {
                showMessage('Password must be at least 6 characters.');
                return;
            }

            if (password !== confirmPassword) {
                showMessage('Passwords do not match.');
                return;
            }

            // Set loading state
            setLoading(true, submitBtn, btnText, spinner);

            try {
                const formData = new FormData(registerForm);
                const response = await fetch('auth/register.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    showMessage(data.message || 'An error occurred.');
                    setLoading(false, submitBtn, btnText, spinner);
                }
            } catch (err) {
                console.error(err);
                showMessage('Connection error. Please try again.');
                setLoading(false, submitBtn, btnText, spinner);
            }
        });
    }

    // Helper functions
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function setLoading(isLoading, button, textEl, spinnerEl) {
        if (isLoading) {
            button.disabled = true;
            if (textEl) textEl.style.opacity = '0.5';
            if (spinnerEl) spinnerEl.style.display = 'block';
        } else {
            button.disabled = false;
            if (textEl) textEl.style.opacity = '1';
            if (spinnerEl) spinnerEl.style.display = 'none';
        }
    }
});
