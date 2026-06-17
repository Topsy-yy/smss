/**
 * signatory-interactions.js
 * Enhances the legacy PHP Signatory pages with smooth animations, form handling, 
 * and interactive transitions to match the new vibrant CSS.
 */

document.addEventListener('DOMContentLoaded', () => {

    // 1. Staggered Animations for Tables and Form Elements
    // Targets application rows and form groups to animate them in sequentially
    const animatableElements = document.querySelectorAll('table.table tbody tr, .form-group, .row > div');
    
    animatableElements.forEach((el, index) => {
        el.classList.add('stagger-item');
        // Cap the delay so long tables don't take forever to load
        const delay = Math.min(index * 0.05, 1.2); 
        el.style.animationDelay = `${delay}s`;
    });

    // 2. Smooth Profile Edit Toggle (Overrides tempSigProfile.php)
    const showDivButton = document.getElementById('showDivButton');
    const editDiv = document.getElementById('editDiv');
    const displayDiv = document.getElementById('display');

    if (showDivButton && editDiv && displayDiv) {
        showDivButton.addEventListener('click', (e) => {
            e.preventDefault();
            
            displayDiv.style.transition = "opacity 0.3s ease";
            displayDiv.style.opacity = "0";
            
            setTimeout(() => {
                displayDiv.style.display = "none";
                
                editDiv.style.display = "block";
                editDiv.style.opacity = "0";
                editDiv.style.transform = "translateY(15px)";
                editDiv.style.transition = "all 0.4s cubic-bezier(0.4, 0, 0.2, 1)";
                
                void editDiv.offsetWidth; // Force reflow
                
                editDiv.style.opacity = "1";
                editDiv.style.transform = "translateY(0)";
            }, 300);
        });
    }

    // 3. Button Ripple Effect (Material Design style)
    const buttons = document.querySelectorAll('input[type="submit"], button, .button');
    
    buttons.forEach(btn => {
        // Skip disabled buttons
        if(btn.disabled || btn.style.display === 'none') return;

        btn.addEventListener('mousedown', function(e) {
            const rect = e.target.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.style.position = 'absolute';
            ripple.style.background = 'rgba(255, 255, 255, 0.5)';
            ripple.style.width = '120px';
            ripple.style.height = '120px';
            ripple.style.borderRadius = '50%';
            ripple.style.transform = 'translate(-50%, -50%) scale(0)';
            ripple.style.animation = 'sigRipple 0.6s linear';
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            ripple.style.pointerEvents = 'none';
            
            if (window.getComputedStyle(btn).position === 'static') {
                btn.style.position = 'relative';
                btn.style.overflow = 'hidden';
            }
            
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });

    // Add ripple animation keyframes dynamically
    if (!document.getElementById('sig-ripple-style')) {
        const style = document.createElement('style');
        style.id = 'sig-ripple-style';
        style.innerHTML = `
            @keyframes sigRipple {
                to { transform: translate(-50%, -50%) scale(3); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    // 4. Custom File Upload Feedback
    // Replaces the standard ugly file input text with a confirmation message when selected
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                const fileName = this.files[0].name;
                
                // Check if a custom label exists, otherwise create one
                let feedbackLabel = this.nextElementSibling;
                if (!feedbackLabel || !feedbackLabel.classList.contains('file-feedback')) {
                    feedbackLabel = document.createElement('div');
                    feedbackLabel.classList.add('file-feedback');
                    feedbackLabel.style.marginTop = '0.5rem';
                    feedbackLabel.style.fontSize = '0.9rem';
                    this.parentNode.insertBefore(feedbackLabel, this.nextSibling);
                }
                
                feedbackLabel.innerHTML = `
                    <span style="color: var(--sig-teal); font-weight: 600;">
                        ✓ Document Attached: ${fileName}
                    </span>`;
                this.style.borderColor = 'var(--sig-teal)';
                this.style.background = '#f0fdf4';
            }
        });
    });
});