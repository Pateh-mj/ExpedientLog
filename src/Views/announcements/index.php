<?php $pageTitle = 'Announcements'; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
  <div>
    <h2 style="margin:0;font-size:1.25rem;font-weight:700">Announcements</h2>
    <p style="margin:.2rem 0 0;color:var(--text-muted);font-size:.875rem">Company notices and updates</p>
  </div>
  <?php if (is_admin()): ?>
    <a href="<?= url('admin/announcements') ?>" class="btn btn-primary btn-sm">
      <i class="fas fa-plus"></i> Post Announcement
    </a>
  <?php endif; ?>
</div>

<?php if (empty($announcements)): ?>
  <div class="empty-state">
    <i class="fas fa-bullhorn"></i>
    <div style="font-weight:500">No announcements yet</div>
    <div style="font-size:.875rem">Check back later for company updates.</div>
  </div>
<?php else: ?>
  <?php foreach ($announcements as $ann): ?>
    <div class="announcement-card <?= $ann['is_pinned'] ? 'pinned' : '' ?>">
      <?php if ($ann['is_pinned']): ?>
        <div style="font-size:.7rem;font-weight:600;color:var(--accent);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem">
          <i class="fas fa-thumbtack"></i> Pinned
        </div>
      <?php endif; ?>
      <div class="announcement-title"><?= e($ann['title']) ?></div>
      <div style="color:var(--text);font-size:.875rem;margin:.5rem 0 .75rem;line-height:1.6">
        <?= nl2br(e($ann['body'])) ?>
      </div>
      <div class="announcement-meta">
        <i class="fas fa-user-tie"></i> <?= e($ann['author']) ?>
        &nbsp;·&nbsp;
        <?= format_date($ann['created_at'], 'j M Y, H:i') ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
