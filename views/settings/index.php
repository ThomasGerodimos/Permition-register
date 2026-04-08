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
                            <option value="type_admin">Διαχειριστής Πόρου (Type Admin)</option>
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
                            <?php
                            $roleBadge = match($ip['role']) {
                                'admin'      => ['bg-danger', 'Admin'],
                                'manager'    => ['bg-warning text-dark', 'Manager'],
                                'type_admin' => ['bg-info text-dark', 'Type Admin'],
                                default      => ['bg-secondary', $ip['role']],
                            };
                            ?>
                            <span class="badge <?= $roleBadge[0] ?>">
                                <?= $roleBadge[1] ?>
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

<!-- ── Type Admins (Διαχειριστές Τύπου Πόρου) ───────────────────────── -->
<hr class="my-4">
<h5 class="mb-3"><i class="bi bi-person-gear me-1 text-primary"></i> Διαχειριστές Τύπου Πόρου</h5>

<div class="row g-3">
    <!-- Add Type Admin -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 fw-semibold">
                <i class="bi bi-person-plus me-1 text-primary"></i> Ανάθεση Διαχειριστή
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $appUrl ?>/settings/type-admins/store">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Χρήστης <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" id="taUsernameInput"
                               placeholder="username (π.χ. t.gerodimos)" required autocomplete="off">
                        <div id="taAutoSuggest" class="list-group position-absolute shadow-sm" style="z-index:1050;display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Τύπος Πόρου <span class="text-danger">*</span></label>
                        <select name="resource_type_id" class="form-select" required>
                            <option value="">-- Επιλέξτε --</option>
                            <?php foreach ($resourceTypes as $rt): ?>
                            <option value="<?= $rt['id'] ?>">
                                <?= View::e($rt['label']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ανάθεση</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Type Admins list -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 fw-semibold">
                <i class="bi bi-people-fill me-1 text-primary"></i> Υφιστάμενοι Διαχειριστές
                <?php if (!empty($typeAdmins)): ?>
                <span class="badge bg-primary ms-1"><?= count($typeAdmins) ?></span>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="thead-app">
                        <tr>
                            <th>Χρήστης</th>
                            <th>Τύπος Πόρου</th>
                            <th>Ανατέθηκε από</th>
                            <th>Ημ/νία</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($typeAdmins)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Δεν υπάρχουν αναθέσεις</td></tr>
                    <?php else: ?>
                    <?php foreach ($typeAdmins as $ta): ?>
                    <tr>
                        <td>
                            <strong><?= View::e($ta['full_name'] ?? $ta['username']) ?></strong>
                            <div class="text-muted small"><?= View::e($ta['username']) ?> &mdash; <?= View::e($ta['department'] ?? '') ?></div>
                        </td>
                        <td>
                            <i class="<?= View::e($ta['type_icon'] ?? 'bi-file-earmark') ?> me-1 text-secondary"></i>
                            <?= View::e($ta['type_label']) ?>
                        </td>
                        <td class="text-muted small"><?= View::e($ta['created_by_name'] ?? '—') ?></td>
                        <td class="text-muted small"><?= substr($ta['created_at'], 0, 10) ?></td>
                        <td class="text-end">
                            <form method="POST" action="<?= $appUrl ?>/settings/type-admins/<?= $ta['id'] ?>/delete"
                                  class="d-inline" onsubmit="return confirm('Αφαίρεση αυτής της ανάθεσης;')">
                                <?= Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Αφαίρεση">
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

<div class="card border-0 shadow-sm mt-3 border-start border-4 border-warning">
    <div class="card-body py-2">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Ο διαχειριστής τύπου πόρου μπορεί να δημιουργεί, επεξεργάζεται και διαγράφει δικαιώματα <strong>μόνο</strong>
            για τον τύπο πόρου που του έχει ανατεθεί. Δεν έχει πρόσβαση σε Ρυθμίσεις, Πόρους ή Ιστορικό.
            Η αλλαγή ενεργοποιείται μετά από αποσύνδεση και επανασύνδεση του χρήστη.
        </small>
    </div>
</div>

<!-- Autocomplete for type-admin username -->
<script>
(function(){
    const input = document.getElementById('taUsernameInput');
    const suggest = document.getElementById('taAutoSuggest');
    if (!input || !suggest) return;

    let debounce;
    input.addEventListener('input', function(){
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { suggest.style.display='none'; return; }
        debounce = setTimeout(() => {
            fetch(APP_URL + '/api/ad-search?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    suggest.innerHTML = '';
                    if (!data.length) { suggest.style.display='none'; return; }
                    data.forEach(u => {
                        const a = document.createElement('a');
                        a.className = 'list-group-item list-group-item-action small';
                        a.href = '#';
                        a.innerHTML = '<strong>'+u.username+'</strong> — '+u.full_name+' <span class="text-muted">('+u.department+')</span>';
                        a.addEventListener('click', e => {
                            e.preventDefault();
                            input.value = u.username;
                            suggest.style.display = 'none';
                        });
                        suggest.appendChild(a);
                    });
                    suggest.style.display = 'block';
                });
        }, 300);
    });
    document.addEventListener('click', e => { if (!input.contains(e.target)) suggest.style.display='none'; });
})();
</script>
