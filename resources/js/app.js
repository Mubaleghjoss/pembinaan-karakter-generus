import './bootstrap';
import '../css/app.css';
import './biometric';
import Alpine from 'alpinejs';

let rpg3dModulePromise;

function loadRpg3dModule() {
    if (!rpg3dModulePromise) {
        window.__pkgRpg3dLazyLoader = true;
        rpg3dModulePromise = import('./rpg-3d.js').catch((error) => {
            rpg3dModulePromise = null;
            throw error;
        });
    }

    return rpg3dModulePromise;
}

function resolveRpg3dRoot(target) {
    if (!target) {
        return null;
    }

    if (typeof target === 'string') {
        return document.querySelector(target);
    }

    return target instanceof Element ? target : null;
}

async function loadRpg3dScene(target) {
    const root = resolveRpg3dRoot(target);

    if (!root) {
        return null;
    }

    if (root.__pkgRpgThreeScene) {
        return root.__pkgRpgThreeScene;
    }

    if (root.__pkgRpgThreeScenePromise) {
        return root.__pkgRpgThreeScenePromise;
    }

    root.classList.remove('pkg-rpg-3d-error');
    root.classList.add('pkg-rpg-3d-loading');

    if (!root.innerHTML.trim()) {
        root.innerHTML = '<div class="pkg-rpg-3d-fallback">Memuat tampilan 3D...</div>';
    }

    root.__pkgRpgThreeScenePromise = loadRpg3dModule()
        .then((module) => {
            const scene = module.bootRpgThreeScene(root);
            root.classList.remove('pkg-rpg-3d-loading');
            root.dispatchEvent(new CustomEvent('pkg:rpg3d:ready', {
                bubbles: true,
                detail: { scene },
            }));
            return scene;
        })
        .catch((error) => {
            root.__pkgRpgThreeScenePromise = null;
            root.classList.remove('pkg-rpg-3d-loading');
            root.classList.add('pkg-rpg-3d-error');
            root.innerHTML = '<div class="pkg-rpg-3d-fallback">Tampilan 3D belum bisa dimuat di perangkat ini.</div>';
            root.dispatchEvent(new CustomEvent('pkg:rpg3d:error', {
                bubbles: true,
                detail: { error },
            }));
            throw error;
        });

    return root.__pkgRpgThreeScenePromise;
}

function loadRpg3dScenes(scope = document) {
    return Promise.all(
        Array.from(scope.querySelectorAll('[data-rpg-3d-scene]')).map((root) => loadRpg3dScene(root))
    );
}

window.pkgLoadRpg3dScene = loadRpg3dScene;
window.pkgLoadRpg3dScenes = loadRpg3dScenes;
window.dispatchEvent(new CustomEvent('pkg:rpg3d-loader-ready'));

window.Alpine = Alpine;
Alpine.start();

async function mountReactComponent(id, loader) {
    const element = document.getElementById(id);

    if (!element) {
        return;
    }

    try {
        const [
            React,
            ReactDOM,
            componentModule,
        ] = await Promise.all([
            import('react'),
            import('react-dom/client'),
            loader(),
        ]);

        const props = { ...element.dataset };
        const Component = componentModule.default;
        const root = ReactDOM.createRoot(element);
        root.render(React.createElement(Component, props));
    } catch (error) {
        console.error(`Failed to load component for #${id}`, error);
    }
}

function initializeFeatureMounts() {
    mountReactComponent('news-slider', () => import('./components/NewsSlider.jsx'));
    mountReactComponent('qr-scanner', () => import('./components/QRScanner.jsx'));
    mountReactComponent('attendance-success', () => import('./components/AttendanceSuccess.jsx'));
}

let fullCalendarLoaderPromise;
let html5QrcodeLoaderPromise;
let chartJsLoaderPromise;
let qrCodeLoaderPromise;

async function loadFullCalendar() {
    if (!fullCalendarLoaderPromise) {
        fullCalendarLoaderPromise = Promise.all([
            import('@fullcalendar/core'),
            import('@fullcalendar/daygrid'),
            import('@fullcalendar/list'),
            import('@fullcalendar/core/locales/id'),
        ]).then(([core, dayGrid, list, localeId]) => ({
            Calendar: core.Calendar,
            dayGridPlugin: dayGrid.default,
            listPlugin: list.default,
            localeId: localeId.default,
        }));
    }

    return fullCalendarLoaderPromise;
}

async function loadHtml5Qrcode() {
    if (!html5QrcodeLoaderPromise) {
        html5QrcodeLoaderPromise = import('html5-qrcode').then((module) => ({
            Html5Qrcode: module.Html5Qrcode,
            Html5QrcodeScanner: module.Html5QrcodeScanner,
        }));
    }

    return html5QrcodeLoaderPromise;
}

async function loadChartJs() {
    if (!chartJsLoaderPromise) {
        chartJsLoaderPromise = import('chart.js/auto').then((module) => module.default || module.Chart);
    }

    return chartJsLoaderPromise;
}

async function loadQrCode() {
    if (!qrCodeLoaderPromise) {
        qrCodeLoaderPromise = import('qrcode').then((module) => module.default || module);
    }

    return qrCodeLoaderPromise;
}

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initializeNotifications();
    initializeModals();
    initializeConfirmActions();
    initializeFormValidation();
    initializeQRScanner();
    initializeFeatureMounts();

    const flashSuccessMessage = sessionStorage.getItem('pkgActionSuccess');
    if (flashSuccessMessage) {
        showNotification(flashSuccessMessage, 'success');
        sessionStorage.removeItem('pkgActionSuccess');
    }
});

// Notification system
function initializeNotifications() {
    // Auto-hide success messages after 5 seconds
    const successMessages = document.querySelectorAll('.alert-success');
    successMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 300);
        }, 5000);
    });
    
    // Auto-hide error messages after 8 seconds
    const errorMessages = document.querySelectorAll('.alert-error');
    errorMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 300);
        }, 8000);
    });
}

// Modal functionality
function initializeModals() {
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            closeModal();
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

function initializeConfirmActions() {
    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('form[data-confirm]')) {
            return;
        }

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        const confirmed = await showConfirmation(form.dataset.confirm || 'Lanjutkan aksi ini?', {
            title: form.dataset.confirmTitle || 'Konfirmasi tindakan',
            confirmText: form.dataset.confirmButton || 'Lanjutkan',
            cancelText: form.dataset.cancelButton || 'Batal',
            tone: form.dataset.confirmTone || 'danger',
        });

        if (!confirmed) {
            return;
        }

        form.dataset.confirmed = 'true';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.classList.add('hidden');
    });
    document.body.style.overflow = 'auto';
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Field ini wajib diisi');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Format email tidak valid');
            isValid = false;
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('border-red-500');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-500 text-sm mt-1 field-error';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('border-red-500');
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// QR Scanner functionality
function initializeQRScanner() {
    const scannerButton = document.getElementById('start-scanner');
    if (scannerButton) {
        scannerButton.addEventListener('click', startQRScanner);
    }
}

function startQRScanner() {
    // This would integrate with a QR scanning library
    console.log('Starting QR scanner...');
    
    // For now, show a placeholder message
    showNotification('QR Scanner akan segera tersedia', 'info');
}

// Utility functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${getNotificationClass(type)}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function showConfirmation(message, options = {}) {
    return new Promise((resolve) => {
        const {
            title = 'Konfirmasi tindakan',
            confirmText = 'Lanjutkan',
            cancelText = 'Batal',
            tone = 'danger',
        } = options;

        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/55 p-4';

        const toneClasses = {
            danger: {
                iconWrap: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-300',
                button: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
            },
            warning: {
                iconWrap: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300',
                button: 'bg-amber-500 hover:bg-amber-600 focus:ring-amber-400',
            },
            primary: {
                iconWrap: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300',
                button: 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500',
            },
        };

        const selectedTone = toneClasses[tone] || toneClasses.danger;

        overlay.innerHTML = `
            <div class="pkg-modal w-full max-w-md p-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ${selectedTone.iconWrap}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.29 3.86l-7.19 12.47A2 2 0 004.81 19h14.38a2 2 0 001.71-2.67L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">${title}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">${message}</p>
                    </div>
                </div>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" data-confirm-cancel class="btn-secondary !justify-center !px-4 !py-2 text-sm">${cancelText}</button>
                    <button type="button" data-confirm-ok class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 ${selectedTone.button}">${confirmText}</button>
                </div>
            </div>
        `;

        const cleanup = (result) => {
            document.removeEventListener('keydown', onKeydown);
            overlay.remove();
            resolve(result);
        };

        const onKeydown = (event) => {
            if (event.key === 'Escape') {
                cleanup(false);
            }
        };

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                cleanup(false);
            }
        });

        overlay.querySelector('[data-confirm-cancel]')?.addEventListener('click', () => cleanup(false));
        overlay.querySelector('[data-confirm-ok]')?.addEventListener('click', () => cleanup(true));

        document.addEventListener('keydown', onKeydown);
        document.body.appendChild(overlay);
        overlay.querySelector('[data-confirm-cancel]')?.focus();
    });
}

function getNotificationClass(type) {
    switch (type) {
        case 'success':
            return 'bg-green-500 text-white';
        case 'error':
            return 'bg-red-500 text-white';
        case 'warning':
            return 'bg-yellow-500 text-white';
        default:
            return 'bg-blue-500 text-white';
    }
}

function formatDate(date) {
    return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

function formatTime(time) {
    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(`2000-01-01T${time}`));
}

function togglePassword(inputId = 'password', iconId = 'eye-icon') {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    if (!input || !icon) {
        return;
    }

    const showPassword = input.type === 'password';
    input.type = showPassword ? 'text' : 'password';
    icon.innerHTML = showPassword
        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>'
        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
}

// Loading state management
function showLoading(element) {
    if (element) {
        element.disabled = true;
        const originalText = element.textContent;
        element.dataset.originalText = originalText;
        element.innerHTML = '<span class="spinner"></span> Loading...';
    }
}

function hideLoading(element) {
    if (element && element.dataset.originalText) {
        element.disabled = false;
        element.textContent = element.dataset.originalText;
        delete element.dataset.originalText;
    }
}

// Export functions for global use
window.openModal = openModal;
window.closeModal = closeModal;
window.showNotification = showNotification;
window.showConfirmation = showConfirmation;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.formatDate = formatDate;
window.formatTime = formatTime;
window.togglePassword = togglePassword;
window.loadFullCalendar = loadFullCalendar;
window.loadHtml5Qrcode = loadHtml5Qrcode;
window.loadChartJs = loadChartJs;
window.loadQrCode = loadQrCode;
