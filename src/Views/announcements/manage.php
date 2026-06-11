<?php $pageTitle = 'Manage Announcements'; ?>

<div class="manage-grid">

  <!-- Existing announcements -->
  <div>
    <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:1rem">Posted Announcements</h2>

    <?php if (empty($announcements)): ?>
      <div class="empty-state">
        <i class="fas fa-bullhorn"></i>
        <div>No announcements posted yet.</div>
      </div>
    <?php endif; ?>

    <?php foreach ($announcements as $ann): ?>
      <div class="announcement-card <?= $ann['is_pinned'] ? 'pinned' : '' ?>"
           style="margin-bottom:.75rem;cursor:pointer"
           data-ann-title="<?= e($ann['title']) ?>"
           data-ann-body="<?= e($ann['body']) ?>"
           data-ann-author="<?= e($ann['author']) ?>"
           data-ann-date="<?= format_date($ann['created_at'], 'j M Y, H:i') ?>"
           data-ann-pinned="<?= $ann['is_pinned'] ? '1' : '0' ?>"
           onclick="openAnnModal(this)">

        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
          <div style="flex:1;min-width:0">
            <?php if ($ann['is_pinned']): ?>
              <span style="font-size:.68rem;font-weight:600;color:var(--accent);text-transform:uppercase;letter-spacing:.06em">
                <i class="fas fa-thumbtack"></i> Pinned &nbsp;
              </span>
            <?php endif; ?>
            <div class="announcement-title"><?= e($ann['title']) ?></div>
            <div style="font-size:.825rem;color:var(--text-muted);margin-top:.25rem;line-height:1.5">
              <?= nl2br(e(mb_strimwidth($ann['body'], 0, 160, '…'))) ?>
            </div>
            <div class="announcement-meta" style="margin-top:.5rem">
              <i class="fas fa-user-tie"></i> <?= e($ann['author']) ?>
              &nbsp;·&nbsp; <?= format_date($ann['created_at'], 'j M Y, H:i') ?>
              &nbsp;·&nbsp; <span style="color:var(--primary);font-size:.72rem">Click to read</span>
            </div>
          </div>

          <!-- Stop the click from propagating to the card's onclick -->
          <form method="POST" action="<?= url('admin/announcements/delete') ?>"
                onsubmit="return confirm('Delete this announcement?')"
                onclick="event.stopPropagation()">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $ann['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);flex-shrink:0">
              <i class="fas fa-trash"></i>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Post new announcement -->
  <div class="card" style="position:sticky;top:calc(var(--topbar-h) + 1rem)">
    <div class="card-header-clean">
      <h3><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Post Announcement</h3>
    </div>
    <div style="padding:1.25rem">

      <?php if (\App\Core\Session::hasFlash('errors')): ?>
        <div class="alert alert-danger" style="margin-bottom:1rem">
          <?php foreach (\App\Core\Session::getFlash('errors', []) as $err): ?>
            <div><?= e($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= url('admin/announcements') ?>">
        <?= csrf_field() ?>

        <div style="margin-bottom:1rem">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control"
                 placeholder="Announcement title" required maxlength="200">
        </div>

        <div style="margin-bottom:1rem">
          <label class="form-label">Message</label>
          <textarea name="body" class="form-control" rows="5"
                    placeholder="Write your announcement here…" required></textarea>
        </div>

        <div style="margin-bottom:1.25rem">
          <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.875rem">
            <input type="checkbox" name="is_pinned" value="1" style="width:15px;height:15px;accent-color:var(--accent)">
            <i class="fas fa-thumbtack" style="color:var(--accent)"></i>
            Pin to top
          </label>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
          <i class="fas fa-bullhorn"></i> Post Announcement
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Announcement view modal -->
<div id="annOverlay" onclick="closeAnnModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:300"></div>

<div id="annModal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            background:#fff;border-radius:16px;width:calc(100% - 2rem);max-width:560px;
            max-height:88vh;overflow-y:auto;z-index:400;box-shadow:0 24px 64px rgba(0,0,0,.18)">

  <!-- Modal header -->
  <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);
              display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;
              position:sticky;top:0;background:#fff;z-index:1">
    <div>
      <div id="ann-modal-pin" style="display:none;font-size:.7rem;font-weight:700;
           color:var(--accent);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.35rem">
        <i class="fas fa-thumbtack"></i> Pinned Announcement
      </div>
      <h2 id="ann-modal-title" style="margin:0;font-size:1.1rem;font-weight:700;line-height:1.35;
           color:var(--text)"></h2>
    </div>
    <button onclick="closeAnnModal()"
            style="background:none;border:none;cursor:pointer;color:var(--text-muted);
                   font-size:1.4rem;line-height:1;flex-shrink:0;padding:.1rem">
      &times;
    </button>
  </div>

  <!-- Modal body -->
  <div style="padding:1.5rem">
    <p id="ann-modal-body"
       style="color:var(--text);font-size:.9375rem;line-height:1.75;margin:0 0 1.25rem;
              white-space:pre-wrap"></p>

    <div style="padding-top:1rem;border-top:1px solid var(--border);
                display:flex;align-items:center;gap:.5rem;font-size:.78rem;color:var(--text-muted)">
      <i class="fas fa-user-tie"></i>
      <span id="ann-modal-author"></span>
      <span style="color:var(--border)">·</span>
      <i class="fas fa-clock"></i>
      <span id="ann-modal-date"></span>
    </div>
  </div>
</div>

<script>
function openAnnModal(card) {
  const title   = card.dataset.annTitle;
  const body    = card.dataset.annBody;
  const author  = card.dataset.annAuthor;
  const date    = card.dataset.annDate;
  const pinned  = card.dataset.annPinned === '1';

  document.getElementById('ann-modal-title').textContent  = title;
  document.getElementById('ann-modal-body').textContent   = body;
  document.getElementById('ann-modal-author').textContent = author;
  document.getElementById('ann-modal-date').textContent   = date;
  document.getElementById('ann-modal-pin').style.display  = pinned ? 'block' : 'none';

  document.getElementById('annModal').style.display   = 'block';
  document.getElementById('annOverlay').style.display = 'block';
  document.body.style.overflow = 'hidden';
}

function closeAnnModal() {
  document.getElementById('annModal').style.display   = 'none';
  document.getElementById('annOverlay').style.display = 'none';
  document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeAnnModal();
});
</script>
