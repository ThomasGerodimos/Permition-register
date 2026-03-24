<?php
use App\Core\{View, Csrf, Config};
$appUrl = Config::appUrl();
?>

<div class="card border-0 shadow-sm mt-2" style="max-width:800px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $appUrl ?>/permissions/bulk" id="bulkForm">
            <?= Csrf::field() ?>
            <input type="hidden" name="usernames" id="usernamesHidden" value="[]">

            <!-- Resource Type -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Τύπος Πόρου <span class="text-danger">*</span></label>
                <select class="form-select" id="resourceTypeSelect" required>
                    <option value="">— Επιλέξτε τύπο —</option>
                    <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id'] ?>"
                            data-permissions='<?= htmlspecialchars(json_encode($t['permissions']),ENT_QUOTES) ?>'>
                        <?= View::e($t['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Resources (multi-select) -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Πόροι <span class="text-danger">*</span>
                    <span id="resourceCount" class="badge bg-secondary ms-2 d-none">0</span>
                </label>
                <div class="border rounded p-2" id="resourceCheckboxes" style="max-height:200px;overflow-y:auto">
                    <?php foreach ($resources as $r): ?>
                    <div class="form-check resource-check-item" data-type="<?= $r['resource_type_id'] ?>" style="display:none">
                        <input class="form-check-input resource-checkbox" type="checkbox"
                               name="resource_ids[]" value="<?= $r['id'] ?>" id="res_<?= $r['id'] ?>">
                        <label class="form-check-label" for="res_<?= $r['id'] ?>">
                            <?= View::e($r['name']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <div id="noResourcesMsg" class="text-muted small py-2 text-center">Επιλέξτε πρώτα τύπο πόρου</div>
                </div>
                <div class="d-flex gap-2 mt-1">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllResources">
                        <i class="bi bi-check-all"></i> Επιλογή όλων
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllResources">
                        <i class="bi bi-x-lg"></i> Αποεπιλογή
                    </button>
                </div>
            </div>

            <!-- Permission Level -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Επίπεδο Δικαιώματος <span class="text-danger">*</span></label>
                <select class="form-select" name="permission_level" id="permissionSelect" required>
                    <option value="">— Επιλέξτε δικαίωμα —</option>
                </select>
            </div>

            <hr class="my-4">

            <!-- Multi-user Search -->
            <div class="mb-3 position-relative">
                <label class="form-label fw-semibold">
                    Χρήστες <span class="text-danger">*</span>
                    <span id="userCount" class="badge bg-secondary ms-2 d-none">0</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="bulkUserSearch" class="form-control"
                           placeholder="Αναζήτηση χρήστη (username ή ονοματεπώνυμο)..."
                           autocomplete="off">
                </div>
                <div id="bulkSuggestions" class="list-group position-absolute w-100 shadow-sm z-3 d-none" style="top:100%"></div>
            </div>

            <!-- Selected Users -->
            <div id="selectedUsersContainer" class="mb-3 d-none">
                <div class="card bg-light border-0">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="fw-semibold text-muted">Επιλεγμένοι χρήστες:</small>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllUsers">
                                <i class="bi bi-x-lg"></i> Καθαρισμός
                            </button>
                        </div>
                        <div id="selectedUsersList" class="d-flex flex-wrap gap-1"></div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Expiry -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Ημερομηνία Λήξης</label>
                <input type="date" name="expires_at" class="form-control">
                <div class="form-text">Αφήστε κενό για χωρίς λήξη</div>
            </div>

            <!-- Notes -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Σημειώσεις</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="Προαιρετικές σημειώσεις..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" id="btnBulkSubmit">
                    <i class="bi bi-people-fill"></i> Μαζική Ανάθεση
                </button>
                <a href="<?= $appUrl ?>/permissions" class="btn btn-outline-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-shield-check me-2"></i>Επιβεβαίωση Μαζικής Ανάθεσης</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div class="text-center mb-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                         style="width:64px;height:64px">
                        <i class="bi bi-people-fill text-primary fs-2"></i>
                    </div>
                </div>
                <p class="text-center mb-3 fs-6">Πρόκειται να αναθέσετε:</p>
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body py-3">
                        <div class="row g-2 text-center">
                            <div class="col-12">
                                <small class="text-muted d-block">Δικαίωμα</small>
                                <span id="confirmPerm" class="badge bg-primary fs-6 px-3 py-2"></span>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block mt-2">Πόροι</small>
                                <div id="confirmResource" class="text-dark"></div>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block mt-2">Χρήστες</small>
                                <span id="confirmCount" class="badge bg-success fs-6 rounded-pill px-3 py-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="confirmUserList" class="mb-2" style="max-height:200px;overflow-y:auto"></div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Ακύρωση
                </button>
                <button type="button" class="btn btn-primary px-4" id="confirmSubmit">
                    <i class="bi bi-check-lg me-1"></i>Ανάθεση
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Validation Alert Modal -->
<div class="modal fade" id="alertModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning border-0">
                <h6 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Προσοχή</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p id="alertMessage" class="mb-0 fw-semibold"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-warning px-4" data-bs-dismiss="modal">Εντάξει</button>
            </div>
        </div>
    </div>
</div>

<script>
var APP_URL = '<?= $appUrl ?>';

document.addEventListener('DOMContentLoaded', function() {
    const searchInput   = document.getElementById('bulkUserSearch');
    const suggestions   = document.getElementById('bulkSuggestions');
    const listContainer = document.getElementById('selectedUsersList');
    const container     = document.getElementById('selectedUsersContainer');
    const hiddenInput   = document.getElementById('usernamesHidden');
    const countBadge    = document.getElementById('userCount');
    const form          = document.getElementById('bulkForm');
    const clearBtn      = document.getElementById('clearAllUsers');
    const typeSel       = document.getElementById('resourceTypeSelect');
    const permSel       = document.getElementById('permissionSelect');
    const resCBs        = document.getElementById('resourceCheckboxes');
    const resCountBadge = document.getElementById('resourceCount');
    const noResMsg      = document.getElementById('noResourcesMsg');

    let selectedUsers = [];
    let debounceTimer;

    // ── Resource Type Change → filter resources + permissions ─────────
    typeSel.addEventListener('change', function() {
        const typeId = this.value;
        const opt    = this.options[this.selectedIndex];

        // Filter resource checkboxes
        let visibleCount = 0;
        resCBs.querySelectorAll('.resource-check-item').forEach(function(div) {
            if (typeId && div.dataset.type === typeId) {
                div.style.display = '';
                visibleCount++;
            } else {
                div.style.display = 'none';
                div.querySelector('input').checked = false;
            }
        });
        noResMsg.style.display = visibleCount > 0 ? 'none' : '';
        updateResourceCount();

        // Populate permission levels
        permSel.innerHTML = '<option value="">— Επιλέξτε δικαίωμα —</option>';
        if (opt && opt.dataset.permissions) {
            try {
                var perms = JSON.parse(opt.dataset.permissions);
                perms.forEach(function(p) {
                    var o = document.createElement('option');
                    o.value = p;
                    o.textContent = p;
                    permSel.appendChild(o);
                });
            } catch(e) {}
        }
    });

    // ── Select All / Deselect All Resources ──────────────────────────
    document.getElementById('selectAllResources').addEventListener('click', function() {
        resCBs.querySelectorAll('.resource-check-item').forEach(function(div) {
            if (div.style.display !== 'none') {
                div.querySelector('input').checked = true;
            }
        });
        updateResourceCount();
    });

    document.getElementById('deselectAllResources').addEventListener('click', function() {
        resCBs.querySelectorAll('.resource-checkbox').forEach(function(cb) {
            cb.checked = false;
        });
        updateResourceCount();
    });

    // Update resource count badge on checkbox change
    resCBs.addEventListener('change', updateResourceCount);

    function updateResourceCount() {
        var count = resCBs.querySelectorAll('.resource-checkbox:checked').length;
        resCountBadge.textContent = count;
        resCountBadge.classList.toggle('d-none', count === 0);
        resCountBadge.className = 'badge ms-2 ' + (count > 0 ? 'bg-success' : 'bg-secondary');
    }

    // ── Autocomplete ──────────────────────────────────────────────────
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = searchInput.value.trim();
        if (q.length < 2) { suggestions.classList.add('d-none'); return; }

        debounceTimer = setTimeout(async () => {
            try {
                const res  = await fetch(`${APP_URL}/api/ad/search?q=${encodeURIComponent(q)}`);
                const data = await res.json();

                suggestions.innerHTML = '';
                if (!data.length) { suggestions.classList.add('d-none'); return; }

                data.forEach(user => {
                    if (selectedUsers.some(u => u.username === user.username)) return;

                    const item = document.createElement('a');
                    item.href  = '#';
                    item.className = 'list-group-item list-group-item-action py-2';
                    item.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${esc(user.full_name)}</strong>
                                <span class="text-muted ms-2 small">${esc(user.username)}</span>
                            </div>
                            <span class="text-muted small">${esc(user.department || '')}</span>
                        </div>`;

                    item.addEventListener('click', e => {
                        e.preventDefault();
                        addUser(user);
                        searchInput.value = '';
                        suggestions.classList.add('d-none');
                        searchInput.focus();
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
        if (!suggestions.contains(e.target) && e.target !== searchInput) {
            suggestions.classList.add('d-none');
        }
    });

    // ── Add / Remove Users ────────────────────────────────────────────
    function addUser(user) {
        if (selectedUsers.some(u => u.username === user.username)) return;
        selectedUsers.push({
            username:   user.username,
            full_name:  user.full_name,
            department: user.department || ''
        });
        renderUsers();
    }

    function removeUser(username) {
        selectedUsers = selectedUsers.filter(u => u.username !== username);
        renderUsers();
    }

    function renderUsers() {
        listContainer.innerHTML = '';
        selectedUsers.forEach(user => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary d-inline-flex align-items-center fs-6 fw-normal py-2 px-3';
            badge.innerHTML = `
                <i class="bi bi-person-fill me-1"></i>
                ${esc(user.full_name)}
                <small class="ms-1 opacity-75">(${esc(user.username)})</small>
                <button type="button" class="btn-close btn-close-white ms-2"
                        style="font-size:0.6rem" data-username="${esc(user.username)}"></button>`;
            listContainer.appendChild(badge);
        });

        hiddenInput.value = JSON.stringify(selectedUsers.map(u => u.username));
        container.classList.toggle('d-none', selectedUsers.length === 0);
        countBadge.textContent = selectedUsers.length;
        countBadge.classList.toggle('d-none', selectedUsers.length === 0);
        countBadge.className = `badge ms-2 ${selectedUsers.length > 0 ? 'bg-success' : 'bg-secondary'}`;
    }

    listContainer.addEventListener('click', e => {
        const btn = e.target.closest('[data-username]');
        if (btn) removeUser(btn.dataset.username);
    });

    clearBtn.addEventListener('click', () => {
        selectedUsers = [];
        renderUsers();
    });

    // ── Modals ────────────────────────────────────────────────────────
    const confirmModal  = new bootstrap.Modal(document.getElementById('confirmModal'));
    const alertModal    = new bootstrap.Modal(document.getElementById('alertModal'));
    const submitBtn     = document.getElementById('btnBulkSubmit');
    const confirmSubmit = document.getElementById('confirmSubmit');

    function showAlert(msg) {
        document.getElementById('alertMessage').textContent = msg;
        alertModal.show();
    }

    function getSelectedResources() {
        var checked = resCBs.querySelectorAll('.resource-checkbox:checked');
        var list = [];
        checked.forEach(function(cb) {
            list.push({ id: cb.value, name: cb.closest('.resource-check-item').querySelector('label').textContent.trim() });
        });
        return list;
    }

    submitBtn.addEventListener('click', () => {
        var selectedRes = getSelectedResources();

        if (!typeSel.value)            { showAlert('Επιλέξτε τύπο πόρου.'); return; }
        if (selectedRes.length === 0)  { showAlert('Επιλέξτε τουλάχιστον έναν πόρο.'); return; }
        if (!permSel.value)            { showAlert('Επιλέξτε δικαίωμα.'); return; }
        if (selectedUsers.length === 0){ showAlert('Προσθέστε τουλάχιστον έναν χρήστη.'); searchInput.focus(); return; }

        var permText = permSel.value;
        var totalAssignments = selectedRes.length * selectedUsers.length;

        document.getElementById('confirmPerm').textContent  = permText;
        document.getElementById('confirmCount').textContent = selectedUsers.length + ' χρήστες';

        // Build resource list
        var resHtml = selectedRes.map(function(r) { return esc(r.name); }).join(', ');
        document.getElementById('confirmResource').innerHTML =
            selectedRes.length + ' πόροι: <span class="small text-muted">' + resHtml + '</span>';

        // Build summary
        var userListEl = document.getElementById('confirmUserList');
        userListEl.innerHTML =
            '<div class="alert alert-warning small mb-2 py-1 px-2">' +
            '<i class="bi bi-calculator me-1"></i>Σύνολο αναθέσεων: <strong>' + totalAssignments + '</strong>' +
            ' (' + selectedUsers.length + ' χρήστες × ' + selectedRes.length + ' πόροι)</div>' +
            '<table class="table table-sm table-borderless mb-0 small">' +
            selectedUsers.map((u, i) =>
                `<tr>
                    <td class="text-muted" style="width:30px">${i+1}.</td>
                    <td><i class="bi bi-person-fill text-primary me-1"></i>${esc(u.full_name)}</td>
                    <td class="text-muted">${esc(u.username)}</td>
                    <td class="text-muted">${esc(u.department)}</td>
                </tr>`
            ).join('') + '</table>';

        confirmModal.show();
    });

    confirmSubmit.addEventListener('click', () => {
        confirmSubmit.disabled  = true;
        confirmSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Αποστολή...';
        confirmModal.hide();
        form.submit();
    });
});

function esc(str) {
    return String(str || '').replace(/[&<>"']/g, m => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    })[m]);
}
</script>
