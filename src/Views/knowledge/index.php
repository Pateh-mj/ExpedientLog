<?php
$pageTitle   = 'Knowledge Base';
$totalItems  = count($items);
$categories  = array_unique(array_filter(array_column($items, 'category')));
sort($categories);

// Colour palette mapped to category hash
function kbCategoryColor(string $cat): array {
    $palettes = [
        ['bg'=>'#dbeafe','text'=>'#1e40af'],  // blue
        ['bg'=>'#d1fae5','text'=>'#065f46'],  // green
        ['bg'=>'#fef3c7','text'=>'#92400e'],  // amber
        ['bg'=>'#fce7f3','text'=>'#9d174d'],  // pink
        ['bg'=>'#ede9fe','text'=>'#5b21b6'],  // purple
        ['bg'=>'#ffedd5','text'=>'#9a3412'],  // orange
        ['bg'=>'#cffafe','text'=>'#155e75'],  // cyan
    ];
    return $palettes[crc32($cat) % count($palettes)];
}
?>

<style>
/* ── Knowledge Base layout ─────────────────────────── */
.kb-grid {
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));
  gap:1.1rem;
}
.kb-entry-card {
  background:#fff;
  border:1px solid var(--border);
  border-radius:14px;
  display:flex;
  flex-direction:column;
  transition:box-shadow .2s, transform .2s;
  overflow:hidden;
}
.kb-entry-card:hover {
  box-shadow:0 8px 28px rgba(0,0,0,.09);
  transform:translateY(-2px);
}
.kb-card-header {
  padding:.85rem 1rem .65rem;
  border-bottom:1px solid var(--border);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:.5rem;
}
.kb-card-body {
  padding:.85rem 1rem;
  flex:1;
  line-height:1.65;
  font-size:.86rem;
  color:var(--text);
}
.kb-card-footer {
  padding:.6rem 1rem;
  border-top:1px solid var(--border);
  background:#fafbfc;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:.5rem;
}
.kb-avatar {
  width:24px;height:24px;border-radius:50%;
  display:inline-flex;align-items:center;justify-content:center;
  font-size:.6rem;font-weight:800;color:#fff;flex-shrink:0;
}
.kb-read-btn {
  background:none;border:1px solid var(--border);border-radius:6px;
  padding:.2rem .6rem;font-size:.72rem;font-weight:600;color:var(--primary);
  cursor:pointer;transition:background .15s,border-color .15s;white-space:nowrap;
}
.kb-read-btn:hover { background:var(--primary-subtle);border-color:var(--primary); }

/* ── Detail drawer ─────────────────────────────────── */
#kb-drawer {
  position:fixed;top:0;right:-500px;width:480px;max-width:96vw;height:100vh;
  background:#fff;box-shadow:-6px 0 40px rgba(0,0,0,.14);
  z-index:600;transition:right .28s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;border-left:1px solid var(--border);
}
#kb-drawer.open { right:0; }
#kb-drawer-overlay {
  position:fixed;inset:0;background:rgba(0,0,0,.25);z-index:599;
  opacity:0;pointer-events:none;transition:opacity .28s;
}
#kb-drawer-overlay.open { opacity:1;pointer-events:auto; }
.kb-drawer-hdr {
  padding:1rem 1.25rem;border-bottom:1px solid var(--border);
  background:#fafbfc;display:flex;gap:.75rem;align-items:flex-start;
}
.kb-drawer-body {
  padding:1.25rem;flex:1;overflow-y:auto;
}
.kb-drawer-body p { line-height:1.85;font-size:.9rem;color:var(--text);margin:0 0 1.25rem; }
.kb-drawer-ftr {
  padding:.875rem 1.25rem;border-top:1px solid var(--border);background:#fafbfc;
  display:flex;gap:.6rem;justify-content:flex-end;
}

/* ── Image viewer ──────────────────────────────────── */
#imageModalOverlay { position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:700;
                     display:none;cursor:pointer; }
#imageModal {
  display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
  background:#111;border-radius:14px;padding:0;
  width:calc(100% - 2rem);max-width:92vw;max-height:92vh;
  z-index:750;box-shadow:0 24px 80px rgba(0,0,0,.6);overflow:hidden;
  flex-direction:column;
}
#imageModal.open { display:flex !important; }
.img-modal-toolbar {
  display:flex;justify-content:space-between;align-items:center;
  padding:.6rem 1rem;background:rgba(255,255,255,.06);
  border-bottom:1px solid rgba(255,255,255,.1);flex-shrink:0;
}
.img-modal-scroll {
  overflow:auto;flex:1;display:flex;align-items:center;
  justify-content:center;padding:.75rem;
}
#imageModal-img {
  max-width:100%;max-height:calc(92vh - 60px);
  border-radius:8px;display:block;object-fit:contain;
}
</style>

<!-- ── Page header ─────────────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;
            flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem">
  <div>
    <h2 style="margin:0;font-size:1.2rem;font-weight:800">
      <i class="fas fa-lightbulb" style="color:var(--accent)"></i> Knowledge Base
    </h2>
    <p style="margin:.15rem 0 0;color:var(--text-muted);font-size:.825rem">
      <?= $totalItems ?> <?= $totalItems === 1 ? 'entry' : 'entries' ?> · shared team knowledge
    </p>
  </div>
</div>

<!-- ── Search & filter bar ─────────────────────────── -->
<div class="card" style="padding:.875rem 1.25rem;margin-bottom:1.25rem;border-radius:12px">
  <form method="GET" action="<?= url('knowledge') ?>">
    <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end">
      <div style="flex:1;min-width:180px">
        <label class="form-label" style="font-size:.75rem;margin-bottom:.25rem">Search</label>
        <input type="text" name="q" class="form-control"
               placeholder="Search knowledge…" value="<?= e($search) ?>"
               style="padding:.4rem .75rem;font-size:.85rem">
      </div>
      <div>
        <label class="form-label" style="font-size:.75rem;margin-bottom:.25rem">Category</label>
        <select name="cat" class="form-select" style="padding:.4rem .75rem;font-size:.85rem;width:auto">
          <option value="all" <?= ($category === '' || $category === 'all') ? 'selected' : '' ?>>All Categories</option>
          <?php foreach ($kb_categories as $c): ?>
            <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">
        <i class="fas fa-search"></i> Search
      </button>
      <?php if ($search || ($category && $category !== 'all')): ?>
        <a href="<?= url('knowledge') ?>" class="btn btn-ghost btn-sm" style="align-self:flex-end">
          <i class="fas fa-times"></i> Clear
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- ── Category chips (quick filter) ──────────────── -->
<?php if (!empty($categories)): ?>
<div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem">
  <a href="<?= url('knowledge') ?>?q=<?= urlencode($search) ?>&cat=all"
     style="text-decoration:none;padding:.2rem .7rem;border-radius:99px;font-size:.72rem;
            font-weight:600;border:1px solid var(--border);
            background:<?= ($category === '' || $category === 'all') ? 'var(--primary)' : '#fff' ?>;
            color:<?= ($category === '' || $category === 'all') ? '#fff' : 'var(--text-muted)' ?>">
    All
  </a>
  <?php foreach ($categories as $c):
    $col = kbCategoryColor($c);
    $isActive = $category === $c;
  ?>
    <a href="<?= url('knowledge') ?>?q=<?= urlencode($search) ?>&cat=<?= urlencode($c) ?>"
       style="text-decoration:none;padding:.2rem .7rem;border-radius:99px;font-size:.72rem;
              font-weight:600;border:1px solid <?= $isActive ? $col['text'] : 'var(--border)' ?>;
              background:<?= $isActive ? $col['bg'] : '#fff' ?>;
              color:<?= $isActive ? $col['text'] : 'var(--text-muted)' ?>">
      <?= e($c) ?>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Grid ────────────────────────────────────────── -->
<?php if (empty($items)): ?>
  <div class="empty-state">
    <i class="fas fa-lightbulb"></i>
    <div style="font-weight:500;margin-bottom:.25rem">No knowledge entries found</div>
    <div style="font-size:.875rem">Log activities and tick "Save as Reusable Knowledge" to contribute.</div>
  </div>
<?php else: ?>
  <div class="kb-grid">
    <?php foreach ($items as $item):
      $col      = kbCategoryColor($item['category'] ?? 'General');
      $initials = strtoupper(substr($item['username'], 0, 2));
      $avatarBg = ['#3b5bdb','#0ca678','#f59f00','#e64980','#7048e8','#1098ad'];
      $bg       = $avatarBg[crc32($item['username']) % count($avatarBg)];
      $preview  = e(mb_strimwidth($item['task'], 0, 130, '…'));
    ?>
      <div class="kb-entry-card">

        <!-- Header: category pill + date -->
        <div class="kb-card-header">
          <span style="background:<?= $col['bg'] ?>;color:<?= $col['text'] ?>;
                       font-size:.7rem;font-weight:700;padding:.22rem .65rem;
                       border-radius:99px;white-space:nowrap">
            <i class="fas fa-tag" style="font-size:.6rem"></i> <?= e($item['category']) ?>
          </span>
          <small style="color:var(--text-muted);font-size:.72rem;white-space:nowrap">
            <?= format_date($item['created_at']) ?>
          </small>
        </div>

        <!-- Body: preview text -->
        <div class="kb-card-body">
          <?= $preview ?>
        </div>

        <!-- Footer: avatar + author + actions -->
        <div class="kb-card-footer">
          <div style="display:flex;align-items:center;gap:.45rem;min-width:0">
            <div class="kb-avatar" style="background:<?= $bg ?>">
              <?= $initials ?>
            </div>
            <div style="min-width:0">
              <div style="font-size:.775rem;font-weight:600;white-space:nowrap;
                          overflow:hidden;text-overflow:ellipsis">
                <?= e($item['username']) ?>
              </div>
              <?php if (!empty($item['department'])): ?>
                <div style="font-size:.65rem;color:var(--text-muted)"><?= e($item['department']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:.4rem;flex-shrink:0">
            <?php if ($item['image_path']): ?>
              <button onclick="openKbImg('<?= url('uploads/' . basename($item['image_path'])) ?>')"
                      style="background:none;border:1px solid var(--border);border-radius:6px;
                             padding:.2rem .5rem;font-size:.72rem;color:var(--text-muted);cursor:pointer">
                <i class="fas fa-image"></i>
              </button>
            <?php endif; ?>
            <button class="kb-read-btn"
                    onclick="openKbDrawer(this)"
                    data-task="<?= e($item['task']) ?>"
                    data-category="<?= e($item['category']) ?>"
                    data-username="<?= e($item['username']) ?>"
                    data-department="<?= e($item['department'] ?? '') ?>"
                    data-date="<?= format_date($item['created_at']) ?>"
                    data-image="<?= $item['image_path'] ? url('uploads/' . basename($item['image_path'])) : '' ?>">
              Read more <i class="fas fa-chevron-right" style="font-size:.6rem"></i>
            </button>
          </div>
        </div>

      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>


<!-- ── Knowledge Detail Drawer ──────────────────────── -->
<div id="kb-drawer-overlay" onclick="closeKbDrawer()"></div>
<div id="kb-drawer">
  <div class="kb-drawer-hdr">
    <div style="flex:1;min-width:0">
      <span id="kb-d-cat" style="font-size:.7rem;font-weight:700;padding:.2rem .6rem;border-radius:99px;display:inline-block;margin-bottom:.4rem"></span>
      <div style="font-size:.8rem;color:var(--text-muted)" id="kb-d-meta"></div>
    </div>
    <button onclick="closeKbDrawer()"
            style="background:none;border:none;cursor:pointer;font-size:1.4rem;
                   color:var(--text-muted);line-height:1;padding:0;flex-shrink:0">
      &times;
    </button>
  </div>

  <div class="kb-drawer-body">
    <p id="kb-d-text"></p>
    <div id="kb-d-img-wrap" style="display:none">
      <button onclick="openKbImg(window.__kbImgSrc)"
              class="btn btn-ghost btn-sm">
        <i class="fas fa-image"></i> View Attached Image
      </button>
    </div>
  </div>

  <div class="kb-drawer-ftr">
    <button onclick="closeKbDrawer()" class="btn btn-ghost btn-sm">Close</button>
  </div>
</div>

<!-- ── Image viewer modal ───────────────────────────── -->
<div id="imageModalOverlay" onclick="closeKbImg()"></div>
<div id="imageModal">
  <div class="img-modal-toolbar">
    <span style="color:#fff;font-size:.8rem;opacity:.7"><i class="fas fa-image"></i> Attachment</span>
    <button onclick="closeKbImg()"
            style="background:none;border:none;cursor:pointer;font-size:1.4rem;color:#fff;opacity:.8;line-height:1">
      &times;
    </button>
  </div>
  <div class="img-modal-scroll">
    <img id="imageModal-img" src="" alt="Attachment">
  </div>
</div>

<script>
var __kbImgSrc = '';

function openKbDrawer(btn) {
  var text = btn.dataset.task;
  var cat = btn.dataset.category;
  var user = btn.dataset.username;
  var dept = btn.dataset.department;
  var date = btn.dataset.date;
  var imgSrc = btn.dataset.image;

  var col = '<?= json_encode(kbCategoryColor('__')) ?>';
  // Set category chip
  var catEl = document.getElementById('kb-d-cat');
  catEl.textContent = cat;
  catEl.style.background = getCatBg(cat);
  catEl.style.color       = getCatText(cat);

  document.getElementById('kb-d-meta').innerHTML =
    '<i class="fas fa-user-circle"></i> ' + user +
    (dept ? ' &nbsp;·&nbsp; ' + dept : '') +
    ' &nbsp;·&nbsp; ' + date;

  document.getElementById('kb-d-text').textContent = text;

  window.__kbImgSrc = imgSrc;
  document.getElementById('kb-d-img-wrap').style.display = imgSrc ? 'block' : 'none';

  document.getElementById('kb-drawer').classList.add('open');
  document.getElementById('kb-drawer-overlay').classList.add('open');
}
function closeKbDrawer() {
  document.getElementById('kb-drawer').classList.remove('open');
  document.getElementById('kb-drawer-overlay').classList.remove('open');
}
function openKbImg(src) {
  document.getElementById('imageModal-img').src = src;
  document.getElementById('imageModal').classList.add('open');
  document.getElementById('imageModalOverlay').style.display = 'block';
}
function closeKbImg() {
  document.getElementById('imageModal').classList.remove('open');
  document.getElementById('imageModalOverlay').style.display = 'none';
}

// Category colour helpers (must match PHP palette by crc32 position)
var _palettes = [
  {bg:'#dbeafe',text:'#1e40af'},{bg:'#d1fae5',text:'#065f46'},
  {bg:'#fef3c7',text:'#92400e'},{bg:'#fce7f3',text:'#9d174d'},
  {bg:'#ede9fe',text:'#5b21b6'},{bg:'#ffedd5',text:'#9a3412'},
  {bg:'#cffafe',text:'#155e75'}
];
function _catHash(str) {
  var h = 0;
  for (var i = 0; i < str.length; i++) {
    h = (Math.imul(31, h) + str.charCodeAt(i)) | 0;
  }
  return Math.abs(h) % _palettes.length;
}
function getCatBg(cat)   { return _palettes[_catHash(cat)].bg; }
function getCatText(cat) { return _palettes[_catHash(cat)].text; }
</script>
