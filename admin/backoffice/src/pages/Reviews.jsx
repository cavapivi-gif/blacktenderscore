import { useState, useEffect, useCallback, useMemo } from 'react'
import { Search, Download, Trash, RefreshDouble, Star, CandlestickChart, Group } from 'iconoir-react'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import {
  ComposedChart, Area, BarChart, Bar,
  XAxis, YAxis, CartesianGrid, Tooltip, Legend,
  ResponsiveContainer, ReferenceLine, Cell, Line,
} from 'recharts'
import { api } from '../lib/api'
import { COLORS } from '../lib/constants'
import { today, monthsAgo, delta } from '../lib/utils'
import { PageHeader, Table, Pagination, Notice, Spinner, Btn } from '../components/ui'
import { PeriodPicker } from '../components/PeriodPicker'
import ReviewsImporter from '../components/ReviewsImporter'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/Tabs'

// ── Constants ─────────────────────────────────────────────────────────────────

const FR_WEEKDAYS = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']
const LEAD_BUCKETS = ['<7j', '7-30j', '30-90j', '90-180j', '>180j']

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Format YYYY-MM-DD → "3 jan. 2024"
 * @param {string} d
 * @returns {string}
 */
function fmtDate(d) {
  if (!d || d === '0000-00-00') return '—'
  try { return format(new Date(d + 'T12:00:00'), 'd MMM yyyy', { locale: fr }) }
  catch { return d }
}

/**
 * Compute the comparison period = same duration shifted back 1 day before from.
 * @param {string} from YYYY-MM-DD
 * @param {string} to   YYYY-MM-DD
 * @returns {{ from: string, to: string }}
 */
function computeComparePeriod(from, to) {
  const fromDate = new Date(from + 'T12:00:00')
  const toDate   = new Date(to   + 'T12:00:00')
  const duration = toDate.getTime() - fromDate.getTime()
  const compareTo   = new Date(fromDate.getTime() - 86400000)
  const compareFrom = new Date(fromDate.getTime() - duration - 86400000)
  return {
    from: compareFrom.toISOString().slice(0, 10),
    to:   compareTo.toISOString().slice(0, 10),
  }
}

/**
 * Build export CSV string from review rows.
 * @param {Array} rows
 */
function buildExportCsv(rows) {
  const hdr = ['N° commande', 'Produit', 'Client', 'Email', 'Note', 'Résumé', 'Avis', 'Date']
  const csv = [hdr, ...rows.map(r => [
    r.order_number, r.product_name, r.customer_name, r.customer_email,
    r.rating, r.review_title, (r.review_body ?? '').replace(/\n/g, ' '),
    r.review_date,
  ])].map(row => row.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(';')).join('\n')
  Object.assign(document.createElement('a'), {
    href: URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' })),
    download: 'avis.csv',
  }).click()
}

// ── Inline components ─────────────────────────────────────────────────────────

/**
 * SVG star row — filled amber if i <= value, grey outline otherwise.
 * @param {{ value: number, size?: number }} props
 */
function Stars({ value, size = 12 }) {
  if (!value) return <span className="text-muted-foreground text-xs">—</span>
  return (
    <span className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map(i => (
        <svg key={i} width={size} height={size} viewBox="0 0 24 24"
          fill={i <= value ? '#f59e0b' : 'none'}
          stroke={i <= value ? '#f59e0b' : '#d1d5db'} strokeWidth="1.5"
          strokeLinecap="round" strokeLinejoin="round">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
      ))}
    </span>
  )
}

/**
 * Large numeric rating with label.
 * @param {{ value: number|null }} props
 */
function BigRating({ value }) {
  if (!value) return <span className="text-3xl font-bold text-muted-foreground">—</span>
  return (
    <div className="flex items-end gap-2">
      <span className="text-3xl font-bold tabular-nums">{Number(value).toFixed(1)}</span>
      <span className="text-sm text-muted-foreground mb-1">/ 5</span>
    </div>
  )
}

/**
 * Delta badge — green for positive delta, red for negative.
 * @param {{ current: number, previous: number|null, invert?: boolean }} props
 */
function Delta({ current, previous, invert = false }) {
  const pct = delta(current, previous)
  if (pct == null) return null
  const isGood  = invert ? pct <= 0 : pct >= 0
  const sign    = pct > 0 ? '+' : ''
  return (
    <span
      className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold tabular-nums"
      style={{
        background: isGood ? COLORS.delta_pos_bg  : COLORS.delta_neg_bg,
        color:      isGood ? COLORS.delta_pos_text : COLORS.delta_neg_text,
      }}
    >
      {sign}{pct}%
    </span>
  )
}

/**
 * KPI card for the analytics overview row.
 */
function KpiCard({ label, value, sub, compare, invert = false }) {
  return (
    <div className="px-6 py-5">
      <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-1">{label}</p>
      <div className="flex items-center gap-2 flex-wrap">
        <div className="text-2xl font-bold tabular-nums">{value ?? '—'}</div>
        {compare !== undefined && <Delta current={compare.current} previous={compare.previous} invert={invert} />}
      </div>
      {sub && <p className="text-xs text-muted-foreground mt-0.5">{sub}</p>}
    </div>
  )
}

/**
 * Recharts tooltip — card style matching the design system.
 */
function ChartTip({ active, payload, label }) {
  if (!active || !payload?.length) return null
  return (
    <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs space-y-0.5 min-w-[110px]">
      <p className="font-medium text-muted-foreground">{label}</p>
      {payload.map(p => (
        <p key={p.dataKey} style={{ color: p.stroke ?? p.fill ?? p.color }}>
          {p.name} : <strong>{typeof p.value === 'number' ? Number(p.value).toFixed(2) : p.value}</strong>
        </p>
      ))}
    </div>
  )
}

/**
 * Distribution bars (5★ → 1★).
 */
function DistributionBars({ distribution, total }) {
  if (!total) return <p className="text-xs text-muted-foreground">Aucune note disponible</p>
  const max = Math.max(...Object.values(distribution), 1)
  return (
    <div className="space-y-1.5 w-full">
      {[5, 4, 3, 2, 1].map(star => {
        const count = distribution[star] ?? 0
        const pct   = total ? Math.round((count / total) * 100) : 0
        return (
          <div key={star} className="flex items-center gap-2 text-xs">
            <span className="w-3 text-right text-muted-foreground shrink-0">{star}</span>
            <svg width="10" height="10" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" strokeWidth="1.5">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <div className="flex-1 h-1.5 rounded-full bg-muted overflow-hidden">
              <div className="h-full rounded-full bg-amber-400 transition-all" style={{ width: `${(count / max) * 100}%` }} />
            </div>
            <span className="w-6 text-right tabular-nums text-muted-foreground">{count}</span>
            <span className="w-8 text-right tabular-nums text-muted-foreground/60">{pct}%</span>
          </div>
        )
      })}
    </div>
  )
}

// ── Tab: Vue d'ensemble ────────────────────────────────────────────────────────

/**
 * Analytics overview tab — KPI row + monthly trend + distribution + weekday + lead time.
 */
function OverviewTab({ stats, compareStats, compareActive, from, to }) {
  if (!stats) return null

  const {
    total, total_rated, avg_rating, distribution,
    monthly, reviews_needed_4_8, avg_lead_time_days,
    by_weekday, lead_time_buckets,
  } = stats

  // Monthly chart data — merge current + compare onto same display months
  const monthlyChart = useMemo(() => {
    const map = {}
    ;(monthly ?? []).forEach(m => {
      const key = m.month?.slice(0, 7) ?? ''
      if (!key) return
      map[key] = {
        month: key,
        avg:   parseFloat(m.avg_rating) || 0,
        count: parseInt(m.count)        || 0,
      }
    })
    // Abbreviated month label
    return Object.values(map).map(d => ({
      ...d,
      label: (() => {
        try { return format(new Date(d.month + '-01T12:00:00'), 'MMM yy', { locale: fr }) }
        catch { return d.month }
      })(),
    }))
  }, [monthly])

  // Overlay comparison monthly data
  const compareMonthly = useMemo(() => {
    if (!compareActive || !compareStats?.monthly) return []
    return compareStats.monthly.map((m, i) => ({
      idx:       i,
      avgCmp:    parseFloat(m.avg_rating) || 0,
      countCmp:  parseInt(m.count)        || 0,
    }))
  }, [compareActive, compareStats])

  // Merge monthly + compare by index (align by position)
  const mergedMonthly = useMemo(() => {
    if (!compareActive) return monthlyChart
    return monthlyChart.map((d, i) => ({
      ...d,
      ...(compareMonthly[i] ?? {}),
    }))
  }, [monthlyChart, compareMonthly, compareActive])

  // Weekday chart data (reorder from 1=Mon to 0=Sun at end for readability Mon-Sun)
  const weekdayChart = useMemo(() => {
    const raw = by_weekday ?? []
    const sorted = [1, 2, 3, 4, 5, 6, 0].map(wd => {
      const found = raw.find(d => d.weekday === wd)
      return {
        label:      FR_WEEKDAYS[wd],
        count:      found ? parseInt(found.count)           || 0 : 0,
        avg_rating: found ? parseFloat(found.avg_rating)    || 0 : 0,
      }
    })
    return sorted
  }, [by_weekday])

  // Lead time buckets (keep canonical order)
  const leadTimeChart = useMemo(() => {
    const raw = lead_time_buckets ?? []
    return LEAD_BUCKETS.map(b => {
      const found = raw.find(d => d.bucket === b)
      return { bucket: b, count: found ? parseInt(found.count) || 0 : 0 }
    })
  }, [lead_time_buckets])

  // Most common rating = median approximation
  const modeStar = useMemo(() => {
    if (!distribution) return null
    let max = 0, mode = null
    Object.entries(distribution).forEach(([star, cnt]) => {
      if (cnt > max) { max = cnt; mode = parseInt(star) }
    })
    return mode
  }, [distribution])

  const leadDays = avg_lead_time_days != null ? `${Math.round(avg_lead_time_days)} j` : '—'
  const peakWeekday = useMemo(() => {
    if (!weekdayChart.length) return '—'
    const peak = weekdayChart.reduce((a, b) => b.count > a.count ? b : a)
    return peak.label
  }, [weekdayChart])

  return (
    <div className="space-y-5">
      {/* KPI Row */}
      <div className="rounded-xl border bg-card overflow-hidden">
        <div className="grid grid-cols-2 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x">
          <KpiCard
            label="Total avis"
            value={total?.toLocaleString('fr-FR')}
            sub={total !== total_rated ? `${total_rated?.toLocaleString('fr-FR')} notés` : null}
            compare={compareActive && compareStats ? { current: total, previous: compareStats.total } : undefined}
          />
          <KpiCard
            label="Note moyenne"
            value={
              avg_rating ? (
                <span className="flex flex-col gap-1">
                  <BigRating value={avg_rating} />
                  <Stars value={Math.round(avg_rating)} size={13} />
                </span>
              ) : '—'
            }
            compare={compareActive && compareStats ? { current: avg_rating, previous: compareStats.avg_rating } : undefined}
          />
          <KpiCard
            label="Objectif 4.8★"
            value={
              avg_rating >= 4.8
                ? <span className="text-emerald-600 font-bold text-xl">Atteint ✓</span>
                : reviews_needed_4_8 > 0
                  ? reviews_needed_4_8.toLocaleString('fr-FR')
                  : '—'
            }
            sub={
              avg_rating < 4.8 && reviews_needed_4_8 > 0
                ? 'avis 5★ supplémentaires'
                : avg_rating != null
                  ? `Moy. actuelle : ${Number(avg_rating).toFixed(2)}/5`
                  : null
            }
          />
          <KpiCard
            label="Délai moy. avis"
            value={leadDays}
            sub="entre excursion et dépôt"
            compare={compareActive && compareStats ? { current: avg_lead_time_days, previous: compareStats.avg_lead_time_days } : undefined}
          />
        </div>
      </div>

      {/* Progress bar toward 4.8 */}
      {avg_rating != null && avg_rating < 4.8 && (
        <div className="rounded-xl border bg-card px-6 py-4">
          <div className="flex items-center justify-between text-xs text-muted-foreground mb-2">
            <span>Note actuelle : <strong className="text-foreground">{Number(avg_rating).toFixed(2)}</strong></span>
            <span>Objectif : <strong className="text-amber-600">4.8★</strong></span>
          </div>
          <div className="h-2 rounded-full bg-muted overflow-hidden">
            <div
              className="h-full rounded-full bg-amber-400 transition-all duration-700"
              style={{ width: `${Math.min(100, Math.max(0, ((avg_rating - 1) / (4.8 - 1)) * 100))}%` }}
            />
          </div>
        </div>
      )}

      {/* Charts Row 1: Monthly trend (2/3) + Distribution (1/3) */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {/* Monthly trend ComposedChart */}
        <div className="lg:col-span-2 rounded-xl border bg-card p-5">
          <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-4 font-semibold">
            Tendance mensuelle
          </p>
          {mergedMonthly.length > 1 ? (
            <ResponsiveContainer width="100%" height={220}>
              <ComposedChart data={mergedMonthly} margin={{ top: 8, right: 16, bottom: 0, left: -8 }}>
                <defs>
                  <linearGradient id="ratingAreaGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%"  stopColor={COLORS.current} stopOpacity={0.18}/>
                    <stop offset="95%" stopColor={COLORS.current} stopOpacity={0}/>
                  </linearGradient>
                  {compareActive && (
                    <linearGradient id="ratingAreaCmpGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%"  stopColor={COLORS.compare} stopOpacity={0.12}/>
                      <stop offset="95%" stopColor={COLORS.compare} stopOpacity={0}/>
                    </linearGradient>
                  )}
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
                <XAxis
                  dataKey="label" tick={{ fontSize: 10, fill: COLORS.axis }}
                  tickLine={false} axisLine={false}
                />
                {/* Left Y: avg_rating 0–5 */}
                <YAxis
                  yAxisId="left" domain={[0, 5]} ticks={[0, 1, 2, 3, 4, 5]}
                  tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false}
                />
                {/* Right Y: count */}
                <YAxis
                  yAxisId="right" orientation="right"
                  tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false}
                />
                <Tooltip content={<ChartTip />} />
                <Legend wrapperStyle={{ fontSize: 11, paddingTop: 8 }} />

                {/* Count bars (right axis, grey) */}
                <Bar yAxisId="right" dataKey="count" name="Avis"
                  fill={COLORS.axis} opacity={0.18} radius={[2, 2, 0, 0]} />

                {/* avg_rating area (left axis) */}
                <Area yAxisId="left" type="monotone" dataKey="avg" name="Moy."
                  stroke={COLORS.current} strokeWidth={2}
                  fill="url(#ratingAreaGrad)" dot={false} />

                {/* Comparison avg line (if active) */}
                {compareActive && (
                  <Line yAxisId="left" type="monotone" dataKey="avgCmp" name="Moy. préc."
                    stroke={COLORS.compare} strokeWidth={1.5} strokeDasharray="4 3"
                    dot={false} />
                )}

                {/* Reference line at 4.8 */}
                <ReferenceLine yAxisId="left" y={4.8} stroke={COLORS.basket}
                  strokeDasharray="4 3" strokeWidth={1.5}
                  label={{ value: '4.8★', position: 'insideTopRight', fontSize: 10, fill: COLORS.basket }} />
              </ComposedChart>
            </ResponsiveContainer>
          ) : (
            <p className="text-xs text-muted-foreground py-12 text-center">Pas assez de données</p>
          )}
        </div>

        {/* Distribution */}
        <div className="rounded-xl border bg-card p-5">
          <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-4 font-semibold">
            Distribution des notes
          </p>
          <DistributionBars distribution={distribution ?? {}} total={total_rated ?? 0} />
          {modeStar && (
            <p className="text-xs text-muted-foreground mt-4">
              Note la plus fréquente : <strong className="text-foreground">{modeStar}★</strong>
            </p>
          )}
        </div>
      </div>

      {/* Charts Row 2: Weekday + Lead time */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {/* By weekday */}
        <div className="rounded-xl border bg-card p-5">
          <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-4 font-semibold">
            Avis par jour de la semaine
          </p>
          {weekdayChart.some(d => d.count > 0) ? (
            <ResponsiveContainer width="100%" height={180}>
              <BarChart data={weekdayChart} margin={{ top: 4, right: 8, bottom: 0, left: -20 }}>
                <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
                <XAxis dataKey="label" tick={{ fontSize: 11, fill: COLORS.axis }} tickLine={false} axisLine={false} />
                <YAxis tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false} />
                <Tooltip content={({ active, payload, label }) => {
                  if (!active || !payload?.length) return null
                  const d = payload[0]?.payload
                  return (
                    <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs space-y-0.5">
                      <p className="font-medium">{label}</p>
                      <p style={{ color: COLORS.current }}>{d.count} avis</p>
                      {d.avg_rating > 0 && <p className="text-muted-foreground">Moy. : {d.avg_rating.toFixed(2)}★</p>}
                    </div>
                  )
                }} />
                <Bar dataKey="count" name="Avis" fill={COLORS.current} radius={[3, 3, 0, 0]} opacity={0.85} />
              </BarChart>
            </ResponsiveContainer>
          ) : (
            <p className="text-xs text-muted-foreground py-10 text-center">Pas assez de données</p>
          )}
          {weekdayChart.some(d => d.count > 0) && (
            <p className="text-xs text-muted-foreground mt-2">
              Pic : <strong className="text-foreground">{peakWeekday}</strong>
            </p>
          )}
        </div>

        {/* Lead time histogram */}
        <div className="rounded-xl border bg-card p-5">
          <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-4 font-semibold">
            Délai excursion → avis
          </p>
          {avg_lead_time_days != null && (
            <p className="text-xs text-muted-foreground mb-3">
              Délai moyen : <strong className="text-foreground">{Math.round(avg_lead_time_days)} jours</strong>
            </p>
          )}
          {leadTimeChart.some(d => d.count > 0) ? (
            <ResponsiveContainer width="100%" height={160}>
              <BarChart data={leadTimeChart} margin={{ top: 4, right: 8, bottom: 0, left: -20 }}>
                <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
                <XAxis dataKey="bucket" tick={{ fontSize: 11, fill: COLORS.axis }} tickLine={false} axisLine={false} />
                <YAxis tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false} />
                <Tooltip content={({ active, payload, label }) => {
                  if (!active || !payload?.length) return null
                  return (
                    <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs">
                      <p className="font-medium">{label}</p>
                      <p style={{ color: COLORS.basket }}>{payload[0].value} avis</p>
                    </div>
                  )
                }} />
                <Bar dataKey="count" name="Avis" fill={COLORS.basket} radius={[3, 3, 0, 0]} opacity={0.85} />
              </BarChart>
            </ResponsiveContainer>
          ) : (
            <p className="text-xs text-muted-foreground py-10 text-center">Pas assez de données</p>
          )}
        </div>
      </div>
    </div>
  )
}

// ── Tab: Produits ──────────────────────────────────────────────────────────────

/**
 * Products tab — table + two horizontal bar charts.
 */
function ProductsTab({ stats, compareStats, compareActive }) {
  if (!stats?.by_product?.length) {
    return (
      <div className="rounded-xl border bg-card px-6 py-16 text-center">
        <p className="text-sm text-muted-foreground">Aucune donnée produit disponible.</p>
      </div>
    )
  }

  const { by_product, total } = stats
  const top8 = by_product.slice(0, 8)

  // Color by avg_rating
  const ratingColor = avg => {
    if (avg >= 4) return '#10b981'  // emerald
    if (avg >= 3) return '#f59e0b'  // amber
    return '#ef4444'                // red
  }

  // Compare map by product name
  const cmpMap = useMemo(() => {
    if (!compareActive || !compareStats?.by_product) return {}
    return Object.fromEntries(compareStats.by_product.map(p => [p.product_name, p]))
  }, [compareActive, compareStats])

  const columns = [
    {
      key: 'product_name', label: 'Produit',
      render: r => (
        <span className="text-sm font-medium max-w-[200px] truncate block" title={r.product_name}>
          {r.product_name || '—'}
        </span>
      ),
    },
    {
      key: 'count', label: 'Avis', sortable: true,
      render: r => (
        <div className="flex items-center gap-2">
          <span className="font-bold tabular-nums">{r.count}</span>
          {compareActive && cmpMap[r.product_name] && (
            <Delta current={r.count} previous={cmpMap[r.product_name].count} />
          )}
        </div>
      ),
    },
    {
      key: 'avg_rating', label: 'Moy.', sortable: true,
      render: r => (
        <div className="flex items-center gap-2">
          <span className="font-medium tabular-nums">{Number(r.avg_rating).toFixed(2)}</span>
          <Stars value={Math.round(r.avg_rating)} size={11} />
          {compareActive && cmpMap[r.product_name] && (
            <Delta current={r.avg_rating} previous={cmpMap[r.product_name].avg_rating} />
          )}
        </div>
      ),
    },
    {
      key: 'pct', label: '% total',
      render: r => {
        const pct = total ? Math.round((r.count / total) * 100) : 0
        return (
          <div className="flex items-center gap-2">
            <div className="h-1.5 w-16 rounded-full bg-muted overflow-hidden">
              <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${pct}%` }} />
            </div>
            <span className="text-xs tabular-nums text-muted-foreground">{pct}%</span>
          </div>
        )
      },
    },
  ]

  return (
    <div className="space-y-5">
      {/* Horizontal bar chart: count by product */}
      <div className="rounded-xl border bg-card p-5">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-4 font-semibold">
          Volume d'avis par produit (top 8)
        </p>
        <ResponsiveContainer width="100%" height={Math.max(180, top8.length * 36)}>
          <BarChart data={top8} layout="vertical" margin={{ top: 4, right: 40, bottom: 0, left: 8 }}>
            <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} horizontal={false} />
            <XAxis type="number" tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false} />
            <YAxis type="category" dataKey="product_name" width={140}
              tick={{ fontSize: 11, fill: COLORS.axis }} tickLine={false} axisLine={false}
              tickFormatter={v => v?.length > 22 ? v.slice(0, 22) + '…' : v}
            />
            <Tooltip content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const d = payload[0]?.payload
              return (
                <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs space-y-0.5 max-w-[200px]">
                  <p className="font-medium break-words">{label}</p>
                  <p style={{ color: ratingColor(d.avg_rating) }}>{d.count} avis · {Number(d.avg_rating).toFixed(2)}★</p>
                </div>
              )
            }} />
            <Bar dataKey="count" name="Avis" radius={[0, 3, 3, 0]}>
              {top8.map((entry, i) => (
                <Cell key={i} fill={ratingColor(entry.avg_rating)} opacity={0.82} />
              ))}
            </Bar>
          </BarChart>
        </ResponsiveContainer>
        <div className="flex items-center gap-4 mt-3 text-[11px] text-muted-foreground">
          <span className="flex items-center gap-1"><span className="w-3 h-2 rounded-sm inline-block bg-emerald-500 opacity-80" /> ≥ 4.0★</span>
          <span className="flex items-center gap-1"><span className="w-3 h-2 rounded-sm inline-block bg-amber-500 opacity-80" /> ≥ 3.0★</span>
          <span className="flex items-center gap-1"><span className="w-3 h-2 rounded-sm inline-block bg-red-500 opacity-80" /> &lt; 3.0★</span>
        </div>
      </div>

      {/* Horizontal bar chart: avg_rating by product */}
      <div className="rounded-xl border bg-card p-5">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-4 font-semibold">
          Note moyenne par produit (top 8, trié par note)
        </p>
        <ResponsiveContainer width="100%" height={Math.max(180, top8.length * 36)}>
          <BarChart
            data={[...top8].sort((a, b) => b.avg_rating - a.avg_rating)}
            layout="vertical"
            margin={{ top: 4, right: 40, bottom: 0, left: 8 }}
          >
            <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} horizontal={false} />
            <XAxis type="number" domain={[0, 5]} ticks={[0,1,2,3,4,5]}
              tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false} />
            <YAxis type="category" dataKey="product_name" width={140}
              tick={{ fontSize: 11, fill: COLORS.axis }} tickLine={false} axisLine={false}
              tickFormatter={v => v?.length > 22 ? v.slice(0, 22) + '…' : v}
            />
            <ReferenceLine x={4.8} stroke={COLORS.basket} strokeDasharray="4 3" strokeWidth={1.5} />
            <Tooltip content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const d = payload[0]?.payload
              return (
                <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs max-w-[200px]">
                  <p className="font-medium break-words">{label}</p>
                  <p style={{ color: ratingColor(d.avg_rating) }}>{Number(d.avg_rating).toFixed(2)}★ ({d.count} avis)</p>
                </div>
              )
            }} />
            <Bar dataKey="avg_rating" name="Moy." radius={[0, 3, 3, 0]}>
              {[...top8].sort((a, b) => b.avg_rating - a.avg_rating).map((entry, i) => (
                <Cell key={i} fill={ratingColor(entry.avg_rating)} opacity={0.82} />
              ))}
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* Products table */}
      <div className="rounded-xl border bg-card overflow-hidden">
        <div className="px-5 py-3 border-b">
          <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-semibold">
            Détail par produit
          </p>
        </div>
        <Table columns={columns} data={by_product} empty="Aucun produit." />
      </div>
    </div>
  )
}

// ── Tab: Comportement ──────────────────────────────────────────────────────────

/**
 * Behavior tab — weekday details + lead time details + insights.
 */
function BehaviorTab({ stats }) {
  if (!stats) return null

  const { by_weekday, lead_time_buckets, avg_lead_time_days } = stats

  const weekdayChart = useMemo(() => {
    const raw = by_weekday ?? []
    return [1, 2, 3, 4, 5, 6, 0].map(wd => {
      const found = raw.find(d => d.weekday === wd)
      return {
        label:      FR_WEEKDAYS[wd],
        count:      found ? parseInt(found.count)        || 0 : 0,
        avg_rating: found ? parseFloat(found.avg_rating) || 0 : 0,
      }
    })
  }, [by_weekday])

  const leadTimeChart = useMemo(() => {
    const raw = lead_time_buckets ?? []
    return LEAD_BUCKETS.map(b => {
      const found = raw.find(d => d.bucket === b)
      return { bucket: b, count: found ? parseInt(found.count) || 0 : 0 }
    })
  }, [lead_time_buckets])

  const totalWeekday = weekdayChart.reduce((s, d) => s + d.count, 0)
  const peakWd = weekdayChart.length ? weekdayChart.reduce((a, b) => b.count > a.count ? b : a) : null

  const majorBucket = useMemo(() => {
    if (!leadTimeChart.some(d => d.count > 0)) return null
    return leadTimeChart.reduce((a, b) => b.count > a.count ? b : a)
  }, [leadTimeChart])

  return (
    <div className="space-y-5">
      {/* Insight banner */}
      {(peakWd || majorBucket) && (
        <div className="rounded-xl border border-primary/20 bg-primary/5 px-5 py-4 flex flex-wrap gap-6">
          {peakWd && peakWd.count > 0 && (
            <div>
              <p className="text-[11px] text-muted-foreground uppercase tracking-wider">Jour de pic</p>
              <p className="text-sm font-semibold mt-0.5">
                Le <strong>{peakWd.label}</strong> ({peakWd.count} avis, moy. {peakWd.avg_rating.toFixed(2)}★)
              </p>
            </div>
          )}
          {majorBucket && (
            <div>
              <p className="text-[11px] text-muted-foreground uppercase tracking-wider">Délai dominant</p>
              <p className="text-sm font-semibold mt-0.5">
                La majorité des avis sont déposés <strong>{majorBucket.bucket}</strong> après l'excursion
              </p>
            </div>
          )}
          {avg_lead_time_days != null && (
            <div>
              <p className="text-[11px] text-muted-foreground uppercase tracking-wider">Délai moyen</p>
              <p className="text-sm font-semibold mt-0.5"><strong>{Math.round(avg_lead_time_days)} jours</strong></p>
            </div>
          )}
        </div>
      )}

      {/* Weekday chart full */}
      <div className="rounded-xl border bg-card p-5">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-1 font-semibold">
          Répartition par jour de la semaine
        </p>
        <p className="text-xs text-muted-foreground mb-4">
          Jour auquel les clients déposent leurs avis
        </p>
        {weekdayChart.some(d => d.count > 0) ? (
          <ResponsiveContainer width="100%" height={220}>
            <ComposedChart data={weekdayChart} margin={{ top: 8, right: 24, bottom: 0, left: -8 }}>
              <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
              <XAxis dataKey="label" tick={{ fontSize: 11, fill: COLORS.axis }} tickLine={false} axisLine={false} />
              <YAxis yAxisId="left" tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false} />
              <YAxis yAxisId="right" orientation="right" domain={[0, 5]} ticks={[0,1,2,3,4,5]}
                tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false} />
              <Tooltip content={({ active, payload, label }) => {
                if (!active || !payload?.length) return null
                const d = payload[0]?.payload
                return (
                  <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs space-y-1">
                    <p className="font-medium">{label}</p>
                    <p style={{ color: COLORS.current }}>{d.count} avis ({totalWeekday ? Math.round((d.count/totalWeekday)*100) : 0}%)</p>
                    {d.avg_rating > 0 && <p className="text-muted-foreground">Note moy. : {d.avg_rating.toFixed(2)}★</p>}
                  </div>
                )
              }} />
              <Legend wrapperStyle={{ fontSize: 11 }} />
              <Bar yAxisId="left" dataKey="count" name="Avis" fill={COLORS.current} radius={[3,3,0,0]} opacity={0.85} />
              <Line yAxisId="right" type="monotone" dataKey="avg_rating" name="Note moy."
                stroke={COLORS.basket} strokeWidth={2} dot={{ r: 3, fill: COLORS.basket }} />
            </ComposedChart>
          </ResponsiveContainer>
        ) : (
          <p className="text-xs text-muted-foreground py-12 text-center">Pas assez de données</p>
        )}
      </div>

      {/* Lead time full */}
      <div className="rounded-xl border bg-card p-5">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-1 font-semibold">
          Délai entre excursion et dépôt d'avis
        </p>
        <p className="text-xs text-muted-foreground mb-4">
          Distribution du temps écoulé entre la date d'événement et la date d'évaluation
        </p>
        {leadTimeChart.some(d => d.count > 0) ? (
          <ResponsiveContainer width="100%" height={200}>
            <BarChart data={leadTimeChart} margin={{ top: 4, right: 16, bottom: 0, left: -8 }}>
              <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
              <XAxis dataKey="bucket" tick={{ fontSize: 12, fill: COLORS.axis }} tickLine={false} axisLine={false} />
              <YAxis tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false} />
              <Tooltip content={({ active, payload, label }) => {
                if (!active || !payload?.length) return null
                const d = payload[0]?.payload
                const tot = leadTimeChart.reduce((s, x) => s + x.count, 0)
                const pct = tot ? Math.round((d.count / tot) * 100) : 0
                return (
                  <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs">
                    <p className="font-medium">{label}</p>
                    <p style={{ color: COLORS.basket }}>{d.count} avis · {pct}%</p>
                  </div>
                )
              }} />
              <Bar dataKey="count" name="Avis" fill={COLORS.basket} radius={[3,3,0,0]} opacity={0.85}>
                {leadTimeChart.map((entry, i) => (
                  <Cell key={i} fill={entry.bucket === majorBucket?.bucket ? COLORS.basket : COLORS.axis} opacity={0.7} />
                ))}
              </Bar>
            </BarChart>
          </ResponsiveContainer>
        ) : (
          <p className="text-xs text-muted-foreground py-12 text-center">Pas assez de données</p>
        )}
      </div>
    </div>
  )
}

// ── Page principale ────────────────────────────────────────────────────────────

export default function Reviews() {
  // ── Period ───────────────────────────────────────────────────────────────────
  const [from, setFrom] = useState(() => monthsAgo(12))
  const [to,   setTo]   = useState(() => today())
  const [compareActive, setCompareActive] = useState(false)

  const comparePeriod = useMemo(() => computeComparePeriod(from, to), [from, to])

  // ── Stats ────────────────────────────────────────────────────────────────────
  const [stats,          setStats]          = useState(null)
  const [compareStats,   setCompareStats]   = useState(null)
  const [statsLoading,   setStatsLoading]   = useState(true)

  // ── List ─────────────────────────────────────────────────────────────────────
  const [data,         setData]         = useState([])
  const [total,        setTotal]        = useState(0)
  const [loading,      setLoading]      = useState(true)
  const [error,        setError]        = useState(null)
  const [page,         setPage]         = useState(1)
  const [q,            setQ]            = useState('')
  const [search,       setSearch]       = useState('')
  const [product,      setProduct]      = useState('')
  const [ratingFilter, setRatingFilter] = useState('')
  const [sort,         setSort]         = useState({ key: 'review_date', dir: 'desc' })
  const [expanded,     setExpanded]     = useState(null)

  // ── UI ───────────────────────────────────────────────────────────────────────
  const [showImporter, setShowImporter] = useState(false)
  const [resetting,    setResetting]    = useState(false)
  const [activeTab,    setActiveTab]    = useState('overview')
  const perPage = 50

  // ── Loaders ──────────────────────────────────────────────────────────────────

  const loadStats = useCallback(() => {
    setStatsLoading(true)
    const params = { from, to }
    api.avisStats(params)
      .then(s => {
        setStats(s)
        // Load compare stats if active
        if (compareActive) {
          return api.avisStats({ from: comparePeriod.from, to: comparePeriod.to })
            .then(setCompareStats)
            .catch(() => setCompareStats(null))
        } else {
          setCompareStats(null)
        }
      })
      .catch(() => {})
      .finally(() => setStatsLoading(false))
  }, [from, to, compareActive, comparePeriod.from, comparePeriod.to])

  const load = useCallback(() => {
    setLoading(true)
    api.avis({
      page,
      per_page: perPage,
      search:  search  || undefined,
      product: product || undefined,
      rating:  ratingFilter || undefined,
      from,
      to,
      sort: sort.key,
      dir:  sort.dir.toUpperCase(),
    })
      .then(r => { setData(r.data ?? []); setTotal(r.total ?? 0) })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [page, search, product, ratingFilter, from, to, sort])

  useEffect(() => { loadStats() }, [loadStats])
  useEffect(() => { load() },      [load])

  // Debounce search input
  useEffect(() => {
    const t = setTimeout(() => { setSearch(q); setPage(1) }, 400)
    return () => clearTimeout(t)
  }, [q])

  // Reset page on filter changes
  const handlePeriodChange = useCallback((f, t) => {
    setFrom(f); setTo(t); setPage(1)
  }, [])

  const onSort = key => setSort(s => ({
    key, dir: s.key === key && s.dir === 'asc' ? 'desc' : 'asc',
  }))

  const handleReset = async () => {
    if (!confirm('Supprimer tous les avis importés ? Cette action est irréversible.')) return
    setResetting(true)
    try {
      await api.resetAvis()
      setData([]); setTotal(0); setStats(null); setCompareStats(null)
      loadStats()
    } catch (e) {
      setError(e.message)
    } finally {
      setResetting(false)
    }
  }

  const handleImportDone = useCallback(() => {
    setShowImporter(false); load(); loadStats()
  }, [load, loadStats])

  const products = useMemo(() => stats?.products ?? [], [stats])

  // ── Columns ──────────────────────────────────────────────────────────────────

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
        <div className="max-w-[260px]">
          {r.review_title && <p className="text-sm font-medium truncate">{r.review_title}</p>}
          {r.review_body && (
            <p
              className={`text-xs text-muted-foreground cursor-pointer ${expanded === r.id ? '' : 'line-clamp-2'}`}
              onClick={e => { e.stopPropagation(); setExpanded(expanded === r.id ? null : r.id) }}
            >
              {r.review_body}
            </p>
          )}
          {r.response && (
            <p className="text-xs text-primary/70 mt-0.5 line-clamp-1 italic">↩ {r.response}</p>
          )}
        </div>
      ),
    },
    {
      key: 'review_date', label: 'Date avis', sortable: true,
      render: r => <span className="text-xs text-muted-foreground whitespace-nowrap">{fmtDate(r.review_date)}</span>,
    },
    {
      key: 'order_number', label: 'N° commande',
      render: r => <span className="text-xs text-muted-foreground font-mono">{r.order_number}</span>,
    },
  ]

  // ── Render ───────────────────────────────────────────────────────────────────

  return (
    <div>
      {/* ── Page Header ───────────────────────────────────────────────────────── */}
      <PageHeader
        title="Avis clients"
        subtitle="Import CSV Regiondo · analyse des avis"
        actions={
          <div className="flex items-center gap-2 flex-wrap">
            <Btn
              variant={showImporter ? 'primary' : 'secondary'}
              size="sm"
              onClick={() => setShowImporter(v => !v)}
            >
              {showImporter ? 'Masquer import' : 'Importer CSV'}
            </Btn>
            <Btn variant="secondary" size="sm" onClick={() => buildExportCsv(data)} disabled={!data.length}>
              <Download className="w-4 h-4" /> Export
            </Btn>
            <Btn variant="ghost" size="sm" onClick={() => { load(); loadStats() }} title="Rafraîchir">
              <RefreshDouble className="w-4 h-4" />
            </Btn>
            <Btn variant="danger" size="sm" onClick={handleReset} loading={resetting} disabled={!total}>
              <Trash className="w-4 h-4" /> Réinitialiser
            </Btn>
            <span className="text-xs text-muted-foreground">{total.toLocaleString('fr-FR')} avis</span>
          </div>
        }
      />

      {/* ── Import zone (collapsible) ──────────────────────────────────────────── */}
      {showImporter && (
        <div className="mx-6 mt-5 rounded-xl border bg-card p-5">
          <p className="text-sm font-medium mb-1">Import CSV Regiondo — Avis clients</p>
          <p className="text-xs text-muted-foreground mb-4">
            Exportez vos avis depuis Regiondo et importez-les ici.
            Colonnes requises : N° de commande, Évaluation (note), évaluation (texte).
          </p>
          <ReviewsImporter onDone={handleImportDone} />
        </div>
      )}

      {error && <div className="mx-6 mt-5"><Notice type="error">{error}</Notice></div>}

      {/* ── Analytics Section ──────────────────────────────────────────────────── */}
      <div className="mx-6 mt-6">

        {/* Period + Compare toolbar */}
        <div className="flex items-center gap-3 flex-wrap mb-4">
          <PeriodPicker from={from} to={to} onChange={handlePeriodChange} />
          <div className="h-4 w-px bg-border mx-1 hidden sm:block" />
          <button
            type="button"
            onClick={() => setCompareActive(v => !v)}
            className={
              `px-3 py-1.5 rounded-md text-xs font-medium border transition-colors ` +
              (compareActive
                ? 'border-primary bg-primary/10 text-primary'
                : 'border-border bg-card text-muted-foreground hover:text-foreground hover:border-foreground/40')
            }
          >
            <span className="flex items-center gap-1.5">
              <CandlestickChart className="w-3.5 h-3.5" />
              VS période précédente
            </span>
          </button>
          {compareActive && (
            <span className="text-[11px] text-muted-foreground hidden sm:block">
              Comparé à : {fmtDate(comparePeriod.from)} → {fmtDate(comparePeriod.to)}
            </span>
          )}
        </div>

        {/* Analytics Tabs */}
        {statsLoading && !stats ? (
          <div className="flex items-center justify-center py-20 rounded-xl border bg-card">
            <Spinner size={20} />
          </div>
        ) : (
          <Tabs value={activeTab} onValueChange={setActiveTab}>
            <div className="flex items-center justify-between mb-4 flex-wrap gap-3">
              <TabsList>
                <TabsTrigger value="overview">
                  <Star className="w-3.5 h-3.5 mr-1.5" /> Vue d'ensemble
                </TabsTrigger>
                <TabsTrigger value="products">
                  <Group className="w-3.5 h-3.5 mr-1.5" /> Produits
                </TabsTrigger>
                <TabsTrigger value="behavior">
                  <CandlestickChart className="w-3.5 h-3.5 mr-1.5" /> Comportement
                </TabsTrigger>
              </TabsList>
            </div>

            <TabsContent value="overview">
              <OverviewTab
                stats={stats}
                compareStats={compareStats}
                compareActive={compareActive}
                from={from}
                to={to}
              />
            </TabsContent>

            <TabsContent value="products">
              <ProductsTab
                stats={stats}
                compareStats={compareStats}
                compareActive={compareActive}
              />
            </TabsContent>

            <TabsContent value="behavior">
              <BehaviorTab stats={stats} />
            </TabsContent>
          </Tabs>
        )}
      </div>

      {/* ── Reviews Table ──────────────────────────────────────────────────────── */}
      <div className="mx-6 mt-8">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-semibold mb-3">
          Liste des avis
        </p>

        {/* Filters */}
        <div className="flex items-center gap-2 flex-wrap mb-3">
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

          <span className="text-xs text-muted-foreground ml-auto">
            {total.toLocaleString('fr-FR')} avis
          </span>
        </div>

        {/* Table */}
        <div className="rounded-lg border overflow-hidden">
          {loading
            ? <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
            : (
              <>
                <Table
                  columns={columns}
                  data={data}
                  empty="Aucun avis. Importez un CSV Regiondo pour commencer."
                  sortKey={sort.key}
                  sortDir={sort.dir}
                  onSort={onSort}
                />
                <Pagination page={page} total={total} perPage={perPage} onChange={setPage} />
              </>
            )
          }
        </div>
      </div>

      {/* Bottom spacing */}
      <div className="h-10" />
    </div>
  )
}
