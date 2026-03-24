<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\View::e($pageTitle ?? 'Μητρώο Δικαιωμάτων') ?> — Μητρώο Δικαιωμάτων</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- App CSS -->
    <link href="<?= \App\Core\Config::appUrl() ?>/assets/css/app.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<?php
use App\Core\Session;
$appUrl = \App\Core\Config::appUrl();
$role   = Session::role();
?>

<!-- ═══ Header ═══ -->
<header class="app-header bg-white border-bottom" style="border-bottom-width:3px!important;border-color:#1a5276!important;">
    <div class="container-fluid px-3 py-2">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= $appUrl ?>/assets/images/logo.png" alt="ΑΚΝΕΕΔ" style="height:44px;width:auto;">
            <div style="font-size:1.05rem;font-weight:700;line-height:1.35;color:#1a3c5e;text-align:center;flex:1;">
                Αρχή Καταπολέμησης της Νομιμοποίησης
                Εσόδων από Εγκληματικές Δραστηριότητες
            </div>
            <div style="width:44px;flex-shrink:0;"></div>
        </div>
    </div>
</header>

<div class="d-flex flex-grow-1" id="wrapper">

    <!-- ── Sidebar ──────────────────────────────────────────────────── -->
    <nav id="sidebar" class="d-flex flex-column flex-shrink-0 p-3 text-white" style="width:250px;background:#2c5f8a;">
        <a href="<?= $appUrl ?>/dashboard" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="bi bi-shield-lock-fill fs-4 me-2 text-primary"></i>
            <span class="fs-5 fw-semibold">Μητρώο Δικαιωμάτων</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="<?= $appUrl ?>/dashboard" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/dashboard')||$_SERVER['REQUEST_URI']===($appUrl.'/') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="<?= $appUrl ?>/permissions" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/permissions') ? 'active' : '' ?>">
                    <i class="bi bi-key-fill me-2"></i> Δικαιώματα
                </a>
            </li>
            <li>
                <a href="<?= $appUrl ?>/users" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/users') ? 'active' : '' ?>">
                    <i class="bi bi-people-fill me-2"></i> Χρήστες
                </a>
            </li>
            <?php if ($role === 'admin'): ?>
            <li>
                <a href="<?= $appUrl ?>/audit" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/audit') ? 'active' : '' ?>">
                    <i class="bi bi-clock-history me-2"></i> Ιστορικό
                </a>
            </li>
            <li>
                <a href="<?= $appUrl ?>/resources" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/resources') ? 'active' : '' ?>">
                    <i class="bi bi-hdd-network-fill me-2"></i> Πόροι
                </a>
            </li>
            <li>
                <a href="<?= $appUrl ?>/settings" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/settings') ? 'active' : '' ?>">
                    <i class="bi bi-gear-fill me-2"></i> Ρυθμίσεις
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="<?= $appUrl ?>/network-graph" class="nav-link text-white <?= str_contains($_SERVER['REQUEST_URI'],'/network-graph') ? 'active' : '' ?>">
                    <i class="bi bi-diagram-3 me-2"></i> Γράφημα Δικτύου
                </a>
            </li>
            <?php if ($role === 'admin' || Session::department() === 'Υποδιεύθυνση Ψηφιακής Διακυβέρνησης'): ?>
            <li>
                <a href="<?= $appUrl ?>/docs/DOCUMENTATION.docx" class="nav-link text-white" target="_blank" download>
                    <i class="bi bi-file-earmark-text me-2"></i> Τεκμηρίωση
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <hr>
        <?php
        $roleLabels = ['admin' => 'Διαχειριστής', 'manager' => 'Προϊστάμενος', 'viewer' => 'Χρήστης'];
        $currentRole = Session::role();
        $roleLabel   = $roleLabels[$currentRole] ?? $currentRole;
        $roleBadge   = $currentRole === 'admin' ? 'bg-danger' : ($currentRole === 'manager' ? 'bg-warning text-dark' : 'bg-secondary');
        ?>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle fs-5 me-2"></i>
                <span class="small"><?= \App\Core\View::e(Session::get('full_name', Session::get('username','Χρήστης'))) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" style="min-width:220px;">
                <li class="px-3 py-2">
                    <div class="fw-semibold"><?= \App\Core\View::e(Session::get('full_name', '')) ?></div>
                    <div class="text-muted small"><?= \App\Core\View::e(Session::get('username', '')) ?></div>
                    <?php if (Session::get('email')): ?>
                    <div class="text-muted small"><i class="bi bi-envelope me-1"></i><?= \App\Core\View::e(Session::get('email')) ?></div>
                    <?php endif; ?>
                    <span class="badge <?= $roleBadge ?> mt-1"><?= \App\Core\View::e($roleLabel) ?></span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= $appUrl ?>/logout"><i class="bi bi-box-arrow-right me-1"></i> Αποσύνδεση</a></li>
            </ul>
        </div>
    </nav>

    <!-- ── Main Content ──────────────────────────────────────────────── -->
    <div id="page-content-wrapper" class="flex-grow-1">

        <!-- Impersonate banner -->
        <?php if (Session::isImpersonating()): ?>
        <div class="alert alert-warning text-center mb-0 py-2 rounded-0 d-flex align-items-center justify-content-center gap-3" style="font-size:.85rem;">
            <i class="bi bi-eye-fill"></i>
            <span>
                <strong>Προβολή ως <?= Session::role() === 'manager' ? 'Προϊστάμενος' : 'Viewer' ?></strong>
                <?php if (Session::department()): ?>
                    — Τμήμα: <strong><?= \App\Core\View::e(Session::department()) ?></strong>
                <?php endif; ?>
            </span>
            <a href="<?= $appUrl ?>/impersonate/stop" class="btn btn-sm btn-dark">
                <i class="bi bi-x-lg me-1"></i>Επιστροφή σε Admin
            </a>
        </div>
        <?php endif; ?>

        <!-- Top bar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2">
            <button class="btn btn-sm btn-outline-secondary me-3" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-text fw-semibold text-muted"><?= \App\Core\View::e($pageTitle ?? '') ?></span>
            <div class="ms-auto d-flex gap-2 align-items-center">
                <?php if (Session::isAdmin() && !Session::isImpersonating()): ?>
                <a href="<?= $appUrl ?>/permissions/bulk" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-people-fill"></i> Μαζική Ανάθεση
                </a>
                <a href="<?= $appUrl ?>/permissions/create" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Νέο Δικαίωμα
                </a>

                <!-- Impersonate button -->
                <div class="dropdown ms-2">
                    <button class="btn btn-sm btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown" title="Προβολή ως...">
                        <i class="bi bi-eye"></i> Προβολή ως...
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-3 shadow" style="min-width:280px;">
                        <h6 class="dropdown-header px-0">Προβολή ως Προϊστάμενος</h6>
                        <form method="POST" action="<?= $appUrl ?>/impersonate/start">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="imp_role" value="manager">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold mb-1">Τμήμα</label>
                                <select name="imp_department" class="form-select form-select-sm" required>
                                    <option value="">— Επιλέξτε τμήμα —</option>
                                    <?php
                                    $depts = \App\Core\Database::getInstance()->fetchAll(
                                        'SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != "" AND is_active = 1 ORDER BY department'
                                    );
                                    foreach ($depts as $d): ?>
                                    <option value="<?= \App\Core\View::e($d['department']) ?>">
                                        <?= \App\Core\View::e($d['department']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-warning w-100">
                                <i class="bi bi-eye-fill me-1"></i>Προβολή
                            </button>
                        </form>
                    </div>
                </div>
                <?php elseif (Session::isAdmin()): ?>
                <!-- While impersonating, still show admin buttons but greyed -->
                <?php endif; ?>
            </div>
        </nav>

        <!-- Flash messages -->
        <div class="container-fluid px-4 pt-3">
            <?php
            $success = Session::flash('success');
            $error   = Session::flash('error');
            if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Page content -->
        <div class="container-fluid px-4 pb-5">
            <?= $content ?>
        </div>

    </div><!-- /page-content-wrapper -->
</div><!-- /wrapper -->

<!-- ═══ Footer ═══ -->
<footer class="app-footer py-2 px-3" style="background:#1b2a3d;color:rgba(255,255,255,.6);font-size:.75rem;line-height:1.6;">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            &copy; <?= date('Y') ?> Υποδιεύθυνση Ψηφιακής Διακυβέρνησης &mdash;
            Τμήμα Ανάπτυξης και Υποστήριξης Εφαρμογών
            <span style="display:inline-block;background:rgba(255,255,255,.12);padding:1px 10px;border-radius:10px;font-size:.7rem;margin-left:6px;">v1.0</span>
        </div>
        <div id="footerClock" style="color:rgba(255,255,255,.8);font-size:.8rem;">
            <i class="bi bi-calendar3 me-1"></i><span id="clockText"></span>
        </div>
    </div>
</footer>

<script>
(function() {
    var el = document.getElementById('clockText');
    if (!el) return;
    function tick() {
        var d = new Date();
        var days = ['Κυριακή','Δευτέρα','Τρίτη','Τετάρτη','Πέμπτη','Παρασκευή','Σάββατο'];
        var day = days[d.getDay()];
        var dd = String(d.getDate()).padStart(2,'0');
        var mm = String(d.getMonth()+1).padStart(2,'0');
        var yyyy = d.getFullYear();
        var hh = String(d.getHours()).padStart(2,'0');
        var mi = String(d.getMinutes()).padStart(2,'0');
        var ss = String(d.getSeconds()).padStart(2,'0');
        el.textContent = day + ' ' + dd + '/' + mm + '/' + yyyy + '  ' + hh + ':' + mi + ':' + ss;
    }
    tick();
    setInterval(tick, 1000);
})();
</script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- App JS -->
<script src="<?= $appUrl ?>/assets/js/app.js"></script>

</body>
</html>
