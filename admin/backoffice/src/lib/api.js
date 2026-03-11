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
}

function toQuery(params) {
  if (!params) return ''
  const q = new URLSearchParams(
    Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== ''))
  ).toString()
  return q ? `?${q}` : ''
}
