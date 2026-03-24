<?php
use App\Core\{View, Session, Csrf, Config};
$appUrl  = Config::appUrl();
$rows    = $result['rows'];
$total   = $result['total'];
$page    = $result['page'];
$perPage = $result['perPage'];
$pages   = (int)ceil($total / $perPage);
?>

<!-- Filters -->
<form method="GET" action="<?= $appUrl ?>/permissions" id="filterForm" class="card border-0 shadow-sm mt-2 mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Αναζήτηση</label>
                <input type="text" name="search" class="form-control form-control-sm filter-live"
                       placeholder="Χρήστης, πόρος..." value="<?= View::e($filters['search']) ?>" autofocus>
            </div>
            <?php if (Session::isAdmin()): ?>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Τμήμα</label>
                <select name="department" class="form-select form-select-sm filter-instant">
                    <option value="">Όλα</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= View::e($d['department']) ?>" <?= $filters['department']===$d['department']?'selected':'' ?>>
                        <?= View::e($d['department']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Τύπος Πόρου</label>
                <select name="type_id" class="form-select form-select-sm filter-instant">
                    <option value="">Όλοι</option>
                    <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $filters['type_id']==$t['id']?'selected':'' ?>>
                        <?= View::e($t['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Δικαίωμα</label>
                <input type="text" name="permission_level" class="form-control form-control-sm filter-live"
                       placeholder="π.χ. Read" value="<?= View::e($filters['permission_level']) ?>">
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Φίλτρο</button>
                <a href="<?= $appUrl ?>/permissions" class="btn btn-sm btn-outline-secondary">Καθαρισμός</a>
            </div>
        </div>
    </div>
</form>

<!-- Export & Email buttons -->
<div class="d-flex justify-content-between align-items-center mb-2" id="statsRow">
    <div class="text-muted small">
        <strong><?= number_format($total) ?></strong> εγγραφές
        <?php if ($pages > 1): ?> — Σελίδα <?= $page ?> από <?= $pages ?><?php endif; ?>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php
        $qs = http_build_query(array_filter($filters));
        ?>
        <a href="<?= $appUrl ?>/export/csv?<?= $qs ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-filetype-csv"></i> CSV
        </a>
        <a href="<?= $appUrl ?>/export/excel?<?= $qs ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel"></i> Excel
        </a>
        <a href="<?= $appUrl ?>/export/pdf?<?= $qs ?>" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#emailModal">
            <i class="bi bi-envelope-at"></i> Αποστολή Email
        </button>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="thead-app">
                <tr>
                    <th data-sort="text">Χρήστης</th>
                    <th data-sort="text">Τμήμα</th>
                    <th data-sort="text">Τύπος Πόρου</th>
                    <th data-sort="text">Πόρος</th>
                    <th data-sort="text">Δικαίωμα</th>
                    <th data-sort="text">Εγκρίθηκε από</th>
                    <th data-sort="date">Ημ/νία</th>
                    <th data-sort="date">Λήξη</th>
                    <?php if (Session::isAdmin()): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Δεν βρέθηκαν εγγραφές</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <a href="<?= $appUrl ?>/users/<?= $row['user_id'] ?>" class="text-decoration-none fw-semibold">
                            <?= View::e($row['full_name'] ?: $row['username']) ?>
                        </a>
                        <div class="text-muted small"><?= View::e($row['email']) ?></div>
                    </td>
                    <td><span class="text-muted small"><?= View::e($row['department']) ?></span></td>
                    <td>
                        <i class="<?= View::e($row['type_icon']) ?> me-1 text-secondary"></i>
                        <?= View::e($row['type_label']) ?>
                    </td>
                    <td><?= View::e($row['resource_name']) ?></td>
                    <td><span class="badge bg-primary"><?= View::e($row['permission_level']) ?></span></td>
                    <td class="text-muted small"><?= View::e($row['granted_by_name'] ?? '—') ?></td>
                    <td class="text-muted small"><?= substr($row['granted_at'],0,10) ?></td>
                    <td class="text-muted small">
                        <?php if ($row['expires_at']): ?>
                            <?php $exp = new DateTime($row['expires_at']); $now = new DateTime(); ?>
                            <span class="<?= $exp < $now ? 'text-danger' : '' ?>">
                                <?= $exp->format('d/m/Y') ?>
                            </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <?php if (Session::isAdmin()): ?>
                    <td class="text-end">
                        <a href="<?= $appUrl ?>/permissions/<?= $row['id'] ?>/edit"
                           class="btn btn-sm btn-outline-secondary" title="Επεξεργασία">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="<?= $appUrl ?>/permissions/<?= $row['id'] ?>/delete"
                              class="d-inline" onsubmit="return confirm('Διαγραφή αυτού του δικαιώματος;')">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Διαγραφή">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 3);
        $end   = min($pages, $page + 3);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => 1])) ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
        <?php endif; ?>

        <?php for ($p = $start; $p <= $end; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
            <?php if ($end < $pages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pages])) ?>"><?= $pages ?></a></li>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
        <li class="page-item">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-envelope-at me-1"></i> Αποστολή Αναφοράς</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="emailResult" class="mb-2 d-none"></div>
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Εύρος Αναφοράς</label>
                    <select class="form-select" id="emailScope" name="scope">
                        <?php if (Session::isAdmin()): ?>
                        <option value="user">Συγκεκριμένος Χρήστης</option>
                        <option value="department">Τμήμα</option>
                        <?php else: ?>
                        <option value="department">Το Τμήμα μου (<?= View::e(Session::department()) ?>)</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div id="emailUserField" class="mb-3">
                    <label class="form-label">Χρήστης</label>
                    <input type="text" class="form-control" id="emailUsername" placeholder="Αναζήτηση...">
                    <input type="hidden" id="emailUserId" name="user_id">
                    <div id="emailUserSuggestions" class="list-group mt-1 d-none"></div>
                </div>
                <div id="emailDeptField" class="mb-3 d-none">
                    <label class="form-label">Τμήμα</label>
                    <select class="form-select" name="department" id="emailDept">
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= View::e($d['department']) ?>"><?= View::e($d['department']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Μορφή</label>
                    <select class="form-select" name="format" id="emailFormat">
                        <option value="pdf">PDF</option>
                        <option value="xlsx">Excel (.xlsx)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Παραλήπτη</label>
                    <input type="email" class="form-control" id="emailRecipient" name="recipient_email"
                           placeholder="Προαιρετικό — χρησιμοποιείται το AD email">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση</button>
                <button type="button" class="btn btn-primary" id="btnSendEmail">
                    <i class="bi bi-send"></i> Αποστολή
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Live filtering script -->
<script>
(function() {
    var form = document.getElementById('filterForm');
    if (!form) return;

    var debounceTimer;
    var currentRequest = null;

    function liveFilter() {
        var params = new URLSearchParams(new FormData(form));
        var keys = [];
        params.forEach(function(v, k) { if (!v) keys.push(k); });
        keys.forEach(function(k) { params.delete(k); });
        params.delete('page');

        var url = form.action + '?' + params.toString();
        history.replaceState(null, '', url);

        if (currentRequest) currentRequest.abort();
        var controller = new AbortController();
        currentRequest = controller;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal
        })
        .then(function(res) { return res.text(); })
        .then(function(html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');

            // Update table
            var newTable = doc.querySelector('.table-responsive');
            var oldTable = document.querySelector('.table-responsive');
            if (newTable && oldTable) {
                oldTable.innerHTML = newTable.innerHTML;
            }

            // Update stats row
            var newStats = doc.getElementById('statsRow');
            var oldStats = document.getElementById('statsRow');
            if (newStats && oldStats) {
                oldStats.innerHTML = newStats.innerHTML;
            }

            // Update pagination
            var newPag = doc.querySelector('nav.mt-3');
            var oldPag = document.querySelector('nav.mt-3');
            if (newPag && oldPag) {
                oldPag.outerHTML = newPag.outerHTML;
            } else if (!newPag && oldPag) {
                oldPag.remove();
            } else if (newPag && !oldPag) {
                var tc = document.querySelector('.table-responsive');
                if (tc) tc.closest('.card').insertAdjacentHTML('afterend', newPag.outerHTML);
            }

            currentRequest = null;
        })
        .catch(function(err) {
            if (err.name !== 'AbortError') console.error('Filter error:', err);
        });
    }

    // Text inputs: debounce 400ms
    form.querySelectorAll('.filter-live').forEach(function(input) {
        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(liveFilter, 400);
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                liveFilter();
            }
        });
    });

    // Dropdowns: immediate
    form.querySelectorAll('.filter-instant').forEach(function(sel) {
        sel.addEventListener('change', function() {
            clearTimeout(debounceTimer);
            liveFilter();
        });
    });

    // Prevent form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        clearTimeout(debounceTimer);
        liveFilter();
    });
})();
</script>
