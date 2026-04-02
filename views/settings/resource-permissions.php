<?php
use App\Core\{View, Config, Csrf, Session};
$appUrl = Config::appUrl();
$canEdit = Session::isAdmin() || Session::isTypeAdmin((int)($resource['resource_type_id'] ?? 0));
?>

<!-- Resource Header -->
<div class="card border-0 shadow-sm mt-2 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="<?= View::e($resource['type_icon'] ?? 'bi bi-hdd-network') ?> fs-3 text-primary"></i>
                    <h4 class="mb-0 fw-bold"><?= View::e($resource['name']) ?></h4>
                </div>
                <div class="d-flex gap-3 text-muted small flex-wrap">
                    <span><i class="bi bi-tag-fill me-1"></i><?= View::e($resource['type_label']) ?></span>
                    <?php if (!empty($resource['location'])): ?>
                    <span><i class="bi bi-geo-alt-fill me-1"></i><code><?= View::e($resource['location']) ?></code></span>
                    <?php endif; ?>
                    <?php if (!empty($resource['description'])): ?>
                    <span><i class="bi bi-info-circle me-1"></i><?= View::e($resource['description']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($resource['expires_at'])): ?>
                    <?php
                    $resExp  = new DateTime($resource['expires_at']);
                    $resNow  = new DateTime();
                    $resDiff = (int)$resNow->diff($resExp)->format('%r%a');
                    ?>
                    <span>
                        <i class="bi bi-calendar-event me-1"></i>Λήξη:
                        <?php if ($resDiff < 0): ?>
                            <span class="badge bg-danger">Ληγμένος (<?= $resExp->format('d/m/Y') ?>)</span>
                        <?php elseif ($resDiff <= 30): ?>
                            <span class="badge bg-warning text-dark">Λήγει <?= $resExp->format('d/m/Y') ?></span>
                        <?php else: ?>
                            <span class="text-muted"><?= $resExp->format('d/m/Y') ?></span>
                        <?php endif; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if (!empty($permissions) && $canEdit): ?>
                <button type="button" class="btn btn-sm btn-outline-warning d-none" id="btnBulkExpiry">
                    <i class="bi bi-calendar-event me-1"></i>Ορισμός Λήξης (<span id="bulkExpiryCount">0</span>)
                </button>
                <button type="button" class="btn btn-sm btn-danger d-none" id="btnBulkDelete">
                    <i class="bi bi-trash3 me-1"></i>Αφαίρεση (<span id="bulkDeleteCount">0</span>)
                </button>
                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#cloneModal">
                    <i class="bi bi-clipboard2-data me-1"></i>Αντιγραφή Δικαιωμάτων
                </button>
                <?php endif; ?>
                <?php if ($canEdit): ?>
                <a href="<?= $appUrl ?>/permissions/bulk" class="btn btn-sm btn-primary">
                    <i class="bi bi-people-fill me-1"></i>Μαζική Ανάθεση
                </a>
                <?php endif; ?>
                <a href="<?= $appUrl ?>/resources" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Πίσω
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mt-3">
            <div class="col-auto">
                <div class="bg-primary bg-opacity-10 rounded-3 px-3 py-2 text-center">
                    <div class="fs-3 fw-bold text-primary"><?= count($permissions) ?></div>
                    <small class="text-muted">Συνολικά δικαιώματα</small>
                </div>
            </div>
            <div class="col-auto">
                <div class="bg-success bg-opacity-10 rounded-3 px-3 py-2 text-center">
                    <div class="fs-3 fw-bold text-success"><?= count($grouped) ?></div>
                    <small class="text-muted">Επίπεδα πρόσβασης</small>
                </div>
            </div>
            <div class="col-auto">
                <div class="bg-info bg-opacity-10 rounded-3 px-3 py-2 text-center">
                    <?php
                    $uniqueUsers = [];
                    foreach ($permissions as $p) $uniqueUsers[$p['user_id']] = true;
                    ?>
                    <div class="fs-3 fw-bold text-info"><?= count($uniqueUsers) ?></div>
                    <small class="text-muted">Μοναδικοί χρήστες</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($permissions)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-shield-x fs-1 d-block mb-2"></i>
        <p class="mb-2">Δεν υπάρχουν δικαιώματα για αυτόν τον πόρο.</p>
        <a href="<?= $appUrl ?>/permissions/create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Προσθήκη Δικαιώματος
        </a>
    </div>
</div>
<?php else: ?>

<!-- Grouped by Permission Level -->
<?php foreach ($grouped as $level => $users): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <div>
            <span class="badge bg-primary fs-6 px-3 py-2 me-2"><?= View::e($level) ?></span>
            <span class="text-muted small"><?= count($users) ?> χρήστες</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="thead-app">
                <tr>
                    <?php if ($canEdit): ?>
                    <th style="width:40px" class="text-center">
                        <input type="checkbox" class="form-check-input" id="selectAllPerms" title="Επιλογή όλων">
                    </th>
                    <?php endif; ?>
                    <th style="width:40px"></th>
                    <th data-sort="text">Χρήστης</th>
                    <th data-sort="text">Τμήμα</th>
                    <th data-sort="text">Θέση</th>
                    <th data-sort="text">Εγκρίθηκε από</th>
                    <th data-sort="date">Ημ/νία</th>
                    <th data-sort="date">Λήξη</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $i => $p): ?>
            <tr>
                <?php if ($canEdit): ?>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input perm-checkbox" value="<?= $p['id'] ?>"
                           data-name="<?= View::e($p['full_name'] ?: $p['username']) ?>"
                           data-level="<?= View::e($level) ?>">
                </td>
                <?php endif; ?>
                <td class="text-muted text-center small"><?= $i + 1 ?></td>
                <td>
                    <a href="<?= $appUrl ?>/users/<?= $p['user_id'] ?>" class="text-decoration-none fw-semibold">
                        <?= View::e($p['full_name'] ?: $p['username']) ?>
                    </a>
                    <div class="text-muted small">
                        <?= View::e($p['username']) ?>
                        <?php if ($p['email']): ?>
                         &middot; <a href="mailto:<?= View::e($p['email']) ?>" class="text-muted"><?= View::e($p['email']) ?></a>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="text-muted small"><?= View::e($p['department'] ?? '—') ?></td>
                <td class="text-muted small"><?= View::e($p['job_title'] ?? '—') ?></td>
                <td class="text-muted small"><?= View::e($p['granted_by_name'] ?? '—') ?></td>
                <td class="text-muted small"><?= substr($p['granted_at'], 0, 10) ?></td>
                <td class="small">
                    <?php if ($p['expires_at']): ?>
                        <?php $exp = new DateTime($p['expires_at']); $now = new DateTime(); ?>
                        <span class="<?= $exp < $now ? 'text-danger fw-semibold' : 'text-muted' ?>">
                            <?= $exp->format('d/m/Y') ?>
                            <?= $exp < $now ? ' (ληγμένο)' : '' ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<!-- Bulk Delete Permissions Modal -->
<?php if ($canEdit && !empty($permissions)): ?>
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="bulkDeleteForm" action="<?= $appUrl ?>/resources/<?= $resource['id'] ?>/bulk-delete-permissions">
                <?= Csrf::field() ?>
                <div id="bulkDeleteHiddenInputs"></div>
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-1"></i> Μαζική Αφαίρεση Δικαιωμάτων</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Πρόκειται να αφαιρέσετε <strong id="bulkDeleteModalCount">0</strong> δικαιώματα
                       από τον πόρο <strong><?= View::e($resource['name']) ?></strong>.</p>
                    <div id="bulkDeleteList" class="small border rounded p-2 mb-3" style="max-height:200px;overflow-y:auto;background:#fafafa;"></div>
                    <div class="alert alert-warning mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Τα δικαιώματα θα απενεργοποιηθούν (soft delete). Η ενέργεια καταγράφεται στο ιστορικό.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i>Αφαίρεση
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bulk Set Expiry Modal -->
<?php if ($canEdit && !empty($permissions)): ?>
<div class="modal fade" id="bulkExpiryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="bulkExpiryForm" action="<?= $appUrl ?>/resources/<?= $resource['id'] ?>/bulk-set-expiry">
                <?= Csrf::field() ?>
                <div id="bulkExpiryHiddenInputs"></div>
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-calendar-event me-1"></i> Ορισμός Λήξης Δικαιωμάτων</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Ορισμός λήξης σε <strong id="bulkExpiryModalCount">0</strong> δικαιώματα
                       του πόρου <strong><?= View::e($resource['name']) ?></strong>.</p>
                    <div id="bulkExpiryList" class="small border rounded p-2 mb-3" style="max-height:200px;overflow-y:auto;background:#fafafa;"></div>

                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="expiry_action" id="expiryActionSet" value="set" checked>
                            <label class="form-check-label fw-semibold" for="expiryActionSet">
                                Ορισμός ημερομηνίας λήξης
                            </label>
                        </div>
                        <input type="date" name="expires_at" id="bulkExpiryDate" class="form-control ms-4" style="max-width:250px;"
                               min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="expiry_action" id="expiryActionClear" value="clear">
                            <label class="form-check-label fw-semibold" for="expiryActionClear">
                                Αφαίρεση λήξης <span class="text-muted fw-normal">(χωρίς ημερομηνία λήξης)</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-lg me-1"></i>Εφαρμογή
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Clone Permissions Modal -->
<?php if (!empty($permissions)): ?>
<div class="modal fade" id="cloneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $appUrl ?>/resources/<?= $resource['id'] ?>/clone-permissions">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-clipboard2-data me-1 text-warning"></i>
                        Αντιγραφή Δικαιωμάτων
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Αντιγραφή <strong><?= count($permissions) ?></strong> δικαιωμάτων από
                        <strong><?= View::e($resource['name']) ?></strong> σε άλλον πόρο.
                        Τα ήδη υπάρχοντα δικαιώματα δεν θα αντικατασταθούν.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Τύπος Πόρου Στόχου</label>
                        <select class="form-select" id="cloneTargetType" name="target_type_id">
                            <?php foreach ($allTypes as $t): ?>
                            <option value="<?= $t['id'] ?>"
                                <?= $t['id'] == $resource['resource_type_id'] ? 'selected' : '' ?>>
                                <?= View::e($t['label']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Πόρος Στόχος *</label>
                        <select class="form-select" id="cloneTargetResource" name="target_resource_id" required>
                            <option value="">— Επιλέξτε πόρο —</option>
                        </select>
                        <div class="form-text">Επιλέξτε τον πόρο στον οποίο θα αντιγραφούν τα δικαιώματα</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cloneKeepExpiry" name="keep_expiry" value="1" checked>
                            <label class="form-check-label" for="cloneKeepExpiry">
                                Διατήρηση ημερομηνιών λήξης
                            </label>
                        </div>
                    </div>

                    <div class="mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cloneKeepNotes" name="keep_notes" value="1" checked>
                            <label class="form-check-label" for="cloneKeepNotes">
                                Αντιγραφή σημειώσεων
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-warning" id="btnClone">
                        <i class="bi bi-clipboard2-data me-1"></i> Αντιγραφή
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var typeSelect     = document.getElementById('cloneTargetType');
    var resourceSelect = document.getElementById('cloneTargetResource');
    var sourceId       = <?= (int)$resource['id'] ?>;
    var appUrl         = '<?= $appUrl ?>';

    function loadResources() {
        var typeId = typeSelect.value;
        resourceSelect.innerHTML = '<option value="">Φόρτωση...</option>';

        fetch(appUrl + '/api/resources-by-type/' + typeId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var html = '<option value="">— Επιλέξτε πόρο —</option>';
            data.forEach(function(res) {
                if (res.id != sourceId) {
                    html += '<option value="' + res.id + '">' + res.name + '</option>';
                }
            });
            resourceSelect.innerHTML = html;
        })
        .catch(function() {
            resourceSelect.innerHTML = '<option value="">Σφάλμα φόρτωσης</option>';
        });
    }

    typeSelect.addEventListener('change', loadResources);
    document.getElementById('cloneModal').addEventListener('show.bs.modal', loadResources);
})();
</script>
<?php endif; ?>

<?php if ($canEdit && !empty($permissions)): ?>
<script>
(function() {
    var checkboxes     = document.querySelectorAll('.perm-checkbox');
    var selectAll      = document.getElementById('selectAllPerms');
    var btnBulkDelete  = document.getElementById('btnBulkDelete');
    var btnBulkExpiry  = document.getElementById('btnBulkExpiry');
    var countSpan      = document.getElementById('bulkDeleteCount');
    var expiryCountSpan = document.getElementById('bulkExpiryCount');

    if (!checkboxes.length || !selectAll || !btnBulkDelete) return;

    function updateUI() {
        var checked = document.querySelectorAll('.perm-checkbox:checked');
        var count   = checked.length;
        countSpan.textContent = count;
        if (expiryCountSpan) expiryCountSpan.textContent = count;
        if (count > 0) {
            btnBulkDelete.classList.remove('d-none');
            if (btnBulkExpiry) btnBulkExpiry.classList.remove('d-none');
        } else {
            btnBulkDelete.classList.add('d-none');
            if (btnBulkExpiry) btnBulkExpiry.classList.add('d-none');
        }
        // Update select-all state
        selectAll.checked      = count === checkboxes.length && count > 0;
        selectAll.indeterminate = count > 0 && count < checkboxes.length;
    }

    // Select all / deselect all
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
        updateUI();
    });

    // Individual checkboxes
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateUI);
    });

    // Helper: build user list HTML from checked checkboxes
    function buildCheckedList(checked) {
        var listHtml = '';
        checked.forEach(function(cb) {
            listHtml += '<div class="py-1 border-bottom"><i class="bi bi-person me-1 text-muted"></i>'
                      + cb.dataset.name
                      + ' <span class="badge bg-primary bg-opacity-75 ms-1">' + cb.dataset.level + '</span></div>';
        });
        return listHtml;
    }

    // Bulk delete button → open modal
    btnBulkDelete.addEventListener('click', function() {
        var checked  = document.querySelectorAll('.perm-checkbox:checked');
        var hidden   = document.getElementById('bulkDeleteHiddenInputs');
        var list     = document.getElementById('bulkDeleteList');
        var modalCnt = document.getElementById('bulkDeleteModalCount');

        hidden.innerHTML = '';
        checked.forEach(function(cb) {
            hidden.innerHTML += '<input type="hidden" name="perm_ids[]" value="' + cb.value + '">';
        });
        list.innerHTML       = buildCheckedList(checked);
        modalCnt.textContent = checked.length;

        var modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
        modal.show();
    });

    // Bulk expiry button → open modal
    if (btnBulkExpiry) {
        btnBulkExpiry.addEventListener('click', function() {
            var checked  = document.querySelectorAll('.perm-checkbox:checked');
            var hidden   = document.getElementById('bulkExpiryHiddenInputs');
            var list     = document.getElementById('bulkExpiryList');
            var modalCnt = document.getElementById('bulkExpiryModalCount');

            hidden.innerHTML = '';
            checked.forEach(function(cb) {
                hidden.innerHTML += '<input type="hidden" name="perm_ids[]" value="' + cb.value + '">';
            });
            list.innerHTML       = buildCheckedList(checked);
            modalCnt.textContent = checked.length;

            // Reset form state
            document.getElementById('expiryActionSet').checked = true;
            document.getElementById('bulkExpiryDate').disabled = false;
            document.getElementById('bulkExpiryDate').value = '';

            var modal = new bootstrap.Modal(document.getElementById('bulkExpiryModal'));
            modal.show();
        });

        // Toggle date input based on radio selection
        document.querySelectorAll('input[name="expiry_action"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                var dateInput = document.getElementById('bulkExpiryDate');
                dateInput.disabled = (this.value === 'clear');
                if (this.value === 'clear') dateInput.value = '';
            });
        });

        // Validate before submit
        document.getElementById('bulkExpiryForm').addEventListener('submit', function(e) {
            var action = document.querySelector('input[name="expiry_action"]:checked').value;
            if (action === 'set') {
                var dateVal = document.getElementById('bulkExpiryDate').value;
                if (!dateVal) {
                    e.preventDefault();
                    alert('Επιλέξτε ημερομηνία λήξης.');
                }
            }
        });
    }
})();
</script>
<?php endif; ?>
