<?php
use App\Core\{View, Session, Config};
$appUrl = Config::appUrl();
?>

<!-- Stats cards — Row 1: Δικαιώματα & Χρήστες -->
<div class="row g-3 mt-1 justify-content-center">
    <div class="col-sm-6 col-xl-4">
        <a href="<?= $appUrl ?>/permissions" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                        <i class="bi bi-key-fill fs-3 text-primary"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-dark"><?= number_format($stats['total']) ?></div>
                        <div class="text-muted small">Σύνολο Δικαιωμάτων</div>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-4">
        <a href="<?= $appUrl ?>/users" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 p-3">
                        <i class="bi bi-people-fill fs-3 text-success"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-dark"><?= number_format($totalUsers) ?></div>
                        <div class="text-muted small">Χρήστες</div>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Stats cards — Row 2: Πόροι ανά τύπο -->
<div class="row g-3 mt-1 justify-content-center">
    <?php
    $pluralLabels = [
        'Εφαρμογή'             => 'Εφαρμογές',
        'Κοινόχρηστος Φάκελος' => 'Κοινόχρηστοι Φάκελοι',
        'Κοινόχρηστο Mailbox'  => 'Κοινόχρηστα Mailbox',
    ];
    $typeCount = count($stats['byType']);
    foreach ($stats['byType'] as $i => $t):
        $color  = ['info','warning','secondary'][$i % 3];
        $plural = $pluralLabels[$t['label']] ?? $t['label'];
        $colClass = $typeCount <= 3 ? 'col-sm-6 col-xl-4' : 'col-sm-6 col-xl-3';
    ?>
    <div class="<?= $colClass ?>">
        <?php if (Session::isAdmin()): ?>
        <a href="<?= $appUrl ?>/resources/by-type/<?= $t['type_id'] ?>" class="text-decoration-none">
        <?php endif; ?>
            <div class="card border-0 shadow-sm h-100 <?= Session::isAdmin() ? 'card-hover' : '' ?>">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-<?= $color ?> bg-opacity-10 p-3">
                        <i class="<?= View::e($t['icon']) ?> fs-3 text-<?= $color ?>"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-dark"><?= number_format($t['cnt']) ?></div>
                        <div class="text-muted small"><?= View::e($plural) ?></div>
                    </div>
                    <?php if (Session::isAdmin()): ?>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    <?php endif; ?>
                </div>
            </div>
        <?php if (Session::isAdmin()): ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mt-1">
    <!-- Recent permissions -->
    <div class="<?= Session::isManager() ? 'col-12' : 'col-xl-7' ?>">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold border-0 pt-3">
                <i class="bi bi-clock-history me-1 text-primary"></i> Τελευταίες Εγγραφές
                <?php if (Session::isManager()): ?>
                <span class="text-muted small fw-normal ms-2">— <?= View::e(Session::department()) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-app">
                            <tr>
                                <th data-sort="text">Χρήστης</th>
                                <th data-sort="text">Πόρος</th>
                                <th data-sort="text">Δικαίωμα</th>
                                <th data-sort="date">Ημ/νία</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent'] as $r): ?>
                            <tr>
                                <td>
                                    <a href="<?= $appUrl ?>/users/<?= $r['user_id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= View::e($r['full_name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (Session::isAdmin()): ?>
                                    <a href="<?= $appUrl ?>/resources/<?= $r['resource_id'] ?>/permissions"
                                       class="text-decoration-none">
                                        <span class="badge bg-secondary"><?= View::e($r['type_label']) ?></span>
                                        <?= View::e($r['resource_name']) ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="badge bg-secondary"><?= View::e($r['type_label']) ?></span>
                                    <?= View::e($r['resource_name']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-primary"><?= View::e($r['permission_level']) ?></span></td>
                                <td class="text-muted small"><?= substr($r['granted_at'],0,10) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="<?= $appUrl ?>/permissions" class="btn btn-sm btn-outline-primary">Όλα τα δικαιώματα →</a>
            </div>
        </div>
    </div>

    <?php if (!Session::isManager()): ?>
    <!-- By department (admin only) -->
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold border-0 pt-3">
                <i class="bi bi-building me-1 text-primary"></i> Κατανομή ανά Τμήμα
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($stats['byDept'] as $d): ?>
                    <a href="<?= $appUrl ?>/departments/view?dept=<?= urlencode($d['department']) ?>"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                        <span><?= View::e($d['department']) ?></span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-pill"><?= $d['cnt'] ?></span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($stats['byDept'])): ?>
                    <li class="list-group-item text-muted text-center py-3">Δεν υπάρχουν δεδομένα</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
