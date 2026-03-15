const { rest_url, nonce } = window.btBackoffice || {}

async function apiFetch(path, options = {}) {
  const url = `${rest_url}${path}`
  const res = await fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
      ...options.headers,
    },
    ...options,
  })

  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || `Erreur ${res.status}`)
  }

  return res.json()
}

export const api = {
  get:  (path)        => apiFetch(path),
  post: (path, body)  => apiFetch(path, { method: 'POST', body: JSON.stringify(body) }),
  put:  (path, body)  => apiFetch(path, { method: 'PUT',  body: JSON.stringify(body) }),

  dashboard:   ()       => api.get('/dashboard'),
  products:             (params) => api.get('/products' + toQuery(params)),
  product:              (id)     => api.get(`/products/${id}`),
  variations:           (id)     => api.get(`/products/${id}/variations`),
  crossselling:         (id)     => api.get(`/products/${id}/crossselling`),
  navigationAttributes: (params) => api.get('/products/navigationattributes' + toQuery(params)),
  categories:           ()       => api.get('/categories'),
  bookings:    (params) => api.get('/bookings' + toQuery(params)),
  customers:   (params) => api.get('/customers' + toQuery(params)),
  settings:    ()       => api.get('/settings'),
  saveSettings:(data)   => api.post('/settings', data),
  flushCache:  ()       => api.post('/cache/flush', {}),
  sync:        (id)     => api.post('/sync', id ? { product_id: id } : {}),
  newsletter:  (email, subscribed) => api.put('/customers/newsletter', { email, subscribed }),
  testConnection: ()    => api.get('/test-connection'),
  diagnostic:    ()    => api.get('/diagnostic'),
  bookingsStats: (params) => api.get('/bookings/stats' + toQuery(params)),
  planner:       (from, to) => api.get('/planner' + toQuery({ from, to })),

  // Sync réservations (bookings) → DB locale
  syncBookings:       (body) => api.post('/bookings/sync', body),
  syncBookingsStatus: ()     => api.get('/bookings/sync/status'),
  resetBookingsDb:    ()     => api.post('/bookings/sync/reset', {}),

  // Import solditems (réservations enrichies) → DB locale
  reservations:             (params) => api.get('/reservations' + toQuery(params)),
  importReservations:       (body)   => api.post('/reservations/import', body),
  importReservationsStatus: ()       => api.get('/reservations/import/status'),
  resetReservationsDb:      ()       => api.post('/reservations/import/reset', {}),
  // Import CSV : envoie un batch de lignes parsées côté JS
  importReservationsCsv:    (items)  => api.post('/reservations/import/csv', { items }),
  // Re-parse offer_raw → price_total pour les lignes existantes avec price NULL
  reparsePrices:            ()       => api.post('/reservations/reparse-prices', {}),

  // Import participations (stats externes) → DB locale
  importParticipationsCsv:    (items)  => api.post('/participations/import/csv', { items }),
  participationsImportStatus: ()       => api.get('/participations/import/status'),
  resetParticipationsDb:      ()       => api.post('/participations/import/reset', {}),

  // Avis clients (import CSV Regiondo)
  avis:            (params) => api.get('/avis' + toQuery(params)),
  avisStats:       (params) => api.get('/avis/stats' + toQuery(params)),
  avisByEmail:     (email)  => api.get('/avis/by-email' + toQuery({ email })),
  importAvisCsv:   (items)  => api.post('/avis/import/csv', { items }),
  resetAvis:       ()       => api.post('/avis/reset', {}),

  // Snazzy Maps proxy
  snazzymapsStyles: (params) => api.get('/snazzymaps-styles' + toQuery(params)),

  // Google Analytics 4 + Search Console
  ga4Stats:             (params) => api.get('/ga4/stats' + toQuery(params)),
  searchConsoleStats:   (params) => api.get('/search-console/stats' + toQuery(params)),
  googleTest:           ()       => api.get('/google/test'),
  flushGa4Cache:        ()       => api.post('/ga4/cache/flush', {}),
  flushGscCache:        ()       => api.post('/gsc/cache/flush', {}),

  // Onboarding wizard
  onboardingStatus:   ()     => api.get('/onboarding/status'),
  onboardingSetup:    (body) => api.post('/onboarding/setup', body),
  onboardingComplete: ()     => api.post('/onboarding/complete', {}),
  onboardingReset:    ()     => api.post('/onboarding/reset', {}),

  // AI context + events
  aiContext:       (params) => api.get('/ai/context' + toQuery(params)),
  aiEvents:        (params) => api.get('/ai/events' + toQuery(params)),
  generateEvents:  (body)   => api.post('/ai/events/generate', body),
  importEvents:    (events) => api.post('/ai/events/import', { events }),
  resetEvents:     ()       => api.post('/ai/events/reset', {}),

  // AI status — providers actifs (sans exposer les clés)
  aiStatus: () => api.get('/ai/status'),

  // Traducteur + Correcteur IA
  translate: (body) => api.post('/ai/translate', body),
  correct:   (body) => api.post('/ai/correct', body),

  // Partage de conversations (stocké en WP options, lien admin)
  shareChat:       (conv)    => api.post('/chats/share', conv),
  getSharedChat:   (token)   => api.get(`/chats/shared/${token}`),
  deleteSharedChat:(token)   => apiFetch(`/chats/shared/${token}`, { method: 'DELETE' }),
}

// streamChat() supprimé — remplacé par useAiChat hook (@ai-sdk/react)

function toQuery(params) {
  if (!params) return ''
  const q = new URLSearchParams(
    Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== ''))
  ).toString()
  return q ? `?${q}` : ''
}
