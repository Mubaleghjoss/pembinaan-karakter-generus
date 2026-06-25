/**
 * Global CSRF Token Handler
 * Prevents 419 Page Expired errors by auto-refreshing CSRF tokens
 */

(function () {
    'use strict';

    // Auto-refresh CSRF token every 10 minutes
    setInterval(async function () {
        try {
            const response = await fetch('/csrf-token');
            const data = await response.json();
            if (data.token) {
                // Update meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', data.token);
                }

                // Update all CSRF input fields
                document.querySelectorAll('input[name="_token"]').forEach(input => {
                    input.value = data.token;
                });

                console.log('CSRF token refreshed');
            }
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
        }
    }, 600000); // 10 minutes

    // Handle all form submissions with CSRF retry (except login forms which have their own handlers)
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(form => {
            // Skip forms that already have custom handlers or are login forms
            const formAction = form.getAttribute('action') || '';
            if (form.hasAttribute('data-no-csrf-handler') ||
                formAction.includes('/login') ||
                formAction.includes('/logout')) {
                return;
            }

            form.addEventListener('submit', async function (e) {
                // Skip if form is already being processed
                if (form.hasAttribute('data-submitting')) {
                    return;
                }

                e.preventDefault();
                form.setAttribute('data-submitting', 'true');

                const submitAction = form.getAttribute('action') || window.location.href;
                const formData = new FormData(form);
                const submitButton = form.querySelector('button[type="submit"]');

                // Disable duplicate submissions without changing visible button text.
                if (submitButton) {
                    submitButton.disabled = true;
                }

                // Refresh token before submitting
                try {
                    const tokenResponse = await fetch('/csrf-token');
                    const tokenData = await tokenResponse.json();

                    if (tokenData.token) {
                        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', tokenData.token);
                        formData.set('_token', tokenData.token);
                    }
                } catch (error) {
                    console.error('Failed to refresh token before submit:', error);
                }

                try {
                    const response = await fetch(submitAction, {
                        method: form.method || 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        redirect: 'manual'
                    });

                    if (response.status === 419) {
                        console.log('CSRF token expired, refreshing and retrying...');

                        // Get new CSRF token
                        const tokenResponse = await fetch('/csrf-token');
                        const tokenData = await tokenResponse.json();

                        if (tokenData.token) {
                            // Update meta tag
                            const metaTag = document.querySelector('meta[name="csrf-token"]');
                            if (metaTag) {
                                metaTag.setAttribute('content', tokenData.token);
                            }

                            // Update form data with new token
                            formData.set('_token', tokenData.token);

                            // Retry submission
                            const retryResponse = await fetch(submitAction, {
                                method: form.method || 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                redirect: 'manual'
                            });

                            handleResponse(retryResponse, form);
                        } else {
                            throw new Error('Failed to get new CSRF token');
                        }
                    } else {
                        handleResponse(response, form);
                    }
                } catch (error) {
                    console.error('Form submission error:', error);

                    // Re-enable button
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                    form.removeAttribute('data-submitting');

                    // Fallback to normal form submission
                    form.submit();
                }
            });
        });
    });

    function handleResponse(response, form) {
        const submitButton = form.querySelector('button[type="submit"]');

        if (response.type === 'opaqueredirect' || response.status === 0) {
            // Redirect happened
            window.location.reload();
        } else if (response.redirected) {
            window.location.href = response.url;
        } else if (response.status >= 200 && response.status < 300) {
            // Success - reload or redirect
            window.location.reload();
        } else {
            // Error - submit form normally to show validation errors
            form.removeAttribute('data-submitting');
            if (submitButton) {
                submitButton.disabled = false;
            }
            form.submit();
        }
    }
})();
