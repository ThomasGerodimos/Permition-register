<?php
use App\Core\{View, Config, Csrf};
$appUrl = Config::appUrl();
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
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if (!empty($permissions)): ?>
                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#cloneModal">
                    <i class="bi bi-clipboard2-data me-1"></i>Αντιγραφή Δικαιωμάτων
                </button>
                <?php endif; ?>
                <a href="<?= $appUrl ?>/permissions/bulk" class="btn btn-sm btn-primary">
                    <i class="bi bi-people-fill me-1"></i>Μαζική Ανάθεση
                </a>
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
