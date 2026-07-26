function base64UrlToBuffer(base64url) {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const binary = atob(base64 + padding);
    const buffer = new Uint8Array(binary.length);

    for (let index = 0; index < binary.length; index += 1) {
        buffer[index] = binary.charCodeAt(index);
    }

    return buffer.buffer;
}

function bufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';

    for (let index = 0; index < bytes.length; index += 1) {
        binary += String.fromCharCode(bytes[index]);
    }

    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

const csrfRefreshUrl = '/csrf-token';
const biometricChallengeExpiredMessage = 'Sesi biometrik sudah kedaluwarsa';
let csrfRefreshPromise = null;
let authSessionRecoveryMounted = false;
let authSessionRefreshTimer = null;
const activeBiometricLoginButtons = new WeakSet();

async function parseJsonResponse(response) {
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = payload.error || payload.message || 'Permintaan biometrik gagal.';
        const error = new Error(message);
        error.status = response.status;
        error.payload = payload;
        throw error;
    }

    return payload;
}

function updateCsrfToken(token) {
    if (!token) {
        return;
    }

    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
    document.querySelectorAll('input[name="_token"]').forEach((input) => {
        input.value = token;
    });
}

function currentCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function refreshCsrfToken() {
    if (csrfRefreshPromise) {
        return csrfRefreshPromise;
    }

    csrfRefreshPromise = fetch(csrfRefreshUrl, {
        method: 'GET',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        cache: 'no-store',
    })
        .then(parseJsonResponse)
        .then((payload) => {
            updateCsrfToken(payload.token);
            return payload.token || '';
        })
        .finally(() => {
            csrfRefreshPromise = null;
        });

    return csrfRefreshPromise;
}

async function fetchWithFreshCsrf(url, options = {}, { refreshBefore = false } = {}) {
    if (refreshBefore) {
        await refreshCsrfToken();
    }

    const request = {
        ...options,
        headers: new Headers(options.headers || {}),
        credentials: options.credentials || 'same-origin',
        cache: 'no-store',
    };

    request.headers.set('X-CSRF-TOKEN', currentCsrfToken());

    let response = await fetch(url, request);

    if (response.status !== 419) {
        return response;
    }

    await refreshCsrfToken();
    request.headers.set('X-CSRF-TOKEN', currentCsrfToken());
    response = await fetch(url, request);

    return response;
}

function normalizePublicKeyOptions(options) {
    return options.publicKey || options;
}

function toggleSupportState(visibleSelectors = [], hiddenSelectors = []) {
    visibleSelectors.forEach((selector) => {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.remove('hidden');
        }
    });

    hiddenSelectors.forEach((selector) => {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.add('hidden');
        }
    });
}

async function ensurePlatformAuthenticator({ showOnUnsupported = [], hideOnUnsupported = [] } = {}) {
    if (!window.PublicKeyCredential) {
        toggleSupportState(showOnUnsupported, hideOnUnsupported);
        return false;
    }

    try {
        const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        if (!available) {
            toggleSupportState(showOnUnsupported, hideOnUnsupported);
        }

        return available;
    } catch (error) {
        console.error('Biometric capability check failed', error);
        toggleSupportState(showOnUnsupported, hideOnUnsupported);
        return false;
    }
}

function setButtonBusy(button, html) {
    if (!button) {
        return () => {};
    }

    if (typeof button.__pkgBiometricInitialHtml !== 'string') {
        button.__pkgBiometricInitialHtml = button.innerHTML;
    }

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.dataset.biometricBusy = 'true';
    button.innerHTML = html;

    return () => resetBiometricButton(button);
}

function resetBiometricButton(button) {
    if (!button) {
        return;
    }

    button.disabled = false;
    button.removeAttribute('aria-busy');
    delete button.dataset.biometricBusy;

    if (typeof button.__pkgBiometricInitialHtml === 'string') {
        button.innerHTML = button.__pkgBiometricInitialHtml;
    }
}

function showBiometricDialog({
    type = 'info',
    title,
    text,
    html,
    confirmButtonColor,
    confirmButtonText = 'Mengerti',
}) {
    if (window.Swal) {
        return window.Swal.fire({
            icon: type,
            title,
            text,
            html,
            confirmButtonColor,
            confirmButtonText,
        });
    }

    const fallbackMessage = text || (typeof html === 'string' ? html.replace(/<[^>]+>/g, ' ').trim() : '');
    if (type === 'error' && window.showNotification) {
        window.showNotification(fallbackMessage || title || 'Terjadi kesalahan.', 'error');
        return Promise.resolve();
    }

    if ((type === 'success' || type === 'info') && window.showNotification) {
        window.showNotification(fallbackMessage || title || 'Selesai.', type === 'success' ? 'success' : 'info');
        return Promise.resolve();
    }

    window.alert([title, fallbackMessage].filter(Boolean).join('\n\n'));
    return Promise.resolve();
}

async function requestBiometricConfirmation({
    title,
    text,
    confirmText = 'Lanjutkan',
    cancelText = 'Batal',
    confirmButtonColor = '#EF4444',
}) {
    if (window.Swal) {
        const confirmation = await window.Swal.fire({
            title,
            text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor,
            cancelButtonColor: '#6B7280',
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
        });

        return confirmation.isConfirmed;
    }

    if (window.showConfirmation) {
        return window.showConfirmation(text || title || 'Lanjutkan aksi ini?', {
            title: title || 'Konfirmasi tindakan',
            confirmText,
            cancelText,
            tone: 'danger',
        });
    }

    return window.confirm(text || title || 'Lanjutkan aksi ini?');
}

async function registerDevice({
    button,
    optionsUrl,
    registerUrl,
    successMessage,
    errorMessage = 'Gagal mendaftarkan perangkat.',
    successColor = '#10b981',
    errorColor = '#2563eb',
}) {
    const supported = await ensurePlatformAuthenticator();
    if (!supported) {
        await showBiometricDialog({
            type: 'info',
            title: 'Perangkat belum didukung',
            text: 'Browser atau perangkat ini belum mendukung biometrik.',
            confirmButtonColor: errorColor,
        });
        return false;
    }

    const restoreButton = setButtonBusy(
        button,
        '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> <span>Menunggu biometrik...</span>'
    );

    try {
        const optionsResponse = await fetch(optionsUrl, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const options = normalizePublicKeyOptions(await parseJsonResponse(optionsResponse));

        const credential = await navigator.credentials.create({
            publicKey: {
                challenge: base64UrlToBuffer(options.challenge),
                rp: options.rp,
                user: {
                    id: base64UrlToBuffer(options.user.id),
                    name: options.user.name,
                    displayName: options.user.displayName,
                },
                pubKeyCredParams: options.pubKeyCredParams,
                authenticatorSelection: options.authenticatorSelection,
                timeout: options.timeout,
                attestation: options.attestation,
            },
        });

        const registerResponse = await fetch(registerUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                credential_id: bufferToBase64Url(credential.rawId),
                response: {
                    clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                    attestationObject: bufferToBase64Url(credential.response.attestationObject),
                    transports: typeof credential.response.getTransports === 'function'
                        ? credential.response.getTransports()
                        : [],
                },
            }),
        });

        const result = await parseJsonResponse(registerResponse);

        await showBiometricDialog({
            type: 'success',
            title: 'Berhasil',
            text: successMessage || result.message || 'Perangkat berhasil didaftarkan.',
            confirmButtonColor: successColor,
        });

        window.location.reload();
        return true;
    } catch (error) {
        restoreButton();

        if (error.name === 'NotAllowedError' || error.name === 'AbortError') {
            return false;
        }

        await showBiometricDialog({
            type: 'error',
            title: 'Gagal',
            text: error.message || errorMessage,
            confirmButtonColor: errorColor,
        });

        return false;
    }
}

async function loginWithBiometric({
    button,
    optionsUrl,
    loginUrl,
    fallbackRedirect,
    unknownCredentialHtml,
    successHtml = '<span>Berhasil</span>',
    infoColor = '#0f766e',
    errorColor = '#0f766e',
}) {
    const supported = await ensurePlatformAuthenticator();
    if (!supported) {
        await showBiometricDialog({
            type: 'info',
            title: 'Perangkat belum didukung',
            text: 'Browser atau perangkat ini belum mendukung biometrik.',
            confirmButtonColor: infoColor,
        });
        return false;
    }

    const restoreButton = setButtonBusy(
        button,
        '<svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><span>Memverifikasi...</span>'
    );

    try {
        let challengeRetryCount = 0;

        while (challengeRetryCount <= 1) {
            try {
                const optionsResponse = await fetchWithFreshCsrf(optionsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                }, { refreshBefore: true });
                const options = normalizePublicKeyOptions(await parseJsonResponse(optionsResponse));

                const credential = await navigator.credentials.get({
                    publicKey: {
                        challenge: base64UrlToBuffer(options.challenge),
                        rpId: options.rpId,
                        timeout: options.timeout,
                        userVerification: options.userVerification,
                        allowCredentials: Array.isArray(options.allowCredentials)
                            ? options.allowCredentials.map((item) => ({
                                  ...item,
                                  id: base64UrlToBuffer(item.id),
                              }))
                            : [],
                    },
                });

                const loginResponse = await fetchWithFreshCsrf(loginUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        credential_id: bufferToBase64Url(credential.rawId),
                        response: {
                            clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                            authenticatorData: bufferToBase64Url(credential.response.authenticatorData),
                            signature: bufferToBase64Url(credential.response.signature),
                            userHandle: credential.response.userHandle
                                ? bufferToBase64Url(credential.response.userHandle)
                                : null,
                        },
                    }),
                });

                const result = await parseJsonResponse(loginResponse);
                button.innerHTML = successHtml;
                window.location.href = result.redirect || fallbackRedirect;
                return true;
            } catch (error) {
                const challengeExpired = error.status === 422
                    && error.message?.includes(biometricChallengeExpiredMessage);

                if (!challengeExpired || challengeRetryCount >= 1) {
                    throw error;
                }

                challengeRetryCount += 1;
                await refreshCsrfToken();
            }
        }
    } catch (error) {
        restoreButton();

        if (error.name === 'NotAllowedError' || error.name === 'AbortError') {
            return;
        }

        if (error.message && error.message.includes('tidak dikenali')) {
            await showBiometricDialog({
                type: 'info',
                title: 'Biometrik belum aktif',
                html: unknownCredentialHtml,
                confirmButtonColor: infoColor,
                confirmButtonText: 'Mengerti',
            });
            return;
        }

        if (error.message && error.message.includes('format lama')) {
            await showBiometricDialog({
                type: 'info',
                title: 'Perlu daftar ulang',
                text: error.message,
                confirmButtonColor: infoColor,
                confirmButtonText: 'Mengerti',
            });
            return;
        }

        await showBiometricDialog({
            type: 'error',
            title: 'Gagal',
            text: error.message || 'Terjadi kesalahan saat login biometrik.',
            confirmButtonColor: errorColor,
        });
    }
}

async function removeCredential({
    id,
    endpoint,
    rowSelector,
    confirmText,
    successText = 'Perangkat biometrik berhasil dihapus.',
    errorText = 'Gagal menghapus perangkat.',
    confirmColor = '#EF4444',
    successColor = '#10b981',
    errorColor = '#2563eb',
}) {
    const confirmed = await requestBiometricConfirmation({
        title: 'Hapus perangkat?',
        text: confirmText,
        confirmText: 'Hapus',
        cancelText: 'Batal',
        confirmButtonColor: confirmColor,
    });

    if (!confirmed) {
        return;
    }

    try {
        const response = await fetch(endpoint, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });
        await parseJsonResponse(response);

        const row = document.querySelector(rowSelector);
        if (row) {
            row.remove();
        }

        await showBiometricDialog({
            type: 'success',
            title: 'Dihapus',
            text: successText,
            confirmButtonColor: successColor,
        });

        window.location.reload();
    } catch (error) {
        await showBiometricDialog({
            type: 'error',
            title: 'Gagal',
            text: error.message || errorText,
            confirmButtonColor: errorColor,
        });
    }
}

function closePrompt(prompt) {
    if (!prompt) {
        return;
    }

    prompt.classList.remove('is-visible');
    prompt.setAttribute('aria-hidden', 'true');
}

function openPrompt(prompt) {
    if (!prompt) {
        return;
    }

    prompt.classList.add('is-visible');
    prompt.setAttribute('aria-hidden', 'false');
}

function mountBiometricPrompt() {
    const prompt = document.querySelector('[data-biometric-prompt]');
    if (!prompt) {
        return;
    }

    const registerButton = prompt.querySelector('[data-biometric-action="register"]');
    const dismissButton = prompt.querySelector('[data-biometric-action="dismiss"]');
    const optionsUrl = prompt.dataset.registerOptionsUrl;
    const registerUrl = prompt.dataset.registerUrl;
    const dismissUrl = prompt.dataset.dismissUrl;
    const successMessage = prompt.dataset.successMessage || 'Login biometrik sudah aktif.';

    ensurePlatformAuthenticator().then((available) => {
        if (!available) {
            return;
        }

        window.setTimeout(() => openPrompt(prompt), 1500);
    });

    registerButton?.addEventListener('click', async () => {
        const registered = await registerDevice({
            button: registerButton,
            optionsUrl,
            registerUrl,
            successMessage,
        });

        if (registered) {
            closePrompt(prompt);
        }
    });

    dismissButton?.addEventListener('click', () => {
        closePrompt(prompt);

        if (!dismissUrl) {
            return;
        }

        fetch(dismissUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
        });
    });
}

function mountBiometricSettings() {
    const sections = document.querySelectorAll('[data-biometric-settings]');

    sections.forEach((section) => {
        const addSection = section.querySelector('[data-biometric-add-section]');
        const unsupportedNotice = section.querySelector('[data-biometric-unsupported]');
        const addButton = section.querySelector('[data-biometric-add]');

        ensurePlatformAuthenticator().then((available) => {
            if (available) {
                unsupportedNotice?.classList.add('hidden');
                addSection?.classList.remove('hidden');
                return;
            }

            unsupportedNotice?.classList.remove('hidden');
            addSection?.classList.add('hidden');
        });

        addButton?.addEventListener('click', async () => {
            await registerDevice({
                button: addButton,
                optionsUrl: addButton.dataset.optionsUrl,
                registerUrl: addButton.dataset.registerUrl,
                successMessage: addButton.dataset.successMessage,
                errorMessage: addButton.dataset.errorMessage || 'Gagal mendaftarkan perangkat.',
            });
        });

        section.querySelectorAll('[data-biometric-delete]').forEach((button) => {
            button.addEventListener('click', async () => {
                await removeCredential({
                    id: button.dataset.credentialId,
                    endpoint: button.dataset.deleteUrl,
                    rowSelector: button.dataset.rowSelector,
                    confirmText: button.dataset.confirmText || 'Perangkat ini akan dihapus.',
                    successText: button.dataset.successText || 'Perangkat biometrik berhasil dihapus.',
                });
            });
        });
    });
}

function mountBiometricLogins() {
    const sections = document.querySelectorAll('[data-biometric-login-section]');

    sections.forEach((section) => {
        const button = section.querySelector('[data-biometric-login]');

        if (!button || button.dataset.biometricMounted === 'true') {
            return;
        }

        button.dataset.biometricMounted = 'true';
        if (typeof button.__pkgBiometricInitialHtml !== 'string') {
            button.__pkgBiometricInitialHtml = button.innerHTML;
        }

        ensurePlatformAuthenticator().then((available) => {
            if (!available) {
                section.classList.add('hidden');
            }
        });

        button.addEventListener('click', async () => {
            if (activeBiometricLoginButtons.has(button)) {
                return;
            }

            activeBiometricLoginButtons.add(button);

            try {
                await loginWithBiometric({
                    button,
                    optionsUrl: button.dataset.optionsUrl,
                    loginUrl: button.dataset.loginUrl,
                    fallbackRedirect: button.dataset.fallbackRedirect,
                    unknownCredentialHtml: button.dataset.unknownCredentialHtml,
                    infoColor: button.dataset.infoColor || '#0f766e',
                    errorColor: button.dataset.errorColor || '#0f766e',
                });
            } finally {
                activeBiometricLoginButtons.delete(button);
            }
        });
    });
}

function mountAuthLoginForms() {
    document.querySelectorAll('[data-auth-login-form]').forEach((form) => {
        if (form.dataset.authSessionMounted === 'true') {
            return;
        }

        form.dataset.authSessionMounted = 'true';
        form.addEventListener('submit', async (event) => {
            if (form.dataset.authSessionSubmitting === 'true') {
                return;
            }

            event.preventDefault();
            form.dataset.authSessionSubmitting = 'true';

            const submitButton = event.submitter || form.querySelector('button[type="submit"]');
            const initialHtml = submitButton?.innerHTML;

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span>Memproses...</span>';
            }

            try {
                await refreshCsrfToken();
            } catch (error) {
                console.error('Failed to refresh login session token', error);
            }

            HTMLFormElement.prototype.submit.call(form);

            if (submitButton && typeof initialHtml === 'string') {
                window.setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = initialHtml;
                    delete form.dataset.authSessionSubmitting;
                }, 5000);
            }
        });
    });
}

function recoverAuthSession({ resetButtons = true } = {}) {
    const loginSections = document.querySelectorAll('[data-biometric-login-section]');
    const loginForms = document.querySelectorAll('[data-auth-login-form]');

    if (loginSections.length === 0 && loginForms.length === 0) {
        return;
    }

    mountBiometricLogins();
    mountAuthLoginForms();

    if (resetButtons) {
        loginSections.forEach((section) => {
            const button = section.querySelector('[data-biometric-login]');
            if (button && !activeBiometricLoginButtons.has(button)) {
                resetBiometricButton(button);
            }
        });
    }

    refreshCsrfToken().catch((error) => {
        console.error('Failed to recover authentication session', error);
    });
}

function mountAuthSessionRecovery() {
    if (authSessionRecoveryMounted) {
        return;
    }

    authSessionRecoveryMounted = true;

    window.addEventListener('pageshow', () => {
        recoverAuthSession({ resetButtons: true });
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        recoverAuthSession({ resetButtons: true });
    });

    authSessionRefreshTimer = window.setInterval(() => {
        recoverAuthSession({ resetButtons: false });
    }, 600000);
}

document.addEventListener('DOMContentLoaded', mountBiometricPrompt);
document.addEventListener('DOMContentLoaded', mountBiometricSettings);
document.addEventListener('DOMContentLoaded', mountBiometricLogins);
document.addEventListener('DOMContentLoaded', mountAuthLoginForms);
document.addEventListener('DOMContentLoaded', () => {
    mountAuthSessionRecovery();
    recoverAuthSession({ resetButtons: false });
});

window.PKGBiometric = {
    base64UrlToBuffer,
    bufferToBase64Url,
    ensurePlatformAuthenticator,
    refreshCsrfToken,
    registerDevice,
    loginWithBiometric,
    removeCredential,
    mountBiometricPrompt,
    mountBiometricSettings,
    mountBiometricLogins,
    mountAuthLoginForms,
    recoverAuthSession,
};
