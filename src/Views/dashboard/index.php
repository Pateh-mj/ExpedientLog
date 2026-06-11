<?php
$pageTitle    = 'Dashboard';
$pageSubtitle = $today;
$todayCount   = count($tasks);
$kbCount      = count(array_filter($tasks, fn($t) => $t['is_knowledge']));
$weekTotal    = array_sum(array_column($weekly_strip, 'count'));

// Group history by date for display
$history_grouped = [];
foreach ($history as $h) {
    $day = substr($h['created_at'], 0, 10);
    $history_grouped[$day][] = $h;
}
?>

<!-- ① Weekly activity strip -->
<div class="weekly-strip">
  <div class="weekly-strip-days">
    <?php foreach ($weekly_strip as $day): ?>
      <div class="strip-day <?= $day['is_today'] ? 'today' : '' ?> <?= $day['count'] > 0 ? 'active' : '' ?>">
        <span class="strip-label"><?= $day['label'] ?></span>
        <div class="strip-dot" title="<?= $day['count'] ?> log<?= $day['count'] !== 1 ? 's' : '' ?> on <?= format_date($day['date']) ?>">
          <?php if ($day['count'] > 0): ?>
            <span class="strip-count"><?= $day['count'] ?></span>
          <?php endif; ?>
        </div>
        <span class="strip-date"><?= $day['day_num'] ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="weekly-strip-meta">
    <?php if ($streak > 0): ?>
      <span class="streak-badge">
        <i class="fas fa-fire"></i> <?= $streak ?>-day streak
      </span>
    <?php endif; ?>
    <span style="color:var(--text-muted);font-size:.78rem"><?= $weekTotal ?> activities this week</span>
  </div>
</div>

<!-- Announcements strip -->
<?php if (!empty($announcements)): ?>
  <div style="margin-bottom:1.25rem">
    <?php foreach (array_slice($announcements, 0, 2) as $ann): ?>
      <div class="announcement-card <?= $ann['is_pinned'] ? 'pinned' : '' ?>" style="margin-bottom:.5rem">
        <?php if ($ann['is_pinned']): ?>
          <span style="font-size:.68rem;font-weight:600;color:var(--accent);text-transform:uppercase;letter-spacing:.05em">
            <i class="fas fa-thumbtack"></i> Pinned &nbsp;
          </span>
        <?php endif; ?>
        <strong><?= e($ann['title']) ?></strong>
        <span class="announcement-meta" style="margin-left:.5rem">· <?= e($ann['author']) ?> · <?= format_date($ann['created_at'], 'j M') ?></span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- ① Side-by-side layout: form LEFT, log RIGHT -->
<div class="dashboard-grid">

  <!-- LEFT: Log form -->
  <div class="dashboard-form-col">
    <div class="card" style="height:100%">
      <div class="card-header-clean">
        <h3><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Log Activity</h3>
        <small style="color:var(--text-muted)"><?= date('D, j M') ?></small>
      </div>
      <div style="padding:1.25rem">
        <form method="POST" action="<?= url('tasks') ?>" enctype="multipart/form-data" id="log-form">
          <?= csrf_field() ?>

          <!-- ② Textarea instead of single-line input -->
          <div style="margin-bottom:.9rem">
            <label class="form-label" for="task-input">
              What did you accomplish?
              <span style="color:var(--text-muted);font-weight:400;font-size:.78rem">Ctrl+Enter to submit</span>
            </label>
            <textarea name="task" id="task-input" class="form-control" rows="4"
                      placeholder="Describe your work activity…"
                      required maxlength="1500"
                      data-counter="char-count"
                      style="resize:vertical;min-height:90px"></textarea>
            <div style="display:flex;justify-content:space-between;margin-top:.25rem">
              <small id="draft-indicator" style="color:var(--accent);font-size:.72rem;visibility:hidden">
                <i class="fas fa-circle" style="font-size:.4rem;vertical-align:middle"></i> Draft saved
              </small>
              <small id="char-count" style="color:var(--text-muted);font-size:.75rem">0 / 1500</small>
            </div>
          </div>

          <div style="margin-bottom:.9rem">
            <label class="form-label" for="log-project">Project</label>
            <select name="project" id="log-project" class="form-select">
              <?php foreach ($projects as $p): ?>
                <option><?= e($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- ③ Image upload with preview -->
          <div style="margin-bottom:.9rem">
            <label class="form-label" for="task_image">
              Attach Image <span style="color:var(--text-muted);font-weight:400;font-size:.78rem">optional · max 5MB</span>
            </label>
            <input type="file" name="task_image" id="task_image" class="form-control" accept="image/*">
            <div id="image-preview-wrap" style="display:none;margin-top:.6rem;position:relative">
              <img id="image-preview" src="" alt="Preview"
                   style="max-height:120px;border-radius:8px;border:1px solid var(--border);display:block">
              <button type="button" id="remove-preview"
                      style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,.55);border:none;color:#fff;
                             border-radius:50%;width:22px;height:22px;cursor:pointer;font-size:.75rem;
                             display:grid;place-items:center">
                &times;
              </button>
            </div>
          </div>

          <!-- Knowledge toggle -->
          <div style="background:var(--bg);border-radius:8px;padding:.85rem;margin-bottom:1rem">
            <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-weight:500">
              <input type="checkbox" name="save_as_knowledge" id="kb-check"
                     data-kb-toggle="kb-category-wrap" value="1"
                     style="width:15px;height:15px;accent-color:var(--accent)">
              <i class="fas fa-lightbulb" style="color:var(--accent)"></i>
              Save as Reusable Knowledge
            </label>
            <div id="kb-category-wrap" style="display:none;margin-top:.65rem">
              <select name="category" id="kb-category" class="form-select">
                <?php foreach ($kb_categories as $c): ?>
                  <option><?= e($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
            <i class="fas fa-paper-plane"></i> Log Activity
            <span style="font-size:.72rem;opacity:.7;margin-left:.25rem">Ctrl+↵</span>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- RIGHT: Activity log with tabs -->
  <div class="dashboard-log-col">
    <div class="card" style="height:100%">

      <!-- ⑤ Tabs: Today / History -->
      <div class="card-header-clean" style="padding-bottom:0;border-bottom:none">
        <div style="display:flex;align-items:center;gap:1rem">
          <div class="log-tabs">
            <button class="log-tab active" data-tab="today">
              Today
              <span class="tab-count"><?= $todayCount ?></span>
            </button>
            <button class="log-tab" data-tab="history">
              History
              <?php if (!empty($history)): ?>
                <span class="tab-count"><?= count($history) ?></span>
              <?php endif; ?>
            </button>
          </div>
        </div>
        <!-- ④ Quick stats inline -->
        <div style="display:flex;gap:1rem;align-items:center">
          <?php if ($kbCount > 0): ?>
            <span style="font-size:.78rem;color:var(--accent);font-weight:600">
              <i class="fas fa-lightbulb"></i> <?= $kbCount ?> KB
            </span>
          <?php endif; ?>
        </div>
      </div>
      <div style="height:1px;background:var(--border);margin:0 1.25rem"></div>

      <!-- TODAY panel -->
      <div id="panel-today" data-panel="today">
        <div id="activity-list">
          <?php if (empty($tasks)): ?>
            <!-- ⑨ Richer empty state -->
            <div id="empty-state" class="empty-state" style="padding:2.5rem 1.5rem">
              <i class="fas fa-sun" style="color:var(--warning);opacity:.6"></i>
              <div style="font-weight:600;font-size:1rem;margin-bottom:.4rem">
                Good <?= (date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening')) ?>,
                <?= e(auth()['full_name'] ?: auth()['username']) ?>
              </div>
              <div style="font-size:.875rem;max-width:280px;margin:0 auto .75rem">
                Nothing logged yet today. Your first entry sets the tone for the day.
              </div>
              <div style="font-size:.78rem;color:var(--text-muted)">
                <?= $today ?>
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($tasks as $t): ?>
              <div class="activity-item" data-task-id="<?= $t['id'] ?>">
                <div style="flex:1;min-width:0">
                  <div style="font-weight:500;margin-bottom:.2rem;line-height:1.4">
                    <span data-task-text><?= e($t['task']) ?></span>
                    <?php if ($t['is_knowledge']): ?>
                      <span class="badge-kb" data-task-kb-badge>
                        <i class="fas fa-lightbulb"></i><?= e($t['category']) ?>
                      </span>
                    <?php else: ?>
                      <span class="badge-kb" data-task-kb-badge style="display:none"></span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:.76rem;color:var(--text-muted);display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                    <span data-task-project><?= e($t['project'] ?? 'General / Other') ?></span>
                    <?php if ($t['image_path']): ?>
                      <span>·</span>
                      <button data-image-src="<?= url('uploads/' . basename($t['image_path'])) ?>"
                              style="background:none;border:none;cursor:pointer;color:var(--primary);font-size:.76rem;padding:0">
                        <i class="fas fa-image"></i> image
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="activity-actions">
                  <button class="btn-icon edit" title="Edit"
                          data-edit-task="<?= $t['id'] ?>"
                          data-task="<?= e($t['task']) ?>"
                          data-project="<?= e($t['project'] ?? '') ?>"
                          data-iskb="<?= $t['is_knowledge'] ?>"
                          data-category="<?= e($t['category'] ?? '') ?>">
                    <i class="fas fa-pen"></i>
                  </button>
                  <button class="btn-icon del" title="Delete" data-delete-task="<?= $t['id'] ?>">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
                <div style="font-size:.76rem;color:var(--text-muted);flex-shrink:0;text-align:right;min-width:42px">
                  <span data-task-time><?= substr($t['created_at'], 11, 5) ?></span>
                  <?php if ($t['updated_at']): ?>
                    <br><small style="color:#94a3b8;font-size:.68rem">edited</small>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- HISTORY panel -->
      <div id="panel-history" data-panel="history" style="display:none">
        <?php if (empty($history_grouped)): ?>
          <div class="empty-state" style="padding:2.5rem 1.5rem">
            <i class="fas fa-clock-rotate-left"></i>
            <div style="font-weight:500">No history yet</div>
            <div style="font-size:.875rem">Your past 7 days of activity will appear here.</div>
          </div>
        <?php else: ?>
          <?php foreach ($history_grouped as $date => $entries): ?>
            <div class="history-date-group">
              <div class="history-date-label">
                <?= date('l, j F', strtotime($date)) ?>
                <span class="history-date-count"><?= count($entries) ?></span>
              </div>
              <?php foreach ($entries as $h): ?>
                <div class="activity-item hist-clickable" style="padding-left:1.25rem;cursor:pointer"
                     onclick="openHistModal(
                       '<?= addslashes(e($h['task'])) ?>',
                       '<?= addslashes(e($h['project'] ?? 'General / Other')) ?>',
                       '<?= $h['is_knowledge'] ? addslashes(e($h['category'])) : '' ?>',
                       '<?= substr($h['created_at'], 11, 5) ?>',
                       '<?= $h['image_path'] ? url('uploads/' . basename($h['image_path'])) : '' ?>'
                     )">
                  <div style="flex:1;min-width:0">
                    <div style="font-weight:500;font-size:.875rem;line-height:1.4"><?= e(mb_strimwidth($h['task'], 0, 90, '…')) ?></div>
                    <div style="font-size:.76rem;color:var(--text-muted);margin-top:.15rem;display:flex;align-items:center;gap:.5rem">
                      <span><?= e($h['project'] ?? 'General / Other') ?></span>
                      <?php if ($h['is_knowledge']): ?>
                        <span class="badge-kb" style="font-size:.65rem">
                          <i class="fas fa-lightbulb"></i><?= e($h['category']) ?>
                        </span>
                      <?php endif; ?>
                      <?php if ($h['image_path']): ?>
                        <span style="color:var(--primary)"><i class="fas fa-image"></i></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div style="font-size:.76rem;color:var(--text-muted);flex-shrink:0;text-align:right">
                    <?= substr($h['created_at'], 11, 5) ?>
                    <div style="font-size:.65rem;margin-top:.1rem;color:var(--border)"><i class="fas fa-eye"></i></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>


<!-- Edit Modal -->
<div id="editModalOverlay" class="sidebar-overlay" style="z-index:300"></div>
<div id="editModal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:14px;padding:1.5rem;width:100%;max-width:500px;z-index:400;box-shadow:0 20px 60px rgba(0,0,0,.2)">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <h3 style="margin:0;font-size:1rem;font-weight:600"><i class="fas fa-pen" style="color:var(--primary);margin-right:.5rem"></i>Edit Activity</h3>
    <button data-close-edit-modal style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:var(--text-muted);padding:0">&times;</button>
  </div>
  <form id="edit-form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="edit-id">
    <div style="margin-bottom:1rem">
      <label class="form-label">Task</label>
      <textarea name="task" id="edit-task" class="form-control" rows="3" required maxlength="1500"></textarea>
    </div>
    <div style="margin-bottom:1rem">
      <label class="form-label">Project</label>
      <select name="project" id="edit-project" class="form-select">
        <?php foreach ($projects as $p): ?>
          <option value="<?= e($p) ?>"><?= e($p) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="background:var(--bg);border-radius:8px;padding:.85rem;margin-bottom:1.25rem">
      <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-weight:500">
        <input type="checkbox" name="save_as_knowledge" id="edit-is-knowledge"
               data-kb-toggle="edit-kb-wrap" value="1"
               style="width:15px;height:15px;accent-color:var(--accent)">
        <i class="fas fa-lightbulb" style="color:var(--accent)"></i> Save as Knowledge
      </label>
      <div id="edit-kb-wrap" style="display:none;margin-top:.65rem">
        <select name="category" id="edit-category" class="form-select">
          <?php foreach ($kb_categories as $c): ?>
            <option value="<?= e($c) ?>"><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:.75rem;justify-content:flex-end">
      <button type="button" class="btn btn-ghost btn-sm" data-close-edit-modal>Cancel</button>
      <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
    </div>
  </form>
</div>

<!-- History Detail Modal -->
<div id="histModalOverlay" class="sidebar-overlay" style="z-index:350;display:none"
     onclick="closeHistModal()"></div>
<div id="histModal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            background:#fff;border-radius:16px;padding:0;width:calc(100% - 2rem);max-width:540px;
            z-index:400;box-shadow:0 24px 64px rgba(0,0,0,.18);overflow:hidden">
  <div style="background:#fafbfc;border-bottom:1px solid var(--border);padding:1rem 1.25rem;
              display:flex;align-items:center;justify-content:space-between">
    <div style="display:flex;align-items:center;gap:.6rem">
      <i class="fas fa-clock-rotate-left" style="color:var(--primary)"></i>
      <span style="font-weight:700;font-size:.9rem">Activity Log Entry</span>
    </div>
    <button onclick="closeHistModal()"
            style="background:none;border:none;cursor:pointer;font-size:1.35rem;
                   color:var(--text-muted);line-height:1;padding:0">&times;</button>
  </div>
  <div style="padding:1.25rem">
    <div id="hm-badges" style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.9rem"></div>
    <p id="hm-text" style="line-height:1.8;font-size:.9rem;color:var(--text);margin:0 0 1rem"></p>
    <div id="hm-img-wrap" style="display:none;margin-top:.5rem">
      <button id="hm-img-btn" class="btn btn-ghost btn-sm">
        <i class="fas fa-image"></i> View Attached Image
      </button>
    </div>
  </div>
  <div style="padding:.875rem 1.25rem;border-top:1px solid var(--border);background:#fafbfc;
              display:flex;justify-content:flex-end">
    <button onclick="closeHistModal()" class="btn btn-ghost btn-sm">Close</button>
  </div>
</div>

<!-- Image Modal -->
<div id="imageModalOverlay" class="sidebar-overlay" style="z-index:450;display:none"
     onclick="closeImgModal()"></div>
<div id="imageModal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            background:#111;border-radius:14px;padding:0;width:calc(100% - 2rem);
            max-width:92vw;max-height:92vh;z-index:500;
            box-shadow:0 24px 80px rgba(0,0,0,.55);overflow:hidden">
  <div style="display:flex;justify-content:space-between;align-items:center;
              padding:.6rem 1rem;background:rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.1)">
    <span style="color:#fff;font-size:.8rem;opacity:.7"><i class="fas fa-image"></i> Attachment</span>
    <button data-close-image-modal onclick="closeImgModal()"
            style="background:none;border:none;cursor:pointer;font-size:1.4rem;
                   color:#fff;opacity:.8;line-height:1">&times;</button>
  </div>
  <div style="overflow:auto;max-height:calc(92vh - 46px);display:flex;align-items:center;justify-content:center;padding:.75rem">
    <img id="imageModal-img" src="" alt="Attachment"
         style="max-width:100%;max-height:calc(92vh - 80px);border-radius:8px;
                display:block;object-fit:contain">
  </div>
</div>

<style>
  #editModal.open, #histModal.open { display:block !important; }
  #imageModal.open { display:flex !important; flex-direction:column; }
  .hist-clickable:hover { background:var(--bg); }
</style>

<script>
function openHistModal(text, project, kb, time, imgSrc) {
  // badges
  var badges = document.getElementById('hm-badges');
  badges.innerHTML = '';
  if (time) badges.innerHTML += '<span style="background:var(--primary-subtle);color:var(--primary);font-size:.72rem;font-weight:600;padding:.2rem .6rem;border-radius:5px"><i class="fas fa-clock"></i> ' + time + '</span>';
  if (project && project !== 'General / Other') badges.innerHTML += '<span style="background:var(--primary-subtle);color:var(--primary);font-size:.72rem;font-weight:600;padding:.2rem .6rem;border-radius:5px">' + project + '</span>';
  if (kb) badges.innerHTML += '<span style="background:#d1fae5;color:#065f46;font-size:.72rem;font-weight:600;padding:.2rem .6rem;border-radius:5px"><i class="fas fa-lightbulb"></i> KB: ' + kb + '</span>';

  document.getElementById('hm-text').textContent = text;

  var imgWrap = document.getElementById('hm-img-wrap');
  var imgBtn  = document.getElementById('hm-img-btn');
  if (imgSrc) {
    imgWrap.style.display = 'block';
    imgBtn.onclick = function() { openImg(imgSrc); };
  } else {
    imgWrap.style.display = 'none';
  }

  document.getElementById('histModal').classList.add('open');
  document.getElementById('histModalOverlay').style.display = 'block';
}
function closeHistModal() {
  document.getElementById('histModal').classList.remove('open');
  document.getElementById('histModalOverlay').style.display = 'none';
}
function openImg(src) {
  document.getElementById('imageModal-img').src = src;
  document.getElementById('imageModal').classList.add('open');
  document.getElementById('imageModalOverlay').style.display = 'block';
}
function closeImgModal() {
  document.getElementById('imageModal').classList.remove('open');
  document.getElementById('imageModalOverlay').style.display = 'none';
}
// Legacy data-image-src buttons (Today tab)
document.addEventListener('click', function(e) {
  var btn = e.target.closest('[data-image-src]');
  if (btn) openImg(btn.dataset.imageSrc);
  var close = e.target.closest('[data-close-image-modal]');
  if (close) closeImgModal();
});
</script>
