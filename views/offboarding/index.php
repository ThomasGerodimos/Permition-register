<?php
use App\Core\{View, Csrf, Config};
$appUrl = Config::appUrl();
?>

<div class="row justify-content-center mt-2">
    <div class="col-xl-8 col-lg-10">

        <!-- Page header -->
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="rounded-3 d-flex align-items-center justify-content-center bg-danger bg-opacity-10"
                 style="width:52px;height:52px;flex-shrink:0">
                <i class="bi bi-person-dash-fill fs-3 text-danger"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">Αποχώρηση Υπαλλήλου</h4>
                <p class="text-muted small mb-0">
                    Ορισμός ημερομηνίας λήξης σε όλα τα ενεργά δικαιώματα υπαλλήλου που αποχωρεί.
                </p>
            </div>
        </div>

        <!-- Step 1: Employee search -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 fw-semibold">
                <i class="bi bi-search me-2 text-primary"></i>Βήμα 1 — Επιλογή Υπαλλήλου
            </div>
            <div class="card-body">
                <div class="position-relative">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-person-search text-muted"></i></span>
                        <input type="text" id="employeeSearch" class="form-control form-control-lg border-start-0"
                               placeholder="Πληκτρολογήστε όνομα, username ή τμήμα..." autocomplete="off">
                        <button type="button" class="btn btn-outline-secondary d-none" id="btnClearEmployee"
                                title="Αλλαγή υπαλλήλου">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div id="employeeSuggestions"
                         class="list-group position-absolute shadow z-3 d-none"
                         style="width:100%;top:100%;left:0;max-height:280px;overflow-y:auto"></div>
                </div>
                <div class="form-text mt-1">Αναζητά μεταξύ χρηστών που υπάρχουν ήδη στο σύστημα.</div>
            </div>
        </div>

        <!-- Preview panel (hidden until user selected) -->
        <div id="previewPanel" class="d-none">

            <!-- User info card -->
            <div class="card border-0 shadow-sm mb-3 border-start border-danger border-4">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center"
                             style="width:48px;height:48px;flex-shrink:0">
                            <i class="bi bi-person-fill fs-4 text-danger"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-5" id="previewName"></div>
                            <div class="text-muted small d-flex gap-3 flex-wrap" id="previewMeta"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions table -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">
                        <i class="bi bi-key-fill me-2 text-primary"></i>
                        Βήμα 2 — Ενεργά Δικαιώματα
                    </span>
                    <span class="badge bg-primary rounded-pill" id="previewCount">0</span>
                </div>
                <div id="previewTableWrap">
                    <!-- filled by JS -->
                </div>
            </div>

            <!-- Date + submit -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-3 fw-semibold">
                    <i class="bi bi-calendar-event me-2 text-primary"></i>Βήμα 3 — Ορισμός Ημερομηνίας Λήξης
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $appUrl ?>/offboarding/apply" id="offboardingForm">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="user_id" id="hiddenUserId">

                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">
                                    Τελευταία ημέρα εργασίας
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="expires_at" id="expiresAt"
                                       class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                                <div class="form-text">
                                    Τα δικαιώματα θα λήγουν την ημέρα αυτή.
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="alert alert-warning mb-0 small">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    Θα ενημερωθούν όλα τα δικαιώματα χωρίς ημερομηνία λήξης ή με
                                    ημερομηνία λήξης μεταγενέστερη της επιλεγμένης.
                                    Η ενέργεια καταγράφεται στο ιστορικό.
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="d-flex gap-2 align-items-center">
                            <button type="submit" class="btn btn-danger px-4" id="btnApply">
                                <i class="bi bi-person-dash-fill me-2"></i>Εφαρμογή Αποχώρησης
                            </button>
                            <span class="text-muted small" id="applyNote"></span>
                        </div>
                    </form>
                </div>
            </div>

        </div><!-- /previewPanel -->

        <!-- Empty state (no permissions) -->
        <div id="noPermsPanel" class="d-none">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-shield-check fs-1 d-block mb-2 text-success"></i>
                    <p class="mb-0">Ο συγκεκριμένος υπάλληλος δεν έχει ενεργά δικαιώματα στο σύστημα.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    var APP_URL     = <?= json_encode($appUrl) ?>;
    var searchEl    = document.getElementById('employeeSearch');
    var suggestEl   = document.getElementById('employeeSuggestions');
    var clearBtn    = document.getElementById('btnClearEmployee');
    var previewPanel = document.getElementById('previewPanel');
    var noPermsPanel = document.getElementById('noPermsPanel');
    var timer;

    // ── Employee search autocomplete ────────────────────────────────────────
    searchEl.addEventListener('input', function () {
        clearTimeout(timer);
        var q = searchEl.value.trim();
        if (q.length < 2) { suggestEl.classList.add('d-none'); return; }

        timer = setTimeout(function () {
            fetch(APP_URL + '/api/users/search?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                suggestEl.innerHTML = '';
                if (!data.length) { suggestEl.classList.add('d-none'); return; }

                data.forEach(function (u) {
                    var item = document.createElement('a');
                    item.href      = '#';
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML =
                        '<div class="d-flex align-items-center gap-2">'
                        + '<i class="bi bi-person-circle text-muted"></i>'
                        + '<div>'
                        + '<strong>' + esc(u.full_name || u.username) + '</strong>'
                        + ' <span class="text-muted small">' + esc(u.username) + '</span>'
                        + (u.department ? '<br><span class="text-muted small">' + esc(u.department) + (u.job_title ? ' &middot; ' + esc(u.job_title) : '') + '</span>' : '')
                        + '</div></div>';

                    item.addEventListener('click', function (e) {
                        e.preventDefault();
                        selectEmployee(u.id, u.full_name || u.username);
                    });
                    suggestEl.appendChild(item);
                });
                suggestEl.classList.remove('d-none');
            })
            .catch(function () { suggestEl.classList.add('d-none'); });
        }, 300);
    });

    // Close suggestions on outside click
    document.addEventListener('click', function (e) {
        if (!suggestEl.contains(e.target) && e.target !== searchEl) {
            suggestEl.classList.add('d-none');
        }
    });

    // Clear selection
    clearBtn.addEventListener('click', function () {
        searchEl.value   = '';
        searchEl.disabled = false;
        clearBtn.classList.add('d-none');
        previewPanel.classList.add('d-none');
        noPermsPanel.classList.add('d-none');
        searchEl.focus();
    });

    // ── Select employee → load preview ──────────────────────────────────────
    function selectEmployee(userId, label) {
        suggestEl.classList.add('d-none');
        searchEl.value    = label;
        searchEl.disabled = true;
        clearBtn.classList.remove('d-none');
        previewPanel.classList.add('d-none');
        noPermsPanel.classList.add('d-none');

        fetch(APP_URL + '/offboarding/preview?user_id=' + userId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            renderPreview(data);
        })
        .catch(function () {
            alert('Σφάλμα κατά τη φόρτωση δικαιωμάτων.');
        });
    }

    // ── Render preview ───────────────────────────────────────────────────────
    function renderPreview(data) {
        var u     = data.user;
        var perms = data.permissions;

        // User info
        document.getElementById('previewName').textContent = u.full_name || u.username;
        var meta = [];
        if (u.username)   meta.push('<i class="bi bi-person me-1"></i>' + esc(u.username));
        if (u.department) meta.push('<i class="bi bi-building me-1"></i>' + esc(u.department));
        if (u.job_title)  meta.push('<i class="bi bi-briefcase me-1"></i>' + esc(u.job_title));
        if (u.email)      meta.push('<i class="bi bi-envelope me-1"></i>' + esc(u.email));
        document.getElementById('previewMeta').innerHTML = meta.join(' ');

        document.getElementById('hiddenUserId').value = u.id;

        if (!perms.length) {
            noPermsPanel.classList.remove('d-none');
            previewPanel.classList.add('d-none');
            return;
        }

        // Permissions count badge
        document.getElementById('previewCount').textContent = perms.length;

        // Apply note
        document.getElementById('applyNote').textContent =
            perms.length + ' δικαιώματα θα λάβουν ημερομηνία λήξης';

        // Build table
        var today = new Date();
        var rows  = perms.map(function (p) {
            var expBadge = '';
            if (p.expires_at) {
                var expDate = new Date(p.expires_at);
                var isPast  = expDate < today;
                expBadge = isPast
                    ? '<span class="badge bg-danger">' + fmtDate(p.expires_at) + '</span>'
                    : '<span class="text-muted">' + fmtDate(p.expires_at) + '</span>';
            } else {
                expBadge = '<span class="text-muted">—</span>';
            }
            return '<tr>'
                + '<td><i class="' + esc(p.type_icon) + ' me-1 text-muted"></i>' + esc(p.type_label) + '</td>'
                + '<td class="fw-semibold">' + esc(p.resource_name) + '</td>'
                + '<td><span class="badge bg-primary bg-opacity-75">' + esc(p.permission_level) + '</span></td>'
                + '<td>' + expBadge + '</td>'
                + '</tr>';
        });

        document.getElementById('previewTableWrap').innerHTML =
            '<div class="table-responsive">'
            + '<table class="table table-sm align-middle mb-0 small">'
            + '<thead class="thead-app"><tr>'
            + '<th>Τύπος</th><th>Πόρος</th><th>Επίπεδο</th><th>Τρέχουσα Λήξη</th>'
            + '</tr></thead>'
            + '<tbody>' + rows.join('') + '</tbody>'
            + '</table></div>';

        previewPanel.classList.remove('d-none');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    function fmtDate(iso) {
        if (!iso) return '—';
        var d = iso.substring(0, 10).split('-');
        return d[2] + '/' + d[1] + '/' + d[0];
    }
})();
</script>
