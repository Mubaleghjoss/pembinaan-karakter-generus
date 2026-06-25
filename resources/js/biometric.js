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

async function parseJsonResponse(response) {
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = payload.error || payload.message || 'Permintaan biometrik gagal.';
        throw new Error(message);
    }

    return payload;
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

    const previous = button.innerHTML;
    button.disabled = true;
    button.innerHTML = html;

    return () => {
        button.disabled = false;
        button.innerHTML = previous;
    };
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
        const optionsResponse = await fetch(optionsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });
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

        const loginResponse = await fetch(loginUrl, {
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

        ensurePlatformAuthenticator().then((available) => {
            if (!available) {
                section.classList.add('hidden');
            }
        });

        button?.addEventListener('click', async () => {
            await loginWithBiometric({
                button,
                optionsUrl: button.dataset.optionsUrl,
                loginUrl: button.dataset.loginUrl,
                fallbackRedirect: button.dataset.fallbackRedirect,
                unknownCredentialHtml: button.dataset.unknownCredentialHtml,
                infoColor: button.dataset.infoColor || '#0f766e',
                errorColor: button.dataset.errorColor || '#0f766e',
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', mountBiometricPrompt);
document.addEventListener('DOMContentLoaded', mountBiometricSettings);
document.addEventListener('DOMContentLoaded', mountBiometricLogins);

window.PKGBiometric = {
    base64UrlToBuffer,
    bufferToBase64Url,
    ensurePlatformAuthenticator,
    registerDevice,
    loginWithBiometric,
    removeCredential,
    mountBiometricPrompt,
    mountBiometricSettings,
    mountBiometricLogins,
};
