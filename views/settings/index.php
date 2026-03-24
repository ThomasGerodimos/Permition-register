<?php
use App\Core\{View, Csrf, Config};
$appUrl = Config::appUrl();
?>

<div class="row g-3 mt-1">
    <!-- Add IP Restriction -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 fw-semibold">
                <i class="bi bi-shield-check me-1 text-primary"></i> Προσθήκη IP Restriction
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $appUrl ?>/settings/ip/store">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ρόλος <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="admin">Administrator</option>
                            <option value="manager">Manager (Προϊστάμενος)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">IP / CIDR Range <span class="text-danger">*</span></label>
                        <input type="text" name="ip_range" class="form-control font-monospace"
                               placeholder="π.χ. 192.168.1.0/24 ή 10.0.0.5" required>
                        <div class="form-text">Αποδεκτά: single IP ή CIDR notation</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Περιγραφή</label>
                        <input type="text" name="description" class="form-control" placeholder="π.χ. Εσωτερικό δίκτυο">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Προσθήκη</button>
                </form>
            </div>
        </div>
    </div>

    <!-- IP Restrictions list -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 fw-semibold">
                <i class="bi bi-list-ul me-1 text-primary"></i> Υφιστάμενα IP Restrictions
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="thead-app">
                        <tr>
                            <th data-sort="text">Ρόλος</th>
                            <th data-sort="text">IP / CIDR</th>
                            <th data-sort="text">Περιγραφή</th>
                            <th data-sort="text">Κατάσταση</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($ipRestrictions)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Δεν υπάρχουν restrictions</td></tr>
                    <?php else: ?>
                    <?php foreach ($ipRestrictions as $ip): ?>
                    <tr class="<?= $ip['is_active'] ? '' : 'table-secondary opacity-75' ?>">
                        <td>
                            <span class="badge <?= $ip['role']==='admin' ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                <?= $ip['role'] === 'admin' ? 'Admin' : 'Manager' ?>
                            </span>
                        </td>
                        <td class="font-monospace"><?= View::e($ip['ip_range']) ?></td>
                        <td class="text-muted"><?= View::e($ip['description'] ?? '') ?></td>
                        <td>
                            <span class="badge <?= $ip['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $ip['is_active'] ? 'Ενεργό' : 'Ανενεργό' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="<?= $appUrl ?>/settings/ip/<?= $ip['id'] ?>/toggle" class="d-inline">
                                <?= Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-secondary" title="<?= $ip['is_active']?'Απενεργοποίηση':'Ενεργοποίηση' ?>">
                                    <i class="bi bi-<?= $ip['is_active']?'pause':'play' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= $appUrl ?>/settings/ip/<?= $ip['id'] ?>/delete"
                                  class="d-inline" onsubmit="return confirm('Διαγραφή IP restriction;')">
                                <?= Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Info card -->
<div class="card border-0 shadow-sm mt-3 border-start border-4 border-info">
    <div class="card-body py-2">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Αν δεν υπάρχουν restrictions για έναν ρόλο, η πρόσβαση επιτρέπεται από παντού.
            Χρησιμοποιήστε CIDR notation για εύρη IP (π.χ. <code>10.0.0.0/8</code>).
        </small>
    </div>
</div>
