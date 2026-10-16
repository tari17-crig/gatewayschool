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

// Initialize form validation for all forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        enhanceFormValidation(form);
    });
});

// Gallery Filter Functionality
document.addEventListener('DOMContentLoaded', function() {
  // Filter buttons
  const filterButtons = document.querySelectorAll('.filter-btn');
  const galleryItems = document.querySelectorAll('.gallery-item');

  filterButtons.forEach(button => {
      button.addEventListener('click', function() {
          // Remove active class from all buttons
          filterButtons.forEach(btn => btn.classList.remove('active'));
          // Add active class to clicked button
          this.classList.add('active');

          const filterValue = this.getAttribute('data-filter');

          galleryItems.forEach(item => {
              if (filterValue === 'all' || item.getAttribute('data-category') === filterValue) {
                  item.style.display = 'block';
              } else {
                  item.style.display = 'none';
              }
          });
      });
  });

  // Lightbox functionality
  const lightboxModal = document.querySelector('.lightbox-modal');
  const lightboxImg = document.getElementById('lightbox-img');
  const lightboxCaption = document.querySelector('.lightbox-caption');
  const closeLightbox = document.querySelector('.close-lightbox');
  const prevBtn = document.querySelector('.prev');
  const nextBtn = document.querySelector('.next');

  let currentIndex = 0;
  const galleryImages = Array.from(document.querySelectorAll('.gallery-img'));
  const galleryCaptions = Array.from(document.querySelectorAll('.gallery-overlay'));

  // Open lightbox when gallery image is clicked
  galleryItems.forEach((item, index) => {
      item.addEventListener('click', function() {
          currentIndex = index;
          updateLightbox();
          lightboxModal.style.display = 'block';
          document.body.style.overflow = 'hidden';
      });
  });

  // Close lightbox
  closeLightbox.addEventListener('click', function() {
      lightboxModal.style.display = 'none';
      document.body.style.overflow = 'auto';
  });

  // Close when clicking outside the image
  lightboxModal.addEventListener('click', function(e) {
      if (e.target === lightboxModal) {
          lightboxModal.style.display = 'none';
          document.body.style.overflow = 'auto';
      }
  });

  // Navigation between images
  prevBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
      updateLightbox();
  });

  nextBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      currentIndex = (currentIndex + 1) % galleryImages.length;
      updateLightbox();
  });

  // Keyboard navigation
  document.addEventListener('keydown', function(e) {
      if (lightboxModal.style.display === 'block') {
          if (e.key === 'Escape') {
              lightboxModal.style.display = 'none';
              document.body.style.overflow = 'auto';
          } else if (e.key === 'ArrowLeft') {
              currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
              updateLightbox();
          } else if (e.key === 'ArrowRight') {
              currentIndex = (currentIndex + 1) % galleryImages.length;
              updateLightbox();
          }
      }
  });

  function updateLightbox() {
      const imgSrc = galleryImages[currentIndex].getAttribute('src');
      const caption = galleryCaptions[currentIndex].querySelector('h3').textContent + 
                     ' - ' + 
                     galleryCaptions[currentIndex].querySelector('p').textContent;

      lightboxImg.setAttribute('src', imgSrc);
      lightboxCaption.textContent = caption;
  }
});