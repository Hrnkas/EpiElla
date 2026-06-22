// EpiElla shell — custom NAV/branding; ICONS and chrome from upstream Gentelella.
import {
  ICONS,
  renderTopbar,
  renderFooter,
  parseShellAttrs
} from '../node_modules/gentelella/src/v4/shell-render.js';

export { parseShellAttrs };

export const NAV = [
  {
    label: 'EpiElla',
    items: [
      { key: 'dashboard', href: 'index.html', text: 'Dashboard', icon: 'dashboard' },
      { key: 'records', href: 'records.html', text: 'Records', icon: 'tables' },
      { key: 'logout', href: '#', text: 'Logout', icon: 'profile' }
    ]
  }
];

const CHEVRON = '<svg class="nav-chev" width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6 4l4 4-4 4"/></svg>';

function renderNavItem(item, activeKey) {
  if (item.children) {
    const childActive = item.children.some((c) => c.key === activeKey);
    const sub = item.children.map((c) => {
      const a = c.key === activeKey;
      return `<a class="nav-sublink${a ? ' active' : ''}" href="${c.href}"${a ? ' aria-current="page"' : ''}>${c.text}${c.badge ? `<span class="badge ${c.badge.cls}">${c.badge.text}</span>` : ''}</a>`;
    }).join('');
    const cls = ['nav-tree'];
    if (childActive) { cls.push('open', 'has-active'); }
    return `
      <div class="${cls.join(' ')}">
        <button type="button" class="nav-link nav-toggle" aria-expanded="${childActive ? 'true' : 'false'}">
          ${ICONS[item.icon] || ''}
          <span class="nav-text">${item.text}</span>
          ${item.badge ? `<span class="badge ${item.badge.cls}">${item.badge.text}</span>` : ''}
          ${CHEVRON}
        </button>
        <div class="nav-sub"><div class="nav-sub-inner">${sub}</div></div>
      </div>
    `;
  }
  const a = item.key === activeKey;
  const logoutAttr = item.key === 'logout' ? ' data-logout="1"' : '';
  return `
    <a class="nav-link${a ? ' active' : ''}" href="${item.href}"${logoutAttr}${a ? ' aria-current="page"' : ''}>
      ${ICONS[item.icon] || ''}
      <span class="nav-text">${item.text}</span>
      ${item.badge ? `<span class="badge ${item.badge.cls}">${item.badge.text}</span>` : ''}
    </a>
  `;
}

export function renderSidebar(activeKey) {
  const groups = NAV.map((group) => `
    <div class="nav-group">
      <div class="nav-label">${group.label}</div>
      ${group.items.map((item) => renderNavItem(item, activeKey)).join('')}
    </div>
  `).join('');

  return `
    <aside class="sidebar" aria-label="Primary navigation">
      <div class="sidebar-brand">
        <div class="brand-icon">E</div>
        <div class="brand-name">EpiElla <small>v4</small></div>
      </div>
      <nav class="sidebar-nav">${groups}</nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar">A<span class="online"></span></div>
          <div class="sidebar-user-info">
            <div class="name">Admin</div>
            <div class="role">EpiElla</div>
          </div>
          <button class="more-btn" aria-label="More options">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="3" r="1.2"/><circle cx="8" cy="8" r="1.2"/><circle cx="8" cy="13" r="1.2"/></svg>
          </button>
        </div>
      </div>
    </aside>
  `;
}

export function renderShell({ activeKey = '', breadcrumb = ['Home'] } = {}) {
  return {
    sidebar: renderSidebar(activeKey),
    topbar: renderTopbar(breadcrumb),
    footer: renderFooter()
  };
}
