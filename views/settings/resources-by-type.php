<?php
use App\Core\{View, Config};
$appUrl = Config::appUrl();
$pages  = (int)ceil($total / $perPage);
?>

<!-- Type Header -->
<div class="card border-0 shadow-sm mt-2 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="<?= View::e($type['icon'] ?? 'bi bi-hdd-network') ?> fs-2 text-primary"></i>
                    <h4 class="mb-0 fw-bold"><?= View::e($type['label']) ?></h4>
                </div>
            </div>
            <a href="<?= $appUrl ?>/dashboard" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-auto">
                <div class="bg-primary bg-opacity-10 rounded-3 px-3 py-2 text-center">
                    <div class="fs-3 fw-bold text-primary"><?= $total ?></div>
                    <small class="text-muted">Πόροι</small>
                </div>
            </div>
            <div class="col-auto">
                <div class="bg-success bg-opacity-10 rounded-3 px-3 py-2 text-center">
                    <div class="fs-3 fw-bold text-success"><?= $totalPerms ?></div>
                    <small class="text-muted">Δικαιώματα</small>
                </div>
            </div>
            <div class="col-auto">
                <div class="bg-info bg-opacity-10 rounded-3 px-3 py-2 text-center">
                    <div class="fs-3 fw-bold text-info"><?= $totalUsers ?></div>
                    <small class="text-muted">Χρήστες</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between mb-2">
    <span class="text-muted small">
        <strong><?= $total ?></strong> πόροι
        <?php if ($pages > 1): ?> — Σελίδα <?= $page ?> από <?= $pages ?><?php endif; ?>
    </span>
</div>

<?php if (empty($resources)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
        <p>Δεν υπάρχουν πόροι αυτού του τύπου.</p>
    </div>
</div>
<?php else: ?>

<?php foreach ($resources as $r): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="<?= $appUrl ?>/resources/<?= $r['id'] ?>/permissions"
                   class="fs-5 fw-bold text-decoration-none">
                    <i class="<?= View::e($r['type_icon']) ?> me-1 text-primary"></i>
                    <?= View::e($r['name']) ?>
                </a>
                <?php if (!empty($r['location'])): ?>
                <span class="text-muted small ms-2 font-monospace"><?= View::e($r['location']) ?></span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($r['perm_count'] > 0): ?>
                <span class="badge bg-primary rounded-pill"><?= $r['perm_count'] ?> δικαιώματα</span>
                <?php else: ?>
                <span class="badge bg-light text-muted rounded-pill">Χωρίς δικαιώματα</span>
                <?php endif; ?>
                <a href="<?= $appUrl ?>/resources/<?= $r['id'] ?>/permissions"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>Λεπτομέρειες
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($r['grouped'])): ?>
    <div class="card-body pt-0">
        <?php foreach ($r['grouped'] as $level => $users): ?>
        <div class="mb-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary px-3"><?= View::e($level) ?></span>
                <small class="text-muted"><?= count($users) ?> χρήστες</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($users as $p): ?>
                <a href="<?= $appUrl ?>/users/<?= $p['user_id'] ?>"
                   class="d-inline-flex align-items-center gap-1 bg-light rounded-pill px-3 py-1 text-decoration-none small"
                   title="<?= View::e($p['department'] ?? '') ?>">
                    <i class="bi bi-person-fill text-primary"></i>
                    <span class="fw-semibold text-dark"><?= View::e($p['full_name'] ?: $p['username']) ?></span>
                    <span class="text-muted"><?= View::e($p['username']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card-body pt-0">
        <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Κανένας χρήστης δεν έχει δικαίωμα σε αυτόν τον πόρο.</p>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?page=<?= $page - 1 ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 3);
        $end   = min($pages, $page + 3);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
        <?php endif; ?>

        <?php for ($p = $start; $p <= $end; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
            <?php if ($end < $pages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $pages ?>"><?= $pages ?></a></li>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
        <li class="page-item">
            <a class="page-link" href="?page=<?= $page + 1 ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
