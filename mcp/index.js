#!/usr/bin/env node
/**
 * BlackTenderscore MCP Server
 * Expose les données WordPress/Regiondo à Claude via le protocole MCP (stdio).
 *
 * Config : .env (WORDPRESS_URL + WORDPRESS_AUTH)
 * Démarrage : node index.js
 */

import 'dotenv/config'
import { Server } from '@modelcontextprotocol/sdk/server/index.js'
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js'
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js'
import fetch from 'node-fetch'

// ─── Config ──────────────────────────────────────────────────────────────────

const WP_URL  = (process.env.WORDPRESS_URL || '').replace(/\/$/, '')
const WP_AUTH = process.env.WORDPRESS_AUTH   // base64("user:app_password")
const API_NS  = 'bt-regiondo/v1'

if (!WP_URL || !WP_AUTH) {
  process.stderr.write('[BT-MCP] WORDPRESS_URL et WORDPRESS_AUTH sont requis (.env)\n')
  process.exit(1)
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Appel REST vers WordPress.
 * @param {string} path  — chemin relatif ex: '/bookings/stats'
 * @param {object} params — query string params
 */
async function wpFetch(path, params = {}) {
  const url = new URL(`${WP_URL}/wp-json/${API_NS}${path}`)
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v)
  }

  const res = await fetch(url.toString(), {
    headers: {
      Authorization: `Basic ${WP_AUTH}`,
      Accept: 'application/json',
    },
  })

  if (!res.ok) {
    const body = await res.text()
    throw new Error(`WP API ${res.status}: ${body.slice(0, 200)}`)
  }

  return res.json()
}

/** Formate une date JS en YYYY-MM-DD */
function fmt(date) {
  return date.toISOString().slice(0, 10)
}

/** Retourne {from, to} pour les N derniers jours */
function lastDays(n) {
  const to   = new Date()
  const from = new Date()
  from.setDate(from.getDate() - n + 1)
  return { from: fmt(from), to: fmt(to) }
}

// ─── Définitions des outils ──────────────────────────────────────────────────

const TOOLS = [
  {
    name: 'bt_kpis',
    description:
      'KPIs résumés des réservations Regiondo : CA, nombre de réservations, panier moyen, ' +
      'taux d\'annulation, clients uniques, taux de repeat. ' +
      'Accepte une plage de dates (from/to) et optionnellement une période de comparaison.',
    inputSchema: {
      type: 'object',
      properties: {
        from:         { type: 'string', description: 'Date début YYYY-MM-DD (défaut: 30 derniers jours)' },
        to:           { type: 'string', description: 'Date fin YYYY-MM-DD (défaut: aujourd\'hui)' },
        compare_from: { type: 'string', description: 'Date début comparaison YYYY-MM-DD' },
        compare_to:   { type: 'string', description: 'Date fin comparaison YYYY-MM-DD' },
        granularity:  { type: 'string', enum: ['day', 'week', 'month'], description: 'Granularité (défaut: month)' },
      },
    },
  },
  {
    name: 'bt_timeline',
    description:
      'Série temporelle des réservations et du CA sur une période donnée. ' +
      'Données jour par jour, semaine par semaine ou mois par mois. ' +
      'Utile pour voir les tendances, saisonnalité, pics.',
    inputSchema: {
      type: 'object',
      properties: {
        from:        { type: 'string', description: 'Date début YYYY-MM-DD' },
        to:          { type: 'string', description: 'Date fin YYYY-MM-DD' },
        granularity: { type: 'string', enum: ['day', 'week', 'month'], description: 'Granularité (défaut: month)' },
        compare:     { type: 'boolean', description: 'Inclure la période précédente' },
      },
    },
  },
  {
    name: 'bt_top_products',
    description:
      'Classement des produits/activités Regiondo par nombre de réservations ou CA. ' +
      'Retourne le top 10 avec nom, bookings, CA, part du total.',
    inputSchema: {
      type: 'object',
      properties: {
        from:  { type: 'string', description: 'Date début YYYY-MM-DD' },
        to:    { type: 'string', description: 'Date fin YYYY-MM-DD' },
        limit: { type: 'number', description: 'Nombre de produits (défaut: 10, max: 50)' },
      },
    },
  },
  {
    name: 'bt_top_dates',
    description:
      'Meilleures journées ou périodes en termes de réservations ou CA. ' +
      'Utile pour identifier les pics, jours fériés, événements.',
    inputSchema: {
      type: 'object',
      properties: {
        from:  { type: 'string', description: 'Date début YYYY-MM-DD' },
        to:    { type: 'string', description: 'Date fin YYYY-MM-DD' },
        limit: { type: 'number', description: 'Nombre de jours (défaut: 10)' },
      },
    },
  },
  {
    name: 'bt_bookings_list',
    description:
      'Liste paginée des réservations individuelles avec détail : ' +
      'référence, produit, date d\'activité, statut, client, montant.',
    inputSchema: {
      type: 'object',
      properties: {
        from:     { type: 'string', description: 'Date début YYYY-MM-DD' },
        to:       { type: 'string', description: 'Date fin YYYY-MM-DD' },
        status:   { type: 'string', description: 'Filtre statut (confirmed, cancelled, pending...)' },
        search:   { type: 'string', description: 'Recherche par ref, nom client, email...' },
        page:     { type: 'number', description: 'Numéro de page (défaut: 1)' },
        per_page: { type: 'number', description: 'Résultats par page (défaut: 20, max: 100)' },
      },
    },
  },
  {
    name: 'bt_ga4',
    description:
      'Données Google Analytics 4 du site : sessions, utilisateurs, nouveaux utilisateurs, ' +
      'taux d\'engagement, conversions. Répartition par canal d\'acquisition. ' +
      'Top pages. Données de la période demandée.',
    inputSchema: {
      type: 'object',
      properties: {
        from:    { type: 'string', description: 'Date début YYYY-MM-DD (défaut: 30 derniers jours)' },
        to:      { type: 'string', description: 'Date fin YYYY-MM-DD (défaut: aujourd\'hui)' },
        compare: { type: 'boolean', description: 'Comparer avec la période précédente (défaut: false)' },
      },
    },
  },
  {
    name: 'bt_gsc',
    description:
      'Données Google Search Console : clics organiques, impressions, CTR moyen, position moyenne. ' +
      'Top requêtes SEO avec leur volume et position. ' +
      'Opportunités quick wins (positions 4-15 avec bon volume).',
    inputSchema: {
      type: 'object',
      properties: {
        from:    { type: 'string', description: 'Date début YYYY-MM-DD (défaut: 28 derniers jours)' },
        to:      { type: 'string', description: 'Date fin YYYY-MM-DD (défaut: avant-hier, GSC a 2j de lag)' },
        compare: { type: 'boolean', description: 'Comparer avec la période précédente' },
      },
    },
  },
]

// ─── Handlers des outils ─────────────────────────────────────────────────────

async function handleTool(name, args) {
  const d30 = lastDays(30)

  switch (name) {
    case 'bt_kpis': {
      const { from = d30.from, to = d30.to, compare_from, compare_to, granularity = 'month' } = args
      const params = { from, to, granularity }
      if (compare_from) params.compare_from = compare_from
      if (compare_to)   params.compare_to   = compare_to
      const data = await wpFetch('/bookings/stats', params)
      return formatKpis(data)
    }

    case 'bt_timeline': {
      const { from = d30.from, to = d30.to, granularity = 'month', compare = false } = args
      const params = { from, to, granularity }
      if (compare) {
        // Calcule automatiquement la période précédente de même longueur
        const ms   = new Date(to) - new Date(from)
        const days = Math.round(ms / 86400000)
        const pTo   = new Date(new Date(from) - 86400000)
        const pFrom = new Date(pTo - days * 86400000)
        params.compare_from = fmt(pFrom)
        params.compare_to   = fmt(pTo)
      }
      const data = await wpFetch('/bookings/stats', params)
      return formatTimeline(data)
    }

    case 'bt_top_products': {
      const { from = d30.from, to = d30.to, limit = 10 } = args
      const data = await wpFetch('/bookings/stats', { from, to, granularity: 'month' })
      return formatTopProducts(data, limit)
    }

    case 'bt_top_dates': {
      const { from = d30.from, to = d30.to, limit = 10 } = args
      const data = await wpFetch('/bookings/stats', { from, to, granularity: 'day' })
      return formatTopDates(data, limit)
    }

    case 'bt_bookings_list': {
      const { from = d30.from, to = d30.to, status, search, page = 1, per_page = 20 } = args
      const data = await wpFetch('/bookings', { from, to, status, search, page, per_page })
      return formatBookingsList(data)
    }

    case 'bt_ga4': {
      const { from = d30.from, to = d30.to, compare = false } = args
      const data = await wpFetch('/ga4/stats', { from, to, compare: compare ? 1 : 0 })
      return formatGa4(data)
    }

    case 'bt_gsc': {
      // GSC a 2j de décalage
      const gscTo = new Date(); gscTo.setDate(gscTo.getDate() - 2)
      const gscFrom = new Date(gscTo); gscFrom.setDate(gscFrom.getDate() - 27)
      const { from = fmt(gscFrom), to = fmt(gscTo), compare = false } = args
      const data = await wpFetch('/search-console/stats', { from, to, compare: compare ? 1 : 0 })
      return formatGsc(data)
    }

    default:
      throw new Error(`Outil inconnu: ${name}`)
  }
}

// ─── Formateurs de réponse ────────────────────────────────────────────────────

function formatKpis(data) {
  const kpis = data?.kpis || data?.totals || data
  const lines = ['## KPIs réservations\n']

  if (kpis?.revenue != null)   lines.push(`- **CA total** : ${fmtEur(kpis.revenue)}`)
  if (kpis?.bookings != null)  lines.push(`- **Réservations** : ${kpis.bookings}`)
  if (kpis?.avg_basket != null) lines.push(`- **Panier moyen** : ${fmtEur(kpis.avg_basket)}`)
  if (kpis?.cancel_rate != null) lines.push(`- **Taux d'annulation** : ${fmtPct(kpis.cancel_rate)}`)
  if (kpis?.unique_customers != null) lines.push(`- **Clients uniques** : ${kpis.unique_customers}`)
  if (kpis?.repeat_rate != null) lines.push(`- **Taux de repeat** : ${fmtPct(kpis.repeat_rate)}`)

  // Deltas comparaison
  const delta = data?.delta || data?.compare
  if (delta) {
    lines.push('\n### vs période précédente')
    if (delta.revenue != null)   lines.push(`- CA : ${fmtDelta(delta.revenue)}`)
    if (delta.bookings != null)  lines.push(`- Réservations : ${fmtDelta(delta.bookings)}`)
  }

  if (lines.length === 1) lines.push(JSON.stringify(data, null, 2))
  return lines.join('\n')
}

function formatTimeline(data) {
  const series = data?.chart || data?.timeline || data?.by_period || []
  if (!series.length) return 'Aucune donnée de série temporelle.'

  const lines = ['## Série temporelle\n', '| Période | Réservations | CA |']
  lines.push('|---------|-------------|-----|')

  for (const pt of series.slice(0, 50)) {
    const label    = pt.label || pt.period || pt.date || '?'
    const bookings = pt.bookings ?? pt.count ?? '-'
    const revenue  = pt.revenue != null ? fmtEur(pt.revenue) : '-'
    lines.push(`| ${label} | ${bookings} | ${revenue} |`)
  }

  return lines.join('\n')
}

function formatTopProducts(data, limit) {
  const products = data?.top_products || data?.products || []
  if (!products.length) return 'Aucun produit sur cette période.'

  const lines = [`## Top ${limit} produits\n`, '| # | Produit | Réservations | CA | Part |']
  lines.push('|---|---------|-------------|-----|------|')

  const totalRevenue = products.reduce((s, p) => s + (p.revenue || 0), 0)

  products.slice(0, limit).forEach((p, i) => {
    const share = totalRevenue > 0 ? fmtPct((p.revenue || 0) / totalRevenue * 100) : '-'
    lines.push(`| ${i + 1} | ${p.name || p.product_name || '?'} | ${p.bookings ?? '-'} | ${fmtEur(p.revenue)} | ${share} |`)
  })

  return lines.join('\n')
}

function formatTopDates(data, limit) {
  const series = data?.chart || data?.by_period || []
  if (!series.length) return 'Aucune donnée.'

  const sorted = [...series]
    .filter(p => (p.bookings ?? p.count ?? 0) > 0)
    .sort((a, b) => (b.bookings ?? b.count ?? 0) - (a.bookings ?? a.count ?? 0))
    .slice(0, limit)

  const lines = [`## Top ${limit} journées\n`, '| # | Date | Réservations | CA |']
  lines.push('|---|------|-------------|-----|')

  sorted.forEach((p, i) => {
    const label    = p.label || p.period || p.date || '?'
    const bookings = p.bookings ?? p.count ?? '-'
    const revenue  = p.revenue != null ? fmtEur(p.revenue) : '-'
    lines.push(`| ${i + 1} | ${label} | ${bookings} | ${revenue} |`)
  })

  return lines.join('\n')
}

function formatBookingsList(data) {
  const items = data?.bookings || data?.data || data || []
  const total = data?.total || data?.meta?.total || items.length

  if (!items.length) return 'Aucune réservation trouvée.'

  const lines = [`## Réservations (${total} au total)\n`]
  lines.push('| Réf | Produit | Date activité | Statut | Montant |')
  lines.push('|-----|---------|--------------|--------|---------|')

  for (const b of items.slice(0, 30)) {
    const ref     = b.booking_ref || b.id || '-'
    const product = (b.product_name || b.offer_title || '?').slice(0, 40)
    const date    = b.activity_date || b.date || '-'
    const status  = b.status || '-'
    const amount  = b.price_total != null ? fmtEur(b.price_total) : '-'
    lines.push(`| ${ref} | ${product} | ${date} | ${status} | ${amount} |`)
  }

  if (items.length < total) lines.push(`\n_… et ${total - items.length} autres réservations._`)
  return lines.join('\n')
}

function formatGa4(data) {
  if (!data || data.error) return `Erreur GA4: ${data?.error || 'données indisponibles'}`

  const lines = ['## Google Analytics 4\n']
  const t = data.totals || data

  if (t.sessions != null)         lines.push(`- **Sessions** : ${n(t.sessions)}`)
  if (t.users != null)            lines.push(`- **Utilisateurs** : ${n(t.users)}`)
  if (t.newUsers != null)         lines.push(`- **Nouveaux utilisateurs** : ${n(t.newUsers)}`)
  if (t.engagementRate != null)   lines.push(`- **Taux d'engagement** : ${fmtPct(t.engagementRate * 100)}`)
  if (t.conversions != null)      lines.push(`- **Conversions** : ${n(t.conversions)}`)

  // Canaux d'acquisition
  const channels = data.by_channel || data.channels || []
  if (channels.length) {
    lines.push('\n### Canaux d\'acquisition\n')
    lines.push('| Canal | Sessions | % |')
    lines.push('|-------|---------|---|')
    const totalSessions = channels.reduce((s, c) => s + (c.sessions || 0), 0)
    channels.slice(0, 8).forEach(c => {
      const pct = totalSessions > 0 ? fmtPct((c.sessions / totalSessions) * 100) : '-'
      lines.push(`| ${c.channel || c.name || '?'} | ${n(c.sessions)} | ${pct} |`)
    })
  }

  // Top pages
  const pages = data.top_pages || []
  if (pages.length) {
    lines.push('\n### Top pages\n')
    lines.push('| Page | Sessions |')
    lines.push('|------|---------|')
    pages.slice(0, 10).forEach(p => {
      lines.push(`| ${(p.page || p.path || '?').slice(0, 60)} | ${n(p.sessions)} |`)
    })
  }

  return lines.join('\n')
}

function formatGsc(data) {
  if (!data || data.error) return `Erreur GSC: ${data?.error || 'données indisponibles'}`

  const lines = ['## Google Search Console\n']
  const t = data.totals || data

  if (t.clicks != null)      lines.push(`- **Clics organiques** : ${n(t.clicks)}`)
  if (t.impressions != null) lines.push(`- **Impressions** : ${n(t.impressions)}`)
  if (t.ctr != null)         lines.push(`- **CTR moyen** : ${fmtPct(t.ctr * 100)}`)
  if (t.position != null)    lines.push(`- **Position moyenne** : ${t.position.toFixed(1)}`)

  // Top requêtes
  const queries = data.top_queries || data.queries || []
  if (queries.length) {
    lines.push('\n### Top requêtes\n')
    lines.push('| Requête | Clics | Impr. | CTR | Pos. |')
    lines.push('|---------|-------|-------|-----|------|')
    queries.slice(0, 15).forEach(q => {
      const ctr = q.ctr != null ? fmtPct(q.ctr * 100) : '-'
      const pos = q.position != null ? q.position.toFixed(1) : '-'
      lines.push(`| ${(q.query || q.keys?.[0] || '?').slice(0, 50)} | ${n(q.clicks)} | ${n(q.impressions)} | ${ctr} | ${pos} |`)
    })
  }

  // Quick wins
  const qw = data.quick_wins || []
  if (qw.length) {
    lines.push('\n### Opportunités quick wins (pos. 4-15)\n')
    lines.push('| Requête | Position | Impressions |')
    lines.push('|---------|---------|-------------|')
    qw.slice(0, 10).forEach(q => {
      const pos = q.position != null ? q.position.toFixed(1) : '-'
      lines.push(`| ${(q.query || '?').slice(0, 50)} | ${pos} | ${n(q.impressions)} |`)
    })
  }

  return lines.join('\n')
}

// ─── Formateurs numériques ────────────────────────────────────────────────────

function fmtEur(v)   { return v == null ? '-' : `${Number(v).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €` }
function fmtPct(v)   { return v == null ? '-' : `${Number(v).toFixed(1)} %` }
function fmtDelta(v) { return v == null ? '-' : `${v >= 0 ? '+' : ''}${Number(v).toFixed(1)} %` }
function n(v)        { return v == null ? '-' : Number(v).toLocaleString('fr-FR') }

// ─── Serveur MCP ─────────────────────────────────────────────────────────────

const server = new Server(
  { name: 'blacktenderscore', version: '1.0.0' },
  { capabilities: { tools: {} } },
)

server.setRequestHandler(ListToolsRequestSchema, async () => ({ tools: TOOLS }))

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args = {} } = request.params

  try {
    const text = await handleTool(name, args)
    return { content: [{ type: 'text', text }] }
  } catch (err) {
    return {
      content: [{ type: 'text', text: `Erreur: ${err.message}` }],
      isError: true,
    }
  }
})

// ─── Démarrage ────────────────────────────────────────────────────────────────

const transport = new StdioServerTransport()
await server.connect(transport)
process.stderr.write('[BT-MCP] Serveur démarré (stdio)\n')
