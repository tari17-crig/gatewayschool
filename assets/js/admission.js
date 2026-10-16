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

    // Prevent submission if invalid (for standard forms without custom submit listeners)
    if (!form.classList.contains('admission-form') && !form.classList.contains('contact-form')) {
        form.addEventListener('submit', (e) => {
            let isValid = true;
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                showFormError(form, 'Please correct the highlighted errors');
            }
        });
    }
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

    if (input.type === 'tel' && !/^\d+$/.test(input.value.replace(/[^\d+]/g, ''))) {
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

// Initialize form validation and submission handling
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        enhanceFormValidation(form);
    });

    const admissionForm = document.querySelector('.admission-form');
    if (admissionForm) {
        admissionForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validate fields first
            let isValid = true;
            admissionForm.querySelectorAll('input, select, textarea').forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                showFormError(admissionForm, 'Please correct the highlighted errors before submitting.');
                return;
            }

            const submitBtn = admissionForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Submit Application';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Application...';
            }

            const formData = new FormData(admissionForm);
            const payload = {
                surname: formData.get('surname') || '',
                firstname: formData.get('firstname') || '',
                dateofbirth: formData.get('dateofbirth') || null,
                age: formData.get('age') ? parseInt(formData.get('age')) : null,
                classapplied: formData.get('classapplied') || '',
                formerschool: formData.get('formerschool') || '',
                parentname: formData.get('parentname') || '',
                nationality: formData.get('nationality') || '',
                phone: formData.get('phone') || '',
                email: formData.get('email') || '',
                message: formData.get('message') || '',
                submission_date: new Date().toISOString()
            };

            let submitted = false;

            // 1. Try PHP backend if running on PHP server
            try {
                const res = await fetch('addmission.php', {
                    method: 'POST',
                    body: formData
                });
                const contentType = res.headers.get('content-type') || '';
                if (res.ok && !contentType.includes('octet-stream')) {
                    submitted = true;
                    window.location.href = 'admission-thank-you.html';
                    return;
                }
            } catch (phpErr) {
                console.warn('PHP backend unavailable, submitting directly to Supabase cloud:', phpErr);
            }

            // 2. Direct Supabase fallback (for static hosts like Render Static Sites)
            if (!submitted) {
                try {
                    const sbRes = await fetch('https://qtvmyjgmgmizeglrunjn.supabase.co/rest/v1/admissions', {
                        method: 'POST',
                        headers: {
                            'apikey': 'sb_publishable_CBayUb6GBwKIuKKgDQRW7g_dUVjxmfr',
                            'Authorization': 'Bearer sb_publishable_CBayUb6GBwKIuKKgDQRW7g_dUVjxmfr',
                            'Content-Type': 'application/json',
                            'Prefer': 'return=minimal'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (sbRes.ok || sbRes.status === 201) {
                        submitted = true;
                        window.location.href = 'admission-thank-you.html';
                        return;
                    }
                } catch (sbErr) {
                    console.warn('Admission Supabase insert notice:', sbErr);
                }
            }

            // 3. Static host local backup fallback
            if (!submitted) {
                try {
                    const stored = JSON.parse(localStorage.getItem('gw_admissions') || '[]');
                    stored.push(payload);
                    localStorage.setItem('gw_admissions', JSON.stringify(stored));
                } catch (storageErr) {}
                window.location.href = 'admission-thank-you.html';
            }
        });
    }
});