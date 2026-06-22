// EpiElla entry — Gentelella v4 from npm + auth overlay.
import 'gentelella/scss/v4/main.scss';
import { mountShell } from 'gentelella/v4/shell.js';
import { initCharts } from 'gentelella/v4/charts.js';
import { initTables } from 'gentelella/v4/tables.js';
import { openMenu, DEFAULT_CARD_MENU } from 'gentelella/v4/menus.js';
import { initCommandPalette } from 'gentelella/v4/command-palette.js';
import { initPageActions } from 'gentelella/v4/page-actions.js';
import { ensureAuth, wireLogoutLinks } from './auth.js';

const isAdminShell = document.body?.dataset?.shell === 'admin';

function bootGentelella() {
  mountShell();
  initCharts();
  initCommandPalette();
  initPageActions();
}

if (isAdminShell) {
  wireLogoutLinks();
  ensureAuth().then((token) => {
    if (!token) return;
    bootGentelella();
    if (document.body.dataset.page === 'records') {
      import('./records.js').then((m) => m.initRecordsPage());
    } else {
      initTables();
    }
  });
} else {
  bootGentelella();
  initTables();
}

if ('serviceWorker' in navigator && import.meta.env.PROD) {
  window.addEventListener('load', () => {
    const swPath = `${import.meta.env.BASE_URL}sw.js`;
    navigator.serviceWorker.register(swPath).catch(() => { /* ignore */ });
  });
}

document.addEventListener('click', (e) => {
  const toggle = e.target.closest('.toggle');
  if (toggle) toggle.classList.toggle('on');
});

document.addEventListener('click', (e) => {
  const cb = e.target.closest('.todo-cb');
  if (!cb) return;
  cb.classList.toggle('done');
  const row = cb.closest('.todo-row');
  if (row) row.classList.toggle('done');
  const card = cb.closest('.card');
  if (!card) return;
  const counter = card.querySelector('[data-todo-counter]');
  if (!counter) return;
  const all = card.querySelectorAll('.todo-row');
  const done = card.querySelectorAll('.todo-row.done');
  counter.textContent = `${all.length - done.length} of ${all.length} remaining`;
});

document.addEventListener('click', (e) => {
  const tab = e.target.closest('.chart-tab');
  if (!tab) return;
  tab.parentElement.querySelectorAll('.chart-tab').forEach((t) => t.classList.remove('active'));
  tab.classList.add('active');
});

document.addEventListener('click', (e) => {
  const btn = e.target.closest('.btn-group[data-group] > .btn');
  if (!btn) return;
  btn.parentElement.querySelectorAll('.btn').forEach((b) => {
    b.classList.remove('active');
    b.setAttribute('aria-pressed', 'false');
  });
  btn.classList.add('active');
  btn.setAttribute('aria-pressed', 'true');
});

document.addEventListener('click', (e) => {
  const btn = e.target.closest('.card-opt-btn');
  if (!btn || e.defaultPrevented) return;
  e.preventDefault();
  openMenu(btn, DEFAULT_CARD_MENU);
});

document.addEventListener('click', (e) => {
  const closer = e.target.closest('.chip-close');
  if (closer) {
    const chip = closer.closest('.chip');
    if (chip) {
      chip.style.transition = 'opacity 150ms, transform 150ms';
      chip.style.opacity = '0';
      chip.style.transform = 'scale(0.85)';
      setTimeout(() => chip.remove(), 160);
    }
    return;
  }
  const chip = e.target.closest('.chip');
  if (chip) chip.classList.toggle('active');
});

document.addEventListener('submit', (e) => {
  const form = e.target;
  if (!(form instanceof HTMLFormElement)) return;
  if (form.id === 'record-form' || form.id === 'login-form') return;
  e.preventDefault();
  const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
  const label = (submitBtn?.textContent || submitBtn?.value || 'Saved').trim();
  import('gentelella/v4/toast.js').then(({ showToast }) => showToast(`${label} ✓`, { variant: 'success' }));
  if (form.dataset.resetOnSubmit !== 'false') form.reset();
});
