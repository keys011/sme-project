</div>
    </div>
    
    <?php if (!isset($hideFooter) || !$hideFooter): ?>
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> SMEPro Manager. All rights reserved.</p>
            <p style="font-size: 0.9rem; margin-top: 0.5rem; color: #aaa;">
                Version 1.0 | For Educational Purposes
            </p>
        </div>
    </footer>
    <?php endif; ?>
    
    <script>
        // Common JavaScript functions
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = message;
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => alertDiv.remove(), 300);
            }, 5000);
        }
        
        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        // Form validation helper
        function validateForm(formId, rules) {
            const form = document.getElementById(formId);
            if (!form) return true;
            
            let isValid = true;
            const errors = [];
            
            rules.forEach(rule => {
                const element = form.querySelector(rule.selector);
                if (element) {
                    const value = element.value.trim();
                    
                    if (rule.required && !value) {
                        errors.push(`${rule.name} is required`);
                        highlightError(element);
                        isValid = false;
                    }
                    
                    if (rule.minLength && value.length < rule.minLength) {
                        errors.push(`${rule.name} must be at least ${rule.minLength} characters`);
                        highlightError(element);
                        isValid = false;
                    }
                    
                    if (rule.type === 'email' && value && !isValidEmail(value)) {
                        errors.push(`Please enter a valid email address`);
                        highlightError(element);
                        isValid = false;
                    }
                    
                    if (rule.type === 'password' && value && !isValidPassword(value)) {
                        errors.push(`Password must contain at least 8 characters, one uppercase, one lowercase, and one number`);
                        highlightError(element);
                        isValid = false;
                    }
                    
                    if (rule.match) {
                        const matchElement = form.querySelector(rule.match);
                        if (matchElement && value !== matchElement.value.trim()) {
                            errors.push(`${rule.name} do not match`);
                            highlightError(element);
                            highlightError(matchElement);
                            isValid = false;
                        }
                    }
                }
            });
            
            if (!isValid && errors.length > 0) {
                showAlert(`<strong>Please fix the following errors:</strong><br>${errors.join('<br>')}`, 'error');
            }
            
            return isValid;
        }
        
        function highlightError(element) {
            element.style.borderColor = '#f56565';
            element.style.boxShadow = '0 0 0 3px rgba(245, 101, 101, 0.1)';
            
            setTimeout(() => {
                element.style.borderColor = '';
                element.style.boxShadow = '';
            }, 3000);
        }
        
        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
        
        function isValidPassword(password) {
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(password);
        }
    </script>
</body>
</html>