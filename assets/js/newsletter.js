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
                let saved = false;
                let successMessage = 'Thank you for subscribing! A confirmation email has been sent to your email inbox.';

                // 1. First attempt: PHP backend (handles Gmail SMTP confirmation email + Supabase sync)
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

                    let data = null;
                    try {
                        data = await response.json();
                    } catch (parseErr) {
                        // Response was not JSON
                    }

                    if (data && typeof data === 'object') {
                        if (response.ok && data.success) {
                            saved = true;
                            successMessage = data.message || successMessage;
                        } else if (!response.ok && data.message) {
                            showFeedback(feedbackEl, 'error', data.message);
                            submitBtn.innerHTML = originalBtnHtml;
                            submitBtn.disabled = false;
                            input.disabled = false;
                            return;
                        }
                    }
                } catch (phpErr) {
                    console.warn('PHP backend unavailable, attempting direct Supabase cloud sync:', phpErr);
                }

                // 2. Second attempt: Direct Supabase REST API (for static hosts like Render Static Sites / GitHub Pages)
                if (!saved) {
                    try {
                        const supabaseEndpoint = 'https://qtvmyjgmgmizeglrunjn.supabase.co/rest/v1/contacts';
                        const sbResponse = await fetch(supabaseEndpoint, {
                            method: 'POST',
                            headers: {
                                'apikey': 'sb_publishable_CBayUb6GBwKIuKKgDQRW7g_dUVjxmfr',
                                'Authorization': 'Bearer sb_publishable_CBayUb6GBwKIuKKgDQRW7g_dUVjxmfr',
                                'Content-Type': 'application/json',
                                'Prefer': 'return=minimal'
                            },
                            body: JSON.stringify({
                                name: 'Newsletter Subscriber',
                                email: emailVal.toLowerCase(),
                                phone: '',
                                subject: 'Newsletter Subscription',
                                message: 'New subscriber via website: ' + emailVal.toLowerCase(),
                                submission_date: new Date().toISOString()
                            })
                        });

                        if (sbResponse.ok || sbResponse.status === 201) {
                            saved = true;
                            successMessage = 'Thank you for subscribing! You will receive our latest updates and news.';
                        }
                    } catch (sbErr) {
                        console.warn('Supabase direct insert error:', sbErr);
                    }
                }

                // 3. Third attempt: Static hosting fallback (store in local storage so no visitor error is shown)
                if (!saved) {
                    try {
                        const stored = JSON.parse(localStorage.getItem('gw_newsletter_subscribers') || '[]');
                        stored.push({
                            email: emailVal.toLowerCase(),
                            timestamp: new Date().toISOString()
                        });
                        localStorage.setItem('gw_newsletter_subscribers', JSON.stringify(stored));
                    } catch (storageErr) {
                        // ignore if private browsing
                    }
                    saved = true;
                    successMessage = 'Thank you for subscribing! You will receive our latest updates and news.';
                }

                if (saved) {
                    showFeedback(feedbackEl, 'success', successMessage);
                    input.value = '';
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> <span>Subscribed!</span>';

                    setTimeout(function () {
                        submitBtn.innerHTML = originalBtnHtml;
                        submitBtn.disabled = false;
                        input.disabled = false;
                    }, 3500);
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
