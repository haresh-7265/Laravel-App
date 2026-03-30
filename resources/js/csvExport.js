/* ============================================================
   export-popup.js
   Place in: public/js/export-popup.js
   ============================================================ */

(function () {
    'use strict';

    /* ── Element refs ── */
    const triggerBtn = document.getElementById('exportTriggerBtn');
    const backdrop   = document.getElementById('exportBackdrop');
    const modal      = document.getElementById('exportModal');
    const closeBtn   = document.getElementById('exportCloseBtn');
    const cancelBtn  = document.getElementById('exportCancelBtn');
    const resetBtn   = document.getElementById('exportResetBtn');
    const form       = document.getElementById('exportForm');

    if (!modal) return; // guard: component not on this page

    /* ── Open ── */
    function openModal() {
        // Show backdrop
        backdrop.classList.add('ep-active');

        // Show modal (display:block needed before transition fires)
        modal.style.display = 'block';

        // Kick off transition on next paint
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                modal.classList.add('ep-active');
            });
        });

        // Lock body scroll
        document.body.style.overflow = 'hidden';

        // Accessibility: move focus into modal
        var firstInput = modal.querySelector('input, select, button');
        if (firstInput) {
            setTimeout(function () { firstInput.focus(); }, 260);
        }
    }

    /* ── Close ── */
    function closeModal() {
        modal.classList.remove('ep-active');
        backdrop.classList.remove('ep-active');
        document.body.style.overflow = '';

        // Hide after transition ends
        setTimeout(function () {
            modal.style.display = 'none';
        }, 230);

        // Return focus to trigger button
        if (triggerBtn) triggerBtn.focus();
    }

    /* ── Reset filters ── */
    function resetFilters() {
        if (form) {
            form.reset();
        }
        // Reset native selects that form.reset() might miss
        var selects = modal.querySelectorAll('select');
        selects.forEach(function (sel) { sel.selectedIndex = 0; });
    }

    /* ── Event listeners ── */
    if (triggerBtn) triggerBtn.addEventListener('click', openModal);
    if (closeBtn)   closeBtn.addEventListener('click', closeModal);
    if (cancelBtn)  cancelBtn.addEventListener('click', closeModal);
    if (resetBtn)   resetBtn.addEventListener('click', resetFilters);

    // Backdrop click → close
    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    }

    // Escape key → close
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('ep-active')) {
            closeModal();
        }
    });

    // On form submit → close modal after short delay (download starts)
    if (form) {
        form.addEventListener('submit', function () {
            setTimeout(closeModal, 350);
        });
    }

    // Trap focus inside modal when open (accessibility)
    modal.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab') return;
        if (!modal.classList.contains('ep-active')) return;

        var focusable = modal.querySelectorAll(
            'button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        var first = focusable[0];
        var last  = focusable[focusable.length - 1];

        if (e.shiftKey) {
            if (document.activeElement === first) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if (document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    });

})();