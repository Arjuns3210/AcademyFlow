import './bootstrap';

import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';

window.bootstrap = bootstrap;
window.Chart = Chart;

// Wires any pair of date inputs sharing a data-range-group value so the
// "to" side can't be set before the "from" side and vice versa — used by
// every from/to date-range filter (attendance, collections, fee/student
// admission reports) to stop a crossed range instead of silently
// returning an empty result set.
document.addEventListener('DOMContentLoaded', () => {
    const groups = {};

    document.querySelectorAll('input[type="date"][data-range-group]').forEach((input) => {
        const key = input.dataset.rangeGroup;
        groups[key] = groups[key] || {};
        groups[key][input.dataset.rangeRole] = input;
    });

    Object.values(groups).forEach(({ from, to }) => {
        if (!from || !to) {
            return;
        }

        const sync = () => {
            to.min = from.value || '';
            from.max = to.value || '';
        };

        from.addEventListener('change', sync);
        to.addEventListener('change', sync);
        sync();
    });

    // A modal re-opened after a server-side validation redirect needs to be
    // shown through Bootstrap's own JS (not just by adding the "show"/
    // "d-block" classes server-side) — otherwise Bootstrap never registers
    // a modal instance for it, so the backdrop/aria state never gets set
    // up and the Cancel/× dismiss buttons have nothing to close.
    document.querySelectorAll('.modal[data-open-on-load]').forEach((modal) => {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    });

    // The sidebar is a plain flex sibling of #mainContent (not a Bootstrap
    // offcanvas) — hiding it with d-none removes it from the flex row
    // entirely so #mainContent reflows into the freed space on its own,
    // with no manual margin math and no fixed-position overlap to guard
    // against. The toggle icon mirrors what a click will do next: a
    // left-pointing chevron while open (click to collapse), a
    // right-pointing chevron while collapsed (click to expand).
    const sidebarNav = document.getElementById('sidebarNav');
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');

    if (sidebarNav && sidebarToggleBtn) {
        const icon = sidebarToggleBtn.querySelector('i');

        const setSidebarOpen = (open) => {
            sidebarNav.classList.toggle('d-none', !open);
            icon.classList.toggle('bi-chevron-right', !open);
            icon.classList.toggle('bi-chevron-left', open);
            sidebarToggleBtn.setAttribute('aria-expanded', String(open));
        };

        sidebarToggleBtn.addEventListener('click', () => setSidebarOpen(sidebarNav.classList.contains('d-none')));

        setSidebarOpen(!sidebarNav.classList.contains('d-none'));
    }
});

document.addEventListener('click', function (event) {
    var toggle = event.target.closest('.toggle-password');
    if (! toggle) return;

    var input = document.getElementById(toggle.dataset.target);
    if (! input) return;

    var icon = toggle.querySelector('i');
    var showing = input.type === 'text';

    input.type = showing ? 'password' : 'text';
    toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    icon.classList.toggle('bi-eye', showing);
    icon.classList.toggle('bi-eye-slash', ! showing);
});
