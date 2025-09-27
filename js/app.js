/**
 * JavaScript per il sistema Gestione KM
 * Funzionalità comuni e utilities
 */

// Configurazione globale
const AppConfig = {
    animationDuration: 300,
    debounceTime: 300,
    maxKmDifference: 1000,
    previewDelay: 100
};

// Utility functions
const Utils = {
    // Debounce function per ottimizzare le performance
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Format number con separatori italiani
    formatNumber: function(number, decimals = 2) {
        return new Intl.NumberFormat('it-IT', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    },

    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    },

    // Mostra toast notification
    showToast: function(message, type = 'info') {
        // Crea toast dinamicamente se non esiste
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '1060';
            document.body.appendChild(toastContainer);
        }

        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = new bootstrap.Toast(document.getElementById(toastId), {
            autohide: true,
            delay: 4000
        });
        toastElement.show();

        // Rimuovi toast dal DOM dopo che è nascosto
        document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }
};

// Form validation handler
class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.errors = {};
        this.init();
    }

    init() {
        if (!this.form) return;
        
        // Bind events
        this.form.addEventListener('submit', this.handleSubmit.bind(this));
        
        // Add real-time validation
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', Utils.debounce(() => this.clearFieldError(input), AppConfig.debounceTime));
        });
    }

    handleSubmit(event) {
        event.preventDefault();
        event.stopPropagation();

        if (this.validateAll()) {
            this.showLoadingState();
            setTimeout(() => {
                this.form.submit();
            }, 500);
        }

        this.form.classList.add('was-validated');
    }

    validateAll() {
        let isValid = true;
        const inputs = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name || field.id;
        let isValid = true;

        // Clear previous errors
        this.clearFieldError(field);

        // Required validation
        if (field.hasAttribute('required') && !value) {
            this.setFieldError(field, 'Campo obbligatorio');
            isValid = false;
        }

        // Type-specific validation
        if (value && field.type === 'number') {
            const num = parseFloat(value);
            if (isNaN(num)) {
                this.setFieldError(field, 'Inserire un numero valido');
                isValid = false;
            } else if (field.min && num < parseFloat(field.min)) {
                this.setFieldError(field, `Valore minimo: ${field.min}`);
                isValid = false;
            } else if (field.max && num > parseFloat(field.max)) {
                this.setFieldError(field, `Valore massimo: ${field.max}`);
                isValid = false;
            }
        }

        // Email validation
        if (value && field.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                this.setFieldError(field, 'Inserire un indirizzo email valido');
                isValid = false;
            }
        }

        return isValid;
    }

    setFieldError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');

        // Find or create feedback element
        let feedback = field.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;

        this.errors[field.name || field.id] = message;
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback && !field.hasAttribute('data-keep-feedback')) {
            feedback.textContent = '';
        }
        delete this.errors[field.name || field.id];
    }

    showLoadingState() {
        const submitBtn = this.form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Caricamento...';
            submitBtn.disabled = true;
        }
    }
}

// Image preview handler
class ImagePreview {
    constructor(containerSelector = '#image-preview-container') {
        this.container = document.querySelector(containerSelector);
        this.image = this.container?.querySelector('img');
        this.init();
    }

    init() {
        if (!this.container) return;

        // Use event delegation for dynamic content
        document.addEventListener('mouseover', this.handleMouseOver.bind(this));
        document.addEventListener('mouseout', this.handleMouseOut.bind(this));
        document.addEventListener('mousemove', this.handleMouseMove.bind(this));
    }

    handleMouseOver(event) {
        const link = event.target.closest('[data-image-preview]');
        if (!link) return;

        const imageUrl = link.dataset.imagePreview || link.dataset.cedolinoUrl;
        if (imageUrl) {
            this.showPreview(imageUrl, event);
        }
    }

    handleMouseOut(event) {
        const link = event.target.closest('[data-image-preview]');
        if (link) {
            this.hidePreview();
        }
    }

    handleMouseMove(event) {
        if (this.container.style.display === 'block') {
            this.updatePosition(event);
        }
    }

    showPreview(imageUrl, event) {
        if (!this.image) return;

        this.image.src = imageUrl;
        this.container.style.display = 'block';
        this.updatePosition(event);

        // Animate in
        this.container.style.opacity = '0';
        this.container.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            this.container.style.opacity = '1';
            this.container.style.transform = 'scale(1)';
        }, AppConfig.previewDelay);
    }

    hidePreview() {
        this.container.style.opacity = '0';
        this.container.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            this.container.style.display = 'none';
        }, AppConfig.animationDuration);
    }

    updatePosition(event) {
        const offset = 15;
        const rect = this.container.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        let left = event.pageX + offset;
        let top = event.pageY + offset;

        // Adjust if preview would go off-screen
        if (left + rect.width > viewportWidth) {
            left = event.pageX - rect.width - offset;
        }
        if (top + rect.height > viewportHeight) {
            top = event.pageY - rect.height - offset;
        }

        this.container.style.left = Math.max(0, left) + 'px';
        this.container.style.top = Math.max(0, top) + 'px';
    }
}

// Consumption calculator
class ConsumptionCalculator {
    constructor() {
        this.kmInizialiInput = document.getElementById('chilometri_iniziali');
        this.kmFinaliInput = document.getElementById('chilometri_finali');
        this.litriInput = document.getElementById('litri_carburante');
        this.badge = null;
        this.init();
    }

    init() {
        if (!this.kmInizialiInput || !this.kmFinaliInput || !this.litriInput) return;

        [this.kmFinaliInput, this.litriInput].forEach(input => {
            input.addEventListener('input', Utils.debounce(() => this.calculate(), AppConfig.debounceTime));
        });
    }

    calculate() {
        const kmIniziali = parseFloat(this.kmInizialiInput.value) || 0;
        const kmFinali = parseFloat(this.kmFinaliInput.value) || 0;
        const litri = parseFloat(this.litriInput.value) || 0;
        
        const kmPercorsi = kmFinali - kmIniziali;
        
        if (kmPercorsi > 0 && litri > 0) {
            const consumo = (litri / kmPercorsi * 100);
            this.showConsumption(consumo);
        } else {
            this.hideConsumption();
        }
    }

    showConsumption(consumo) {
        if (!this.badge) {
            this.badge = this.createBadge();
        }

        const formattedConsumo = Utils.formatNumber(consumo, 2);
        this.badge.innerHTML = `<i class="bi bi-speedometer2 me-1"></i>Consumo: ${formattedConsumo} L/100km`;
        
        // Color coding based on consumption
        this.badge.className = consumo > 10 ? 'badge bg-danger ms-2' : 
                              consumo > 8 ? 'badge bg-warning text-dark ms-2' : 
                              'badge bg-success ms-2';
        
        this.badge.style.display = 'inline-block';
    }

    hideConsumption() {
        if (this.badge) {
            this.badge.style.display = 'none';
        }
    }

    createBadge() {
        const badge = document.createElement('span');
        badge.id = 'consumo-badge';
        badge.style.display = 'none';
        
        const label = document.querySelector('label[for="litri_carburante"]');
        if (label) {
            label.appendChild(badge);
        }
        
        return badge;
    }
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    const form = document.getElementById('inserimentoForm');
    if (form) {
        new FormValidator('inserimentoForm');
    }

    // Initialize image preview
    new ImagePreview();

    // Initialize consumption calculator
    new ConsumptionCalculator();

    // Add smooth animations
    const animatedElements = document.querySelectorAll('.slide-in');
    animatedElements.forEach((element, index) => {
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Initialize tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Expose utilities globally
window.AppUtils = Utils;
window.AppConfig = AppConfig;