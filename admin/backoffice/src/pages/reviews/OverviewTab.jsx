import { useMemo } from 'react'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import {
  ComposedChart, Area, BarChart, Bar,
  XAxis, YAxis, CartesianGrid, Tooltip, Legend,
  ResponsiveContainer, ReferenceLine, Cell, Line,
} from 'recharts'
import { COLORS } from '../../lib/constants'
import { Stars, BigRating, KpiCard, ChartTip, DistributionBars } from './components'
import { FR_WEEKDAYS, LEAD_BUCKETS } from './helpers'

/**
 * Analytics overview tab — KPI row + monthly trend + distribution + weekday + lead time.
 */
export default function OverviewTab({ stats, compareStats, compareActive, from, to }) {
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

  // Overlay comparison monthly data — indexé par clé mois
  const compareMonthlyMap = useMemo(() => {
    if (!compareActive || !compareStats?.monthly) return {}
    const map = {}
    compareStats.monthly.forEach(m => {
      const key = m.month?.slice(0, 7) ?? ''
      if (key) map[key] = { avgCmp: parseFloat(m.avg_rating) || 0, countCmp: parseInt(m.count) || 0 }
    })
    return map
  }, [compareActive, compareStats])

  // Merge monthly + compare par clé mois (pas par index)
  const mergedMonthly = useMemo(() => {
    if (!compareActive) return monthlyChart
    return monthlyChart.map(d => ({
      ...d,
      ...(compareMonthlyMap[d.month] ?? {}),
    }))
  }, [monthlyChart, compareMonthlyMap, compareActive])

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
                {/* Left Y: avg_rating 0-5 */}
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
