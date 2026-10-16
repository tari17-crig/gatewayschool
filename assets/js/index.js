// Mobile Menu Toggle
function toggleMenu() {
    const navLinks = document.querySelector('.nav-links');
    navLinks.classList.toggle('active');
}

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        const navLinks = document.querySelector('.nav-links');
        navLinks.classList.remove('active');
    });
});

// Hero Slider
let slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) {
    showSlides(slideIndex += n);
}

function currentSlide(n) {
    showSlides(slideIndex = n);
}

function showSlides(n) {
    let slides = document.getElementsByClassName("slide");
    let dots = document.getElementsByClassName("dot");
    
    if (n > slides.length) {slideIndex = 1}
    if (n < 1) {slideIndex = slides.length}
    
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.opacity = "0";
        slides[i].style.zIndex = "0";
    }
    
    for (let i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }
    
    slides[slideIndex-1].style.opacity = "1";
    slides[slideIndex-1].style.zIndex = "1";
    dots[slideIndex-1].className += " active";
}

// Auto slide change
let slideInterval = setInterval(() => {
    plusSlides(1);
}, 5000);

// Pause on hover
const slider = document.querySelector('.hero-slider');
slider.addEventListener('mouseenter', () => {
    clearInterval(slideInterval);
});

slider.addEventListener('mouseleave', () => {
    slideInterval = setInterval(() => {
        plusSlides(1);
    }, 5000);
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

// Sticky header on scroll
window.addEventListener('scroll', function() {
    const header = document.querySelector('.navbar');
    header.classList.toggle('sticky', window.scrollY > 0);
});

// Enhanced form validation with real-time feedback
function enhanceFormValidation(form) {
    if (form.classList.contains('newsletter-form')) {
        return;
    }
    const inputs = form.querySelectorAll('input, select, textarea');

    // Add real-time validation
    inputs.forEach(input => {
        // Remove any existing error messages
        const existingError = input.parentElement.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        // Real-time validation
        input.addEventListener('input', () => {
            validateField(input);
        });

        // Show error on blur if invalid
        input.addEventListener('blur', () => {
            if (!input.checkValidity()) {
                validateField(input);
            }
        });
    });

    // Prevent submission if invalid
    form.addEventListener('submit', (e) => {
        let isValid = true;
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            // Show overall form error
            showFormError(form, 'Please correct the highlighted errors');
        }
    });

    // Prevent duplicate submissions
    form.addEventListener('submit', function(e) {
        if (form.dataset.submitted === 'true') {
            e.preventDefault();
            return;
        }
        form.dataset.submitted = 'true';
        const originalButtonText = form.querySelector('button[type="submit"]')?.textContent;
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
        }
    });
}

function validateField(input) {
    // Skip validation for hidden inputs or buttons
    if (input.type === 'hidden' || input.type === 'submit' || input.type === 'button') {
        return true;
    }

    const errorElement = input.parentElement.querySelector('.error-message') ||
                        createErrorElement(input.parentElement);

    if (input.checkValidity()) {
        errorElement.style.display = 'none';
        input.classList.remove('invalid');
        input.classList.add('valid');
        return true;
    } else {
        errorElement.textContent = getErrorMessage(input);
        errorElement.style.display = 'block';
        input.classList.add('invalid');
        input.classList.remove('valid');
        return false;
    }
}

function createErrorElement(parent) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = 'var(--color-error)';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '0.25rem';
    errorDiv.style.display = 'none';
    parent.appendChild(errorDiv);
    return errorDiv;
}

function getErrorMessage(input) {
    if (input.value === '') {
        if (input.hasAttribute('required')) {
            return `${getFieldName(input)} is required`;
        }
        return '';
    }

    if (input.type === 'email' && !input.value.includes('@')) {
        return 'Please enter a valid email address';
    }

    if (input.type === 'tel' && !/^\d+$/.replace(/[^\d+]/g, '').test(input.value)) {
        return 'Please enter a valid phone number';
    }

    if (input.type === 'number') {
        const min = parseInt(input.getAttribute('min')) || 0;
        const max = parseInt(input.getAttribute('max')) || 100;
        if (input.value < min) {
            return `Please enter a value greater than or equal to ${min}`;
        }
        if (input.value > max) {
            return `Please enter a value less than or equal to ${max}`;
        }
    }

    if (input.hasAttribute('minlength')) {
        const minLength = parseInt(input.getAttribute('minlength'));
        if (input.value.length < minLength) {
            return `${getFieldName(input)} must be at least ${minLength} characters long`;
        }
    }

    return input.validationMessage || 'Please correct this field';
}

function getFieldName(input) {
    const label = input.parentElement.querySelector('label');
    if (label) {
        return label.textContent.replace(/[*:\s]+/g, ' ').trim();
    }

    const ariaLabel = input.getAttribute('aria-label');
    if (ariaLabel) {
        return ariaLabel;
    }

    return input.name || 'Field';
}

function showFormError(form, message) {
    // Remove any existing form-level errors
    const existingFormError = form.querySelector('.form-error');
    if (existingFormError) {
        existingFormError.remove();
    }

    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.style.backgroundColor = 'rgba(244, 67, 54, 0.1)';
    errorDiv.style.border = '1px solid var(--color-error)';
    errorDiv.style.color = 'var(--color-error)';
    errorDiv.style.padding = '1rem';
    errorDiv.style.marginBottom = '1rem';
    errorDiv.style.borderRadius = 'var(--radius-md)';
    errorDiv.textContent = message;

    // Insert at the beginning of the form
    form.insertBefore(errorDiv, form.firstChild);
}

// Initialize form validation for all forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        enhanceFormValidation(form);
    });
});