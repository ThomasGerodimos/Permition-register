<?php
use App\Core\{View, Session, Config};
$appUrl  = Config::appUrl();
$users   = $result['rows'];
$total   = $result['total'];
$page    = $result['page'];
$perPage = $result['perPage'];
$pages   = (int)ceil($total / $perPage);
?>

<!-- Filters -->
<form method="GET" action="<?= $appUrl ?>/users" id="userFilterForm" class="card border-0 shadow-sm mt-2 mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Αναζήτηση</label>
                <input type="text" name="search" class="form-control form-control-sm filter-live"
                       placeholder="Όνομα, username, email, τμήμα, θέση..."
                       value="<?= View::e($filters['search']) ?>" autofocus>
            </div>
            <?php if (Session::isAdmin()): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Τμήμα</label>
                <select name="department" class="form-select form-select-sm filter-instant">
                    <option value="">Όλα τα τμήματα</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= View::e($d['department']) ?>"
                            <?= ($filters['department'] ?? '') === $d['department'] ? 'selected' : '' ?>>
                        <?= View::e($d['department']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Αναζήτηση</button>
                <a href="<?= $appUrl ?>/users" class="btn btn-sm btn-outline-secondary">Καθαρισμός</a>
            </div>
        </div>
    </div>
</form>

<!-- Count + AD Sync -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small">
        <strong><?= number_format($total) ?></strong> χρήστες
        <?php if ($pages > 1): ?> — Σελίδα <?= $page ?> από <?= $pages ?><?php endif; ?>
    </div>
    <?php if (Session::isAdmin()): ?>
    <form method="POST" action="<?= $appUrl ?>/users/sync-ad"
          onsubmit="return confirm('Συγχρονισμός όλων των χρηστών από το Active Directory;')">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="btn btn-sm btn-outline-primary" id="btnSyncAd">
            <i class="bi bi-arrow-repeat"></i> Συγχρονισμός AD
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- User cards grid -->
<div class="row g-3" id="usersGrid">
    <?php if (empty($users)): ?>
    <div class="col-12 text-center text-muted py-5">
        <i class="bi bi-people fs-1 opacity-25"></i>
        <p class="mt-2">Δεν βρέθηκαν χρήστες</p>
    </div>
    <?php else: ?>
    <?php foreach ($users as $u): ?>
    <div class="col-sm-6 col-md-4 col-xl-3">
        <a href="<?= $appUrl ?>/users/<?= $u['id'] ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body text-center p-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 mx-auto mb-2 d-flex align-items-center justify-content-center"
                         style="width:50px;height:50px">
                        <i class="bi bi-person-fill text-primary"></i>
                    </div>
                    <div class="fw-semibold text-dark"><?= View::e($u['full_name'] ?: $u['username']) ?></div>
                    <div class="text-muted small"><?= View::e($u['username']) ?></div>
                    <?php if (!empty($u['job_title'])): ?>
                    <div class="text-muted small"><?= View::e($u['job_title']) ?></div>
                    <?php endif; ?>
                    <?php if ($u['department']): ?>
                    <span class="badge bg-light text-muted border mt-1"><?= View::e($u['department']) ?></span>
                    <?php endif; ?>
                    <?php if ($u['perm_count'] > 0): ?>
                    <div class="mt-1">
                        <span class="badge bg-primary rounded-pill"><?= $u['perm_count'] ?> δικαιώματα</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination pagination-sm justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php
        // Show max 7 page numbers with ellipsis
        $start = max(1, $page - 3);
        $end   = min($pages, $page + 3);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => 1])) ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
        <?php endif; ?>

        <?php for ($p = $start; $p <= $end; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>">
                <?= $p ?>
            </a>
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

<!-- Live filtering script -->
<script>
(function() {
    const form = document.getElementById('userFilterForm');
    if (!form) return;

    let debounceTimer;
    let currentRequest = null;

    function liveFilter() {
        const params = new URLSearchParams(new FormData(form));
        for (const [key, val] of [...params]) { if (!val) params.delete(key); }
        params.delete('page'); // reset to page 1

        const url = form.action + '?' + params.toString();
        history.replaceState(null, '', url);

        if (currentRequest) currentRequest.abort();
        const controller = new AbortController();
        currentRequest = controller;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal
        })
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');

            // Update grid
            const newGrid = doc.getElementById('usersGrid');
            const oldGrid = document.getElementById('usersGrid');
            if (newGrid && oldGrid) oldGrid.innerHTML = newGrid.innerHTML;

            // Update count
            const newCount = doc.querySelector('.d-flex.justify-content-between .text-muted.small');
            const oldCount = document.querySelector('.d-flex.justify-content-between .text-muted.small');
            if (newCount && oldCount) oldCount.innerHTML = newCount.innerHTML;

            // Update pagination
            const newPag = doc.querySelector('nav.mt-4');
            const oldPag = document.querySelector('nav.mt-4');
            if (newPag && oldPag) oldPag.innerHTML = newPag.innerHTML;
            else if (!newPag && oldPag) oldPag.remove();
            else if (newPag && !oldPag) document.getElementById('usersGrid')?.insertAdjacentHTML('afterend', newPag.outerHTML);

            currentRequest = null;
        })
        .catch(err => { if (err.name !== 'AbortError') console.error(err); });
    }

    form.querySelectorAll('.filter-live').forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(liveFilter, 400);
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); clearTimeout(debounceTimer); liveFilter(); }
        });
    });

    form.querySelectorAll('.filter-instant').forEach(select => {
        select.addEventListener('change', () => { clearTimeout(debounceTimer); liveFilter(); });
    });

    form.addEventListener('submit', e => { e.preventDefault(); clearTimeout(debounceTimer); liveFilter(); });
})();
</script>
