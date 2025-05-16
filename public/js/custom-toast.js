/**
 * Custom Toast Notification System
 * 
 * A more visually appealing notification system that uses Bootstrap toasts
 * with custom styling and animations.
 */

'use strict';

class ToastNotification {
    constructor(options = {}) {
        this.options = {
            position: options.position || 'top-right',
            autoHide: options.autoHide !== undefined ? options.autoHide : true,
            delay: options.delay || 5000,
            animation: options.animation !== undefined ? options.animation : true,
            closeButton: options.closeButton !== undefined ? options.closeButton : true,
            containerId: options.containerId || 'toast-container',
            maxToasts: options.maxToasts || 5
        };
        
        this.initialize();
    }
    
    initialize() {
        // Create container if it doesn't exist
        let container = document.getElementById(this.options.containerId);
        if (!container) {
            container = document.createElement('div');
            container.id = this.options.containerId;
            container.className = `toast-container position-fixed ${this.getPositionClasses()}`;
            container.style.zIndex = '1090';
            document.body.appendChild(container);
        }
        
        this.container = container;
    }
    
    getPositionClasses() {
        switch (this.options.position) {
            case 'top-left':
                return 'top-0 start-0 p-3';
            case 'top-center':
                return 'top-0 start-50 translate-middle-x p-3';
            case 'top-right':
                return 'top-0 end-0 p-3';
            case 'middle-left':
                return 'top-50 start-0 translate-middle-y p-3';
            case 'middle-center':
                return 'top-50 start-50 translate-middle p-3';
            case 'middle-right':
                return 'top-50 end-0 translate-middle-y p-3';
            case 'bottom-left':
                return 'bottom-0 start-0 p-3';
            case 'bottom-center':
                return 'bottom-0 start-50 translate-middle-x p-3';
            case 'bottom-right':
                return 'bottom-0 end-0 p-3';
            default:
                return 'top-0 end-0 p-3';
        }
    }
    
    show(options) {
        const { type = 'info', title, message, icon } = options;
        
        // Limit the number of toasts
        if (this.container.children.length >= this.options.maxToasts) {
            this.container.removeChild(this.container.firstChild);
        }
        
        // Create toast element
        const toastEl = document.createElement('div');
        toastEl.className = `toast toast-${type} overflow-hidden mt-2`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        if (this.options.animation) {
            toastEl.classList.add('fade', 'show');
        }
        
        // Create toast header
        let headerContent = '';
        if (title) {
            headerContent = `
                <div class="toast-header">
                    ${icon ? `<i class="${icon} me-2"></i>` : ''}
                    <strong class="me-auto">${title}</strong>
                    <small>Just now</small>
                    ${this.options.closeButton ? '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>' : ''}
                </div>
            `;
        }
        
        // Create toast body
        const bodyContent = `
            <div class="toast-body d-flex align-items-center">
                ${!title && icon ? `<i class="${icon} me-2 fs-5"></i>` : ''}
                <div>${message}</div>
            </div>
        `;
        
        // Set toast content
        toastEl.innerHTML = headerContent + bodyContent;
        
        // Add toast to container
        this.container.appendChild(toastEl);
        
        // Initialize Bootstrap toast
        const toast = new bootstrap.Toast(toastEl, {
            autohide: this.options.autoHide,
            delay: this.options.delay
        });
        
        // Show toast
        toast.show();
        
        // Return toast instance for further manipulation
        return toast;
    }
    
    success(options) {
        const defaults = {
            type: 'success',
            title: 'Success',
            icon: 'bx bx-check-circle'
        };
        return this.show({ ...defaults, ...options });
    }
    
    error(options) {
        const defaults = {
            type: 'danger',
            title: 'Error',
            icon: 'bx bx-error-circle'
        };
        return this.show({ ...defaults, ...options });
    }
    
    warning(options) {
        const defaults = {
            type: 'warning',
            title: 'Warning',
            icon: 'bx bx-error'
        };
        return this.show({ ...defaults, ...options });
    }
    
    info(options) {
        const defaults = {
            type: 'info',
            title: 'Information',
            icon: 'bx bx-info-circle'
        };
        return this.show({ ...defaults, ...options });
    }
}

// Create global instance
window.toastNotification = new ToastNotification();
