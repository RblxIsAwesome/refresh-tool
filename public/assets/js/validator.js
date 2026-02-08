/**
 * Cookie Format Validator
 * 
 * Client-side validation for Roblox cookie format
 * 
 * @package RobloxRefresher
 * @version 1.0.0
 */

class CookieValidator {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            minLength: 1024,
            requiredPrefix: '_|WARNING:-DO-NOT-SHARE-THIS.--Sharing-this-will-allow-someone-to-log-in-as-you-and-to-steal-your-ROBUX-and-items.|_',
            showCounter: true,
            ...options
        };
        
        this.validationMessageElement = null;
        this.characterCounterElement = null;
        
        this.init();
    }
    
    init() {
        // Create validation message element
        const messageDiv = document.createElement('div');
        messageDiv.className = 'validation-message';
        messageDiv.style.display = 'none';
        this.input.parentNode.insertBefore(messageDiv, this.input.nextSibling);
        this.validationMessageElement = messageDiv;
        
        // Create character counter if enabled
        if (this.options.showCounter) {
            const counterDiv = document.createElement('div');
            counterDiv.className = 'character-counter';
            messageDiv.parentNode.insertBefore(counterDiv, messageDiv.nextSibling);
            this.characterCounterElement = counterDiv;
        }
        
        // Add event listeners
        this.input.addEventListener('input', () => this.validate());
        this.input.addEventListener('blur', () => this.validate());
        this.input.addEventListener('paste', (e) => {
            setTimeout(() => this.validate(), 10);
        });
    }
    
    validate() {
        const value = this.input.value.trim();
        
        // Update character counter
        if (this.characterCounterElement) {
            const length = value.length;
            this.characterCounterElement.textContent = `${length} / ${this.options.minLength} characters`;
            
            if (length === 0) {
                this.characterCounterElement.className = 'character-counter';
            } else if (length < this.options.minLength) {
                this.characterCounterElement.className = 'character-counter warning';
            } else {
                this.characterCounterElement.className = 'character-counter';
            }
        }
        
        // Don't validate if empty (let required attribute handle it)
        if (value.length === 0) {
            this.clearValidation();
            return true;
        }
        
        // Perform validation checks
        const errors = [];
        
        // Check for warning prefix
        if (!value.startsWith(this.options.requiredPrefix)) {
            errors.push('Missing required warning prefix');
        }
        
        // Check minimum length
        if (value.length < this.options.minLength) {
            errors.push(`Cookie too short (minimum ${this.options.minLength} characters)`);
        }
        
        // Check for invalid characters (basic check)
        const invalidChars = value.match(/[<>{}[\]\\]/g);
        if (invalidChars) {
            errors.push('Invalid characters detected');
        }
        
        // Check for common patterns that might indicate an error
        if (value.includes('ERROR') || value.includes('INVALID')) {
            errors.push('Cookie appears to be invalid');
        }
        
        // Show validation result
        if (errors.length > 0) {
            this.showError(errors[0]);
            return false;
        } else {
            this.showSuccess('Cookie format looks valid');
            return true;
        }
    }
    
    showError(message) {
        this.input.classList.remove('valid');
        this.input.classList.add('invalid');
        
        this.validationMessageElement.className = 'validation-message error';
        this.validationMessageElement.innerHTML = `<span>✕</span> ${message}`;
        this.validationMessageElement.style.display = 'flex';
    }
    
    showSuccess(message) {
        this.input.classList.remove('invalid');
        this.input.classList.add('valid');
        
        this.validationMessageElement.className = 'validation-message success';
        this.validationMessageElement.innerHTML = `<span>✓</span> ${message}`;
        this.validationMessageElement.style.display = 'flex';
    }
    
    clearValidation() {
        this.input.classList.remove('valid', 'invalid');
        this.validationMessageElement.style.display = 'none';
    }
    
    isValid() {
        return this.validate();
    }
}

// Utility functions
const CookieValidatorUtils = {
    /**
     * Show a toast notification
     */
    showToast(message, duration = 3000) {
        // Remove existing toast if any
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <span class="toast-icon">✓</span>
            <span class="toast-message">${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },
    
    /**
     * Add ripple effect to button
     */
    addRippleEffect(button) {
        button.classList.add('ripple-container');
        
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    },
    
    /**
     * Enhanced copy to clipboard with feedback
     */
    copyToClipboard(text, button) {
        return navigator.clipboard.writeText(text).then(() => {
            // Change button text and style
            const originalText = button.textContent;
            const originalBg = button.style.background;
            const originalColor = button.style.color;
            const originalBorder = button.style.borderColor;
            
            button.textContent = 'Copied!';
            button.style.background = 'rgba(41, 194, 127, 0.2)';
            button.style.color = '#29c27f';
            button.style.borderColor = 'rgba(41, 194, 127, 0.3)';
            
            // Show toast
            this.showToast('Copied to clipboard!');
            
            // Reset after delay
            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = originalBg;
                button.style.color = originalColor;
                button.style.borderColor = originalBorder;
            }, 2000);
            
            return true;
        }).catch(err => {
            console.error('Failed to copy:', err);
            this.showToast('Failed to copy to clipboard', 2000);
            return false;
        });
    }
};

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CookieValidator, CookieValidatorUtils };
}
