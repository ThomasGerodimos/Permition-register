/* Permission Register — App JS */

// APP_URL may already be set by inline script in form.php
if (typeof APP_URL === 'undefined') {
    var APP_URL = document.querySelector('meta[name="app-url"]')?.content
        || window.location.origin + '/permissions';
}

// ── Sidebar toggle ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const toggle  = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('hidden'));
    }
});

// ── AD Autocomplete (permission form) ──────────────────────────────────────
(function () {
    const input       = document.getElementById('usernameInput');
    const hidden      = document.getElementById('usernameHidden');
    const suggestions = document.getElementById('adSuggestions');
    const userInfo    = document.getElementById('adUserInfo');

    if (!input) return;

    let debounceTimer;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = input.value.trim();

        if (q.length < 2) {
            suggestions.classList.add('d-none');
            suggestions.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                const res  = await fetch(`${APP_URL}/api/ad/search?q=${encodeURIComponent(q)}`);
                const data = await res.json();

                suggestions.innerHTML = '';
                if (!data.length) {
                    suggestions.classList.add('d-none');
                    return;
                }

                data.forEach(user => {
                    const item = document.createElement('a');
                    item.href  = '#';
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = `<strong>${esc(user.full_name)}</strong>
                        <span class="text-muted ms-2 small">${esc(user.username)}</span>
                        <span class="text-muted ms-2 small">${esc(user.department || '')}</span>`;

                    item.addEventListener('click', e => {
                        e.preventDefault();
                        input.value  = `${user.full_name} (${user.username})`;
                        hidden.value = user.username;
                        suggestions.classList.add('d-none');
                        // Show user info
                        userInfo.innerHTML = `
                            <div class="d-flex gap-3 flex-wrap">
                                <span><i class="bi bi-envelope-fill me-1 text-muted"></i>${esc(user.email || '—')}</span>
                                <span><i class="bi bi-building me-1 text-muted"></i>${esc(user.department || '—')}</span>
                                <span><i class="bi bi-briefcase me-1 text-muted"></i>${esc(user.job_title || '—')}</span>
                                <span><i class="bi bi-telephone me-1 text-muted"></i>${esc(user.phone || '—')}</span>
                            </div>`;
                        userInfo.classList.remove('d-none');
                    });

                    suggestions.appendChild(item);
                });

                suggestions.classList.remove('d-none');
            } catch (err) {
                console.error('AD search error:', err);
            }
        }, 300);
    });

    document.addEventListener('click', e => {
        if (!suggestions.contains(e.target) && e.target !== input) {
            suggestions.classList.add('d-none');
        }
    });
})();

// ── Resource Type → Filter resources & load permissions ──────────────────────
(function () {
    const typeSelect = document.getElementById('resourceTypeSelect');
    const resSelect  = document.getElementById('resourceSelect');
    const permSelect = document.getElementById('permissionSelect');

    if (!typeSelect) return;

    typeSelect.addEventListener('change', () => {
        const typeId = typeSelect.value;
        filterResources(typeId);
        loadPermissionsFromSelect(typeId);
    });
})();

function filterResources(typeId) {
    const resSelect = document.getElementById('resourceSelect');
    if (!resSelect) return;
    resSelect.value = '';
    Array.from(resSelect.options).forEach(opt => {
        if (!opt.value) return; // placeholder
        opt.style.display = (opt.dataset.type === typeId) ? '' : 'none';
    });
}

function loadPermissions(typeId, selected = '') {
    const permSelect = document.getElementById('permissionSelect');
    if (!permSelect) return;

    // Get permissions from the type option's data attribute
    const typeOption = document.querySelector(`#resourceTypeSelect option[value="${typeId}"]`);
    if (!typeOption) return;

    let permissions = [];
    try {
        permissions = JSON.parse(typeOption.dataset.permissions || '[]');
    } catch (e) {}

    permSelect.innerHTML = '<option value="">— Επιλέξτε δικαίωμα —</option>';
    permissions.forEach(p => {
        const opt = new Option(p, p, p === selected, p === selected);
        permSelect.add(opt);
    });
}

function loadPermissionsFromSelect(typeId, selected = '') {
    loadPermissions(typeId, selected);
}

// ── Email modal (permissions list page) ────────────────────────────────────
(function () {
    const scopeSelect = document.getElementById('emailScope');
    const userField   = document.getElementById('emailUserField');
    const deptField   = document.getElementById('emailDeptField');
    const usernameIn  = document.getElementById('emailUsername');
    const userIdHid   = document.getElementById('emailUserId');
    const suggestions = document.getElementById('emailUserSuggestions');
    const btnSend     = document.getElementById('btnSendEmail');
    const resultDiv   = document.getElementById('emailResult');

    if (!btnSend) return;

    // Scope toggle
    if (scopeSelect) {
        scopeSelect.addEventListener('change', () => {
            const isUser = scopeSelect.value === 'user';
            userField?.classList.toggle('d-none', !isUser);
            deptField?.classList.toggle('d-none', isUser);
        });
    }

    // Autocomplete for email user search
    if (usernameIn) {
        let debounce;
        usernameIn.addEventListener('input', () => {
            clearTimeout(debounce);
            const q = usernameIn.value.trim();
            if (q.length < 2) { suggestions.classList.add('d-none'); return; }

            debounce = setTimeout(async () => {
                const res  = await fetch(`${APP_URL}/api/ad/search?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                suggestions.innerHTML = '';
                data.forEach(u => {
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action small';
                    item.textContent = `${u.full_name} (${u.username})`;
                    item.addEventListener('click', e => {
                        e.preventDefault();
                        usernameIn.value = `${u.full_name} (${u.username})`;
                        userIdHid.value  = u.id || u.username;
                        document.getElementById('emailRecipient').value = u.email || '';
                        suggestions.classList.add('d-none');
                    });
                    suggestions.appendChild(item);
                });
                suggestions.classList.toggle('d-none', !data.length);
            }, 300);
        });
    }

    // Send button
    btnSend.addEventListener('click', async () => {
        const csrfToken = document.querySelector('[name="_csrf"]')?.value || btnSend.dataset.csrf;
        const scope     = scopeSelect?.value || btnSend.dataset.scope || 'user';
        const format    = document.getElementById('emailFormat')?.value || 'pdf';
        const userId    = document.getElementById('emailUserId')?.value || '';
        const dept      = document.getElementById('emailDept')?.value || '';
        const recipient = document.getElementById('emailRecipient')?.value || '';

        const body = new URLSearchParams({
            _csrf: csrfToken, scope, format,
            user_id: userId, department: dept, recipient_email: recipient
        });

        btnSend.disabled = true;
        btnSend.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Αποστολή...';

        try {
            const res  = await fetch(`${APP_URL}/email/send`, { method: 'POST', body });
            const data = await res.json();

            resultDiv.className = `alert alert-${data.success ? 'success' : 'danger'} mb-2`;
            resultDiv.textContent = data.message;
            resultDiv.classList.remove('d-none');
        } catch (err) {
            resultDiv.className = 'alert alert-danger mb-2';
            resultDiv.textContent = 'Σφάλμα αποστολής: ' + err.message;
            resultDiv.classList.remove('d-none');
        } finally {
            btnSend.disabled = false;
            btnSend.innerHTML = '<i class="bi bi-send"></i> Αποστολή';
        }
    });
})();

// ── Table Sorting (event delegation — works after AJAX reload) ──────────────
document.addEventListener('click', function (e) {
    const th = e.target.closest('th[data-sort]');
    if (!th) return;

    const table = th.closest('table');
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const colIdx = Array.from(th.parentNode.children).indexOf(th);
    const type   = th.dataset.sort; // 'text', 'num', 'date'

    // Toggle direction
    const isAsc = th.classList.contains('sort-asc');
    // Clear all sort classes in this table
    table.querySelectorAll('th[data-sort]').forEach(function (h) {
        h.classList.remove('sort-asc', 'sort-desc');
    });
    const dir = isAsc ? 'desc' : 'asc';
    th.classList.add('sort-' + dir);

    // Collect rows (skip detail/collapse rows and empty-state rows)
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(function (r) {
        return !r.classList.contains('collapse') && r.cells.length > 1
            && !r.querySelector('td[colspan]');
    });

    rows.sort(function (a, b) {
        const cellA = a.cells[colIdx];
        const cellB = b.cells[colIdx];
        if (!cellA || !cellB) return 0;

        var valA = (cellA.dataset.sortValue || cellA.textContent).trim().toLowerCase();
        var valB = (cellB.dataset.sortValue || cellB.textContent).trim().toLowerCase();

        var cmp = 0;
        if (type === 'num') {
            cmp = (parseFloat(valA) || 0) - (parseFloat(valB) || 0);
        } else if (type === 'date') {
            var dA = valA ? new Date(valA) : new Date(0);
            var dB = valB ? new Date(valB) : new Date(0);
            cmp = dA - dB;
        } else {
            cmp = valA.localeCompare(valB, 'el');
        }
        return dir === 'asc' ? cmp : -cmp;
    });

    // Re-append in order (detail rows follow their parent)
    rows.forEach(function (row) {
        tbody.appendChild(row);
        // Move associated collapse/detail row right after
        var detailBtn = row.querySelector('[data-bs-target]');
        if (detailBtn) {
            var targetSel = detailBtn.getAttribute('data-bs-target');
            var detailRow = tbody.querySelector('tr' + targetSel);
            if (detailRow) tbody.appendChild(detailRow);
        }
    });
});

// ── Helpers ─────────────────────────────────────────────────────────────────
function esc(str) {
    return String(str || '').replace(/[&<>"']/g, m => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    })[m]);
}
