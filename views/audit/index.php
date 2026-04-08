<?php
use App\Core\{View, Config};
$appUrl  = Config::appUrl();
$rows    = $result['rows'];
$total   = $result['total'];
$page    = $result['page'];
$perPage = $result['perPage'];
$pages   = (int)ceil($total / $perPage);
?>

<!-- Filters -->
<form method="GET" class="card border-0 shadow-sm mt-2 mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Ενέργεια</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">Όλες</option>
                    <option value="create"  <?= $filters['action']==='create'  ?'selected':'' ?>>Δημιουργία</option>
                    <option value="update"  <?= $filters['action']==='update'  ?'selected':'' ?>>Ενημέρωση</option>
                    <option value="delete"  <?= $filters['action']==='delete'  ?'selected':'' ?>>Διαγραφή</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Χρήστης</label>
                <select name="changed_by" class="form-select form-select-sm">
                    <option value="">Όλοι</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filters['changed_by']==$u['id']?'selected':'' ?>>
                        <?= View::e($u['full_name'] ?: $u['username']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Από</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= View::e($filters['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Έως</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= View::e($filters['date_to']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Αναζήτηση</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= View::e($filters['search']) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Φίλτρο</button>
                <a href="<?= $appUrl ?>/audit" class="btn btn-sm btn-outline-secondary">Καθαρισμός</a>
            </div>
        </div>
    </div>
</form>

<div class="d-flex justify-content-between mb-2">
    <span class="text-muted small">
        <strong><?= number_format($total) ?></strong> εγγραφές
        <?php if ($pages > 1): ?> — Σελίδα <?= $page ?> από <?= $pages ?><?php endif; ?>
    </span>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="thead-app">
                <tr>
                    <th data-sort="num">#</th>
                    <th data-sort="text">Ενέργεια</th>
                    <th data-sort="text">Πίνακας</th>
                    <th data-sort="text">Περιγραφή</th>
                    <th data-sort="text">Από</th>
                    <th data-sort="text">IP</th>
                    <th data-sort="date">Ημερομηνία</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Δεν βρέθηκαν εγγραφές</td></tr>
            <?php else: ?>
            <?php foreach ($rows as $row): ?>
            <?php
            $badgeClass = match($row['action']) {
                'create' => 'bg-success',
                'update' => 'bg-warning text-dark',
                'delete' => 'bg-danger',
                default  => 'bg-secondary',
            };
            $actionLabel = match($row['action']) {
                'create' => 'Δημιουργία',
                'update' => 'Ενημέρωση',
                'delete' => 'Διαγραφή',
                default  => $row['action'],
            };
            ?>
            <tr>
                <td class="text-muted"><?= $row['id'] ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= $actionLabel ?></span></td>
                <td class="text-muted font-monospace"><?= View::e($row['table_name']) ?></td>
                <td><?= View::e($row['description']) ?></td>
                <td><?= View::e($row['full_name'] ?: $row['username'] ?: '—') ?></td>
                <td class="text-muted font-monospace"><?= View::e($row['ip_address'] ?? '—') ?></td>
                <td class="text-muted"><?= $row['created_at'] ?></td>
                <td>
                    <?php if ($row['old_values'] || $row['new_values']): ?>
                    <button class="btn btn-xs btn-outline-secondary btn-sm"
                            data-bs-toggle="collapse"
                            data-bs-target="#detail-<?= $row['id'] ?>">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($row['old_values'] || $row['new_values']): ?>
            <tr class="collapse" id="detail-<?= $row['id'] ?>">
                <td colspan="8" class="bg-light">
                    <div class="row g-2 p-2">
                        <?php if ($row['old_values']): ?>
                        <div class="col-md-6">
                            <div class="fw-semibold small mb-1 text-danger">Παλαιές Τιμές</div>
                            <pre class="small mb-0"><?= View::e(json_encode(json_decode($row['old_values'],true),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
                        </div>
                        <?php endif; ?>
                        <?php if ($row['new_values']): ?>
                        <div class="col-md-6">
                            <div class="fw-semibold small mb-1 text-success">Νέες Τιμές</div>
                            <pre class="small mb-0"><?= View::e(json_encode(json_decode($row['new_values'],true),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
                        </div>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
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
