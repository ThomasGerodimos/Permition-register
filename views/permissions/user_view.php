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
                <?php if (Session::isAdmin()): ?>
                <a href="<?= $appUrl ?>/permissions/create" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Νέο
                </a>
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
                                <?php if (Session::isAdmin()): ?><th></th><?php endif; ?>
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
                            <?php if (Session::isAdmin()): ?>
                            <td>
                                <a href="<?= $appUrl ?>/permissions/<?= $p['id'] ?>/edit"
                                   class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="<?= $appUrl ?>/permissions/<?= $p['id'] ?>/delete"
                                      class="d-inline" onsubmit="return confirm('Διαγραφή;')">
                                    <?= Csrf::field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                                </form>
                            </td>
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
