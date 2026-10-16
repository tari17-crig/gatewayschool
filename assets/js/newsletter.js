/**
 * Gateway Education Services - Newsletter Subscription Handler
 * Provides AJAX submission, real-time validation, loading animations,
 * and accessible feedback states without reloading or jumping the page.
 */

document.addEventListener('DOMContentLoaded', function () {
    const newsletterForms = document.querySelectorAll('.newsletter-form');

    if (!newsletterForms.length) {
        return;
    }

    // Helper: Validate email format
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email.trim());
    }

    // Helper: Show feedback message
    function showFeedback(feedbackEl, type, message) {
        if (!feedbackEl) return;
        feedbackEl.className = 'newsletter-feedback show ' + type;
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        feedbackEl.innerHTML = '<i class="fas ' + iconClass + '"></i><span>' + message + '</span>';
    }

    // Helper: Clear feedback message
    function clearFeedback(feedbackEl) {
        if (!feedbackEl) return;
        feedbackEl.className = 'newsletter-feedback';
        feedbackEl.innerHTML = '';
    }

    newsletterForms.forEach(function (form) {
        // Ensure feedback container exists
        let feedbackEl = form.querySelector('.newsletter-feedback');
        if (!feedbackEl) {
            feedbackEl = document.createElement('div');
            feedbackEl.className = 'newsletter-feedback';
            feedbackEl.setAttribute('role', 'alert');
            feedbackEl.setAttribute('aria-live', 'polite');
            form.appendChild(feedbackEl);
        }

        const input = form.querySelector('input[type="email"]');
        const submitBtn = form.querySelector('button[type="submit"]');

        if (!input || !submitBtn) {
            return;
        }

        // Real-time cleanup of error message as user types
        input.addEventListener('input', function () {
            if (feedbackEl.classList.contains('error')) {
                clearFeedback(feedbackEl);
            }
        });

        // Form submit handler
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            const emailVal = input.value.trim();

            if (!emailVal) {
                showFeedback(feedbackEl, 'error', 'Please enter your email address.');
                input.focus();
                return;
            }

            if (!isValidEmail(emailVal)) {
                showFeedback(feedbackEl, 'error', 'Please enter a valid email address.');
                input.focus();
                return;
            }

            // Save original button state
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            input.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Subscribing...</span>';
            clearFeedback(feedbackEl);

            try {
                const response = await fetch('newsletter.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ email: emailVal })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showFeedback(feedbackEl, 'success', data.message || 'Thank you for subscribing!');
                    input.value = '';
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> <span>Subscribed!</span>';
                    
                    // Reset button after 3.5 seconds
                    setTimeout(function () {
                        submitBtn.innerHTML = originalBtnHtml;
                        submitBtn.disabled = false;
                        input.disabled = false;
                    }, 3500);
                } else {
                    const errorMsg = (data && data.message) ? data.message : 'Subscription failed. Please try again.';
                    showFeedback(feedbackEl, 'error', errorMsg);
                    submitBtn.innerHTML = originalBtnHtml;
                    submitBtn.disabled = false;
                    input.disabled = false;
                }
            } catch (err) {
                console.error('Newsletter submission error:', err);
                showFeedback(feedbackEl, 'error', 'Unable to connect to the server. Please try again shortly.');
                submitBtn.innerHTML = originalBtnHtml;
                submitBtn.disabled = false;
                input.disabled = false;
            }
        });
    });

    // Check for query parameters if returning from a standard form submission fallback
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('newsletter')) {
        const status = urlParams.get('newsletter');
        const firstFeedback = document.querySelector('.newsletter-feedback');
        if (firstFeedback) {
            if (status === 'success') {
                showFeedback(firstFeedback, 'success', 'Thank you for subscribing to our newsletter!');
            } else if (status === 'error') {
                const msg = urlParams.get('msg') || 'Subscription failed. Please try again.';
                showFeedback(firstFeedback, 'error', msg);
            }
        }
    }
});
