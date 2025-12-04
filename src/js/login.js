// Login Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('senha');
    const submitBtn = document.querySelector('.login-btn');
    const btnText = document.querySelector('.btn-text');
    const loadingSpinner = document.querySelector('.loading-spinner');

    // Form validation
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function validateField(input, isValid) {
        const inputGroup = input.closest('.input-group');
        inputGroup.classList.remove('success', 'error');
        
        if (input.value.trim() !== '') {
            inputGroup.classList.add(isValid ? 'success' : 'error');
        }
    }

    // Real-time validation
    emailInput.addEventListener('blur', function() {
        validateField(this, validateEmail(this.value));
    });

    emailInput.addEventListener('input', function() {
        if (this.value.trim() === '') {
            const inputGroup = this.closest('.input-group');
            inputGroup.classList.remove('success', 'error');
        }
    });

    passwordInput.addEventListener('blur', function() {
        validateField(this, this.value.length >= 1);
    });

    passwordInput.addEventListener('input', function() {
        if (this.value.trim() === '') {
            const inputGroup = this.closest('.input-group');
            inputGroup.classList.remove('success', 'error');
        }
    });

    // Form submission with loading state
    form.addEventListener('submit', function(e) {
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();

        // Client-side validation
        let isValid = true;

        if (email === '') {
            validateField(emailInput, false);
            isValid = false;
        } else if (!validateEmail(email)) {
            validateField(emailInput, false);
            isValid = false;
        }

        if (password === '') {
            validateField(passwordInput, false);
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            return;
        }

        // Show loading state
        btnText.style.display = 'none';
        loadingSpinner.style.display = 'block';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.8';
        submitBtn.style.cursor = 'not-allowed';
    });

    // Input focus animations
    const inputs = document.querySelectorAll('.input-group input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    // Smooth error message display
    const errorMessage = document.querySelector('.error-message');
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.opacity = '1';
        }, 100);

        // Auto-hide error message after 5 seconds
        setTimeout(() => {
            errorMessage.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            errorMessage.style.opacity = '0';
            errorMessage.style.transform = 'translateY(-10px)';
            
            setTimeout(() => {
                if (errorMessage.parentNode) {
                    errorMessage.parentNode.removeChild(errorMessage);
                }
            }, 500);
        }, 5000);
    }

    // Add ripple effect to button
    submitBtn.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        `;
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });

    // Add CSS for ripple animation
    if (!document.querySelector('#ripple-styles')) {
        const style = document.createElement('style');
        style.id = 'ripple-styles';
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            .login-btn {
                position: relative;
                overflow: hidden;
            }
        `;
        document.head.appendChild(style);
    }
});

// Toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('senha');
    const toggleIcon = document.querySelector('.password-toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🙈';
        toggleIcon.style.transform = 'scale(1.1)';
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
        toggleIcon.style.transform = 'scale(1)';
    }
    
    // Brief animation
    toggleIcon.style.transition = 'transform 0.2s ease';
    setTimeout(() => {
        toggleIcon.style.transform = 'scale(1)';
    }, 200);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Enter key to submit form
    if (e.key === 'Enter' && !e.shiftKey) {
        const activeElement = document.activeElement;
        if (activeElement.tagName === 'INPUT') {
            e.preventDefault();
            document.querySelector('.login-btn').click();
        }
    }
    
    // Escape key to clear form
    if (e.key === 'Escape') {
        document.getElementById('email').value = '';
        document.getElementById('senha').value = '';
        
        // Remove validation states
        document.querySelectorAll('.input-group').forEach(group => {
            group.classList.remove('success', 'error', 'focused');
        });
        
        // Focus on email field
        document.getElementById('email').focus();
    }
});

// Auto-focus on email field when page loads
window.addEventListener('load', function() {
    setTimeout(() => {
        document.getElementById('email').focus();
    }, 500);
});