import { useState, useEffect, useCallback, useMemo } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  AreaChart, Area, BarChart, Bar,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, ReferenceLine, Cell,
} from 'recharts'
import { Expand, Xmark } from 'iconoir-react'
import { cn } from '../lib/utils'
import { api } from '../lib/api'
import { STATUS_MAP } from '../lib/status'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/Tabs'
import { TooltipProvider, Tooltip as UiTooltip } from '../components/Tooltip'
import { PageHeader, StatCard, Table, Notice, Spinner, Badge } from '../components/ui'

// ── Constants ────────────────────────────────────────────────────────────────

const CHART_CURRENT = '#10b981'
const CHART_COMPARE = '#6366f1'
const CHART_PEAK    = '#ef4444'
const CHART_GRID    = '#e5e5e5'
const CHART_AXIS    = '#737373'
const CHART_BASKET  = '#f59e0b'
const CHANNEL_COLORS = ['#10b981','#6366f1','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16','#f97316','#64748b']

// ── Date helpers ─────────────────────────────────────────────────────────────

function today() { return new Date().toISOString().slice(0, 10) }
function daysAgo(n) {
  const d = new Date(); d.setDate(d.getDate() - n); return d.toISOString().slice(0, 10)
}
function monthsAgo(n) {
  const d = new Date(); d.setMonth(d.getMonth() - n); d.setDate(1); return d.toISOString().slice(0, 10)
}
function addDays(dateStr, n) {
  const d = new Date(dateStr + 'T12:00:00'); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10)
}
function prevPeriod(from, to) {
  const days = Math.round((new Date(to + 'T12:00:00') - new Date(from + 'T12:00:00')) / 86400000) + 1
  return { cmpFrom: addDays(from, -days), cmpTo: addDays(from, -1) }
}
function fmtShort(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr + 'T12:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}
function fmtCurrency(v, decimals = 0) {
  if (v == null) return '—'
  return Number(v).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: decimals })
}
function fmtNum(v) {
  if (v == null) return '—'
  return Number(v).toLocaleString('fr-FR')
}
function delta(curr, prev) {
  if (prev == null || prev === 0) return null
  return Math.round(((curr - prev) / Math.abs(prev)) * 100)
}

// ── Quick presets ─────────────────────────────────────────────────────────────
const PRESETS = [
  { label: '7j',    from: () => daysAgo(6),    to: today,        granularity: 'day'   },
  { label: '30j',   from: () => daysAgo(29),   to: today,        granularity: 'day'   },
  { label: '90j',   from: () => daysAgo(89),   to: today,        granularity: 'week'  },
  { label: '1an',   from: () => monthsAgo(11), to: today,        granularity: 'month' },
  { label: 'Tout',  from: () => '2017-01-01',  to: today,        granularity: 'month' },
]

function loadPrefs() {
  try {
    const s = JSON.parse(localStorage.getItem('bt-dashboard-prefs') || 'null')
    if (s?.from && s?.to && s?.granularity) return s
  } catch {}
  return null
}

// ── Sub-components ────────────────────────────────────────────────────────────

function Sparkline({ data = [], color = CHART_CURRENT, w = 60, h = 24 }) {
  if (!data.length || data.every(v => v === 0)) return null
  const max = Math.max(...data, 1)
  const min = Math.min(...data, 0)
  const range = max - min || 1
  const pts = data.map((v, i) => {
    const x = (i / Math.max(data.length - 1, 1)) * w
    const y = h - ((v - min) / range) * (h - 3) - 1.5
    return `${x.toFixed(1)},${y.toFixed(1)}`
  })
  return (
    <svg width={w} height={h} viewBox={`0 0 ${w} ${h}`} className="shrink-0">
      <path d={`M${pts.join(' L')}`} fill="none" stroke={color} strokeWidth="1.5"
        strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

function EmptyChart({ onReset }) {
  return (
    <div className="mx-6 mt-4 rounded-lg border bg-card p-10 text-center">
      <div className="text-5xl mb-4">📊</div>
      <p className="text-sm font-medium">Aucune réservation sur cette période</p>
      <p className="text-xs text-muted-foreground mt-1">Essayez d'élargir la plage de dates.</p>
      <button onClick={onReset} className="mt-4 text-xs text-primary underline hover:text-primary/80">
        Réinitialiser sur l'historique complet
      </button>
    </div>
  )
}

function FullscreenOverlay({ title, onClose, children }) {
  useEffect(() => {
    function onKey(e) { if (e.key === 'Escape') onClose() }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [onClose])
  return (
    <div className="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center p-6" onClick={onClose}>
      <div className="bg-card rounded-xl border w-full max-w-5xl p-6 shadow-2xl" onClick={e => e.stopPropagation()}>
        <div className="flex items-center justify-between mb-4">
          <span className="text-sm font-medium">{title}</span>
          <button onClick={onClose} className="h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
            <Xmark width={14} height={14} />
          </button>
        </div>
        {children}
      </div>
    </div>
  )
}

/**
 * Tooltip enrichi pour charts : actuel + comparaison + delta % + panier moyen.
 */
function ChartTooltip({ active, payload, label, suffix = '', compareFrom = '', compareTo = '' }) {
  if (!active || !payload?.length) return null
  const fmt = v => suffix ? `${Number(v).toLocaleString('fr-FR')}${suffix}` : Number(v).toLocaleString('fr-FR')

  const currP    = payload.find(p => !p.dataKey?.includes('_prev') && !p.dataKey?.includes('basket'))
  const prevP    = payload.find(p => p.dataKey?.includes('_prev'))
  const basketP  = payload.find(p => p.dataKey?.includes('basket'))
  const curr     = currP?.value ?? 0
  const prev     = prevP?.value
  const basket   = basketP?.value
  const d        = prev != null && prev > 0 ? Math.round(((curr - prev) / prev) * 100) : null

  const prevRange = compareFrom && compareTo
    ? `${fmtShort(compareFrom)} – ${fmtShort(compareTo)}`
    : null

  return (
    <div className="rounded-md border bg-card shadow-md px-3 py-2.5 text-xs min-w-[180px]">
      <p className="font-medium mb-2 text-foreground">{label}</p>
      <div className="space-y-1">
        <div className="flex items-center justify-between gap-4">
          <span className="flex items-center gap-1.5 text-muted-foreground">
            <span className="w-3 h-0.5 rounded-full" style={{ background: currP?.color ?? CHART_CURRENT }} />
            Actuel
          </span>
          <strong className="tabular-nums">{fmt(curr)}</strong>
        </div>
        {prev != null && (
          <div className="flex items-center justify-between gap-4 text-muted-foreground">
            <span className="flex items-center gap-1.5">
              <span className="w-3 inline-block" style={{ borderTop: `1.5px dashed ${CHART_COMPARE}` }} />
              Préc.
            </span>
            <span className="tabular-nums">{fmt(prev)}</span>
          </div>
        )}
        {prevRange && <p className="text-[9px] text-muted-foreground/60 pl-[18px]">{prevRange}</p>}
        {basket != null && (
          <div className="flex items-center justify-between gap-4 mt-1 pt-1 border-t">
            <span className="flex items-center gap-1.5 text-amber-600">
              <span className="w-3 h-0.5 rounded-full" style={{ background: CHART_BASKET }} />
              Panier moy.
            </span>
            <span className="tabular-nums font-medium text-amber-600">
              {Number(basket).toLocaleString('fr-FR')} €
            </span>
          </div>
        )}
      </div>
      {d !== null && (
        <div className={cn(
          'mt-2 pt-1.5 border-t text-center text-[10px] font-bold',
          d >= 0 ? 'text-emerald-600' : 'text-red-500',
        )}>
          {d >= 0 ? '↑' : '↓'} {Math.abs(d)}% vs période préc.
        </div>
      )}
    </div>
  )
}

const pill = (active) =>
  `px-2.5 py-1 rounded text-xs font-medium border transition-colors ${
    active
      ? 'bg-primary text-primary-foreground border-primary'
      : 'bg-card border-border text-muted-foreground hover:text-foreground'
  }`

function ChartLegend({ from, to, compareFrom, compareTo, hasCompare, total, prevTotal, suffix = '' }) {
  const fmt = v => suffix ? `${Number(v).toLocaleString('fr-FR')}${suffix}` : Number(v).toLocaleString('fr-FR')
  const d = hasCompare && prevTotal > 0
    ? Math.round(((total - prevTotal) / prevTotal) * 100)
    : null
  return (
    <div className="flex flex-wrap items-center gap-x-6 gap-y-2 mt-4 pt-4 border-t text-xs">
      <span className="flex items-center gap-2">
        <span className="w-6 h-0.5 rounded-full inline-block shrink-0" style={{ background: CHART_CURRENT }} />
        <span className="font-medium tabular-nums">{fmt(total)}</span>
        <span className="text-muted-foreground">{fmtShort(from)} – {fmtShort(to)}</span>
        {d !== null && (
          <span className={cn('text-[10px] font-bold px-1.5 py-0.5 rounded shrink-0',
            d >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600')}>
            {d >= 0 ? '+' : ''}{d}%
          </span>
        )}
      </span>
      {hasCompare && compareFrom && (
        <span className="flex items-center gap-2">
          <span className="w-6 shrink-0" style={{ display: 'inline-block', borderTop: `2px dashed ${CHART_COMPARE}` }} />
          <span className="font-medium text-muted-foreground tabular-nums">{fmt(prevTotal ?? 0)}</span>
          <span className="text-muted-foreground opacity-70">{fmtShort(compareFrom)} – {fmtShort(compareTo)}</span>
        </span>
      )}
    </div>
  )
}

/**
 * Toolbar filtres : période, granularité, comparaison.
 */
function StatsToolbar({ from, to, granularity, showCompare, onApply }) {
  const [localFrom,    setLocalFrom]    = useState(from)
  const [localTo,      setLocalTo]      = useState(to)
  const [localGran,    setLocalGran]    = useState(granularity)
  const [localCompare, setLocalCompare] = useState(showCompare)
  const [activePreset, setActivePreset] = useState('1an')

  function getCmp(f, t, compare) {
    if (!compare) return { compareFrom: '', compareTo: '' }
    const { cmpFrom, cmpTo } = prevPeriod(f, t)
    return { compareFrom: cmpFrom, compareTo: cmpTo }
  }

  function emit(overrides = {}) {
    const f = overrides.from        ?? localFrom
    const t = overrides.to          ?? localTo
    const g = overrides.granularity ?? localGran
    const c = overrides.compare     ?? localCompare
    onApply({ from: f, to: t, granularity: g, ...getCmp(f, t, c) })
  }

  function applyPreset(p) {
    const f = p.from(), t = p.to()
    setLocalFrom(f); setLocalTo(t)
    setLocalGran(p.granularity)
    setActivePreset(p.label)
    emit({ from: f, to: t, granularity: p.granularity })
  }

  function toggleCompare(v) {
    setLocalCompare(v)
    emit({ compare: v })
  }

  function onDateBlur() {
    if (localFrom && localTo) { setActivePreset(null); emit({}) }
  }

  return (
    <div className="mx-6 mt-5 rounded-lg border bg-card p-4 space-y-3">
      <div className="flex flex-wrap items-center gap-2">
        <span className="text-[11px] text-muted-foreground uppercase tracking-wider shrink-0 w-16">Période</span>
        <div className="flex items-center gap-1 flex-wrap">
          {PRESETS.map(p => (
            <button key={p.label} onClick={() => applyPreset(p)} className={pill(activePreset === p.label)}>
              {p.label}
            </button>
          ))}
        </div>
        <div className="h-4 w-px bg-border mx-1" />
        <input type="date" value={localFrom} max={localTo}
          onChange={e => { setLocalFrom(e.target.value); setActivePreset(null) }}
          onBlur={onDateBlur}
          className="text-xs border border-input rounded px-2 py-1 bg-transparent" />
        <span className="text-[11px] text-muted-foreground">→</span>
        <input type="date" value={localTo} min={localFrom} max={today()}
          onChange={e => { setLocalTo(e.target.value); setActivePreset(null) }}
          onBlur={onDateBlur}
          className="text-xs border border-input rounded px-2 py-1 bg-transparent" />
      </div>
      <div className="flex flex-wrap items-center gap-3">
        <span className="text-[11px] text-muted-foreground uppercase tracking-wider shrink-0 w-16">Vue</span>
        {[['day', 'Jour'], ['week', 'Semaine'], ['month', 'Mois']].map(([val, lbl]) => (
          <button key={val} onClick={() => { setLocalGran(val); emit({ granularity: val }) }} className={pill(localGran === val)}>
            {lbl}
          </button>
        ))}
        <div className="h-4 w-px bg-border mx-1" />
        <button
          type="button"
          onClick={() => toggleCompare(!localCompare)}
          className={cn(
            'flex items-center gap-2 px-3 py-1 rounded text-xs font-medium border transition-colors',
            localCompare
              ? 'bg-indigo-50 border-indigo-200 text-indigo-700'
              : 'bg-card border-border text-muted-foreground hover:text-foreground',
          )}
        >
          <span className={cn('w-3 h-0.5 rounded-full inline-block',
            localCompare ? 'bg-indigo-500' : 'bg-muted-foreground')} />
          Comparer la période préc.
        </button>
      </div>
    </div>
  )
}

/** KPI card dynamique avec delta et couleur. */
function KpiCard({ label, value, sub, delta: d, color = 'default', tooltip: tip }) {
  const isPositive = d != null && d >= 0
  const isNeutral  = d == null

  const colorClass = {
    default: '',
    green:   'border-emerald-200 bg-emerald-50/50',
    amber:   'border-amber-200 bg-amber-50/50',
    red:     'border-red-200 bg-red-50/50',
    indigo:  'border-indigo-200 bg-indigo-50/50',
  }[color] ?? ''

  return (
    <UiTooltip content={tip}>
      <div className={cn('rounded-lg border bg-card p-4 space-y-1', colorClass)}>
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider">{label}</p>
        <div className="flex items-end justify-between gap-2">
          <p className="text-2xl font-bold tabular-nums leading-none">{value}</p>
          {!isNeutral && (
            <span className={cn(
              'text-[11px] font-bold px-1.5 py-0.5 rounded mb-0.5',
              isPositive ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600',
            )}>
              {isPositive ? '+' : ''}{d}%
            </span>
          )}
        </div>
        {sub && <p className="text-[11px] text-muted-foreground">{sub}</p>}
      </div>
    </UiTooltip>
  )
}

// ── Main component ────────────────────────────────────────────────────────────

export default function Dashboard() {
  const navigate = useNavigate()

  const [data,         setData]         = useState(null)
  const [stats,        setStats]        = useState(null)
  const [sparkData,    setSparkData]    = useState(null)
  const [loading,      setLoading]      = useState(true)
  const [statsLoading, setStatsLoading] = useState(true)
  const [error,        setError]        = useState(null)
  const [fullscreen,   setFullscreen]   = useState(null)

  const [filterParams, setFilterParams] = useState(() => {
    const saved = loadPrefs()
    if (saved) return saved
    const from = '2017-01-01', to = today()
    const { cmpFrom, cmpTo } = prevPeriod(from, to)
    return { from, to, granularity: 'month', compareFrom: cmpFrom, compareTo: cmpTo }
  })

  useEffect(() => {
    try { localStorage.setItem('bt-dashboard-prefs', JSON.stringify(filterParams)) } catch {}
  }, [filterParams])

  useEffect(() => {
    api.dashboard()
      .then(setData)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    api.bookingsStats({ from: daysAgo(6), to: today(), granularity: 'day' })
      .then(setSparkData)
      .catch(() => setSparkData(null))
  }, [])

  const loadStats = useCallback(() => {
    setStatsLoading(true)
    const p = { from: filterParams.from, to: filterParams.to, granularity: filterParams.granularity }
    if (filterParams.compareFrom) p.compare_from = filterParams.compareFrom
    if (filterParams.compareTo)   p.compare_to   = filterParams.compareTo
    api.bookingsStats(p)
      .then(setStats)
      .catch(() => setStats(null))
      .finally(() => setStatsLoading(false))
  }, [filterParams])

  useEffect(() => { loadStats() }, [loadStats])

  // ── Chart data merge ──────────────────────────────────────────────────────
  const chartData = useMemo(() => {
    if (!stats?.periods?.length) return []
    const cmpMap = {}
    ;(stats.compare ?? []).forEach((c, i) => { cmpMap[i] = c })
    return stats.periods.map((p, i) => ({
      label:          p.label,
      key:            p.key,
      bookings:       p.bookings,
      revenue:        p.revenue,
      avg_basket:     p.avg_basket,
      cancelled:      p.cancelled,
      bookings_prev:  cmpMap[i]?.bookings   ?? null,
      revenue_prev:   cmpMap[i]?.revenue    ?? null,
      avg_basket_prev: cmpMap[i]?.avg_basket ?? null,
    }))
  }, [stats])

  const hasCompare = !!(filterParams.compareFrom && stats?.compare?.length > 0)

  // ── KPIs from stats ───────────────────────────────────────────────────────
  const kpis     = stats?.kpis     ?? {}
  const kpisCmp  = stats?.kpis_compare ?? null

  const totalBookings     = kpis.total_bookings    ?? 0
  const totalRevenue      = kpis.total_revenue     ?? 0
  const avgBasket         = kpis.avg_basket        ?? null
  const cancelRate        = kpis.cancellation_rate ?? 0
  const totalCancelled    = kpis.total_cancelled   ?? 0
  const uniqueProducts    = kpis.unique_products   ?? 0
  const paidBookings      = kpis.paid_bookings     ?? 0
  const refundsTotal      = kpis.refunds_total     ?? 0

  const cmpBookings    = kpisCmp?.total_bookings    ?? null
  const cmpRevenue     = kpisCmp?.total_revenue     ?? null
  const cmpAvgBasket   = kpisCmp?.avg_basket        ?? null
  const cmpCancelRate  = kpisCmp?.cancellation_rate ?? null

  const bookingsDelta     = delta(totalBookings, cmpBookings)
  const revenueDelta      = delta(totalRevenue,  cmpRevenue)
  const avgBasketDelta    = delta(avgBasket,      cmpAvgBasket)
  // Pour le taux d'annulation, positif = mauvais → on inverse le signe visuel
  const cancelRateDeltaRaw = delta(cancelRate, cmpCancelRate)
  const cancelRateDelta    = cancelRateDeltaRaw != null ? -cancelRateDeltaRaw : null

  // Sparklines (7j)
  const sparkBookings = useMemo(() => sparkData?.periods?.map(p => p.bookings ?? 0) ?? [], [sparkData])
  const sparkRevenue  = useMemo(() => sparkData?.periods?.map(p => p.revenue  ?? 0) ?? [], [sparkData])

  // Peaks
  const peakBookings = stats?.peak_bookings ?? 0
  const peakRevenue  = stats?.peak_revenue  ?? 0
  const peakBasket   = stats?.peak_basket   ?? 0

  // Top produits total pour %
  const totalProductCount = useMemo(
    () => (stats?.by_product ?? []).reduce((s, p) => s + p.count, 0),
    [stats],
  )

  // Drill-down chart → planificateur
  function handleChartClick(clickData) {
    if (!clickData?.activePayload?.length) return
    if (filterParams.granularity !== 'day') return
    const key = clickData.activePayload[0]?.payload?.key
    const month = key?.slice(0, 7)
    navigate(month ? `/planner?month=${month}` : '/planner')
  }

  function resetPeriod() {
    const from = '2017-01-01', to = today()
    const { cmpFrom, cmpTo } = prevPeriod(from, to)
    setFilterParams({ from, to, granularity: 'month', compareFrom: cmpFrom, compareTo: cmpTo })
  }

  // ── Charts ────────────────────────────────────────────────────────────────
  function renderBookingsChart(height = 220) {
    return (
      <>
        <ResponsiveContainer width="100%" height={height}>
          <AreaChart data={chartData} margin={{ top: 8, right: 4, left: -28, bottom: 0 }}
            onClick={handleChartClick}
            className={filterParams.granularity === 'day' ? 'cursor-pointer' : ''}>
            <defs>
              <linearGradient id="gradBookings" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%"  stopColor={CHART_CURRENT} stopOpacity={0.18} />
                <stop offset="95%" stopColor={CHART_CURRENT} stopOpacity={0} />
              </linearGradient>
              <linearGradient id="gradBookingsPrev" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%"  stopColor={CHART_COMPARE} stopOpacity={0.10} />
                <stop offset="95%" stopColor={CHART_COMPARE} stopOpacity={0} />
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" stroke={CHART_GRID} vertical={false} />
            <XAxis dataKey="label" tick={{ fontSize: 10, fill: CHART_AXIS }} axisLine={false} tickLine={false} />
            <YAxis allowDecimals={false} tick={{ fontSize: 10, fill: CHART_AXIS }} axisLine={false} tickLine={false} />
            <Tooltip content={(p) => <ChartTooltip {...p} compareFrom={filterParams.compareFrom} compareTo={filterParams.compareTo} />} />
            {peakBookings > 0 && (
              <ReferenceLine y={peakBookings} stroke={CHART_PEAK} strokeDasharray="4 3" strokeWidth={1.5}
                label={{ value: `Pic ${peakBookings}`, fill: CHART_PEAK, fontSize: 9, position: 'insideTopRight' }} />
            )}
            {hasCompare && (
              <Area type="monotone" dataKey="bookings_prev" name="Préc."
                stroke={CHART_COMPARE} strokeWidth={1.5} strokeDasharray="5 3"
                fill="url(#gradBookingsPrev)" dot={false} />
            )}
            <Area type="monotone" dataKey="bookings" name="Actuel"
              stroke={CHART_CURRENT} strokeWidth={2}
              fill="url(#gradBookings)" dot={false} />
          </AreaChart>
        </ResponsiveContainer>
        <ChartLegend from={filterParams.from} to={filterParams.to}
          compareFrom={filterParams.compareFrom} compareTo={filterParams.compareTo}
          hasCompare={hasCompare} total={totalBookings} prevTotal={cmpBookings ?? 0} />
        {filterParams.granularity === 'day' && (
          <p className="text-[10px] text-muted-foreground mt-2 text-center">
            Cliquez sur un jour pour voir dans le Planificateur
          </p>
        )}
      </>
    )
  }

  function renderRevenueChart(height = 220) {
    return (
      <>
        <ResponsiveContainer width="100%" height={height}>
          <AreaChart data={chartData} margin={{ top: 8, right: 4, left: -20, bottom: 0 }}
            onClick={handleChartClick}
            className={filterParams.granularity === 'day' ? 'cursor-pointer' : ''}>
            <defs>
              <linearGradient id="gradRevenue" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%"  stopColor={CHART_CURRENT} stopOpacity={0.18} />
                <stop offset="95%" stopColor={CHART_CURRENT} stopOpacity={0} />
              </linearGradient>
              <linearGradient id="gradRevenuePrev" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%"  stopColor={CHART_COMPARE} stopOpacity={0.10} />
                <stop offset="95%" stopColor={CHART_COMPARE} stopOpacity={0} />
              </linearGradient>
              <linearGradient id="gradBasket" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%"  stopColor={CHART_BASKET} stopOpacity={0.12} />
                <stop offset="95%" stopColor={CHART_BASKET} stopOpacity={0} />
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" stroke={CHART_GRID} vertical={false} />
            <XAxis dataKey="label" tick={{ fontSize: 10, fill: CHART_AXIS }} axisLine={false} tickLine={false} />
            <YAxis tick={{ fontSize: 10, fill: CHART_AXIS }} axisLine={false} tickLine={false} />
            <Tooltip content={(p) => <ChartTooltip {...p} suffix=" €" compareFrom={filterParams.compareFrom} compareTo={filterParams.compareTo} />} />
            {peakRevenue > 0 && (
              <ReferenceLine y={peakRevenue} stroke={CHART_PEAK} strokeDasharray="4 3" strokeWidth={1.5}
                label={{ value: `Pic ${Number(peakRevenue).toLocaleString('fr-FR')} €`, fill: CHART_PEAK, fontSize: 9, position: 'insideTopRight' }} />
            )}
            {hasCompare && (
              <Area type="monotone" dataKey="revenue_prev" name="CA préc."
                stroke={CHART_COMPARE} strokeWidth={1.5} strokeDasharray="5 3"
                fill="url(#gradRevenuePrev)" dot={false} />
            )}
            <Area type="monotone" dataKey="revenue" name="CA"
              stroke={CHART_CURRENT} strokeWidth={2}
              fill="url(#gradRevenue)" dot={false} />
            {/* Panier moyen en overlay */}
            <Area type="monotone" dataKey="avg_basket" name="Panier moy."
              stroke={CHART_BASKET} strokeWidth={1.5} strokeDasharray="3 2"
              fill="url(#gradBasket)" dot={false} yAxisId={0} />
          </AreaChart>
        </ResponsiveContainer>
        <ChartLegend from={filterParams.from} to={filterParams.to}
          compareFrom={filterParams.compareFrom} compareTo={filterParams.compareTo}
          hasCompare={hasCompare} total={totalRevenue} prevTotal={cmpRevenue ?? 0} suffix=" €" />
        {avgBasket && (
          <p className="text-[11px] text-muted-foreground mt-1">
            <span className="font-medium" style={{ color: CHART_BASKET }}>— — —</span>{' '}
            Panier moyen : <strong>{fmtCurrency(avgBasket, 0)}</strong>
            {avgBasketDelta != null && (
              <span className={cn(
                'ml-2 text-[10px] font-bold px-1 py-0.5 rounded',
                avgBasketDelta >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600',
              )}>
                {avgBasketDelta >= 0 ? '+' : ''}{avgBasketDelta}%
              </span>
            )}
          </p>
        )}
      </>
    )
  }

  // Colonnes tableau réservations récentes (normalisées depuis bt_reservations)
  const bookingCols = [
    { key: 'booking_ref',   label: 'Référence' },
    { key: 'product_name',  label: 'Produit' },
    { key: 'booking_date',  label: 'Date', render: r => r.booking_date?.slice(0, 10) ?? '—' },
    { key: 'customer_name', label: 'Client' },
    {
      key: 'total_price',
      label: 'Montant',
      render: r => r.total_price ? `${Number(r.total_price).toLocaleString('fr-FR')} €` : '—',
    },
    {
      key: 'status',
      label: 'Statut',
      render: r => {
        const s = STATUS_MAP[r.status] ?? { variant: 'default', label: r.status ?? '—' }
        return <Badge variant={s.variant}>{s.label}</Badge>
      },
    },
  ]

  // ── Render ────────────────────────────────────────────────────────────────
  return (
    <TooltipProvider>
      <div>
        <PageHeader
          title="Tableau de bord"
          subtitle={`Données solditems importés · ${fmtShort(filterParams.from)} – ${fmtShort(filterParams.to)}`}
        />

        {error && (
          <div className="px-6 pt-5">
            <Notice type="error">{error}</Notice>
          </div>
        )}

        {/* ── Ligne 1 : KPIs statiques (produits, clients) + API status ─── */}
        {!loading && (
          <div className="mx-6 mt-5 grid grid-cols-2 lg:grid-cols-4 gap-px bg-border border rounded-lg overflow-hidden">
            <UiTooltip content="Produits synchronisés depuis Regiondo">
              <div>
                <StatCard
                  label="Produits"
                  value={data?.products_count ?? 0}
                  sub={<button onClick={() => navigate('/products')} className="text-xs underline">Voir tout</button>}
                />
              </div>
            </UiTooltip>
            <UiTooltip content="Clients dans le CRM Regiondo">
              <div>
                <StatCard label="Clients CRM" value={data?.customers_total ?? 0}
                  sub={<button onClick={() => navigate('/customers')} className="text-xs underline">Voir tout</button>} />
              </div>
            </UiTooltip>
            <UiTooltip content="Réservations en DB locale (solditems importés)">
              <div>
                <StatCard label="Total en DB" value={fmtNum(data?.bookings_month ?? 0)}
                  sub="Réservations importées" />
              </div>
            </UiTooltip>
            <UiTooltip content="Statut de la connexion API Regiondo">
              <div>
                <StatCard
                  label="API Regiondo"
                  value={data?.api_status === 'ok' ? '✓ OK' : '✗ Erreur'}
                  sub={data?.api_status === 'ok' ? 'Connexion active' : data?.api_error ?? 'Vérifier les clés'}
                />
              </div>
            </UiTooltip>
          </div>
        )}

        {/* ── Toolbar filtres ────────────────────────────────────────────── */}
        <StatsToolbar
          from={filterParams.from}
          to={filterParams.to}
          granularity={filterParams.granularity}
          showCompare={!!filterParams.compareFrom}
          onApply={p => setFilterParams(prev => ({ ...prev, ...p }))}
        />

        {/* ── Ligne 2 : KPIs dynamiques (basés sur la période filtrée) ─── */}
        {statsLoading ? (
          <div className="mx-6 mt-4 flex items-center gap-3 text-sm text-muted-foreground">
            <Spinner size={14} /> Calcul des indicateurs…
          </div>
        ) : (
          <div className="mx-6 mt-4 grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
            <KpiCard
              label="Réservations"
              value={fmtNum(totalBookings)}
              delta={bookingsDelta}
              color="default"
              tooltip={`Total sur ${fmtShort(filterParams.from)} – ${fmtShort(filterParams.to)}`}
              sub={hasCompare ? `vs ${fmtNum(cmpBookings)} préc.` : `${fmtNum(uniqueProducts)} produit${uniqueProducts > 1 ? 's' : ''}`}
            />
            <KpiCard
              label="CA total"
              value={fmtCurrency(totalRevenue)}
              delta={revenueDelta}
              color="green"
              tooltip="Somme des price_total hors annulés/remboursements"
              sub={refundsTotal < 0 ? `Remb. : ${fmtCurrency(refundsTotal)}` : 'CA confirmé'}
            />
            <KpiCard
              label="Panier moyen"
              value={avgBasket ? fmtCurrency(avgBasket) : '—'}
              delta={avgBasketDelta}
              color="amber"
              tooltip="Valeur moyenne d'une réservation payante non annulée"
              sub={paidBookings > 0 ? `Sur ${fmtNum(paidBookings)} rés. payantes` : 'Aucune rés. payante'}
            />
            <KpiCard
              label="Taux annulation"
              value={`${cancelRate}%`}
              delta={cancelRateDelta}
              color={cancelRate > 10 ? 'red' : 'default'}
              tooltip="% de réservations annulées/rejetées sur la période"
              sub={`${fmtNum(totalCancelled)} annulées`}
            />
            <KpiCard
              label="Réservations confirmées"
              value={fmtNum(kpis.total_confirmed ?? 0)}
              color="default"
              tooltip="Réservations non annulées sur la période"
              sub={totalBookings > 0 ? `${Math.round(((kpis.total_confirmed ?? 0) / totalBookings) * 100)}% du total` : ''}
            />
            <KpiCard
              label="Produits actifs"
              value={fmtNum(uniqueProducts)}
              color="indigo"
              tooltip="Nombre de produits distincts sur la période"
              sub={<button onClick={() => navigate('/products')} className="text-xs underline">Voir les produits</button>}
            />
          </div>
        )}

        {/* ── Charts ───────────────────────────────────────────────────── */}
        {!statsLoading && (
          chartData.length === 0
            ? <EmptyChart onReset={resetPeriod} />
            : (
              <div className="mx-6 mt-4">
                <Tabs defaultValue="bookings">
                  <div className="flex items-center justify-between mb-4">
                    <TabsList>
                      <TabsTrigger value="bookings">Réservations</TabsTrigger>
                      <TabsTrigger value="revenue">CA + Panier moyen</TabsTrigger>
                    </TabsList>
                  </div>
                  <TabsContent value="bookings">
                    <div className="rounded-lg border bg-card p-5 relative">
                      <button onClick={() => setFullscreen('bookings')}
                        className="absolute top-3 right-3 h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors" title="Plein écran">
                        <Expand width={13} height={13} />
                      </button>
                      {renderBookingsChart(220)}
                    </div>
                  </TabsContent>
                  <TabsContent value="revenue">
                    <div className="rounded-lg border bg-card p-5 relative">
                      <button onClick={() => setFullscreen('revenue')}
                        className="absolute top-3 right-3 h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors" title="Plein écran">
                        <Expand width={13} height={13} />
                      </button>
                      {renderRevenueChart(220)}
                    </div>
                  </TabsContent>
                </Tabs>
              </div>
            )
        )}

        {/* ── Distributions : canaux + jours ─────────────────────────── */}
        {!statsLoading && (stats?.by_channel?.length > 0 || stats?.by_weekday?.length > 0) && (
          <div className="mx-6 mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">

            {/* Canaux de vente */}
            {stats?.by_channel?.length > 0 && (
              <div className="rounded-lg border bg-card p-5">
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-4">Canaux de vente</p>
                <div className="space-y-2.5">
                  {stats.by_channel.map((c, i) => {
                    const maxCnt = stats.by_channel[0]?.bookings ?? 1
                    const barPct = Math.round((c.bookings / maxCnt) * 100)
                    const totalPct = totalBookings > 0 ? Math.round((c.bookings / totalBookings) * 100) : 0
                    return (
                      <div key={i} className="flex items-center gap-3">
                        <span className="w-2 h-2 rounded-full shrink-0" style={{ background: CHANNEL_COLORS[i % CHANNEL_COLORS.length] }} />
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center justify-between gap-2 mb-1">
                            <span className="text-xs truncate">{c.channel}</span>
                            <div className="flex items-center gap-2 shrink-0">
                              <span className="text-xs font-medium tabular-nums">{fmtNum(c.bookings)}</span>
                              <span className="text-[10px] text-muted-foreground tabular-nums w-8 text-right">{totalPct}%</span>
                            </div>
                          </div>
                          <div className="h-1.5 rounded-full bg-muted overflow-hidden">
                            <div className="h-full rounded-full transition-all duration-500"
                              style={{ width: `${barPct}%`, background: CHANNEL_COLORS[i % CHANNEL_COLORS.length] }} />
                          </div>
                        </div>
                      </div>
                    )
                  })}
                </div>
              </div>
            )}

            {/* Répartition par jour de semaine */}
            {stats?.by_weekday?.length > 0 && (
              <div className="rounded-lg border bg-card p-5">
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-4">Activité par jour</p>
                <ResponsiveContainer width="100%" height={160}>
                  <BarChart
                    data={[...stats.by_weekday].sort((a, b) => {
                      // Réordonner Lun→Dim (DOW MySQL: 1=Dim, 2=Lun ... 7=Sam)
                      const ord = [2, 3, 4, 5, 6, 7, 1]
                      return ord.indexOf(a.dow) - ord.indexOf(b.dow)
                    })}
                    margin={{ top: 4, right: 4, left: -28, bottom: 0 }}
                  >
                    <CartesianGrid strokeDasharray="3 3" stroke={CHART_GRID} vertical={false} />
                    <XAxis dataKey="label" tick={{ fontSize: 10, fill: CHART_AXIS }} axisLine={false} tickLine={false} />
                    <YAxis allowDecimals={false} tick={{ fontSize: 10, fill: CHART_AXIS }} axisLine={false} tickLine={false} />
                    <Tooltip
                      content={({ active, payload, label }) => {
                        if (!active || !payload?.length) return null
                        const v = payload[0]?.value ?? 0
                        const totalWday = stats.by_weekday.reduce((s, d) => s + d.bookings, 0)
                        const pct = totalWday > 0 ? Math.round((v / totalWday) * 100) : 0
                        return (
                          <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs">
                            <p className="font-medium">{label}</p>
                            <p className="mt-1">{fmtNum(v)} rés. <span className="text-muted-foreground">({pct}%)</span></p>
                          </div>
                        )
                      }}
                    />
                    <Bar dataKey="bookings" radius={[3, 3, 0, 0]}>
                      {stats.by_weekday.map((_, i) => (
                        <Cell key={i} fill={CHART_CURRENT} fillOpacity={0.7 + (i % 3) * 0.1} />
                      ))}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
                <p className="text-[10px] text-muted-foreground mt-2 text-center">
                  Nombre de réservations par jour de la semaine
                </p>
              </div>
            )}
          </div>
        )}

        {/* ── Top produits ─────────────────────────────────────────────── */}
        {!statsLoading && stats?.by_product?.length > 0 && (
          <div className="mx-6 mt-4">
            <div className="rounded-lg border bg-card p-5">
              <div className="flex items-center justify-between mb-4">
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider">Top produits</p>
                <span className="text-[11px] text-muted-foreground">
                  {fmtShort(filterParams.from)} – {fmtShort(filterParams.to)}
                </span>
              </div>
              <div className="space-y-3">
                {stats.by_product.map((p, i) => {
                  const barPct   = stats.by_product[0]?.count > 0 ? Math.round((p.count / stats.by_product[0].count) * 100) : 0
                  const totalPct = totalProductCount > 0 ? Math.round((p.count / totalProductCount) * 100) : 0
                  return (
                    <div key={i} className="flex items-center gap-3">
                      <span className="text-xs text-muted-foreground w-4 shrink-0 tabular-nums">{i + 1}</span>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between gap-2 mb-1">
                          <span className="text-xs truncate">{p.name}</span>
                          <div className="flex items-center gap-3 shrink-0">
                            {p.revenue > 0 && (
                              <span className="text-[10px] text-emerald-600 font-medium tabular-nums">
                                {fmtCurrency(p.revenue)}
                              </span>
                            )}
                            <span className="text-xs font-medium tabular-nums">{fmtNum(p.count)}</span>
                            <span className="text-[10px] text-muted-foreground tabular-nums w-8 text-right">{totalPct}%</span>
                          </div>
                        </div>
                        <div className="h-1.5 rounded-full bg-muted overflow-hidden">
                          <div className="h-full rounded-full bg-primary transition-all duration-500"
                            style={{ width: `${barPct}%` }} />
                        </div>
                      </div>
                    </div>
                  )
                })}
              </div>
            </div>
          </div>
        )}

        {/* ── Réservations récentes ─────────────────────────────────────── */}
        {!loading && (
          <div className="mx-6 mt-6 mb-10">
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs text-muted-foreground uppercase tracking-wider">Dernières réservations importées</span>
              <button onClick={() => navigate('/bookings')} className="text-xs text-muted-foreground underline hover:text-foreground">
                Tout voir
              </button>
            </div>
            <div className="rounded-lg border overflow-hidden">
              <Table columns={bookingCols} data={data?.recent_bookings ?? []} empty="Aucune réservation trouvée." />
            </div>
          </div>
        )}
      </div>

      {/* ── Plein écran ────────────────────────────────────────────────── */}
      {fullscreen === 'bookings' && (
        <FullscreenOverlay title="Réservations" onClose={() => setFullscreen(null)}>
          {renderBookingsChart(420)}
        </FullscreenOverlay>
      )}
      {fullscreen === 'revenue' && (
        <FullscreenOverlay title="CA + Panier moyen" onClose={() => setFullscreen(null)}>
          {renderRevenueChart(420)}
        </FullscreenOverlay>
      )}
    </TooltipProvider>
  )
}
