import { useState, useEffect, useCallback, useMemo } from 'react'
import { Search, Download, Trash, RefreshDouble } from 'iconoir-react'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import {
  AreaChart, Area, BarChart, Bar, XAxis, YAxis,
  Tooltip, ResponsiveContainer, Cell,
} from 'recharts'
import { api } from '../lib/api'
import { COLORS } from '../lib/constants'
import { PageHeader, Table, Pagination, Notice, Spinner, Btn } from '../components/ui'
import ReviewsImporter from '../components/ReviewsImporter'

// ── Helpers ───────────────────────────────────────────────────────────────────

function fmtDate(d) {
  if (!d || d === '0000-00-00') return '—'
  try { return format(new Date(d + 'T12:00:00'), 'd MMM yyyy', { locale: fr }) }
  catch { return d }
}

/** Étoiles en SVG — n étoiles remplies sur 5. */
function Stars({ value, size = 12 }) {
  if (!value) return <span className="text-muted-foreground text-xs">—</span>
  const stars = []
  for (let i = 1; i <= 5; i++) {
    stars.push(
      <svg key={i} width={size} height={size} viewBox="0 0 24 24" fill={i <= value ? '#f59e0b' : 'none'}
        stroke={i <= value ? '#f59e0b' : '#d1d5db'} strokeWidth="1.5"
        strokeLinecap="round" strokeLinejoin="round">
        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
      </svg>
    )
  }
  return <span className="flex items-center gap-0.5">{stars}</span>
}

/** Grande note avec texte */
function BigRating({ value }) {
  if (!value) return <span className="text-3xl font-bold text-muted-foreground">—</span>
  return (
    <div className="flex items-end gap-2">
      <span className="text-3xl font-bold tabular-nums">{value.toFixed(1)}</span>
      <span className="text-sm text-muted-foreground mb-1">/ 5</span>
    </div>
  )
}

/** Barre de distribution des étoiles */
function DistributionBars({ distribution, total }) {
  if (!total) return null
  const max = Math.max(...Object.values(distribution), 1)
  return (
    <div className="space-y-1 w-full">
      {[5, 4, 3, 2, 1].map(star => {
        const count = distribution[star] ?? 0
        const pct = total ? Math.round((count / total) * 100) : 0
        return (
          <div key={star} className="flex items-center gap-2 text-xs">
            <span className="w-3 text-right text-muted-foreground shrink-0">{star}</span>
            <svg width="10" height="10" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" strokeWidth="1.5">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <div className="flex-1 h-1.5 rounded-full bg-muted overflow-hidden">
              <div
                className="h-full rounded-full bg-amber-400 transition-all"
                style={{ width: `${(count / max) * 100}%` }}
              />
            </div>
            <span className="w-8 text-right text-muted-foreground tabular-nums">{count}</span>
            <span className="w-8 text-right text-muted-foreground/60 tabular-nums">{pct}%</span>
          </div>
        )
      })}
    </div>
  )
}

/** Mini tooltip recharts */
function ChartTooltipContent({ active, payload, label }) {
  if (!active || !payload?.length) return null
  return (
    <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs space-y-0.5">
      <p className="font-medium text-muted-foreground">{label}</p>
      {payload.map(p => (
        <p key={p.dataKey} style={{ color: p.color }}>
          {p.name} : <strong>{typeof p.value === 'number' ? p.value.toFixed(2) : p.value}</strong>
        </p>
      ))}
    </div>
  )
}

// ── Section Stats ─────────────────────────────────────────────────────────────
function StatsSection({ stats, loading }) {
  if (loading) {
    return (
      <div className="mx-6 mt-5 rounded-xl border bg-card p-6 flex items-center justify-center h-36">
        <Spinner size={20} />
      </div>
    )
  }

  if (!stats) return null

  const {
    total, total_rated, avg_rating, min_date, max_date,
    distribution, monthly, reviews_needed_4_8,
  } = stats

  // Moyenne mensuelle du nombre d'avis sur les 3 derniers mois
  const last3 = (monthly ?? []).slice(-3)
  const avgMonthlyCount = last3.length
    ? Math.round(last3.reduce((s, m) => s + parseInt(m.count), 0) / last3.length)
    : 0

  // Mois estimés pour atteindre 4.8 (à ce rythme)
  let monthsTo48 = null
  if (reviews_needed_4_8 > 0 && avgMonthlyCount > 0) {
    monthsTo48 = Math.ceil(reviews_needed_4_8 / avgMonthlyCount)
  }

  const chartData = (monthly ?? []).map(m => ({
    month: m.month?.slice(0, 7) ?? '',
    avg: parseFloat(m.avg_rating) || 0,
    count: parseInt(m.count) || 0,
  }))

  return (
    <div className="mx-6 mt-5 rounded-xl border bg-card overflow-hidden">
      {/* Row 1 : KPIs principales */}
      <div className="grid grid-cols-2 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x">
        {/* Total */}
        <div className="px-6 py-5">
          <p className="text-xs text-muted-foreground uppercase tracking-wider mb-1">Avis importés</p>
          <p className="text-3xl font-bold tabular-nums">{total?.toLocaleString('fr-FR') ?? '—'}</p>
          {total !== total_rated && (
            <p className="text-xs text-muted-foreground mt-0.5">{total_rated} notés</p>
          )}
        </div>

        {/* Moyenne */}
        <div className="px-6 py-5">
          <p className="text-xs text-muted-foreground uppercase tracking-wider mb-1">Note moyenne</p>
          <BigRating value={avg_rating} />
          {avg_rating && (
            <div className="mt-1.5">
              <Stars value={Math.round(avg_rating)} size={14} />
            </div>
          )}
        </div>

        {/* Période */}
        <div className="px-6 py-5">
          <p className="text-xs text-muted-foreground uppercase tracking-wider mb-1">Période</p>
          {min_date ? (
            <div className="space-y-0.5">
              <p className="text-sm font-medium">{fmtDate(min_date)}</p>
              <p className="text-xs text-muted-foreground">→ {fmtDate(max_date)}</p>
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">Aucune donnée</p>
          )}
        </div>

        {/* Moy mensuelle */}
        <div className="px-6 py-5">
          <p className="text-xs text-muted-foreground uppercase tracking-wider mb-1">Rythme (3 mois)</p>
          <p className="text-3xl font-bold tabular-nums">{avgMonthlyCount}</p>
          <p className="text-xs text-muted-foreground mt-0.5">avis / mois</p>
        </div>
      </div>

      {/* Row 2 : Distribution + Tendance + Projection */}
      <div className="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x border-t">

        {/* Distribution */}
        <div className="px-6 py-5">
          <p className="text-xs text-muted-foreground uppercase tracking-wider mb-3">Distribution</p>
          {total_rated > 0
            ? <DistributionBars distribution={distribution ?? {}} total={total_rated} />
            : <p className="text-xs text-muted-foreground">Aucune note disponible</p>
          }
        </div>

        {/* Tendance mensuelle */}
        <div className="px-6 py-5">
          <p className="text-xs text-muted-foreground uppercase tracking-wider mb-3">Tendance mensuelle</p>
          {chartData.length > 1 ? (
            <ResponsiveContainer width="100%" height={100}>
              <AreaChart data={chartData} margin={{ top: 4, right: 4, bottom: 0, left: -20 }}>
                <defs>
                  <linearGradient id="ratingGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor={COLORS.current} stopOpacity={0.25}/>
                    <stop offset="95%" stopColor={COLORS.current} stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <XAxis dataKey="month" tick={{ fontSize: 9 }} tickLine={false} axisLine={false} />
                <YAxis domain={[1, 5]} tick={{ fontSize: 9 }} tickLine={false} axisLine={false} ticks={[1,2,3,4,5]} />
                <Tooltip content={<ChartTooltipContent />} />
                <Area type="monotone" dataKey="avg" name="Moy." stroke={COLORS.current}
                  strokeWidth={1.5} fill="url(#ratingGrad)" dot={false} />
              </AreaChart>
            </ResponsiveContainer>
          ) : (
            <p className="text-xs text-muted-foreground">Pas assez de données</p>
          )}
        </div>

        {/* Projection 4.8★ */}
        <div className="px-6 py-5">
          <p className="text-xs text-muted-foreground uppercase tracking-wider mb-3">Objectif 4.8 ★</p>
          {avg_rating !== null && avg_rating >= 4.8 ? (
            <div className="flex items-center gap-2">
              <span className="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M20 6 9 17l-5-5"/>
                </svg>
              </span>
              <div>
                <p className="text-sm font-semibold text-emerald-600">Objectif atteint !</p>
                <p className="text-xs text-muted-foreground">Moyenne : {avg_rating?.toFixed(2)}/5</p>
              </div>
            </div>
          ) : reviews_needed_4_8 !== null && reviews_needed_4_8 > 0 ? (
            <div className="space-y-2">
              <div>
                <p className="text-2xl font-bold tabular-nums">{reviews_needed_4_8.toLocaleString('fr-FR')}</p>
                <p className="text-xs text-muted-foreground">avis 5★ supplémentaires</p>
              </div>
              {monthsTo48 !== null && (
                <p className="text-xs text-muted-foreground">
                  Au rythme actuel : <span className="font-medium text-foreground">~{monthsTo48} mois</span>
                </p>
              )}
              {/* Barre de progression vers 4.8 */}
              {avg_rating !== null && (
                <div>
                  <div className="flex justify-between text-[10px] text-muted-foreground mb-1">
                    <span>{avg_rating?.toFixed(2)}</span>
                    <span>4.8</span>
                  </div>
                  <div className="h-1.5 rounded-full bg-muted overflow-hidden">
                    <div
                      className="h-full rounded-full bg-amber-400 transition-all"
                      style={{ width: `${Math.min(100, ((avg_rating - 1) / (4.8 - 1)) * 100)}%` }}
                    />
                  </div>
                </div>
              )}
            </div>
          ) : (
            <p className="text-xs text-muted-foreground">Importez des avis pour voir la projection</p>
          )}
        </div>
      </div>
    </div>
  )
}

// ── Page principale ───────────────────────────────────────────────────────────
export default function Reviews() {
  const [data, setData]           = useState([])
  const [total, setTotal]         = useState(0)
  const [stats, setStats]         = useState(null)
  const [loading, setLoading]     = useState(true)
  const [statsLoading, setStatsLoading] = useState(true)
  const [error, setError]         = useState(null)
  const [page, setPage]           = useState(1)
  const [q, setQ]                 = useState('')
  const [search, setSearch]       = useState('')
  const [product, setProduct]     = useState('')
  const [ratingFilter, setRatingFilter] = useState('')
  const [sort, setSort]           = useState({ key: 'review_date', dir: 'desc' })
  const [expanded, setExpanded]   = useState(null)
  const [resetting, setResetting] = useState(false)
  const [showImporter, setShowImporter] = useState(false)
  const perPage = 50

  const loadStats = useCallback(() => {
    setStatsLoading(true)
    api.avisStats()
      .then(setStats)
      .catch(() => {})
      .finally(() => setStatsLoading(false))
  }, [])

  const load = useCallback(() => {
    setLoading(true)
    api.avis({
      page,
      per_page: perPage,
      search: search || undefined,
      product: product || undefined,
      rating: ratingFilter || undefined,
      sort: sort.key,
      dir: sort.dir.toUpperCase(),
    })
      .then(r => { setData(r.data ?? []); setTotal(r.total ?? 0) })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [page, search, product, ratingFilter, sort])

  useEffect(() => { load(); loadStats() }, [load, loadStats])
  useEffect(() => {
    const t = setTimeout(() => { setSearch(q); setPage(1) }, 400)
    return () => clearTimeout(t)
  }, [q])

  const onSort = key => setSort(s => ({ key, dir: s.key === key && s.dir === 'asc' ? 'desc' : 'asc' }))

  const handleReset = async () => {
    if (!confirm('Supprimer tous les avis importés ? Cette action est irréversible.')) return
    setResetting(true)
    try {
      await api.resetAvis()
      setData([]); setTotal(0); setStats(null)
      loadStats()
    } catch (e) {
      setError(e.message)
    } finally {
      setResetting(false)
    }
  }

  function exportCsv() {
    const hdr = ['N° commande', 'Produit', 'Client', 'Email', 'Note', 'Résumé', 'Avis', 'Date']
    const csv = [hdr, ...data.map(r => [
      r.order_number, r.product_name, r.customer_name, r.customer_email,
      r.rating, r.review_title, (r.review_body ?? '').replace(/\n/g, ' '),
      r.review_date,
    ])].map(row => row.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(';')).join('\n')
    Object.assign(document.createElement('a'), {
      href: URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' })),
      download: 'avis.csv',
    }).click()
  }

  const products = useMemo(() => stats?.products ?? [], [stats])

  const columns = [
    {
      key: 'customer_name', label: 'Client', sortable: true,
      render: r => (
        <div>
          <div className="font-medium text-sm leading-tight">{r.customer_name || '—'}</div>
          <div className="text-xs text-muted-foreground">{r.customer_email}</div>
        </div>
      ),
    },
    {
      key: 'product_name', label: 'Produit', sortable: true,
      render: r => (
        <span className="text-xs text-muted-foreground max-w-[160px] truncate block" title={r.product_name}>
          {r.product_name || '—'}
        </span>
      ),
    },
    {
      key: 'rating', label: 'Note', sortable: true,
      render: r => <Stars value={r.rating} />,
    },
    {
      key: 'review_title', label: 'Avis',
      render: r => (
        <div className="max-w-[240px]">
          {r.review_title && <p className="text-sm font-medium truncate">{r.review_title}</p>}
          {r.review_body && (
            <p
              className={`text-xs text-muted-foreground ${expanded === r.id ? '' : 'line-clamp-2'} cursor-pointer`}
              onClick={e => { e.stopPropagation(); setExpanded(expanded === r.id ? null : r.id) }}
            >
              {r.review_body}
            </p>
          )}
          {r.response && (
            <p className="text-xs text-primary/70 mt-0.5 line-clamp-1">↩ {r.response}</p>
          )}
        </div>
      ),
    },
    {
      key: 'review_date', label: 'Date', sortable: true,
      render: r => <span className="text-xs text-muted-foreground">{fmtDate(r.review_date)}</span>,
    },
    {
      key: 'order_number', label: 'N° commande',
      render: r => <span className="text-xs text-muted-foreground font-mono">{r.order_number}</span>,
    },
  ]

  return (
    <div>
      <PageHeader
        title="Avis clients"
        subtitle="Import CSV Regiondo · avis vérifiés"
        actions={
          <div className="flex items-center gap-2 flex-wrap">
            {/* Bouton import toggle */}
            <Btn variant="secondary" size="sm" onClick={() => setShowImporter(v => !v)}>
              {showImporter ? 'Masquer import' : 'Importer CSV'}
            </Btn>
            <Btn variant="secondary" size="sm" onClick={exportCsv} disabled={!data.length}>
              <Download className="w-4 h-4" /> Export
            </Btn>
            <Btn variant="ghost" size="sm" onClick={() => { load(); loadStats() }}>
              <RefreshDouble className="w-4 h-4" />
            </Btn>
            <Btn variant="danger" size="sm" onClick={handleReset} loading={resetting} disabled={!total}>
              <Trash className="w-4 h-4" /> Réinitialiser
            </Btn>
            <span className="text-xs text-muted-foreground">{total.toLocaleString('fr-FR')} avis</span>
          </div>
        }
      />

      {/* Zone import */}
      {showImporter && (
        <div className="mx-6 mt-5 rounded-xl border bg-card p-5">
          <p className="text-sm font-medium mb-3">Import CSV Regiondo — Avis clients</p>
          <p className="text-xs text-muted-foreground mb-4">
            Exportez vos avis depuis Regiondo et importez-les ici. Colonnes requises : N° de commande, Évaluation (note), évaluation (texte).
          </p>
          <ReviewsImporter onDone={() => { setShowImporter(false); load(); loadStats() }} />
        </div>
      )}

      {/* Stats */}
      <StatsSection stats={stats} loading={statsLoading && !stats} />

      {error && <div className="mx-6 mt-5"><Notice type="error">{error}</Notice></div>}

      {/* Filtres */}
      <div className="mx-6 mt-5 flex items-center gap-2 flex-wrap">
        <div className="relative">
          <Search className="absolute left-2.5 top-2.5 w-4 h-4 text-muted-foreground pointer-events-none" />
          <input
            className="h-9 pl-8 pr-3 text-sm rounded-md border border-input bg-transparent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring w-44"
            placeholder="Rechercher…"
            value={q}
            onChange={e => setQ(e.target.value)}
          />
        </div>

        {products.length > 0 && (
          <select
            className="h-9 px-3 text-sm rounded-md border border-input bg-background cursor-pointer focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring max-w-[220px]"
            value={product}
            onChange={e => { setProduct(e.target.value); setPage(1) }}
          >
            <option value="">Tous les produits</option>
            {products.map(p => <option key={p} value={p}>{p}</option>)}
          </select>
        )}

        <select
          className="h-9 px-3 text-sm rounded-md border border-input bg-background cursor-pointer focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
          value={ratingFilter}
          onChange={e => { setRatingFilter(e.target.value); setPage(1) }}
        >
          <option value="">Toutes les notes</option>
          {[5, 4, 3, 2, 1].map(n => (
            <option key={n} value={n}>{n} étoile{n > 1 ? 's' : ''}</option>
          ))}
        </select>
      </div>

      {/* Tableau */}
      <div className="mx-6 mt-4 rounded-lg border overflow-hidden">
        {loading
          ? <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
          : <>
              <Table
                columns={columns} data={data}
                empty="Aucun avis. Importez un CSV Regiondo pour commencer."
                sortKey={sort.key} sortDir={sort.dir} onSort={onSort}
              />
              <Pagination page={page} total={total} perPage={perPage} onChange={setPage} />
            </>
        }
      </div>
    </div>
  )
}
