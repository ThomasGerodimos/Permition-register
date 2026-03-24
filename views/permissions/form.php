<?php
use App\Core\{View, Csrf, Config};
$appUrl = Config::appUrl();
$isEdit = $action === 'edit';
$p      = $permission ?? [];
?>

<div class="card border-0 shadow-sm mt-2" style="max-width:700px">
    <div class="card-body p-4">
        <form method="POST"
              action="<?= $appUrl ?>/permissions/<?= $isEdit ? View::e($p['id']).'/update' : 'store' ?>">
            <?= Csrf::field() ?>

            <?php if (!$isEdit): ?>
            <!-- User lookup (autocomplete from AD) -->
            <div class="mb-3 position-relative">
                <label class="form-label fw-semibold">Χρήστης <span class="text-danger">*</span></label>
                <input type="text" id="usernameInput" class="form-control" placeholder="Πληκτρολογήστε username ή ονοματεπώνυμο..."
                       autocomplete="off">
                <input type="hidden" name="username" id="usernameHidden">
                <div id="adSuggestions" class="list-group position-absolute w-100 shadow-sm z-3 d-none"></div>
                <div id="adUserInfo" class="mt-2 p-2 rounded bg-light small d-none"></div>
            </div>
            <?php else: ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Χρήστης</label>
                <input type="text" class="form-control" value="<?= View::e($p['full_name'] ?: $p['username']) ?>" disabled>
            </div>
            <?php endif; ?>

            <!-- Resource Type -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Τύπος Πόρου <span class="text-danger">*</span></label>
                <select class="form-select" id="resourceTypeSelect" required>
                    <option value="">— Επιλέξτε τύπο —</option>
                    <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id'] ?>"
                            data-permissions='<?= htmlspecialchars(json_encode($t['permissions']),ENT_QUOTES) ?>'
                            <?= (!$isEdit && ($_POST['resource_type_id']??'')==$t['id'])?'selected':'' ?>>
                        <?= View::e($t['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Resource -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Πόρος <span class="text-danger">*</span></label>
                <select class="form-select" name="resource_id" id="resourceSelect" required>
                    <option value="">— Επιλέξτε πόρο —</option>
                    <?php foreach ($resources as $r): ?>
                    <option value="<?= $r['id'] ?>"
                            data-type="<?= $r['resource_type_id'] ?>"
                            <?= ($isEdit && $p['resource_id']==$r['id'])?'selected':'' ?>
                            style="display:none">
                        <?= View::e($r['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Permission Level -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Επίπεδο Δικαιώματος <span class="text-danger">*</span></label>
                <select class="form-select" name="permission_level" id="permissionSelect" required>
                    <option value="">— Επιλέξτε δικαίωμα —</option>
                    <?php if ($isEdit): ?>
                    <option value="<?= View::e($p['permission_level']) ?>" selected>
                        <?= View::e($p['permission_level']) ?>
                    </option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Expiry -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Ημερομηνία Λήξης</label>
                <input type="date" name="expires_at" class="form-control"
                       value="<?= View::e($isEdit ? substr($p['expires_at']??'',0,10) : '') ?>">
                <div class="form-text">Αφήστε κενό για χωρίς λήξη</div>
            </div>

            <!-- Notes -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Σημειώσεις</label>
                <textarea name="notes" class="form-control" rows="3"
                          placeholder="Προαιρετικές σημειώσεις..."><?= View::e($isEdit ? ($p['notes']??'') : '') ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-<?= $isEdit ? 'floppy2' : 'plus-lg' ?>"></i>
                    <?= $isEdit ? 'Αποθήκευση' : 'Καταχώρηση' ?>
                </button>
                <a href="<?= $appUrl ?>/permissions" class="btn btn-outline-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</div>

<script>
var APP_URL = '<?= $appUrl ?>';
const IS_EDIT = <?= $isEdit ? 'true' : 'false' ?>;
<?php if ($isEdit): ?>
// Pre-select type, resource and permission level for edit
document.addEventListener('DOMContentLoaded', () => {
    const typeId     = '<?= $p['resource_type_id'] ?? '' ?>';
    const resourceId = '<?= $p['resource_id'] ?? '' ?>';
    const permLevel  = '<?= View::e($p['permission_level'] ?? '') ?>';

    if (typeId) {
        // 1. Select the type
        document.getElementById('resourceTypeSelect').value = typeId;

        // 2. Show resources of this type
        var resSelect = document.getElementById('resourceSelect');
        Array.from(resSelect.options).forEach(function(opt) {
            if (!opt.value) return;
            opt.style.display = (opt.dataset.type === typeId) ? '' : 'none';
        });

        // 3. Re-select the resource
        if (resourceId) {
            resSelect.value = resourceId;
        }

        // 4. Load all permission levels for this type and pre-select
        loadPermissions(typeId, permLevel);
    }
});
<?php endif; ?>
</script>
