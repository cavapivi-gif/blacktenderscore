/**
 * API client pour la gestion des conversations IA partagées.
 * Séparé de api.js pour ne pas surcharger le fichier principal.
 */

const { rest_url, nonce } = window.btBackoffice || {}

async function apiFetch(path, opts = {}) {
  const res = await fetch(rest_url + path, {
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
    ...opts,
  })
  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || `HTTP ${res.status}`)
  }
  return res.json()
}

// ── Chats CRUD ────────────────────────────────────────────────────────────────

/** Liste tous les chats accessibles par l'utilisateur courant (owned + shared). */
export const listChats = () => apiFetch('/chats')

/** Crée ou met à jour un chat en DB (upsert par uuid). */
export const syncChat = (conv) =>
  apiFetch('/chats', {
    method: 'POST',
    body: JSON.stringify({
      uuid:          conv.id,
      title:         conv.title,
      provider:      conv.provider,
      filter_params: conv.filterParams,
      messages:      conv.messages,
    }),
  })

/** Retourne un chat avec messages + shares (si owner). */
export const getChat = (uuid) => apiFetch(`/chats/${uuid}`)

/** Met à jour titre, provider ou owner_color. */
export const updateChat = (uuid, data) =>
  apiFetch(`/chats/${uuid}`, { method: 'PATCH', body: JSON.stringify(data) })

/** Supprime un chat (owner only). */
export const deleteChat = (uuid) =>
  apiFetch(`/chats/${uuid}`, { method: 'DELETE' })

// ── Partages ──────────────────────────────────────────────────────────────────

/** Retourne la liste des partages d'un chat (owner only). */
export const listShares = (uuid) => apiFetch(`/chats/${uuid}/shares`)

/** Invite un utilisateur sur un chat. */
export const addShare = (uuid, userId, permission) =>
  apiFetch(`/chats/${uuid}/shares`, {
    method: 'POST',
    body: JSON.stringify({ user_id: userId, permission }),
  })

/** Met à jour permission ou couleur d'un partage (owner only). */
export const updateShare = (uuid, userId, data) =>
  apiFetch(`/chats/${uuid}/shares/${userId}`, { method: 'PATCH', body: JSON.stringify(data) })

/** Révoque l'accès d'un utilisateur (owner only). */
export const removeShare = (uuid, userId) =>
  apiFetch(`/chats/${uuid}/shares/${userId}`, { method: 'DELETE' })

// ── Utilisateurs ──────────────────────────────────────────────────────────────

/** Recherche des utilisateurs WP par nom ou email (min 2 caractères). */
export const searchUsers = (q, exclude = []) =>
  apiFetch(`/users/search?q=${encodeURIComponent(q)}&exclude=${exclude.join(',')}`)

// ── Permissions par rôle ──────────────────────────────────────────────────────

/** Retourne les permissions par rôle WP. */
export const getRolePermissions = () => apiFetch('/settings/role-permissions')

/** Sauvegarde les permissions par rôle. */
export const saveRolePermissions = (permissions) =>
  apiFetch('/settings/role-permissions', { method: 'POST', body: JSON.stringify(permissions) })
