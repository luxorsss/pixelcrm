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

    /* ==========================================================================
       Instant Subsequent Tooltips (Emil Kowalski Recipe)
       ========================================================================== */
    let tooltipEl = null;
    let tooltipTimer = null;
    let isAnyTooltipOpen = false;
    let closeGraceTimer = null;

    function getOrCreateTooltip() {
        if (!tooltipEl) {
            tooltipEl = document.createElement('div');
            tooltipEl.className = 'pixel-tooltip';
            document.body.appendChild(tooltipEl);
        }
        return tooltipEl;
    }

    function positionTooltip(el, targetRect) {
        const spacing = 6;
        const ttRect = el.getBoundingClientRect();
        let top = targetRect.top - ttRect.height - spacing;
        let left = targetRect.left + (targetRect.width / 2) - (ttRect.width / 2);

        // Boundary adjustments
        if (top < 10) top = targetRect.bottom + spacing;
        if (left < 10) left = 10;
        if (left + ttRect.width > window.innerWidth - 10) {
            left = window.innerWidth - ttRect.width - 10;
        }

        el.style.top = `${top}px`;
        el.style.left = `${left}px`;
    }

    document.addEventListener('mouseover', (e) => {
        const trigger = e.target.closest('[data-tooltip], [title]');
        if (!trigger) return;

        // Extract and swap native title to prevent default browser tooltip clash
        if (trigger.hasAttribute('title') && !trigger.hasAttribute('data-tooltip')) {
            trigger.setAttribute('data-tooltip', trigger.getAttribute('title'));
            trigger.removeAttribute('title');
        }

        const text = trigger.getAttribute('data-tooltip');
        if (!text) return;

        clearTimeout(closeGraceTimer);
        const tt = getOrCreateTooltip();
        tt.textContent = text;

        const show = () => {
            const rect = trigger.getBoundingClientRect();
            positionTooltip(tt, rect);
            tt.classList.add('visible');
            if (isAnyTooltipOpen) {
                tt.classList.add('instant');
            } else {
                tt.classList.remove('instant');
            }
            isAnyTooltipOpen = true;
        };

        if (isAnyTooltipOpen) {
            show();
        } else {
            clearTimeout(tooltipTimer);
            tooltipTimer = setTimeout(show, 250);
        }
    });

    document.addEventListener('mouseout', (e) => {
        const trigger = e.target.closest('[data-tooltip]');
        if (!trigger) return;

        clearTimeout(tooltipTimer);
        if (tooltipEl) {
            tooltipEl.classList.remove('visible');
        }

        // Keep instant mode alive briefly for adjacent buttons
        closeGraceTimer = setTimeout(() => {
            isAnyTooltipOpen = false;
            if (tooltipEl) tooltipEl.classList.remove('instant');
        }, 300);
    });

    /* ==========================================================================
       Hold-To-Confirm Action Handler (Emil Kowalski Recipe)
       ========================================================================== */
    document.addEventListener('pointerdown', (e) => {
        const btn = e.target.closest('.btn-hold-confirm');
        if (!btn || btn.disabled) return;

        let holdTimeout = null;
        const requiredHold = parseInt(btn.getAttribute('data-hold-time') || '1500', 10);

        holdTimeout = setTimeout(() => {
            // Trigger confirmation completion
            if (btn.tagName === 'A' && btn.href && !btn.href.startsWith('javascript:')) {
                window.location.href = btn.href;
            } else if (btn.dataset.action) {
                try { eval(btn.dataset.action); } catch(err) { console.error(err); }
            } else if (btn.type === 'submit' && btn.form) {
                btn.form.submit();
            } else {
                btn.click();
            }
        }, requiredHold);

        const cancelHold = () => {
            clearTimeout(holdTimeout);
            window.removeEventListener('pointerup', cancelHold);
            window.removeEventListener('pointercancel', cancelHold);
        };

        window.addEventListener('pointerup', cancelHold);
        window.addEventListener('pointercancel', cancelHold);
    });

})(window);

