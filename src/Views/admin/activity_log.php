<?php
$pageTitle   = 'Activity Log';
$totalAll    = count($logs);
$kbCount     = 0;
$staffSet    = [];
foreach ($logs as $l) {
    if ($l['is_knowledge']) $kbCount++;
    $staffSet[$l['username']] = true;
}
$uniqueStaff = count($staffSet);
?>

<style>
.al-filter-bar { display:flex;flex-wrap:wrap;gap:.65rem;align-items:flex-end; }
.al-filter-bar .form-label { font-size:.72rem;margin-bottom:.2rem;display:block; }

.al-tag {
  display:inline-flex;align-items:center;gap:.3rem;
  font-size:.7rem;font-weight:700;padding:.18rem .55rem;
  border-radius:99px;white-space:nowrap;
}
.al-tag-blue   { background:#dbeafe;color:#1e40af; }
.al-tag-amber  { background:#fef3c7;color:#92400e; }
.al-tag-green  { background:#d1fae5;color:#065f46; }
.al-tag-red    { background:#fee2e2;color:#991b1b; }

/* ── Detail drawer ───────────────────────────────── */
#al-overlay {
  position:fixed;inset:0;background:rgba(0,0,0,.28);
  z-index:500;opacity:0;pointer-events:none;transition:opacity .25s;
}
#al-overlay.show { opacity:1;pointer-events:auto; }

#al-drawer {
  position:fixed;top:0;right:0;bottom:0;width:440px;max-width:100vw;
  background:#fff;z-index:510;
  transform:translateX(100%);transition:transform .28s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;
  border-left:1px solid var(--border);
  box-shadow:-8px 0 40px rgba(0,0,0,.12);
}
#al-drawer.show { transform:translateX(0); }

.drawer-hdr {
  padding:.9rem 1.1rem;border-bottom:1px solid var(--border);
  background:#fafbfc;display:flex;align-items:flex-start;gap:.75rem;flex-shrink:0;
}
.drawer-av {
  width:36px;height:36px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:.72rem;font-weight:800;color:#fff;flex-shrink:0;
}
.drawer-body { padding:1.1rem;flex:1;overflow-y:auto; }
.drawer-body .d-text {
  font-size:.9rem;line-height:1.85;color:var(--text);
  background:var(--bg);border-radius:10px;padding:1rem;
  border:1px solid var(--border);white-space:pre-wrap;word-break:break-word;
}
.drawer-ftr {
  padding:.75rem 1.1rem;border-top:1px solid var(--border);
  background:#fafbfc;display:flex;justify-content:flex-end;flex-shrink:0;
}

/* ── Table ──────────────────────────────────────── */
.al-table td { vertical-align:middle; }
.al-task-preview {
  max-width:300px;font-size:.825rem;line-height:1.4;
  cursor:pointer;color:var(--text);transition:color .15s;
}
.al-task-preview:hover { color:var(--primary); }
.al-num { color:var(--text-muted);font-size:.75rem;font-variant-numeric:tabular-nums; }
.al-time { font-size:.78rem;color:var(--text-muted);font-variant-numeric:tabular-nums;white-space:nowrap; }

/* ── Image lightbox ─────────────────────────────── */
#img-overlay {
  position:fixed;inset:0;background:rgba(0,0,0,.7);
  z-index:600;display:none;cursor:pointer;align-items:center;justify-content:center;
}
#img-overlay.show { display:flex; }
#img-overlay img {
  max-width:92vw;max-height:92vh;border-radius:10px;
  object-fit:contain;box-shadow:0 20px 60px rgba(0,0,0,.5);cursor:default;
}
</style>

<!-- ── KPI strip ─────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.75rem;margin-bottom:1.25rem">
  <div class="kpi-card">
    <div class="kpi-label"><i class="fas fa-list-check"></i> Total Entries</div>
    <div class="kpi-number"><?= $totalAll ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label"><i class="fas fa-lightbulb"></i> Knowledge</div>
    <div class="kpi-number"><?= $kbCount ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label"><i class="fas fa-users"></i> Staff Active</div>
    <div class="kpi-number"><?= $uniqueStaff ?></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label"><i class="fas fa-calendar-day"></i> Date</div>
    <div class="kpi-number" style="font-size:.95rem"><?= date('j M Y', strtotime($date)) ?></div>
  </div>
</div>

<!-- ── Filter bar ────────────────────────────────── -->
<div class="card" style="padding:.875rem 1.25rem;margin-bottom:1.25rem">
  <form method="GET" action="<?= url('admin/activity-log') ?>">
    <div class="al-filter-bar">

      <div>
        <label class="form-label">Date</label>
        <input type="date" name="date" class="form-control"
               value="<?= e($date) ?>"
               style="padding:.38rem .65rem;font-size:.84rem;width:auto">
      </div>

      <div>
        <label class="form-label">Department</label>
        <select name="dept" class="form-select" style="padding:.38rem .65rem;font-size:.84rem;width:auto">
          <?php foreach ($departments as $d): ?>
            <option value="<?= e($d) ?>" <?= $d === $dept ? 'selected' : '' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="form-label">Type</label>
        <select name="type" class="form-select" style="padding:.38rem .65rem;font-size:.84rem;width:auto">
          <option value="all"       <?= $typeFilter === 'all'       ? 'selected' : '' ?>>All types</option>
          <option value="task"      <?= $typeFilter === 'task'      ? 'selected' : '' ?>>Tasks only</option>
          <option value="knowledge" <?= $typeFilter === 'knowledge' ? 'selected' : '' ?>>KB only</option>
        </select>
      </div>

      <div>
        <label class="form-label">Search</label>
        <input type="text" name="q" class="form-control"
               value="<?= e($search) ?>" placeholder="Keyword…"
               style="padding:.38rem .65rem;font-size:.84rem;width:170px">
      </div>

      <div style="display:flex;gap:.45rem;align-items:flex-end;margin-left:auto">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fas fa-filter"></i> Filter
        </button>
        <a href="<?= url('admin/export') ?>?date=<?= urlencode($date) ?>&dept=<?= urlencode($dept) ?>&type=<?= urlencode($typeFilter) ?>&q=<?= urlencode($search) ?>"
           class="btn btn-ghost btn-sm">
          <i class="fas fa-download"></i> CSV
        </a>
        <?php if ($search !== '' || $typeFilter !== 'all' || $dept !== 'All'): ?>
          <a href="<?= url('admin/activity-log') ?>?date=<?= urlencode($date) ?>"
             class="btn btn-ghost btn-sm" title="Clear filters">
            <i class="fas fa-times"></i>
          </a>
        <?php endif; ?>
      </div>

    </div>
  </form>
</div>

<!-- ── Table card ────────────────────────────────── -->
<div class="card" style="margin-bottom:1.5rem">

  <div class="card-header-clean">
    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
      <span style="font-weight:700;font-size:.875rem">
        <i class="fas fa-clock-rotate-left" style="color:var(--text-muted);font-size:.8rem;margin-right:.2rem"></i>
        Activity Log
      </span>
      <?php if ($dept !== 'All'): ?>
        <span class="al-tag al-tag-blue"><?= e($dept) ?></span>
      <?php endif; ?>
      <?php if ($typeFilter === 'knowledge'): ?>
        <span class="al-tag al-tag-green"><i class="fas fa-lightbulb"></i> KB only</span>
      <?php elseif ($typeFilter === 'task'): ?>
        <span class="al-tag al-tag-blue">Tasks only</span>
      <?php endif; ?>
      <?php if ($search !== ''): ?>
        <span class="al-tag al-tag-amber">"<?= e(substr($search, 0, 22)) ?><?= strlen($search) > 22 ? '…' : '' ?>"</span>
      <?php endif; ?>
    </div>
    <span style="font-size:.75rem;color:var(--text-muted)"><?= $totalAll ?> <?= $totalAll === 1 ? 'entry' : 'entries' ?></span>
  </div>

  <div style="overflow-x:auto">
    <table class="data-table al-table">
      <thead>
        <tr>
          <th style="width:32px">#</th>
          <th style="width:60px">Time</th>
          <th>Employee</th>
          <th>Dept</th>
          <th>Project</th>
          <th style="width:70px">Type</th>
          <th>Task / Issue</th>
          <th style="width:34px"></th>
        </tr>
      </thead>
      <tbody>

        <?php if (empty($logs)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:3.5rem 1rem;color:var(--text-muted)">
              <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:.6rem;opacity:.3"></i>
              <span style="font-size:.875rem">No entries for this date and filter.</span>
            </td>
          </tr>
        <?php endif; ?>

        <?php
        $palette = ['#3b5bdb','#0ca678','#f59f00','#e64980','#7048e8','#1098ad'];
        foreach ($logs as $i => $log):
            $initials = strtoupper(substr($log['username'], 0, 2));
            $color    = $palette[abs(crc32($log['username'])) % count($palette)];
            $taskShort = strlen($log['task']) > 80
                ? substr($log['task'], 0, 80) . '…'
                : $log['task'];
        ?>
          <tr>
            <td class="al-num"><?= $i + 1 ?></td>
            <td class="al-time"><?= substr($log['created_at'], 11, 5) ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:.5rem">
                <div style="width:26px;height:26px;border-radius:50%;background:<?= $color ?>;
                            color:#fff;font-size:.62rem;font-weight:800;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <?= $initials ?>
                </div>
                <span style="font-weight:600;font-size:.82rem"><?= e($log['username']) ?></span>
              </div>
            </td>
            <td style="font-size:.78rem;color:var(--text-muted)"><?= e($log['department'] ?: '—') ?></td>
            <td>
              <?php if (!empty($log['project'])): ?>
                <span style="background:var(--primary-subtle);color:var(--primary);
                             font-size:.7rem;font-weight:600;padding:.15rem .45rem;
                             border-radius:4px;white-space:nowrap">
                  <?= e($log['project']) ?>
                </span>
              <?php else: ?>
                <span style="color:var(--text-muted)">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($log['is_knowledge']): ?>
                <span class="al-tag al-tag-green" style="font-size:.68rem">
                  <i class="fas fa-lightbulb"></i> KB
                </span>
              <?php else: ?>
                <span class="al-tag al-tag-blue" style="font-size:.68rem">Task</span>
              <?php endif; ?>
            </td>
            <td class="al-task-preview"
                onclick="openDrawer(<?= htmlspecialchars(json_encode([
                    'username'   => $log['username'],
                    'time'       => substr($log['created_at'], 11, 5),
                    'department' => $log['department'] ?? '',
                    'project'    => $log['project']    ?? '',
                    'is_kb'      => (bool) $log['is_knowledge'],
                    'task'       => $log['task'],
                    'color'      => $color,
                ]), ENT_QUOTES) ?>)"
                title="Click to expand">
              <?= e($taskShort) ?>
            </td>
            <td>
              <?php if (!empty($log['image_path'])): ?>
                <button type="button"
                        onclick="openImg('<?= e(url('uploads/' . basename($log['image_path']))) ?>')"
                        style="background:none;border:1px solid var(--border);border-radius:6px;
                               cursor:pointer;padding:.2rem .4rem;color:var(--text-muted);font-size:.72rem"
                        title="View image">
                  <i class="fas fa-image"></i>
                </button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>

      </tbody>
    </table>
  </div>
</div>

<!-- ── Drawer overlay ────────────────────────────── -->
<div id="al-overlay" onclick="closeDrawer()"></div>

<!-- ── Entry detail drawer ───────────────────────── -->
<div id="al-drawer">
  <div class="drawer-hdr">
    <div class="drawer-av" id="d-avatar"></div>
    <div style="flex:1;min-width:0">
      <div style="font-weight:700;font-size:.9rem;color:var(--text)" id="d-name"></div>
      <div style="font-size:.75rem;color:var(--text-muted);margin-top:.1rem" id="d-meta"></div>
    </div>
    <button onclick="closeDrawer()"
            style="background:none;border:none;cursor:pointer;font-size:1.35rem;
                   color:var(--text-muted);padding:0;line-height:1;flex-shrink:0">
      &times;
    </button>
  </div>
  <div class="drawer-body">
    <div id="d-badges" style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.85rem"></div>
    <div class="d-text" id="d-text"></div>
  </div>
  <div class="drawer-ftr">
    <button onclick="closeDrawer()" class="btn btn-ghost btn-sm">Close</button>
  </div>
</div>

<!-- ── Image lightbox ────────────────────────────── -->
<div id="img-overlay" onclick="closeImg()">
  <img id="img-lightbox" src="" alt="Attachment" onclick="event.stopPropagation()">
</div>

<script>
function openDrawer(data) {
  var av = document.getElementById('d-avatar');
  av.textContent    = data.username.substring(0, 2).toUpperCase();
  av.style.background = data.color;

  document.getElementById('d-name').textContent = data.username;
  document.getElementById('d-meta').textContent =
    data.time + (data.department ? ' · ' + data.department : '');

  var badges = document.getElementById('d-badges');
  badges.innerHTML = '';
  if (data.project) {
    badges.innerHTML += '<span style="background:var(--primary-subtle);color:var(--primary);font-size:.72rem;font-weight:600;padding:.2rem .6rem;border-radius:5px">' + data.project + '</span>';
  }
  badges.innerHTML += data.is_kb
    ? '<span style="background:#d1fae5;color:#065f46;font-size:.72rem;font-weight:600;padding:.2rem .6rem;border-radius:5px">Knowledge Base</span>'
    : '<span style="background:#dbeafe;color:#1e40af;font-size:.72rem;font-weight:600;padding:.2rem .6rem;border-radius:5px">Task / Issue</span>';

  document.getElementById('d-text').textContent = data.task;

  document.getElementById('al-drawer').classList.add('show');
  document.getElementById('al-overlay').classList.add('show');
  document.body.style.overflow = 'hidden';
}

function closeDrawer() {
  document.getElementById('al-drawer').classList.remove('show');
  document.getElementById('al-overlay').classList.remove('show');
  document.body.style.overflow = '';
}

function openImg(src) {
  document.getElementById('img-lightbox').src = src;
  document.getElementById('img-overlay').classList.add('show');
}

function closeImg() {
  document.getElementById('img-overlay').classList.remove('show');
  document.getElementById('img-lightbox').src = '';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') { closeDrawer(); closeImg(); }
});
</script>
