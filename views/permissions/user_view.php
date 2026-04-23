<?php
use App\Core\{View, Session, Csrf, Config};
$appUrl = Config::appUrl();
$qs     = http_build_query(['user_id' => $user['id']]);
?>

<div class="row mt-2 g-3">
    <!-- User info card -->
    <div class="col-md-4 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-primary bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center"
                     style="width:80px;height:80px">
                    <i class="bi bi-person-fill fs-1 text-primary"></i>
                </div>
                <h5 class="fw-bold mb-1"><?= View::e($user['full_name'] ?: $user['username']) ?></h5>
                <div class="text-muted small mb-3"><?= View::e($user['job_title'] ?? '') ?></div>

                <ul class="list-unstyled text-start small">
                    <?php if ($user['email']): ?>
                    <li class="mb-1"><i class="bi bi-envelope-fill me-2 text-muted"></i><?= View::e($user['email']) ?></li>
                    <?php endif; ?>
                    <?php if ($user['department']): ?>
                    <li class="mb-1"><i class="bi bi-building me-2 text-muted"></i><?= View::e($user['department']) ?></li>
                    <?php endif; ?>
                    <?php if ($user['phone']): ?>
                    <li class="mb-1"><i class="bi bi-telephone me-2 text-muted"></i><?= View::e($user['phone']) ?></li>
                    <?php endif; ?>
                    <?php if (!empty($user['manager'])): ?>
                    <li class="mb-1"><i class="bi bi-person-badge me-2 text-muted"></i><strong>Προϊστάμενος:</strong> <?= View::e($user['manager']) ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-footer bg-white border-0 d-flex flex-column gap-2 pb-3">
                <a href="<?= $appUrl ?>/export/pdf?<?= $qs ?>&title=<?= urlencode('Δικαιώματα: '.$user['full_name']) ?>"
                   class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                </a>
                <a href="<?= $appUrl ?>/export/excel?<?= $qs ?>" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </a>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#emailModal">
                    <i class="bi bi-envelope-at"></i> Αποστολή Email
                </button>
            </div>
        </div>
    </div>

    <!-- Permissions -->
    <div class="col-md-8 col-xl-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="bi bi-key-fill me-1 text-primary"></i>
                    Δικαιώματα Πρόσβασης (<?= count($permissions) ?>)
                </span>
                <?php if (Session::isAdmin() || Session::isTypeAdmin()): ?>
                <div class="d-flex gap-2">
                    <?php if (!empty($permissions)): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#copyPermsModal">
                        <i class="bi bi-copy"></i> Αντιγραφή
                    </button>
                    <?php endif; ?>
                    <a href="<?= $appUrl ?>/permissions/create" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Νέο
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($permissions)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-key fs-1 opacity-25"></i>
                    <p class="mt-2">Δεν υπάρχουν καταχωρημένα δικαιώματα</p>
                </div>
                <?php else: ?>
                <?php
                // Group by resource type
                $grouped = [];
                foreach ($permissions as $p) {
                    $grouped[$p['type_label']][] = $p;
                }
                ?>
                <?php foreach ($grouped as $typeLabel => $perms): ?>
                <div class="px-3 pt-3 pb-1">
                    <h6 class="text-muted text-uppercase small fw-semibold">
                        <i class="<?= View::e($perms[0]['type_icon']) ?> me-1"></i>
                        <?= View::e($typeLabel) ?>
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-app">
                            <tr>
                                <th data-sort="text">Πόρος</th>
                                <th data-sort="text">Δικαίωμα</th>
                                <th data-sort="text">Εγκρίθηκε από</th>
                                <th data-sort="date">Ημ/νία</th>
                                <th data-sort="date">Λήξη</th>
                                <?php if (Session::isAdmin() || Session::isTypeAdmin()): ?><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($perms as $p): ?>
                        <tr>
                            <td><?= View::e($p['resource_name']) ?></td>
                            <td><span class="badge bg-primary"><?= View::e($p['permission_level']) ?></span></td>
                            <td class="text-muted small"><?= View::e($p['granted_by_name'] ?? '—') ?></td>
                            <td class="text-muted small"><?= substr($p['granted_at'],0,10) ?></td>
                            <td class="text-muted small">
                                <?= $p['expires_at'] ? substr($p['expires_at'],0,10) : '—' ?>
                            </td>
                            <?php if (Session::isAdmin() || Session::isTypeAdmin((int)($p['resource_type_id'] ?? 0))): ?>
                            <td class="text-nowrap">
                                <a href="<?= $appUrl ?>/permissions/<?= $p['id'] ?>/edit"
                                   class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="<?= $appUrl ?>/permissions/<?= $p['id'] ?>/delete"
                                      class="d-inline" onsubmit="return confirm('Διαγραφή;')">
                                    <?= Csrf::field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                                </form>
                            </td>
                            <?php elseif (Session::isTypeAdmin()): ?>
                            <td></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Copy Permissions Modal -->
<?php if ((Session::isAdmin() || Session::isTypeAdmin()) && !empty($permissions)): ?>
<?php
// For type-admins, only show the permissions they can copy (their assigned types)
$copyablePerms = $permissions;
if (!Session::isAdmin() && Session::isTypeAdmin()) {
    $allowedTypeIds = Session::getTypeAdminTypes();
    $copyablePerms = array_values(array_filter(
        $permissions,
        fn($p) => in_array((int)($p['resource_type_id'] ?? 0), $allowedTypeIds, true)
    ));
}
?>
<div class="modal fade" id="copyPermsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-copy me-1 text-primary"></i>
                    Αντιγραφή Δικαιωμάτων
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $appUrl ?>/users/<?= $user['id'] ?>/copy-permissions">
                <?= Csrf::field() ?>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Αντιγραφή <strong><?= count($copyablePerms) ?></strong> δικαιωμάτων
                        του <strong><?= View::e($user['full_name'] ?: $user['username']) ?></strong>
                        σε άλλον χρήστη.
                    </p>

                    <!-- Target user -->
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-semibold">
                            Χρήστης Προορισμού <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="target_username" id="copyTargetInput"
                               class="form-control" placeholder="username (π.χ. k.papadopoulos)"
                               autocomplete="off" required>
                        <div id="copyTargetSuggest"
                             class="list-group position-absolute shadow-sm w-100"
                             style="z-index:1060;display:none;top:100%;left:0;"></div>
                        <div class="form-text">Αναζητά τον χρήστη στη local βάση ή στο Active Directory.</div>
                    </div>

                    <!-- Options -->
                    <div class="d-flex gap-4 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="keep_expiry" id="copyKeepExpiry" value="1" checked>
                            <label class="form-check-label" for="copyKeepExpiry">
                                <i class="bi bi-calendar-x me-1 text-muted"></i>Διατήρηση ημ/νίας λήξης
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="keep_notes" id="copyKeepNotes" value="1" checked>
                            <label class="form-check-label" for="copyKeepNotes">
                                <i class="bi bi-chat-left-text me-1 text-muted"></i>Διατήρηση σημειώσεων
                            </label>
                        </div>
                    </div>

                    <!-- Permissions preview -->
                    <?php if (!empty($copyablePerms)): ?>
                    <div class="border rounded bg-light" style="max-height:220px;overflow-y:auto;">
                        <div class="px-3 py-2 border-bottom small text-muted fw-semibold">
                            Δικαιώματα προς αντιγραφή:
                        </div>
                        <?php foreach ($copyablePerms as $cp): ?>
                        <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom border-light-subtle">
                            <i class="<?= View::e($cp['type_icon'] ?? 'bi-file-earmark') ?> text-muted small"></i>
                            <span class="small text-muted"><?= View::e($cp['type_label']) ?></span>
                            <span class="mx-1 text-muted">›</span>
                            <span class="small fw-semibold"><?= View::e($cp['resource_name']) ?></span>
                            <span class="badge bg-primary ms-auto"><?= View::e($cp['permission_level']) ?></span>
                            <?php if (!empty($cp['expires_at'])): ?>
                            <span class="badge bg-light text-muted border"><?= substr($cp['expires_at'],0,10) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning small py-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Δεν υπάρχουν δικαιώματα για τους τύπους πόρων που διαχειρίζεστε.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-primary" <?= empty($copyablePerms) ? 'disabled' : '' ?>>
                        <i class="bi bi-copy me-1"></i> Αντιγραφή Δικαιωμάτων
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const input   = document.getElementById('copyTargetInput');
    const suggest = document.getElementById('copyTargetSuggest');
    if (!input || !suggest) return;

    let debounce;
    input.addEventListener('input', function () {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { suggest.style.display = 'none'; return; }
        debounce = setTimeout(function () {
            fetch(APP_URL + '/api/ad/search?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                suggest.innerHTML = '';
                if (!data.length) { suggest.style.display = 'none'; return; }
                data.forEach(function (u) {
                    const a = document.createElement('a');
                    a.className = 'list-group-item list-group-item-action small py-1';
                    a.href = '#';
                    a.innerHTML = '<strong class="font-monospace">' + u.username + '</strong>'
                                + ' — ' + (u.full_name || '')
                                + (u.department ? ' <span class="text-muted">(' + u.department + ')</span>' : '');
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        input.value = u.username;
                        suggest.style.display = 'none';
                    });
                    suggest.appendChild(a);
                });
                suggest.style.display = 'block';
            })
            .catch(function () { suggest.style.display = 'none'; });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !suggest.contains(e.target)) {
            suggest.style.display = 'none';
        }
    });
})();
</script>
<?php endif; ?>

<!-- Email Modal (simplified) -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Αποστολή Αναφοράς Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="emailResult" class="mb-2 d-none"></div>
                <input type="hidden" id="emailUserId" value="<?= $user['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Μορφή</label>
                    <select class="form-select" id="emailFormat">
                        <option value="pdf">PDF</option>
                        <option value="xlsx">Excel</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Παραλήπτη</label>
                    <input type="email" class="form-control" id="emailRecipient"
                           value="<?= View::e($user['email'] ?? '') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση</button>
                <button type="button" class="btn btn-primary" id="btnSendEmail"
                        data-scope="user" data-csrf="<?= \App\Core\Csrf::generate() ?>">
                    <i class="bi bi-send"></i> Αποστολή
                </button>
            </div>
        </div>
    </div>
</div>
