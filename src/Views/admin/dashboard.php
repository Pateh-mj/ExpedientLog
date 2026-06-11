<?php
$pageTitle    = 'Overview';
$pageSubtitle = date('l, j F Y', strtotime($date));
$activeCount  = count(array_filter($staff_analytics, fn($s) => $s['log_count'] > 0));
$maxLogs      = max(array_column($staff_analytics, 'log_count') ?: [1]);
$topPerformers = array_values(array_slice(
    array_filter($staff_analytics, fn($s) => $s['log_count'] > 0), 0, 10
));
?>

<!-- ── Filter bar ─────────────────────────────────────────── -->
<div class="card" style="padding:.875rem 1.25rem;margin-bottom:1.5rem;border-radius:12px">
  <form method="GET" action="<?= url('admin') ?>">
    <div style="display:flex;flex-wrap:wrap;gap:.85rem;align-items:flex-end">
      <div>
        <label class="form-label" style="font-size:.75rem;margin-bottom:.25rem">Date</label>
        <input type="date" name="date" class="form-control" value="<?= e($date) ?>"
               style="padding:.4rem .75rem;font-size:.85rem;width:auto">
      </div>
      <div>
        <label class="form-label" style="font-size:.75rem;margin-bottom:.25rem">Department</label>
        <select name="dept" class="form-select" style="padding:.4rem .75rem;font-size:.85rem;width:auto">
          <?php foreach ($departments as $d): ?>
            <option value="<?= e($d) ?>" <?= $d === $dept ? 'selected' : '' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:.5rem;margin-left:auto">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fas fa-filter"></i> Apply
        </button>
        <a href="<?= url('admin/activity-log') ?>?date=<?= urlencode($date) ?>&dept=<?= urlencode($dept) ?>"
           class="btn btn-ghost btn-sm">
          <i class="fas fa-clock-rotate-left"></i> Activity Logs
        </a>
      </div>
    </div>
  </form>
</div>

<!-- ── KPI row ───────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.75rem;margin-bottom:1.5rem">

  <div class="kpi-card">
    <div class="kpi-label"><i class="fas fa-list-check"></i> Logs filed</div>
    <div class="kpi-number"><?= $stats['total_logs'] ?></div>
  </div>

  <div class="kpi-card">
    <div class="kpi-label"><i class="fas fa-user-check"></i> Staff active</div>
    <div class="kpi-number"><?= $stats['active_today'] ?></div>
  </div>

  <div class="kpi-card">
    <div class="kpi-label"><i class="fas fa-gauge-high"></i> Activity rate</div>
    <div class="kpi-number"
         style="color:<?= $stats['active_rate'] >= 80 ? '#059669' : ($stats['active_rate'] >= 50 ? '#d97706' : '#dc2626') ?>">
      <?= $stats['active_rate'] ?>%
    </div>
  </div>

  <div class="kpi-card">
    <div class="kpi-label"><i class="fas fa-users"></i> Total staff</div>
    <div class="kpi-number"><?= $stats['total_staff'] ?></div>
  </div>

  <div class="kpi-card">
    <div class="kpi-label"><i class="fas fa-lightbulb"></i> KB today</div>
    <div class="kpi-number"><?= $kb_today ?></div>
  </div>

  <div class="kpi-card">
    <div class="kpi-label"><i class="fas fa-clipboard-check"></i> Reported in</div>
    <div class="kpi-number"><?= $activeCount ?> / <?= $stats['total_staff'] ?></div>
  </div>

</div>

<!-- ── Activity log + Top performers ────────────────────── -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.25rem;margin-bottom:1.25rem">

  <div class="card" style="border-radius:12px;overflow:hidden">
    <div class="card-header-clean" style="background:#fafbfc">
      <div style="display:flex;align-items:center;gap:.6rem">
        <i class="fas fa-clock-rotate-left" style="color:var(--text-muted);font-size:.85rem"></i>
        <span style="font-weight:700;font-size:.875rem;color:var(--text)">Activity Log</span>
      </div>
      <div style="display:flex;align-items:center;gap:.5rem">
        <?php if ($dept !== 'All'): ?>
          <span style="background:var(--primary-subtle);color:var(--primary);font-size:.7rem;font-weight:600;padding:.15rem .5rem;border-radius:5px"><?= e($dept) ?></span>
        <?php endif; ?>
        <span style="font-size:.75rem;color:var(--text-muted)"><?= count($logs) ?> entries</span>
      </div>
    </div>
    <div style="overflow-x:auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>Time</th>
            <th>Employee</th>
            <th>Dept</th>
            <th>Project</th>
            <th>KB</th>
            <th>Activity</th>
            <th style="width:36px"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="7" style="text-align:center;padding:2.5rem;color:var(--text-muted);font-size:.875rem">
                No activity logged for this date and filter.
              </td>
            </tr>
          <?php endif; ?>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td style="font-variant-numeric:tabular-nums;color:var(--text-muted);font-size:.8rem;white-space:nowrap">
                <?= substr($log['created_at'], 11, 5) ?>
              </td>
              <td style="font-weight:600;font-size:.825rem"><?= e($log['username']) ?></td>
              <td style="font-size:.78rem;color:var(--text-muted)"><?= e($log['department'] ?: '—') ?></td>
              <td>
                <?php if ($log['project']): ?>
                  <span style="background:var(--primary-subtle);color:var(--primary);font-size:.7rem;font-weight:600;
                               padding:.15rem .45rem;border-radius:4px;white-space:nowrap">
                    <?= e($log['project']) ?>
                  </span>
                <?php else: ?>
                  <span style="color:var(--text-muted)">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($log['is_knowledge']): ?>
                  <span class="badge-kb"><i class="fas fa-lightbulb"></i> KB</span>
                <?php else: ?>
                  <span style="color:var(--text-muted)">—</span>
                <?php endif; ?>
              </td>
              <td style="max-width:260px;cursor:pointer;font-size:.825rem;line-height:1.4;
                          color:var(--primary);text-decoration-color:transparent"
                  onclick="window.location.href='<?= url('admin/activity-log') ?>?date=<?= urlencode($date) ?>&dept=<?= urlencode($dept) ?>'"
                  title="View in Activity Logs">
                <?= e(mb_strimwidth($log['task'], 0, 70, '…')) ?>
              </td>
              <td>
                <?php if ($log['image_path']): ?>
                  <button data-image-src="<?= url('uploads/' . basename($log['image_path'])) ?>"
                          style="background:none;border:1px solid var(--border);border-radius:6px;
                                 cursor:pointer;padding:.25rem .4rem;color:var(--text-muted);font-size:.75rem">
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

  <div class="card" style="border-radius:12px;overflow:hidden">
    <div class="card-header-clean" style="background:#fafbfc">
      <div style="display:flex;align-items:center;gap:.6rem">
        <i class="fas fa-medal" style="color:var(--warning);font-size:.85rem"></i>
        <span style="font-weight:700;font-size:.875rem;color:var(--text)">Top Performers</span>
      </div>
      <span style="font-size:.72rem;color:var(--text-muted)">Today</span>
    </div>
    <div>
      <?php if (empty($topPerformers)): ?>
        <div class="empty-state" style="padding:2rem">No logs recorded today.</div>
      <?php endif; ?>
      <?php foreach ($topPerformers as $i => $p): ?>
        <?php
          $medals   = ['🥇','🥈','🥉'];
          $rankDisp = $medals[$i] ?? ($i + 1);
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:.65rem 1.1rem;border-bottom:1px solid var(--border);
                    <?= $i < 3 ? 'background:linear-gradient(to right,#fafbfc,#fff)' : '' ?>">
          <div style="display:flex;align-items:center;gap:.65rem">
            <span style="font-size:<?= $i < 3 ? '1rem' : '.8rem' ?>;font-weight:700;
                         color:var(--primary);width:20px;text-align:center;flex-shrink:0">
              <?= $rankDisp ?>
            </span>
            <div>
              <div style="font-weight:600;font-size:.825rem"><?= e($p['username']) ?></div>
              <div style="font-size:.72rem;color:var(--text-muted)"><?= e($p['department'] ?: '—') ?></div>
            </div>
          </div>
          <span style="background:var(--primary-subtle);color:var(--primary);
                       font-size:.72rem;font-weight:700;padding:.2rem .55rem;border-radius:99px;white-space:nowrap">
            <?= $p['log_count'] ?> tasks
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- ── Projects · KB Leaders · Activity by Hour ──────────── -->
<div style="display:grid;grid-template-columns:1fr 1fr 1.4fr;gap:1.25rem;margin-bottom:1.25rem">

  <div class="card" style="border-radius:12px;overflow:hidden">
    <div class="card-header-clean" style="background:#fafbfc">
      <div style="display:flex;align-items:center;gap:.6rem">
        <i class="fas fa-diagram-project" style="color:var(--text-muted);font-size:.85rem"></i>
        <span style="font-weight:700;font-size:.875rem;color:var(--text)">Projects Today</span>
      </div>
    </div>
    <div style="padding:1rem 1.1rem">
      <?php if (empty($projects)): ?>
        <p style="color:var(--text-muted);text-align:center;font-size:.825rem;padding:.5rem 0">No project data.</p>
      <?php endif; ?>
      <?php foreach ($projects as $p): ?>
        <div style="margin-bottom:.85rem">
          <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:.3rem">
            <span style="font-size:.8rem;font-weight:600;color:var(--text)"><?= e($p['project']) ?></span>
            <span style="font-size:.72rem;font-weight:700;color:var(--text-muted)"><?= $p['tasks'] ?></span>
          </div>
          <div class="progress-track" style="height:5px">
            <div class="progress-fill" style="width:<?= round(($p['tasks'] / $max_tasks) * 100) ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card" style="border-radius:12px;overflow:hidden">
    <div class="card-header-clean" style="background:#fafbfc">
      <div style="display:flex;align-items:center;gap:.6rem">
        <i class="fas fa-lightbulb" style="color:var(--accent);font-size:.85rem"></i>
        <span style="font-weight:700;font-size:.875rem;color:var(--text)">Knowledge Leaders</span>
      </div>
      <span style="font-size:.72rem;color:var(--text-muted)">All time</span>
    </div>
    <div>
      <?php if (empty($kb_leaders)): ?>
        <div class="empty-state" style="padding:1.5rem">No contributions yet.</div>
      <?php endif; ?>
      <?php foreach ($kb_leaders as $i => $l): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:.6rem 1.1rem;border-bottom:1px solid var(--border)">
          <div style="display:flex;align-items:center;gap:.65rem">
            <span style="font-weight:800;color:var(--accent);width:18px;
                         text-align:right;font-size:.8rem;flex-shrink:0"><?= $i + 1 ?></span>
            <div style="min-width:0">
              <div style="font-weight:600;font-size:.825rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= e($l['username']) ?>
              </div>
              <div style="font-size:.72rem;color:var(--text-muted)"><?= e($l['department'] ?: '—') ?></div>
            </div>
          </div>
          <span class="badge-kb" style="flex-shrink:0"><?= $l['contrib'] ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card" style="border-radius:12px;overflow:hidden">
    <div class="card-header-clean" style="background:#fafbfc">
      <div style="display:flex;align-items:center;gap:.6rem">
        <i class="fas fa-chart-column" style="color:var(--text-muted);font-size:.85rem"></i>
        <span style="font-weight:700;font-size:.875rem;color:var(--text)">Activity by Hour</span>
      </div>
      <span style="font-size:.72rem;color:var(--text-muted)"><?= array_sum(array_column($hourly, 'count')) ?> total</span>
    </div>
    <div style="padding:.75rem 1rem 0;overflow-x:auto">
      <div style="display:flex;align-items:flex-end;gap:3px;height:80px;min-width:320px">
        <?php foreach ($hourly as $h): ?>
          <?php $pct = $max_hourly > 0 ? max(round(($h['count'] / $max_hourly) * 100), $h['count'] > 0 ? 6 : 0) : 0; ?>
          <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:1px;min-width:0">
            <div style="width:100%;flex:1;display:flex;align-items:flex-end">
              <div style="width:100%;background:<?= $h['count'] > 0 ? 'var(--primary)' : 'var(--border)' ?>;
                          border-radius:3px 3px 0 0;height:<?= $pct ?>%;min-height:3px;
                          opacity:<?= $h['count'] > 0 ? '.7' : '1' ?>"
                   title="<?= $h['count'] ?> log<?= $h['count'] !== 1 ? 's' : '' ?> at <?= $h['label'] ?>">
              </div>
            </div>
            <?php if ($h['count'] > 0): ?>
              <span style="font-size:.5rem;font-weight:700;color:var(--primary);line-height:1"><?= $h['count'] ?></span>
            <?php endif; ?>
            <span style="font-size:.55rem;color:var(--text-muted);padding-bottom:.35rem;white-space:nowrap"><?= $h['label'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<!-- ── Staff Analytics ───────────────────────────────────── -->
<div class="card" style="border-radius:12px;overflow:hidden">
  <div class="card-header-clean" style="background:#fafbfc">
    <div style="display:flex;align-items:center;gap:.6rem">
      <i class="fas fa-chart-line" style="color:var(--text-muted);font-size:.85rem"></i>
      <span style="font-weight:700;font-size:.875rem;color:var(--text)">Staff Analytics</span>
    </div>
    <span style="font-size:.72rem;color:var(--text-muted);display:flex;align-items:center;gap:.5rem">
      <?= date('j M Y', strtotime($date)) ?> &nbsp;·&nbsp; per-employee breakdown
      &nbsp;<span style="width:1px;height:12px;background:var(--border);display:inline-block;vertical-align:middle"></span>&nbsp;
      <i class="fas fa-clock" style="font-size:.65rem"></i>
      <span id="sa-clock" style="font-variant-numeric:tabular-nums;font-weight:600;color:var(--text)"></span>
    </span>
  </div>
  <div style="overflow-x:auto">
    <table class="data-table">
      <thead>
        <tr>
          <th>Employee</th>
          <th>Department</th>
          <th style="text-align:center">Logs</th>
          <th style="text-align:center">KB</th>
          <th>First Log</th>
          <th>Last Log</th>
          <th>Active For</th>
          <th>Top Project</th>
          <th style="width:90px">Activity</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staff_analytics as $s): ?>
          <?php
            $spanH   = $s['span_min'] !== null ? floor($s['span_min'] / 60) : 0;
            $spanM   = $s['span_min'] !== null ? ($s['span_min'] % 60) : 0;
            $spanStr = $s['log_count'] > 0
              ? ($spanH > 0 ? "{$spanH}h {$spanM}m" : ($spanM > 0 ? "{$spanM}m" : '<1m'))
              : '—';
            $barPct  = $maxLogs > 0 ? round(($s['log_count'] / $maxLogs) * 100) : 0;
            $faded   = $s['log_count'] === 0 ? 'opacity:.4' : '';
          ?>
          <tr style="<?= $faded ?>">
            <td style="font-weight:600;font-size:.825rem"><?= e($s['username']) ?></td>
            <td style="font-size:.78rem;color:var(--text-muted)"><?= e($s['department'] ?: '—') ?></td>
            <td style="text-align:center">
              <?php if ($s['log_count'] > 0): ?>
                <strong style="font-size:.825rem"><?= $s['log_count'] ?></strong>
              <?php else: ?>
                <span style="color:var(--text-muted);font-size:.8rem">0</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center">
              <?php if ($s['kb_count'] > 0): ?>
                <span class="badge-kb"><?= $s['kb_count'] ?></span>
              <?php else: ?>
                <span style="color:var(--text-muted)">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem;color:var(--text-muted);white-space:nowrap"><?= $s['first_log'] ?? '—' ?></td>
            <td style="font-size:.8rem;color:var(--text-muted);white-space:nowrap"><?= $s['last_log'] ?? '—' ?></td>
            <td style="font-size:.8rem;color:var(--text-muted);white-space:nowrap"><?= $spanStr ?></td>
            <td>
              <?php if ($s['top_project']): ?>
                <span style="background:var(--primary-subtle);color:var(--primary);font-size:.7rem;font-weight:600;
                             padding:.15rem .5rem;border-radius:5px;display:inline-block;max-width:130px;
                             overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle">
                  <?= e($s['top_project']) ?>
                </span>
              <?php else: ?>
                <span style="color:var(--text-muted)">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="height:5px;background:var(--border);border-radius:99px;overflow:hidden">
                <div style="height:100%;width:<?= $barPct ?>%;background:var(--primary);
                            border-radius:99px;min-width:<?= $barPct > 0 ? '3' : '0' ?>px"></div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Task detail modal ─────────────────────────────────── -->
<div id="detailOverlay" class="sidebar-overlay" style="z-index:300;display:none"
     onclick="this.style.display='none';document.getElementById('detailModal').style.display='none'"></div>
<div id="detailModal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            background:#fff;border-radius:14px;padding:0;width:calc(100% - 2rem);max-width:560px;
            z-index:400;box-shadow:0 20px 60px rgba(0,0,0,.15);overflow:hidden;max-height:88vh">
  <div style="display:flex;justify-content:space-between;align-items:center;
              padding:1rem 1.25rem;border-bottom:1px solid var(--border);background:#fafbfc">
    <div>
      <strong id="detail-author" style="font-size:.875rem;color:var(--text)"></strong>
      <span id="detail-time" style="font-size:.78rem;color:var(--text-muted);margin-left:.5rem"></span>
    </div>
    <button onclick="document.getElementById('detailModal').style.display='none';document.getElementById('detailOverlay').style.display='none'"
            style="background:none;border:none;cursor:pointer;font-size:1.3rem;color:var(--text-muted);line-height:1">&times;</button>
  </div>
  <div style="padding:1.25rem;overflow-y:auto">
    <p id="detail-text" style="line-height:1.75;color:var(--text);font-size:.9rem;margin:0"></p>
  </div>
</div>

<!-- ── Image modal ───────────────────────────────────────── -->
<div id="imageModalOverlay" class="sidebar-overlay" style="z-index:450"></div>
<div id="imageModal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            background:#111;border-radius:14px;padding:0;width:calc(100% - 2rem);
            max-width:92vw;max-height:92vh;z-index:500;
            box-shadow:0 24px 80px rgba(0,0,0,.55);overflow:hidden">
  <div style="display:flex;justify-content:space-between;align-items:center;
              padding:.6rem 1rem;background:rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.1)">
    <span style="color:#fff;font-size:.8rem;opacity:.7"><i class="fas fa-image"></i> Attachment</span>
    <button data-close-image-modal
            style="background:none;border:none;cursor:pointer;font-size:1.4rem;
                   color:#fff;opacity:.8;line-height:1">&times;</button>
  </div>
  <div style="overflow:auto;max-height:calc(92vh - 46px);display:flex;align-items:center;justify-content:center;padding:.75rem">
    <img id="imageModal-img" src="" alt="Attachment"
         style="max-width:100%;max-height:calc(92vh - 80px);border-radius:8px;
                display:block;object-fit:contain">
  </div>
</div>

<style>#imageModal.open { display:flex !important; flex-direction:column; }</style>

<script>
/* Live system clock for Staff Analytics */
(function () {
  var el = document.getElementById('sa-clock');
  if (!el) return;
  function tick() {
    var now = new Date();
    var h = String(now.getHours()).padStart(2,'0');
    var m = String(now.getMinutes()).padStart(2,'0');
    var s = String(now.getSeconds()).padStart(2,'0');
    el.textContent = h + ':' + m + ':' + s;
  }
  tick();
  setInterval(tick, 1000);
})();
</script>
