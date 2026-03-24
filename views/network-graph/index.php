<?php
$appUrl = \App\Core\Config::appUrl();
$role   = \App\Core\Session::role();

// Encode data for JS
$nodesJson = json_encode($nodes, JSON_UNESCAPED_UNICODE);
$edgesJson = json_encode($edges, JSON_UNESCAPED_UNICODE);
$typesJson = json_encode($resourceTypes, JSON_UNESCAPED_UNICODE);
?>

<!-- vis.js CDN -->
<link href="https://cdn.jsdelivr.net/npm/vis-network@9.1.9/styles/vis-network.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/vis-network@9.1.9/standalone/umd/vis-network.min.js"></script>

<div class="container-fluid py-3">

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Γράφημα Δικτύου Πρόσβασης</h4>
        <div class="d-flex gap-2">
            <span class="badge bg-primary fs-6"><i class="bi bi-people-fill me-1"></i><?= $totalUsers ?> Χρήστες</span>
            <span class="badge bg-success fs-6"><i class="bi bi-hdd-network me-1"></i><?= $totalResources ?> Πόροι</span>
            <span class="badge bg-secondary fs-6"><i class="bi bi-link-45deg me-1"></i><?= $totalEdges ?> Δικαιώματα</span>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 fw-semibold small">Φίλτρα:</label>
                </div>
                <?php if ($role === 'admin'): ?>
                <div class="col-md-3">
                    <select id="filterDept" class="form-select form-select-sm">
                        <option value="">Όλα τα Τμήματα</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d['department']) ?>"><?= htmlspecialchars($d['department']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" id="filterDept" value="">
                <?php endif; ?>
                <div class="col-md-3">
                    <select id="filterType" class="form-select form-select-sm">
                        <option value="">Όλοι οι Τύποι Πόρων</option>
                        <?php foreach ($resourceTypes as $t): ?>
                        <option value="resource_<?= $t['id'] ?>"><?= htmlspecialchars($t['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterLevel" class="form-select form-select-sm">
                        <option value="">Όλα τα Επίπεδα</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" id="btnFit" title="Προσαρμογή"><i class="bi bi-arrows-fullscreen"></i></button>
                        <button class="btn btn-outline-secondary" id="btnPhysics" title="Φυσική On/Off"><i class="bi bi-snow"></i></button>
                        <button class="btn btn-outline-secondary" id="btnReset" title="Επαναφορά"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Graph -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body p-0">
                    <div id="networkGraph" style="height:650px;border-radius:.375rem;"></div>
                </div>
            </div>
        </div>

        <!-- Legend + Info -->
        <div class="col-lg-3">
            <!-- Legend -->
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0"><i class="bi bi-palette me-1"></i>Υπόμνημα</h6></div>
                <div class="card-body py-2">
                    <div class="d-flex align-items-center mb-2">
                        <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:#4A90D9;margin-right:8px;"></span>
                        <small>Χρήστες</small>
                    </div>
                    <?php
                    $typeColors = ['#2ECC71','#E67E22','#9B59B6','#E74C3C','#1ABC9C','#F39C12','#3498DB','#D35400'];
                    foreach ($resourceTypes as $i => $t):
                        $color = $typeColors[$i % count($typeColors)];
                    ?>
                    <div class="d-flex align-items-center mb-2">
                        <span style="display:inline-block;width:18px;height:18px;border-radius:3px;background:<?= $color ?>;margin-right:8px;"></span>
                        <small><i class="bi bi-<?= htmlspecialchars($t['icon']) ?> me-1"></i><?= htmlspecialchars($t['label']) ?></small>
                    </div>
                    <?php endforeach; ?>
                    <hr class="my-2">
                    <small class="text-muted d-block mb-1"><strong>Γραμμές σύνδεσης:</strong></small>
                    <div class="d-flex align-items-center mb-1">
                        <span style="display:inline-block;width:20px;height:3px;background:#27AE60;margin-right:8px;"></span>
                        <small>Read</small>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                        <span style="display:inline-block;width:20px;height:3px;background:#E67E22;margin-right:8px;"></span>
                        <small>Write</small>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                        <span style="display:inline-block;width:20px;height:3px;background:#E74C3C;margin-right:8px;"></span>
                        <small>Full Control</small>
                    </div>
                </div>
            </div>

            <!-- Node info panel -->
            <div class="card" id="infoPanel" style="display:none;">
                <div class="card-header py-2"><h6 class="mb-0"><i class="bi bi-info-circle me-1"></i>Πληροφορίες</h6></div>
                <div class="card-body py-2" id="infoPanelBody">
                </div>
            </div>

            <!-- Stats -->
            <div class="card mt-3" id="statsCard">
                <div class="card-header py-2"><h6 class="mb-0"><i class="bi bi-bar-chart me-1"></i>Στατιστικά</h6></div>
                <div class="card-body py-2">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Χρήστες</td><td class="fw-bold text-end"><?= $totalUsers ?></td></tr>
                        <tr><td class="text-muted">Πόροι</td><td class="fw-bold text-end"><?= $totalResources ?></td></tr>
                        <tr><td class="text-muted">Δικαιώματα</td><td class="fw-bold text-end"><?= $totalEdges ?></td></tr>
                        <tr><td class="text-muted">Μ.Ο. / χρήστη</td><td class="fw-bold text-end"><?= $totalUsers > 0 ? round($totalEdges / $totalUsers, 1) : 0 ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // ── Data ──
    var rawNodes = <?= $nodesJson ?>;
    var rawEdges = <?= $edgesJson ?>;
    var resourceTypes = <?= $typesJson ?>;

    // Color palette
    var typeColors = ['#2ECC71','#E67E22','#9B59B6','#E74C3C','#1ABC9C','#F39C12','#3498DB','#D35400'];
    var edgeColors = {
        'Read': '#27AE60', 'Write': '#E67E22', 'Full Control': '#E74C3C',
        'Ανάγνωση': '#27AE60', 'Εγγραφή': '#E67E22', 'Πλήρης Έλεγχος': '#E74C3C'
    };

    // Populate permission levels filter
    var levelsSet = {};
    rawEdges.forEach(function(e) { levelsSet[e.level] = true; });
    var filterLevel = document.getElementById('filterLevel');
    Object.keys(levelsSet).sort().forEach(function(lv) {
        var opt = document.createElement('option');
        opt.value = lv; opt.textContent = lv;
        filterLevel.appendChild(opt);
    });

    // Build type color map
    var typeColorMap = {};
    resourceTypes.forEach(function(t, i) {
        typeColorMap['resource_' + t.id] = typeColors[i % typeColors.length];
    });

    // ── Build vis.js datasets ──
    function buildVisNodes(data) {
        return data.map(function(n) {
            var isUser = n.group === 'user';
            var color = isUser ? '#4A90D9' : (typeColorMap[n.group] || '#95A5A6');
            return {
                id: n.id,
                label: n.label,
                group: n.group,
                department: n.department || '',
                typeLabel: n.typeLabel || '',
                title: (n.title || '').replace(/\n/g, '<br>'),
                shape: isUser ? 'dot' : 'diamond',
                size: isUser ? 18 : 22,
                color: { background: color, border: color, highlight: { background: color, border: '#2C3E50' } },
                font: { color: '#2C3E50', size: 12, face: 'Arial' },
                borderWidth: 2,
                shadow: true,
                _group: n.group,
                _dept: n.department || '',
            };
        });
    }

    function buildVisEdges(data) {
        return data.map(function(e, i) {
            var col = edgeColors[e.level] || '#BDC3C7';
            return {
                id: 'e_' + i,
                from: e.from,
                to: e.to,
                label: e.label,
                _level: e.level,
                color: { color: col, highlight: col, opacity: 0.8 },
                width: e.level === 'Full Control' || e.level === 'Πλήρης Έλεγχος' ? 3 : 2,
                font: { size: 9, color: '#7F8C8D', strokeWidth: 2, strokeColor: '#fff' },
                arrows: { to: { enabled: true, scaleFactor: 0.6 } },
                smooth: { type: 'continuous', roundness: 0.2 },
            };
        });
    }

    var visNodes = new vis.DataSet(buildVisNodes(rawNodes));
    var visEdges = new vis.DataSet(buildVisEdges(rawEdges));

    // ── Network ──
    var container = document.getElementById('networkGraph');
    var network = new vis.Network(container, { nodes: visNodes, edges: visEdges }, {
        physics: {
            enabled: true,
            solver: 'forceAtlas2Based',
            forceAtlas2Based: {
                gravitationalConstant: -40,
                centralGravity: 0.008,
                springLength: 150,
                springConstant: 0.06,
                damping: 0.4,
                avoidOverlap: 0.5
            },
            stabilization: { iterations: 200, updateInterval: 25 }
        },
        interaction: {
            hover: true,
            tooltipDelay: 200,
            navigationButtons: true,
            keyboard: { enabled: true }
        },
        layout: { improvedLayout: true }
    });

    // ── Filters ──
    var filterDept = document.getElementById('filterDept');
    var filterType = document.getElementById('filterType');

    function applyFilters() {
        var dept  = filterDept.value;
        var type  = filterType.value;
        var level = filterLevel.value;

        // Re-build nodes/edges from raw data
        var filteredNodes = rawNodes.slice();
        var filteredEdges = rawEdges.slice();

        // Filter by department (users)
        if (dept) {
            var deptUserIds = {};
            filteredNodes = filteredNodes.filter(function(n) {
                if (n.group === 'user') {
                    if (n.department === dept) { deptUserIds[n.id] = true; return true; }
                    return false;
                }
                return true;
            });
            filteredEdges = filteredEdges.filter(function(e) { return deptUserIds[e.from]; });
        }

        // Filter by resource type
        if (type) {
            var typeResIds = {};
            filteredNodes = filteredNodes.filter(function(n) {
                if (n.group !== 'user') {
                    if (n.group === type) { typeResIds[n.id] = true; return true; }
                    return false;
                }
                return true;
            });
            filteredEdges = filteredEdges.filter(function(e) { return typeResIds[e.to]; });
        }

        // Filter by permission level
        if (level) {
            filteredEdges = filteredEdges.filter(function(e) { return e.level === level; });
        }

        // Remove orphan nodes (no edges)
        var connectedIds = {};
        filteredEdges.forEach(function(e) { connectedIds[e.from] = true; connectedIds[e.to] = true; });
        filteredNodes = filteredNodes.filter(function(n) { return connectedIds[n.id]; });

        visNodes.clear();
        visEdges.clear();
        visNodes.add(buildVisNodes(filteredNodes));
        visEdges.add(buildVisEdges(filteredEdges));

        // Update stats
        var users = filteredNodes.filter(function(n) { return n.group === 'user'; }).length;
        var resources = filteredNodes.filter(function(n) { return n.group !== 'user'; }).length;
        document.querySelector('#statsCard .table').innerHTML =
            '<tr><td class="text-muted">Χρήστες</td><td class="fw-bold text-end">' + users + '</td></tr>' +
            '<tr><td class="text-muted">Πόροι</td><td class="fw-bold text-end">' + resources + '</td></tr>' +
            '<tr><td class="text-muted">Δικαιώματα</td><td class="fw-bold text-end">' + filteredEdges.length + '</td></tr>' +
            '<tr><td class="text-muted">Μ.Ο. / χρήστη</td><td class="fw-bold text-end">' + (users > 0 ? (filteredEdges.length / users).toFixed(1) : 0) + '</td></tr>';

        setTimeout(function() { network.fit({ animation: true }); }, 300);
    }

    filterDept.addEventListener('change', applyFilters);
    filterType.addEventListener('change', applyFilters);
    filterLevel.addEventListener('change', applyFilters);

    // ── Toolbar buttons ──
    document.getElementById('btnFit').addEventListener('click', function() {
        network.fit({ animation: { duration: 500 } });
    });

    var physicsOn = true;
    document.getElementById('btnPhysics').addEventListener('click', function() {
        physicsOn = !physicsOn;
        network.setOptions({ physics: { enabled: physicsOn } });
        this.classList.toggle('btn-outline-secondary', !physicsOn || physicsOn);
        this.classList.toggle('active', !physicsOn);
        this.title = physicsOn ? 'Φυσική On/Off (Ενεργή)' : 'Φυσική On/Off (Ανενεργή)';
    });

    document.getElementById('btnReset').addEventListener('click', function() {
        filterDept.value = '';
        filterType.value = '';
        filterLevel.value = '';
        applyFilters();
    });

    // ── Click node → info panel ──
    network.on('click', function(params) {
        var panel = document.getElementById('infoPanel');
        var body  = document.getElementById('infoPanelBody');

        if (params.nodes.length === 0) {
            panel.style.display = 'none';
            return;
        }

        var nodeId = params.nodes[0];
        var node = rawNodes.find(function(n) { return n.id === nodeId; });
        if (!node) { panel.style.display = 'none'; return; }

        var isUser = node.group === 'user';
        var html = '';

        if (isUser) {
            // Find connected resources
            var userEdges = rawEdges.filter(function(e) { return e.from === nodeId; });
            html += '<div class="text-center mb-2"><i class="bi bi-person-circle" style="font-size:2rem;color:#4A90D9;"></i></div>';
            html += '<h6 class="text-center mb-1">' + escHtml(node.label) + '</h6>';
            html += '<p class="text-center text-muted small mb-2">' + escHtml(node.department || '') + '</p>';
            html += '<hr class="my-2">';
            html += '<small class="fw-bold">Πρόσβαση σε ' + userEdges.length + ' πόρους:</small>';
            html += '<ul class="list-unstyled mt-1 mb-0">';
            userEdges.forEach(function(e) {
                var res = rawNodes.find(function(n) { return n.id === e.to; });
                var col = edgeColors[e.level] || '#95A5A6';
                html += '<li class="small mb-1"><span style="color:' + col + ';">&#9679;</span> ' +
                         escHtml(res ? res.label : e.to) + ' <span class="text-muted">(' + escHtml(e.level) + ')</span></li>';
            });
            html += '</ul>';
        } else {
            // Find connected users
            var resEdges = rawEdges.filter(function(e) { return e.to === nodeId; });
            var color = typeColorMap[node.group] || '#95A5A6';
            html += '<div class="text-center mb-2"><i class="bi bi-' + escHtml(node.typeIcon || 'hdd-network') + '" style="font-size:2rem;color:' + color + ';"></i></div>';
            html += '<h6 class="text-center mb-1">' + escHtml(node.label) + '</h6>';
            html += '<p class="text-center text-muted small mb-2">' + escHtml(node.typeLabel || '') + '</p>';
            html += '<hr class="my-2">';
            html += '<small class="fw-bold">' + resEdges.length + ' χρήστες έχουν πρόσβαση:</small>';
            html += '<ul class="list-unstyled mt-1 mb-0">';
            resEdges.forEach(function(e) {
                var usr = rawNodes.find(function(n) { return n.id === e.from; });
                var col = edgeColors[e.level] || '#95A5A6';
                html += '<li class="small mb-1"><span style="color:' + col + ';">&#9679;</span> ' +
                         escHtml(usr ? usr.label : e.from) + ' <span class="text-muted">(' + escHtml(e.level) + ')</span></li>';
            });
            html += '</ul>';
        }

        body.innerHTML = html;
        panel.style.display = '';
    });

    // ── Highlight connected on hover ──
    network.on('hoverNode', function(params) {
        var nodeId = params.node;
        var connected = network.getConnectedNodes(nodeId);
        connected.push(nodeId);

        visNodes.forEach(function(n) {
            if (connected.indexOf(n.id) === -1) {
                visNodes.update({ id: n.id, opacity: 0.2 });
            }
        });
    });

    network.on('blurNode', function() {
        visNodes.forEach(function(n) {
            visNodes.update({ id: n.id, opacity: 1.0 });
        });
    });

    function escHtml(s) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(s || ''));
        return div.innerHTML;
    }
})();
</script>
