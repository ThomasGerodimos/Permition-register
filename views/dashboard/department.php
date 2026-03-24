<?php
use App\Core\{View, Csrf, Config};
$appUrl = Config::appUrl();

$uniqueResources = count($byResource);
$totalPerms      = count($permissions);
$deptEncoded     = urlencode($department);
$exportTitle     = urlencode('Δικαιώματα — ' . $department);
?>

<!-- Department Header -->
<div class="card border-0 shadow-sm mt-2 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-building-fill fs-3 text-primary"></i>
                    <h4 class="mb-0 fw-bold"><?= View::e($department) ?></h4>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= $appUrl ?>/permissions?department=<?= $deptEncoded ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-table me-1"></i>Πίνακας
                </a>
                <a href="<?= $appUrl ?>/dashboard" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mt-2">
            <div class="col-auto">
                <div class="bg-primary bg-opacity-10 rounded-3 px-3 py-2 text-center">
                    <div class="fs-3 fw-bold text-primary"><?= count($users) ?></div>
                    <small class="text-muted">Στελέχη</small>
                </div>
            </div>
            <div class="col-auto">
                <div class="bg-success bg-opacity-10 rounded-3 px-3 py-2 text-center">
                    <div class="fs-3 fw-bold text-success"><?= $uniqueResources ?></div>
                    <small class="text-muted">Πόροι</small>
                </div>
            </div>
            <div class="col-auto">
                <div class="bg-info bg-opacity-10 rounded-3 px-3 py-2 text-center">
                    <div class="fs-3 fw-bold text-info"><?= $totalPerms ?></div>
                    <small class="text-muted">Δικαιώματα</small>
                </div>
            </div>
        </div>

        <!-- Export / Print / Email -->
        <div class="d-flex gap-2 flex-wrap mt-3 pt-3 border-top">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Εκτύπωση
            </button>
            <a href="<?= $appUrl ?>/export/pdf?department=<?= $deptEncoded ?>&title=<?= $exportTitle ?>"
               class="btn btn-sm btn-outline-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="<?= $appUrl ?>/export/excel?department=<?= $deptEncoded ?>"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="<?= $appUrl ?>/export/csv?department=<?= $deptEncoded ?>"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-filetype-csv me-1"></i>CSV
            </a>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#emailDeptModal">
                <i class="bi bi-envelope-at me-1"></i>Αποστολή Email
            </button>
        </div>
    </div>
</div>

<?php if (empty($byResource)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-shield-x fs-1 d-block mb-2"></i>
        <p>Δεν υπάρχουν δικαιώματα για στελέχη αυτού του τμήματος.</p>
    </div>
</div>
<?php else: ?>

<!-- Resources list -->
<?php foreach ($byResource as $resId => $res):
    // Group users by permission level
    $grouped = [];
    foreach ($res['users'] as $p) {
        $grouped[$p['permission_level']][] = $p;
    }
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="<?= $appUrl ?>/resources/<?= $resId ?>/permissions"
                   class="text-decoration-none fs-5 fw-bold">
                    <i class="<?= View::e($res['type_icon']) ?> me-2 text-primary"></i><?= View::e($res['name']) ?>
                </a>
                <span class="badge bg-secondary ms-2"><?= View::e($res['type_label']) ?></span>
                <?php if (!empty($res['location'])): ?>
                <span class="text-muted small ms-2 font-monospace"><?= View::e($res['location']) ?></span>
                <?php endif; ?>
            </div>
            <span class="badge bg-primary rounded-pill"><?= count($res['users']) ?></span>
        </div>
    </div>

    <div class="card-body pt-2">
        <?php foreach ($grouped as $level => $users): ?>
        <div class="mb-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary px-3"><?= View::e($level) ?></span>
                <small class="text-muted"><?= count($users) ?> στελέχη</small>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <tbody>
                    <?php foreach ($users as $p): ?>
                    <tr>
                        <td style="width:30px"><i class="bi bi-person-fill text-primary"></i></td>
                        <td>
                            <a href="<?= $appUrl ?>/users/<?= $p['user_id'] ?>" class="text-decoration-none fw-semibold">
                                <?= View::e($p['full_name'] ?: $p['username']) ?>
                            </a>
                        </td>
                        <td class="text-muted small"><?= View::e($p['username']) ?></td>
                        <td class="text-muted small"><?= View::e($p['job_title'] ?? '—') ?></td>
                        <td class="text-muted small"><?= View::e($p['email'] ?? '') ?></td>
                        <td class="text-muted small"><?= substr($p['granted_at'], 0, 10) ?></td>
                        <td class="small">
                            <?php if ($p['expires_at']): ?>
                                <?php $exp = new DateTime($p['expires_at']); $now = new DateTime(); ?>
                                <span class="<?= $exp < $now ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                    <?= $exp->format('d/m/Y') ?>
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
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Email Modal -->
<div class="modal fade" id="emailDeptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title">
                    <i class="bi bi-envelope-at me-2"></i>Αποστολή Αναφοράς — <?= View::e($department) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div id="deptEmailResult" class="mb-2 d-none"></div>
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Μορφή Αναφοράς</label>
                    <select class="form-select" id="deptEmailFormat">
                        <option value="pdf">PDF</option>
                        <option value="xlsx">Excel (.xlsx)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Παραλήπτη</label>
                    <input type="email" class="form-control" id="deptEmailRecipient"
                           placeholder="Αφήστε κενό για αποστολή στον εαυτό σας">
                </div>
                <div class="bg-light rounded p-3 small text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Θα αποσταλεί αναφορά με <strong>όλα τα δικαιώματα</strong> του τμήματος
                    <strong><?= View::e($department) ?></strong>
                    (<?= $totalPerms ?> εγγραφές).
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Ακύρωση</button>
                <button type="button" class="btn btn-primary" id="btnDeptSendEmail">
                    <i class="bi bi-send me-1"></i>Αποστολή
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn       = document.getElementById('btnDeptSendEmail');
    const resultDiv = document.getElementById('deptEmailResult');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        const csrfToken = document.querySelector('[name="_csrf"]')?.value;
        const format    = document.getElementById('deptEmailFormat').value;
        const recipient = document.getElementById('deptEmailRecipient').value;

        const body = new URLSearchParams({
            _csrf: csrfToken,
            scope: 'department',
            department: <?= json_encode($department) ?>,
            format: format,
            recipient_email: recipient
        });

        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Αποστολή...';
        resultDiv.classList.add('d-none');

        try {
            const appUrl = <?= json_encode($appUrl) ?>;
            const res  = await fetch(`${appUrl}/email/send`, { method: 'POST', body });
            const data = await res.json();

            resultDiv.className = `alert alert-${data.success ? 'success' : 'danger'} mb-2`;
            resultDiv.innerHTML = `<i class="bi bi-${data.success ? 'check-circle' : 'exclamation-triangle'}-fill me-1"></i>${data.message}`;
            resultDiv.classList.remove('d-none');
        } catch (err) {
            resultDiv.className = 'alert alert-danger mb-2';
            resultDiv.textContent = 'Σφάλμα αποστολής: ' + err.message;
            resultDiv.classList.remove('d-none');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i>Αποστολή';
        }
    });
});
</script>
