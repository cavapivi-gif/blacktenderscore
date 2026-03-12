import { AreaChart, Area, XAxis, YAxis, Tooltip, ReferenceLine, ResponsiveContainer } from 'recharts'
import { COLORS } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'

/**
 * Taux d'annulation sur une période — courbe + ligne de moyenne.
 *
 * Accepte des données pré-calculées : [{label, total, cancelled}]
 * Calcule le rate % (cancelled/total) pour chaque point.
 *
 * @param {Array}  data   [{label: string, total: number, cancelled: number}]
 * @param {number} height Hauteur du graphique (défaut 120)
 */
export function CancellationChart({ data = [], height = 120 }) {
  if (!data.length) return null

  const totalAll  = data.reduce((s, d) => s + (d.total    ?? 0), 0)
  const cancelAll = data.reduce((s, d) => s + (d.cancelled ?? 0), 0)
  if (!totalAll) return null

  const avgRate = Math.round((cancelAll / totalAll) * 100)

  const enriched = data.map(d => ({
    ...d,
    rate: (d.total ?? 0) > 0 ? Math.round(((d.cancelled ?? 0) / d.total) * 100) : 0,
  }))

  // Show dots only for short series (readable at that density)
  const showDots = enriched.length <= 16

  return (
    <div className="rounded-lg border bg-card p-5">
      <div className="mb-4">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">
          Taux d'annulation
        </p>
        <p className="text-[10px] text-muted-foreground mt-0.5">
          {fmtNum(cancelAll)} annulées / {fmtNum(totalAll)} rés.
          <span className="ml-1">· moy. <span className="font-semibold text-foreground">{avgRate}%</span></span>
        </p>
      </div>

      <ResponsiveContainer width="100%" height={height}>
        <AreaChart data={enriched} margin={{ top: 8, right: 8, left: -20, bottom: 0 }}>
          <XAxis
            dataKey="label"
            tick={{ fontSize: 9, fill: COLORS.axis }}
            axisLine={false} tickLine={false}
            interval="preserveStartEnd"
          />
          <YAxis
            tickFormatter={v => `${v}%`}
            tick={{ fontSize: 9, fill: COLORS.axis }}
            axisLine={false} tickLine={false}
            domain={[0, d => Math.max(d, avgRate + 5)]}
            width={32}
          />
          {/* Ligne de moyenne */}
          <ReferenceLine
            y={avgRate}
            stroke={COLORS.axis}
            strokeDasharray="4 2"
            strokeOpacity={0.5}
            label={{ value: `${avgRate}%`, position: 'right', fontSize: 9, fill: COLORS.axis }}
          />
          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const d = payload[0]?.payload
              return (
                <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs">
                  <p className="font-medium">{label}</p>
                  <p className="mt-0.5">
                    <span className="text-red-500 tabular-nums font-medium">{d?.cancelled ?? 0} annulées</span>
                    <span className="text-muted-foreground ml-1">({d?.rate ?? 0}%)</span>
                  </p>
                  <p className="text-muted-foreground">{fmtNum(d?.total ?? 0)} rés. total</p>
                </div>
              )
            }}
          />
          <Area
            dataKey="rate"
            stroke="#ef4444"
            strokeWidth={1.5}
            fill="rgba(239,68,68,0.08)"
            dot={showDots ? { r: 2.5, fill: '#ef4444', strokeWidth: 0 } : false}
            activeDot={{ r: 3.5, fill: '#ef4444', strokeWidth: 0 }}
          />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  )
}
