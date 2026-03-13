/**
 * UnifiedTimeline — Graphique dual-axe combinant GA4 + données de réservations.
 * Axe gauche : Sessions ou Utilisateurs (area verte).
 * Axe droit  : CA ou Réservations (line ambrée).
 * Données fusionnées par date clé.
 */
import { useState, useMemo } from 'react'
import {
  ComposedChart, Area, Line,
  XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid, Legend,
} from 'recharts'
import { C_CURRENT, C_BOOKINGS, C_REVENUE, C_COMPARE, C_GRID, C_AXIS, fmtDate, fmtNum } from './analyticsUtils'
import { fmtCurrency } from '../../lib/utils'

// Métriques disponibles avec leur config d'affichage
const LEFT_METRICS = [
  { key: 'sessions',    label: 'Sessions',     color: C_CURRENT  },
  { key: 'activeUsers', label: 'Utilisateurs', color: C_COMPARE  },
]
const RIGHT_METRICS = [
  { key: 'revenue',  label: 'CA',           color: C_REVENUE  },
  { key: 'bookings', label: 'Réservations', color: C_BOOKINGS },
]

/**
 * @param {Array}  ga4Timeline    - Timeline GA4 [{date, sessions, activeUsers}]
 * @param {Array}  bookingPeriods - Périodes réservations [{key, bookings, revenue}]
 * @param {string} from
 * @param {string} to
 */
export function UnifiedTimeline({ ga4Timeline, bookingPeriods, from, to }) {
  const [leftMetric,  setLeftMetric]  = useState('sessions')
  const [rightMetric, setRightMetric] = useState('revenue')

  const hasGA4      = ga4Timeline?.length > 0
  const hasBookings = bookingPeriods?.length > 0

  // Fusionner les deux sources par date
  const mergedData = useMemo(() => {
    if (!hasGA4 && !hasBookings) return []

    // Construire un map de dates pour GA4
    const ga4Map = {}
    ;(ga4Timeline ?? []).forEach(d => {
      ga4Map[d.date] = { sessions: d.sessions ?? 0, activeUsers: d.activeUsers ?? 0 }
    })

    // Construire un map de dates pour les réservations
    // Les périodes bookings utilisent la clé `key` (YYYY-MM-DD quand granularity=day)
    const bookMap = {}
    ;(bookingPeriods ?? []).forEach(p => {
      bookMap[p.key] = { bookings: p.bookings ?? 0, revenue: p.revenue ?? 0 }
    })

    // Réunir toutes les dates
    const allDates = [...new Set([...Object.keys(ga4Map), ...Object.keys(bookMap)])].sort()

    return allDates.map(date => ({
      date,
      sessions:    ga4Map[date]?.sessions    ?? null,
      activeUsers: ga4Map[date]?.activeUsers ?? null,
      bookings:    bookMap[date]?.bookings   ?? null,
      revenue:     bookMap[date]?.revenue    ?? null,
    }))
  }, [ga4Timeline, bookingPeriods, hasGA4, hasBookings])

  if (!hasGA4 && !hasBookings) return null

  const leftMeta  = LEFT_METRICS.find(m => m.key === leftMetric)
  const rightMeta = RIGHT_METRICS.find(m => m.key === rightMetric)

  const showLeft  = hasGA4
  const showRight = hasBookings

  return (
    <div className="rounded-lg border bg-card p-5 space-y-3">
      {/* En-tête + toggles */}
      <div className="flex flex-wrap items-center justify-between gap-3">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">
          Timeline unifiée
        </p>
        <div className="flex items-center gap-2">
          {/* Toggles axe gauche */}
          {hasGA4 && (
            <div className="flex items-center gap-0.5 rounded border border-border bg-muted/30 p-0.5">
              {LEFT_METRICS.map(m => (
                <button
                  key={m.key}
                  onClick={() => setLeftMetric(m.key)}
                  className={`px-2 py-0.5 text-[10px] font-medium rounded transition-colors ${
                    leftMetric === m.key
                      ? 'bg-card text-foreground shadow-sm'
                      : 'text-muted-foreground hover:text-foreground'
                  }`}
                >
                  {m.label}
                </button>
              ))}
            </div>
          )}
          {/* Toggles axe droit */}
          {hasBookings && (
            <div className="flex items-center gap-0.5 rounded border border-border bg-muted/30 p-0.5">
              {RIGHT_METRICS.map(m => (
                <button
                  key={m.key}
                  onClick={() => setRightMetric(m.key)}
                  className={`px-2 py-0.5 text-[10px] font-medium rounded transition-colors ${
                    rightMetric === m.key
                      ? 'bg-card text-foreground shadow-sm'
                      : 'text-muted-foreground hover:text-foreground'
                  }`}
                >
                  {m.label}
                </button>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Chart */}
      <ResponsiveContainer width="100%" height={230}>
        <ComposedChart data={mergedData} margin={{ top: 4, right: showRight ? 40 : 8, left: -20, bottom: 0 }}>
          <defs>
            <linearGradient id="unified-left" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%"  stopColor={leftMeta?.color ?? C_CURRENT} stopOpacity={0.18} />
              <stop offset="95%" stopColor={leftMeta?.color ?? C_CURRENT} stopOpacity={0} />
            </linearGradient>
          </defs>

          <CartesianGrid stroke={C_GRID} vertical={false} />

          <XAxis
            dataKey="date"
            tick={{ fontSize: 10, fill: C_AXIS }}
            tickFormatter={fmtDate}
            interval="preserveStartEnd"
          />

          {/* Axe gauche — GA4 */}
          <YAxis
            yAxisId="left"
            tick={{ fontSize: 10, fill: C_AXIS }}
            tickFormatter={v => fmtNum(v)}
            width={40}
          />

          {/* Axe droit — Réservations/CA */}
          {showRight && (
            <YAxis
              yAxisId="right"
              orientation="right"
              tick={{ fontSize: 10, fill: C_AXIS }}
              tickFormatter={v => rightMetric === 'revenue' ? `${Math.round(v / 1000)}k€` : fmtNum(v)}
              width={44}
            />
          )}

          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              return (
                <div className="rounded-lg border bg-white shadow-md px-3 py-2 text-xs space-y-1">
                  <p className="font-medium text-muted-foreground">{fmtDate(label)}</p>
                  {payload.map((p, i) => (
                    <p key={i} style={{ color: p.color }}>
                      {p.name} : <span className="font-semibold">
                        {p.dataKey === 'revenue'
                          ? fmtCurrency(p.value)
                          : fmtNum(p.value)}
                      </span>
                    </p>
                  ))}
                </div>
              )
            }}
          />

          {/* Area GA4 — axe gauche */}
          {showLeft && (
            <Area
              yAxisId="left"
              type="monotone"
              dataKey={leftMetric}
              name={leftMeta?.label}
              stroke={leftMeta?.color ?? C_CURRENT}
              fill="url(#unified-left)"
              strokeWidth={2}
              dot={false}
              connectNulls
            />
          )}

          {/* Line réservations — axe droit */}
          {showRight && (
            <Line
              yAxisId="right"
              type="monotone"
              dataKey={rightMetric}
              name={rightMeta?.label}
              stroke={rightMeta?.color ?? C_BOOKINGS}
              strokeWidth={2}
              dot={false}
              connectNulls
            />
          )}
        </ComposedChart>
      </ResponsiveContainer>

      {/* Légende manuelle */}
      <div className="flex items-center gap-4 text-[10px] text-muted-foreground">
        {showLeft && (
          <span className="flex items-center gap-1">
            <span className="inline-block w-3 h-0.5 rounded" style={{ background: leftMeta?.color ?? C_CURRENT }} />
            {leftMeta?.label} (axe gauche)
          </span>
        )}
        {showRight && (
          <span className="flex items-center gap-1">
            <span className="inline-block w-3 h-0.5 rounded" style={{ background: rightMeta?.color ?? C_BOOKINGS }} />
            {rightMeta?.label} (axe droit)
          </span>
        )}
      </div>
    </div>
  )
}
