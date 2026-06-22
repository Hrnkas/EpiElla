const API_BASE = import.meta.env.VITE_API_BASE ?? '';

export function apiFetch(path, opts = {}) {
  const token = sessionStorage.getItem('accessToken');
  const headers = { ...(opts.headers || {}) };
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  if (opts.body && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json';
  }
  return fetch(`${API_BASE}${path}`, { ...opts, headers });
}

export async function apiJson(path, opts = {}) {
  const res = await apiFetch(path, opts);
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.error || res.statusText || 'Request failed');
  }
  return data;
}
