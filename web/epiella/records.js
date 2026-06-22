import { apiFetch } from './api-client.js';

function recordsAdapter() {
  const baseUrl = '/api/records.json';
  const f = apiFetch;

  const json = async (res) => {
    if (!res.ok) {
      const body = await res.text().catch(() => '');
      throw new Error(body || res.statusText);
    }
    return res.json();
  };

  return {
    async list() {
      const data = await json(await f(baseUrl));
      return data.records ?? [];
    },
    async get(id) {
      return json(await f(`${baseUrl.replace('.json', '')}/${id}.json`));
    },
    async create(data) {
      return json(await f(baseUrl, {
        method: 'POST',
        body: JSON.stringify(data)
      }));
    },
    async update(id, patch) {
      return json(await f(`${baseUrl.replace('.json', '')}/${id}.json`, {
        method: 'PUT',
        body: JSON.stringify(patch)
      }));
    },
    async remove(id) {
      await json(await f(`${baseUrl.replace('.json', '')}/${id}.json`, { method: 'DELETE' }));
      return true;
    }
  };
}

export async function initRecordsPage() {
  const form = document.getElementById('record-form');
  const tbody = document.getElementById('records-tbody');
  const refreshBtn = document.getElementById('records-refresh');
  if (!form || !tbody) return;

  const adapter = recordsAdapter();

  async function loadTable() {
    const records = await adapter.list();
    tbody.innerHTML = records.map((r) => {
      const payload = typeof r.payload === 'object' ? JSON.stringify(r.payload) : String(r.payload);
      return `<tr>
        <td class="cell-mono">${r.id}</td>
        <td class="cell-strong">${escapeHtml(r.title)}</td>
        <td><code>${escapeHtml(payload)}</code></td>
        <td>${escapeHtml(r.created_at)}</td>
        <td>
          <button class="btn btn-outline btn-sm" data-delete="${r.id}" type="button">Delete</button>
        </td>
      </tr>`;
    }).join('');

    const table = document.querySelector('[data-datatable]');
    if (table) {
      const { default: DataTable } = await import('datatables.net');
      if (table._dtInstance) {
        table._dtInstance.destroy();
        table._dtInstance = null;
      }
      table._dtInstance = new DataTable(table, {
        pageLength: 25,
        lengthChange: false,
        order: [],
        columnDefs: [{ targets: 4, orderable: false }],
        language: { search: '', searchPlaceholder: 'Search…' }
      });
    }
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const title = form.querySelector('[name="title"]').value.trim();
    const payloadRaw = form.querySelector('[name="payload"]').value.trim();
    let payload = {};
    if (payloadRaw) {
      try {
        payload = JSON.parse(payloadRaw);
      } catch {
        const { showToast } = await import('gentelella/v4/toast.js');
        showToast('Invalid JSON payload', { variant: 'error' });
        return;
      }
    }
    await adapter.create({ title, payload });
    form.reset();
    await loadTable();
    const { showToast } = await import('gentelella/v4/toast.js');
    showToast('Record created', { variant: 'success' });
  });

  tbody.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-delete]');
    if (!btn) return;
    const id = btn.getAttribute('data-delete');
    await adapter.remove(id);
    await loadTable();
    const { showToast } = await import('gentelella/v4/toast.js');
    showToast('Record deleted', { variant: 'success' });
  });

  refreshBtn?.addEventListener('click', () => loadTable());
  await loadTable();
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}
