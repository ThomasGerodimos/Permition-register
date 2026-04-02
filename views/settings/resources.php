<?php
use App\Core\{View, Csrf, Config, Session};
$appUrl  = Config::appUrl();
$rows    = $result['rows'];
$total   = $result['total'];
$page    = $result['page'];
$perPage = $result['perPage'];
$pages   = (int)ceil($total / $perPage);
$isAdmin = Session::isAdmin();
$isTypeAdmin = Session::isTypeAdmin();
?>

<div class="row g-3 mt-1">
    <!-- Add Resource -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 fw-semibold">
                <i class="bi bi-hdd-network me-1 text-primary"></i> Νέος Πόρος
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $appUrl ?>/resources/store">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Τύπος Πόρου <span class="text-danger">*</span></label>
                        <select name="resource_type_id" class="form-select" required>
                            <option value="">— Επιλέξτε —</option>
                            <?php foreach ($types as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= View::e($t['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Όνομα <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="π.χ. ERP System" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Τοποθεσία / Path</label>
                        <input type="text" name="location" class="form-control"
                               placeholder="\\\\server\\share ή https://app.company.gr">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Περιγραφή</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Δημιουργία</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Resources list -->
    <div class="col-lg-8">
        <!-- Filters -->
        <form id="resourceFilterForm" method="GET" class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Αναζήτηση</label>
                        <input type="text" name="search" class="form-control form-control-sm filter-live"
                               placeholder="Όνομα, τοποθεσία, περιγραφή..."
                               value="<?= View::e($filters['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Τύπος</label>
                        <select name="type_id" class="form-select form-select-sm filter-instant">
                            <option value="">Όλοι</option>
                            <?php foreach ($types as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($filters['type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                                <?= View::e($t['label']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Φίλτρο</button>
                        <a href="<?= $appUrl ?>/resources" class="btn btn-sm btn-outline-secondary">Καθαρισμός</a>
                    </div>
                </div>
            </div>
        </form>

        <div id="resourceListArea">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small" id="resourceCounter">
                    <strong><?= number_format($total) ?></strong> πόροι
                    <?php if ($pages > 1): ?> — Σελίδα <?= $page ?> από <?= $pages ?><?php endif; ?>
                </span>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="thead-app">
                            <tr>
                                <th data-sort="text">Τύπος</th>
                                <th data-sort="text">Όνομα</th>
                                <th data-sort="text">Τοποθεσία</th>
                                <th data-sort="text">Περιγραφή</th>
                                <th data-sort="num" class="text-center">Δικαιώματα</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Δεν βρέθηκαν πόροι</td></tr>
                        <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <i class="<?= View::e($r['type_icon']) ?> me-1 text-muted"></i>
                                <?= View::e($r['type_label']) ?>
                            </td>
                            <td>
                                <a href="<?= $appUrl ?>/resources/<?= $r['id'] ?>/permissions"
                                   class="text-decoration-none fw-semibold" title="Προβολή δικαιωμάτων">
                                    <?= View::e($r['name']) ?>
                                    <i class="bi bi-box-arrow-up-right ms-1 small text-muted"></i>
                                </a>
                            </td>
                            <td class="text-muted font-monospace"><?= View::e($r['location'] ?? '—') ?></td>
                            <td class="text-muted"><?= View::e($r['description'] ?? '') ?></td>
                            <td class="text-center">
                                <?php if ($r['perm_count'] > 0): ?>
                                <a href="<?= $appUrl ?>/resources/<?= $r['id'] ?>/permissions"
                                   class="badge bg-primary rounded-pill text-decoration-none"><?= $r['perm_count'] ?></a>
                                <?php else: ?>
                                <span class="badge bg-light text-muted rounded-pill">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if ($isAdmin || Session::isTypeAdmin((int)$r['resource_type_id'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-resource"
                                        data-id="<?= $r['id'] ?>"
                                        data-name="<?= View::e($r['name']) ?>"
                                        data-type-id="<?= $r['resource_type_id'] ?>"
                                        data-location="<?= View::e($r['location'] ?? '') ?>"
                                        data-description="<?= View::e($r['description'] ?? '') ?>"
                                        title="Επεξεργασία">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-resource"
                                        data-id="<?= $r['id'] ?>"
                                        data-name="<?= View::e($r['name']) ?>"
                                        data-perm-count="<?= $r['perm_count'] ?>"
                                        title="Διαγραφή">
                                    <i class="bi bi-trash3"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <nav class="mt-3" id="resourcePagination">
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
        </div><!-- /resourceListArea -->
    </div>
</div>

<!-- Edit Resource Modal -->
<div class="modal fade" id="editResourceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editResourceForm">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Επεξεργασία Πόρου</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Τύπος Πόρου <span class="text-danger">*</span></label>
                        <select name="resource_type_id" id="editResType" class="form-select" required>
                            <option value="">— Επιλέξτε —</option>
                            <?php foreach ($types as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= View::e($t['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Όνομα <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editResName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Τοποθεσία / Path</label>
                        <input type="text" name="location" id="editResLocation" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Περιγραφή</label>
                        <textarea name="description" id="editResDescription" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Αποθήκευση</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Resource Confirmation Modal -->
<div class="modal fade" id="deleteResourceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="deleteResourceForm">
                <?= Csrf::field() ?>
                <input type="hidden" name="confirm_delete" value="1">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-1"></i> Διαγραφή Πόρου</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Είστε σίγουροι ότι θέλετε να διαγράψετε τον πόρο <strong id="deleteResName"></strong>;</p>
                    <div id="deletePermWarning" class="alert alert-warning d-none">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Προσοχή!</strong> Ο πόρος αυτός έχει
                        <strong id="deletePermCount"></strong> ενεργά δικαιώματα.
                        <br>Με τη διαγραφή θα <strong>απενεργοποιηθούν</strong> όλα τα δικαιώματα αυτόματα.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i>Διαγραφή
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var APP_URL  = <?= json_encode($appUrl) ?>;
    var form     = document.getElementById('resourceFilterForm');
    var listArea = document.getElementById('resourceListArea');
    var timer    = null;

    function liveFilter(resetPage) {
        var params = new URLSearchParams(new FormData(form));
        if (resetPage) params.delete('page');

        // Save cursor position for the search input
        var searchInput = form.querySelector('input[name="search"]');
        var cursorPos   = searchInput === document.activeElement ? searchInput.selectionStart : null;

        fetch(APP_URL + '/resources?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            var doc     = new DOMParser().parseFromString(html, 'text/html');
            var newList = doc.getElementById('resourceListArea');
            if (newList) {
                listArea.innerHTML = newList.innerHTML;
            }

            // Update URL without reload
            var newUrl = APP_URL + '/resources' + (params.toString() ? '?' + params.toString() : '');
            history.replaceState(null, '', newUrl);

            // Restore cursor position
            if (cursorPos !== null) {
                searchInput.focus();
                searchInput.setSelectionRange(cursorPos, cursorPos);
            }

            // Bind pagination links to AJAX
            bindPaginationLinks();
        });
    }

    // Text input: debounce 400ms, reset to page 1
    form.querySelectorAll('.filter-live').forEach(function(el) {
        el.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(function() { liveFilter(true); }, 400);
        });
    });

    // Dropdown: instant, reset to page 1
    form.querySelectorAll('.filter-instant').forEach(function(el) {
        el.addEventListener('change', function() {
            clearTimeout(timer);
            liveFilter(true);
        });
    });

    // Intercept form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        clearTimeout(timer);
        liveFilter(true);
    });

    // AJAX pagination
    function bindPaginationLinks() {
        listArea.querySelectorAll('.pagination a.page-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var url = new URL(link.href);
                // Set form values from pagination URL
                var pageVal = url.searchParams.get('page');
                var hiddenPage = form.querySelector('input[name="page"]');
                if (!hiddenPage) {
                    hiddenPage = document.createElement('input');
                    hiddenPage.type = 'hidden';
                    hiddenPage.name = 'page';
                    form.appendChild(hiddenPage);
                }
                hiddenPage.value = pageVal;
                liveFilter(false);
            });
        });
    }

    bindPaginationLinks();

    // Edit resource modal (event delegation for AJAX compatibility)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-edit-resource');
        if (!btn) return;

        document.getElementById('editResourceForm').action = APP_URL + '/resources/' + btn.dataset.id + '/update';
        document.getElementById('editResType').value        = btn.dataset.typeId;
        document.getElementById('editResName').value        = btn.dataset.name;
        document.getElementById('editResLocation').value    = btn.dataset.location;
        document.getElementById('editResDescription').value = btn.dataset.description;

        var modal = new bootstrap.Modal(document.getElementById('editResourceModal'));
        modal.show();
    });

    // Delete resource — show modal with permission warning
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-delete-resource');
        if (!btn) return;

        var resId     = btn.dataset.id;
        var resName   = btn.dataset.name;
        var permCount = parseInt(btn.dataset.permCount) || 0;

        document.getElementById('deleteResourceForm').action = APP_URL + '/resources/' + resId + '/delete';
        document.getElementById('deleteResName').textContent = '«' + resName + '»';

        var warning = document.getElementById('deletePermWarning');
        if (permCount > 0) {
            document.getElementById('deletePermCount').textContent = permCount;
            warning.classList.remove('d-none');
        } else {
            warning.classList.add('d-none');
        }

        var modal = new bootstrap.Modal(document.getElementById('deleteResourceModal'));
        modal.show();
    });
});
</script>
