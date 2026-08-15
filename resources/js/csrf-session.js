const csrfRefreshUrl = '/csrf-token';
let csrfRefreshPromise = null;

export function updateCsrfToken(token) {
    if (!token) return;
    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
    document.querySelectorAll('input[name="_token"]').forEach((input) => {
        input.value = token;
    });
}

export function currentCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

export async function refreshCsrfToken() {
    if (csrfRefreshPromise) return csrfRefreshPromise;

    csrfRefreshPromise = fetch(csrfRefreshUrl, {
        method: 'GET',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        cache: 'no-store',
    })
        .then(async (response) => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || !payload.token) throw new Error(payload.message || 'Sesi tidak dapat diperbarui.');
            updateCsrfToken(payload.token);
            return payload.token;
        })
        .finally(() => { csrfRefreshPromise = null; });

    return csrfRefreshPromise;
}

export async function fetchWithFreshCsrf(url, options = {}, { refreshBefore = false } = {}) {
    if (refreshBefore) await refreshCsrfToken();
    const request = {
        ...options,
        headers: new Headers(options.headers || {}),
        credentials: options.credentials || 'same-origin',
        cache: 'no-store',
    };
    request.headers.set('X-CSRF-TOKEN', currentCsrfToken());
    let response = await fetch(url, request);
    if (response.status !== 419) return response;

    await refreshCsrfToken();
    request.headers.set('X-CSRF-TOKEN', currentCsrfToken());
    response = await fetch(url, request);
    return response;
}
