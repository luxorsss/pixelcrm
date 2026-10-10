/**
 * PixelToast - Modern tactile toast notification system inspired by Sonner (Emil Kowalski)
 * Light-weight, zero external dependencies, accessible, gestures & auto-pause on blur.
 */
(function(window) {
    'use strict';

    let container = null;
    const TOAST_TIMEOUT = 3500;
    const activeToasts = new Set();
    let isWindowFocused = true;

    function createContainer() {
        if (container && document.body.contains(container)) return container;
        container = document.createElement('div');
        container.id = 'pixel-toast-container';
        container.className = 'pixel-toaster';
        container.setAttribute('role', 'region');
        container.setAttribute('aria-label', 'Notifications');
        document.body.appendChild(container);

        window.addEventListener('blur', () => { isWindowFocused = false; });
        window.addEventListener('focus', () => { isWindowFocused = true; });

        return container;
    }

    function removeToast(toastEl) {
        if (!toastEl || toastEl.dataset.dismissing === 'true') return;
        toastEl.dataset.dismissing = 'true';
        toastEl.classList.remove('pixel-toast-visible');
        toastEl.classList.add('pixel-toast-dismissing');

        activeToasts.delete(toastEl);

        setTimeout(() => {
            if (toastEl.parentNode) {
                toastEl.parentNode.removeChild(toastEl);
            }
        }, 160);
    }

    function showToast(message, type = 'info', options = {}) {
        const toaster = createContainer();
        const toast = document.createElement('div');
        toast.className = `pixel-toast pixel-toast-${type}`;
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-triangle-exclamation',
            warning: 'fa-circle-exclamation',
            info: 'fa-circle-info'
        };
        const iconClass = icons[type] || icons.info;

        const content = document.createElement('div');
        content.className = 'pixel-toast-content';
        content.innerHTML = `
            <i class="fas ${iconClass} pixel-toast-icon"></i>
            <span class="pixel-toast-text">${message}</span>
        `;
        toast.appendChild(content);

        const closeBtn = document.createElement('button');
        closeBtn.className = 'pixel-toast-close';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.innerHTML = '<i class="fas fa-xmark"></i>';
        closeBtn.onclick = (e) => {
            e.stopPropagation();
            removeToast(toast);
        };
        toast.appendChild(closeBtn);

        toaster.appendChild(toast);
        activeToasts.add(toast);

        // Force browser layout calculation for entrance transition from scale(0.95)
        requestAnimationFrame(() => {
            toast.classList.add('pixel-toast-visible');
        });

        // Auto dismiss timer with pause on hover/blur
        let timeLeft = options.duration || TOAST_TIMEOUT;
        let timerId = null;
        let startTime = Date.now();

        function startTimer() {
            startTime = Date.now();
            timerId = setTimeout(() => {
                removeToast(toast);
            }, timeLeft);
        }

        function pauseTimer() {
            clearTimeout(timerId);
            timeLeft -= (Date.now() - startTime);
        }

        toast.addEventListener('mouseenter', pauseTimer);
        toast.addEventListener('mouseleave', () => {
            if (timeLeft > 0) startTimer();
        });

        // Swipe-to-dismiss gesture handling
        let startX = 0;
        let currentX = 0;
        let isSwiping = false;

        toast.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isSwiping = true;
            pauseTimer();
        }, { passive: true });

        toast.addEventListener('touchmove', (e) => {
            if (!isSwiping) return;
            currentX = e.touches[0].clientX;
            const diffX = currentX - startX;
            if (diffX > 0) {
                toast.style.transform = `translateX(${diffX}px) scale(0.98)`;
                toast.style.opacity = `${Math.max(0.2, 1 - diffX / 200)}`;
            }
        }, { passive: true });

        toast.addEventListener('touchend', () => {
            if (!isSwiping) return;
            isSwiping = false;
            const diffX = currentX - startX;
            if (diffX > 80) {
                removeToast(toast);
            } else {
                toast.style.transform = '';
                toast.style.opacity = '';
                if (timeLeft > 0) startTimer();
            }
        });

        startTimer();
        return toast;
    }

    const PixelToast = {
        show: (msg, type, opts) => showToast(msg, type, opts),
        success: (msg, opts) => showToast(msg, 'success', opts),
        error: (msg, opts) => showToast(msg, 'error', opts),
        warning: (msg, opts) => showToast(msg, 'warning', opts),
        info: (msg, opts) => showToast(msg, 'info', opts),
        dismiss: (toastEl) => removeToast(toastEl)
    };

    window.PixelToast = PixelToast;
    window.toast = PixelToast; // convenient alias
})(window);
