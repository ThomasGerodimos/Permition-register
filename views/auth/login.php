<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σύνδεση — Μητρώο Δικαιωμάτων</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            background: linear-gradient(135deg, #2c5f8a 0%, #4a90c4 50%, #6db3d8 100%);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── Header ────────────────────────────────── */
        .login-header {
            background: rgba(255,255,255,.97);
            backdrop-filter: blur(10px);
            border-bottom: 3px solid #1a5276;
            padding: 14px 0;
        }
        .login-header .logo-img {
            height: 52px;
            width: auto;
        }
        .login-header .org-title {
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.4;
            color: #1a3c5e;
            text-align: center;
            flex: 1;
        }

        /* ── Main area ─────────────────────────────── */
        .login-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }
        .login-card {
            max-width: 440px;
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
            border: none;
        }
        .brand-icon { font-size: 2.5rem; color: #0d6efd; }

        /* ── Footer ────────────────────────────────── */
        .login-footer {
            background: rgba(0,0,0,.35);
            backdrop-filter: blur(6px);
            padding: 14px 0;
            color: rgba(255,255,255,.65);
            font-size: .75rem;
            line-height: 1.5;
            text-align: center;
        }
        .login-footer .version {
            display: inline-block;
            background: rgba(255,255,255,.12);
            padding: 1px 10px;
            border-radius: 10px;
            font-size: .7rem;
            margin-left: 6px;
        }
    </style>
</head>
<body>

<?php
use App\Core\{Session, Csrf, View};
$appUrl  = \App\Core\Config::appUrl();
$success = Session::flash('success');
$error   = Session::flash('error');
?>

<!-- ═══ Header ═══ -->
<header class="login-header">
    <div class="container">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= $appUrl ?>/assets/images/logo.png" alt="Organization Logo" class="logo-img">
            <div class="org-title">
                Μητρώο Δικαιωμάτων
            </div>
            <!-- spacer to balance the logo and keep title centered -->
            <div style="width:52px; flex-shrink:0;"></div>
        </div>
    </div>
</header>

<!-- ═══ Login form ═══ -->
<main class="login-main">
    <div class="card login-card mx-3">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock-fill brand-icon"></i>
                <h4 class="mt-2 fw-bold">Μητρώο Δικαιωμάτων</h4>
                <p class="text-muted small">Σύστημα Διαχείρισης Δικαιωμάτων Πρόσβασης</p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success py-2"><i class="bi bi-check-circle-fill me-1"></i> <?= View::e($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= $appUrl ?>/login" autocomplete="off">
                <?= Csrf::field() ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Όνομα Χρήστη (AD)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="domain\username ή username"
                               value="<?= View::e($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Κωδικός</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Σύνδεση μέσω Active Directory
                </button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                Χρησιμοποιήστε τα στοιχεία σύνδεσης του domain σας
            </p>
        </div>
    </div>
</main>

<!-- ═══ Footer ═══ -->
<footer class="login-footer">
    <div class="container">
        &copy; <?= date('Y') ?> Υποδιεύθυνση Ψηφιακής Διακυβέρνησης &mdash;
        Τμήμα Ανάπτυξης και Υποστήριξης Εφαρμογών
        <span class="version">v1.0</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
