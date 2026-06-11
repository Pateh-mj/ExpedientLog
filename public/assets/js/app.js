/* ExpedientLog — app.js */
'use strict';

// ── Clock ──────────────────────────────────────────────────
function initClock() {
  const el = document.getElementById('topbar-clock');
  if (!el) return;
  const tick = () => {
    el.textContent = new Date().toLocaleTimeString('en-GB', {
      hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
  };
  tick();
  setInterval(tick, 1000);
}

// ── Sidebar toggle (mobile) ────────────────────────────────
function initSidebar() {
  const toggle  = document.getElementById('sidebar-toggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  if (!toggle || !sidebar) return;

  const open  = () => { sidebar.classList.add('open'); overlay?.classList.add('open'); document.body.style.overflow = 'hidden'; };
  const close = () => { sidebar.classList.remove('open'); overlay?.classList.remove('open'); document.body.style.overflow = ''; };

  toggle.addEventListener('click', open);
  overlay?.addEventListener('click', close);

  // Close sidebar when a nav link is tapped on mobile
  sidebar.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 992) close();
    });
  });

  // Close on Escape key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') close();
  });
}

// ── Auto-dismiss alerts ────────────────────────────────────
function initAlerts() {
  document.querySelectorAll('.alert[data-dismiss]').forEach(el => {
    setTimeout(() => el.remove(), 5000);
  });
  document.querySelectorAll('.alert .alert-close').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('.alert').remove());
  });
}

// ── Character counter ──────────────────────────────────────
function initCharCounter() {
  document.querySelectorAll('[data-counter]').forEach(input => {
    const targetId = input.dataset.counter;
    const counter  = document.getElementById(targetId);
    const max      = parseInt(input.maxLength, 10);
    if (!counter || !max) return;

    const update = () => {
      const len = input.value.length;
      counter.textContent = `${len} / ${max}`;
      counter.style.color = len > max * .9 ? '#ef4444' : '#64748b';
    };
    input.addEventListener('input', update);
    update();
  });
}

// ── KB category toggle ─────────────────────────────────────
function initKbToggle() {
  document.querySelectorAll('[data-kb-toggle]').forEach(checkbox => {
    const targetId = checkbox.dataset.kbToggle;
    const target   = document.getElementById(targetId);
    if (!target) return;

    const toggle = () => {
      target.style.display = checkbox.checked ? 'block' : 'none';
      target.querySelectorAll('select').forEach(s => s.required = checkbox.checked);
    };
    checkbox.addEventListener('change', toggle);
    toggle();
  });
}

// ── AJAX Task Delete ───────────────────────────────────────
function initTaskDelete() {
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-delete-task]');
    if (!btn) return;
    e.preventDefault();

    if (!confirm('Delete this activity? This cannot be undone.')) return;

    const id   = btn.dataset.deleteTask;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    btn.disabled = true;

    fetch(baseUrl('/tasks/delete'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${id}&_csrf=${encodeURIComponent(csrf)}`,
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const item = document.querySelector(`[data-task-id="${id}"]`);
        if (item) {
          item.style.transition = 'opacity .2s';
          item.style.opacity = '0';
          setTimeout(() => {
            item.remove();
            updateTaskCount(-1);
            checkEmptyState();
          }, 200);
        }
      } else {
        alert('Error: ' + (data.error ?? 'Could not delete.'));
        btn.disabled = false;
      }
    })
    .catch(() => { alert('Network error.'); btn.disabled = false; });
  });
}

// ── AJAX Task Edit ─────────────────────────────────────────
function initTaskEdit() {
  const modal     = document.getElementById('editModal');
  const form      = document.getElementById('edit-form');
  const overlay   = document.getElementById('editModalOverlay');
  if (!modal || !form) return;

  // Open modal
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-edit-task]');
    if (!btn) return;

    document.getElementById('edit-id').value          = btn.dataset.editTask;
    document.getElementById('edit-task').value        = btn.dataset.task;
    document.getElementById('edit-project').value     = btn.dataset.project;
    const isKb = btn.dataset.iskb === '1';
    const kbCb = document.getElementById('edit-is-knowledge');
    kbCb.checked = isKb;
    document.getElementById('edit-category').value   = btn.dataset.category ?? '';

    // Trigger KB toggle
    kbCb.dispatchEvent(new Event('change'));

    modal.classList.add('open');
    overlay?.classList.add('open');
  });

  // Close modal
  document.querySelectorAll('[data-close-edit-modal]').forEach(el => {
    el.addEventListener('click', closeEditModal);
  });
  overlay?.addEventListener('click', closeEditModal);

  function closeEditModal() {
    modal.classList.remove('open');
    overlay?.classList.remove('open');
  }

  // Submit
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const data   = new FormData(this);
    const id     = data.get('id');
    const submit = form.querySelector('[type=submit]');
    submit.disabled = true;
    submit.textContent = 'Saving…';

    fetch(baseUrl('/tasks/update'), { method: 'POST', body: data })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const d = res.data;
        const row = document.querySelector(`[data-task-id="${id}"]`);
        if (row) {
          row.querySelector('[data-task-text]').textContent = d.task;
          row.querySelector('[data-task-project]').textContent = d.project;
          const badge = row.querySelector('[data-task-kb-badge]');
          if (d.is_knowledge) {
            if (badge) { badge.textContent = '💡 ' + d.category; badge.style.display = ''; }
          } else {
            if (badge) badge.style.display = 'none';
          }
          const timeEl = row.querySelector('[data-task-time]');
          if (timeEl) timeEl.innerHTML = d.time + ' <small style="color:#94a3b8">(edited)</small>';
        }
        closeEditModal();
      } else {
        alert('Error: ' + (res.error ?? 'Update failed.'));
      }
    })
    .catch(() => alert('Network error.'))
    .finally(() => { submit.disabled = false; submit.textContent = 'Save Changes'; });
  });
}

// ── Image preview modal ────────────────────────────────────
function initImageModal() {
  const modal = document.getElementById('imageModal');
  const img   = document.getElementById('imageModal-img');
  if (!modal || !img) return;

  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-image-src]');
    if (!btn) return;
    img.src = btn.dataset.imageSrc;
    modal.classList.add('open');
    document.getElementById('imageModalOverlay')?.classList.add('open');
  });

  document.querySelectorAll('[data-close-image-modal]').forEach(el => {
    el.addEventListener('click', () => {
      modal.classList.remove('open');
      document.getElementById('imageModalOverlay')?.classList.remove('open');
      img.src = '';
    });
  });
}

// ── Helpers ────────────────────────────────────────────────
function baseUrl(path) {
  const base = document.querySelector('meta[name="base-url"]')?.content ?? '';
  return base + path;
}

function updateTaskCount(delta) {
  const el = document.getElementById('task-count');
  if (el) el.textContent = Math.max(0, parseInt(el.textContent, 10) + delta);
}

function checkEmptyState() {
  const list  = document.getElementById('activity-list');
  const empty = document.getElementById('empty-state');
  if (!list) return;
  const hasItems = list.querySelector('[data-task-id]');
  if (empty) empty.style.display = hasItems ? 'none' : 'block';
}

// ── Password strength ──────────────────────────────────────
function initPasswordStrength() {
  const input = document.getElementById('password-input');
  const fill  = document.getElementById('strength-fill');
  const label = document.getElementById('strength-label');
  if (!input || !fill) return;

  input.addEventListener('input', () => {
    const v = input.value;
    let score = 0;
    if (v.length >= 8)         score++;
    if (/[A-Z]/.test(v))       score++;
    if (/[a-z]/.test(v))       score++;
    if (/[0-9]/.test(v))       score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    const pct = (score / 5) * 100;
    fill.style.width = pct + '%';

    const levels = ['', '#ef4444', '#f59e0b', '#f59e0b', '#10b981', '#10b981'];
    const texts  = ['', 'Very weak', 'Weak', 'Fair', 'Strong', 'Very strong'];
    fill.style.background = levels[score] || '#e2e8f0';
    if (label) label.textContent = texts[score] || '';
  });
}

// ── ⑥ Draft auto-save ─────────────────────────────────────
function initDraftSave() {
  const textarea  = document.getElementById('task-input');
  const indicator = document.getElementById('draft-indicator');
  const form      = document.getElementById('log-form');
  if (!textarea) return;

  const KEY = 'expedientlog_draft_' + (document.querySelector('meta[name="base-url"]')?.content ?? '');

  // Restore on load
  const saved = localStorage.getItem(KEY);
  if (saved) {
    textarea.value = saved;
    textarea.dispatchEvent(new Event('input')); // trigger char counter
    if (indicator) indicator.style.visibility = 'visible';
  }

  let saveTimer;
  textarea.addEventListener('input', () => {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
      if (textarea.value.trim()) {
        localStorage.setItem(KEY, textarea.value);
        if (indicator) indicator.style.visibility = 'visible';
      } else {
        localStorage.removeItem(KEY);
        if (indicator) indicator.style.visibility = 'hidden';
      }
    }, 600);
  });

  // Clear draft on successful submit
  form?.addEventListener('submit', () => {
    localStorage.removeItem(KEY);
  });
}

// ── ⑦ Ctrl+Enter to submit ────────────────────────────────
function initCtrlEnter() {
  const textarea = document.getElementById('task-input');
  const form     = document.getElementById('log-form');
  if (!textarea || !form) return;

  textarea.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      form.requestSubmit();
    }
  });
}

// ── ③ Image upload preview ────────────────────────────────
function initUploadPreview() {
  const input   = document.getElementById('task_image');
  const wrap    = document.getElementById('image-preview-wrap');
  const preview = document.getElementById('image-preview');
  const remove  = document.getElementById('remove-preview');
  if (!input || !wrap || !preview) return;

  input.addEventListener('change', () => {
    const file = input.files[0];
    if (!file) { wrap.style.display = 'none'; return; }

    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      wrap.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });

  remove?.addEventListener('click', () => {
    input.value = '';
    wrap.style.display = 'none';
    preview.src = '';
  });
}

// ── ⑤ History tabs ────────────────────────────────────────
function initLogTabs() {
  const tabs = document.querySelectorAll('.log-tab');
  if (!tabs.length) return;

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab;

      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');

      document.querySelectorAll('[data-panel]').forEach(p => {
        p.style.display = p.dataset.panel === target ? 'block' : 'none';
      });
    });
  });
}

// ── Bootstrap all ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initClock();
  initSidebar();
  initAlerts();
  initCharCounter();
  initKbToggle();
  initTaskDelete();
  initTaskEdit();
  initImageModal();
  initPasswordStrength();
  initDraftSave();
  initCtrlEnter();
  initUploadPreview();
  initLogTabs();
});
