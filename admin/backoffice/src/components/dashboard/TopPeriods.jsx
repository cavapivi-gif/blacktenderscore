import { useState, useMemo, useEffect, useCallback } from 'react'
import { api } from '../../lib/api'
import { fmtNum, cn } from '../../lib/utils'

const COLOR_CANCEL = '#dc2626'
const COLOR_BOOK   = '#0a0a0a'

const GRAN = [
  { key: 'month', label: 'Mois' },
  { key: 'week',  label: 'Sem.' },
  { key: 'day',   label: 'Jour' },
]

/**
 * Classement des périodes par volume d'annulations ou de réservations.
 * Mini-filtre granularité M/S/J intégré. Fetche à la demande si la granularité change.
 *
 * @param {string}  from               Date de début (YYYY-MM-DD)
 * @param {string}  to                 Date de fin
 * @param {Array}   initialData        Données initiales (chartData du parent)
 * @param {string}  [initialGranularity] Granularité initiale des données passées
 */
export function TopPeriods({ from, to, initialData, initialGranularity = 'month' }) {
  const [gran,    setGran]    = useState(initialGranularity)
  const [rawData, setRawData] = useState(null)
  const [loading, setLoading] = useState(false)
  const [tab,     setTab]     = useState('bookings')

  const doFetch = useCallback(async (g) => {
    setLoading(true)
    try {
      const res = await api.bookingsStats({ from, to, granularity: g })
      setRawData(res.chart ?? res.monthly ?? [])
    } catch {}
    finally { setLoading(false) }
  }, [from, to])

  useEffect(() => {
    if (gran === initialGranularity && initialData?.length) {
      setRawData(initialData)
    } else {
      doFetch(gran)
    }
  }, [gran, from, to])

  // Re-sync quand les données parentes changent (filtre période)
  useEffect(() => {
    if (gran === initialGranularity) setRawData(initialData ?? null)
  }, [initialData])

  const sorted = useMemo(() => {
    if (!rawData?.length) return []
    const enriched = rawData.map(p => ({
      ...p,
      cancelRate: p.bookings > 0 ? Math.round(((p.cancelled ?? 0) / p.bookings) * 1000) / 10 : 0,
    }))
    const key = tab === 'cancel' ? 'cancelled' : 'bookings'
    return [...enriched]
      .filter(p => (p[key] ?? 0) > 0)
      .sort((a, b) => (b[key] ?? 0) - (a[key] ?? 0))
      .slice(0, 10)
  }, [rawData, tab])

  const maxVal = sorted[0]
    ? (tab === 'cancel' ? sorted[0].cancelled : sorted[0].bookings) ?? 1
    : 1

  return (
    <div className="rounded-lg border bg-card p-5">

      {/* Header */}
      <div className="flex items-start justify-between mb-3 gap-2">
        <div>
          <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">
            Top périodes
          </p>
          <p className="text-[10px] text-muted-foreground/60 mt-0.5">
            {tab === 'cancel'
              ? 'Où les annulations sont les plus fortes'
              : 'Où les réservations sont les plus fortes'}
          </p>
        </div>

        {/* Granularity filter */}
        <div className="flex items-center gap-0.5 shrink-0">
          {GRAN.map(({ key, label }) => (
            <button
              key={key}
              onClick={() => setGran(key)}
              className={cn(
                'px-2 py-0.5 rounded text-[10px] font-medium transition-colors',
                gran === key
                  ? 'bg-foreground text-background'
                  : 'text-muted-foreground hover:text-foreground hover:bg-accent',
              )}
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 mb-4">
        <button
          onClick={() => setTab('cancel')}
          className={cn(
            'flex-1 py-1.5 rounded text-[10px] font-medium transition-colors border',
            tab === 'cancel'
              ? 'bg-red-600 text-white border-red-600'
              : 'text-muted-foreground border-border hover:text-foreground hover:bg-accent',
          )}
        >
          Annulations
        </button>
        <button
          onClick={() => setTab('bookings')}
          className={cn(
            'flex-1 py-1.5 rounded text-[10px] font-medium transition-colors border',
            tab === 'bookings'
              ? 'bg-foreground text-background border-foreground'
              : 'text-muted-foreground border-border hover:text-foreground hover:bg-accent',
          )}
        >
          Réservations
        </button>
      </div>

      {/* Liste */}
      {loading ? (
        <div className="space-y-2">
          {[1,2,3,4,5].map(i => (
            <div key={i} className="h-5 rounded bg-muted/60 animate-pulse" />
          ))}
        </div>
      ) : sorted.length === 0 ? (
        <p className="text-[11px] text-muted-foreground text-center py-4">Aucune donnée</p>
      ) : (
        <div className="space-y-2">
          {sorted.map((p, i) => {
            const val    = tab === 'cancel' ? (p.cancelled ?? 0) : (p.bookings ?? 0)
            const pct    = maxVal > 0 ? (val / maxVal) * 100 : 0
            const barCol = tab === 'cancel' ? COLOR_CANCEL : COLOR_BOOK
            return (
              <div key={p.key ?? i} className="flex items-center gap-2">
                <span className="text-[10px] text-muted-foreground w-4 tabular-nums text-right shrink-0">
                  {i + 1}
                </span>
                <span className="text-[11px] font-medium min-w-0 w-20 shrink-0 truncate" title={p.label}>
                  {p.label}
                </span>
                <div className="flex-1 h-1.5 bg-muted rounded-full overflow-hidden">
                  <div
                    className="h-full rounded-full transition-all"
                    style={{ width: `${pct}%`, background: barCol }}
                  />
                </div>
                <span className="text-[11px] font-semibold tabular-nums w-6 text-right shrink-0">
                  {fmtNum(val)}
                </span>
                {tab === 'cancel' && (
                  <span className="text-[10px] text-red-500 tabular-nums w-9 text-right shrink-0">
                    {p.cancelRate}%
                  </span>
                )}
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}
