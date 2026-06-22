import { apiJson, apiFetch } from './api-client.js';

export async function refreshAccessToken() {
  const refreshToken = localStorage.getItem('refreshToken');
  if (!refreshToken) return null;
  try {
    const data = await apiJson('/api/auth/refresh.json', {
      method: 'POST',
      body: JSON.stringify({ refreshToken })
    });
    sessionStorage.setItem('accessToken', data.accessToken);
    return data.accessToken;
  } catch {
    clearAuth();
    return null;
  }
}

export async function ensureAuth() {
  let token = sessionStorage.getItem('accessToken');
  if (!token) {
    token = await refreshAccessToken();
  }
  if (!token) {
    window.location.href = 'login.html';
    return null;
  }
  return token;
}

export async function login(email, password) {
  const data = await apiJson('/api/auth/login.json', {
    method: 'POST',
    body: JSON.stringify({ email, password })
  });
  sessionStorage.setItem('accessToken', data.accessToken);
  localStorage.setItem('refreshToken', data.refreshToken);
  return data.user;
}

export async function logout() {
  const refreshToken = localStorage.getItem('refreshToken');
  if (refreshToken) {
    try {
      await apiFetch('/api/auth/logout.json', {
        method: 'POST',
        body: JSON.stringify({ refreshToken })
      });
    } catch {
      /* ignore network errors on logout */
    }
  }
  clearAuth();
  window.location.href = 'login.html';
}

export function clearAuth() {
  sessionStorage.removeItem('accessToken');
  localStorage.removeItem('refreshToken');
}

export function wireLogoutLinks() {
  document.addEventListener('click', (e) => {
    const link = e.target.closest('[data-logout]');
    if (!link) return;
    e.preventDefault();
    logout();
  });
}
