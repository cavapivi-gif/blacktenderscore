import { useMemo } from 'react'
import {
  ComposedChart, BarChart, Bar,
  XAxis, YAxis, CartesianGrid, Tooltip, Legend,
  ResponsiveContainer, Cell, Line,
} from 'recharts'
import { COLORS } from '../../lib/constants'
import { FR_WEEKDAYS, LEAD_BUCKETS } from './helpers'

/**
 * Behavior tab — weekday details + lead time details + insights.
 */
export default function BehaviorTab({ stats }) {
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
