import { BarChart, Bar, XAxis, YAxis, Tooltip, LabelList, ResponsiveContainer } from 'recharts'
import { COLORS } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'

const BUCKET_ORDER = ['Jour J', '1-7j', '8-30j', '31-90j', '+90j']

/**
 * Distribution de l'avance de réservation — combien de jours avant l'activité
 * les clients commandent-ils ? Données issues de DATEDIFF(appointment_date, created_at).
 *
 * @param {Array}  data     - Buckets de la période principale [{bucket, bookings, avg_days}]
 * @param {Array}  dataCmp  - Buckets de la période de comparaison (optionnel, active les barres groupées)
 */
export function LeadTimeChart({ data = [], dataCmp = null }) {
  const hasCompare = dataCmp && dataCmp.length > 0

  const totalCur = data.reduce((s, d) => s + Number(d.bookings), 0)
  const totalCmp = hasCompare ? dataCmp.reduce((s, d) => s + Number(d.bookings), 0) : 0

  if (!totalCur && !totalCmp) return null

  const buckets = BUCKET_ORDER.map(b => {
    const cur  = data.find(d => d.bucket === b)
    const cmp  = dataCmp?.find(d => d.bucket === b)
    const curN = cur ? Number(cur.bookings) : 0
    const cmpN = cmp ? Number(cmp.bookings) : 0
    return {
      bucket:       b,
      bookings:     curN,
      bookings_cmp: cmpN,
      pct:     totalCur > 0 ? Math.round((curN / totalCur) * 100) : 0,
      pct_cmp: totalCmp > 0 ? Math.round((cmpN / totalCmp) * 100) : 0,
      avg_days:     cur?.avg_days ?? null,
      avg_days_cmp: cmp?.avg_days ?? null,
    }
  }).filter(b => b.bookings > 0 || b.bookings_cmp > 0)

  // Height adapts to number of buckets × number of bars
  const rowH  = hasCompare ? 22 : 28
  const chartH = Math.max(80, buckets.length * rowH * (hasCompare ? 2 : 1))

  return (
    <div className="rounded-lg border bg-card p-5">
      <div className="mb-4">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">
          Avance de réservation
        </p>
        <p className="text-[10px] text-muted-foreground mt-0.5">
          Délai entre commande et date d'activité · {fmtNum(totalCur)} rés.
          {hasCompare && <span className="ml-1 opacity-60">vs {fmtNum(totalCmp)}</span>}
        </p>
      </div>

      <ResponsiveContainer width="100%" height={chartH}>
        <BarChart
          data={buckets}
          layout="vertical"
          barCategoryGap={hasCompare ? '20%' : '30%'}
          barGap={2}
          margin={{ top: 0, right: 44, left: 0, bottom: 0 }}
        >
          <XAxis type="number" hide />
          <YAxis
            type="category" dataKey="bucket" width={50}
            tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false}
          />
          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const item = buckets.find(b => b.bucket === label)
              return (
                <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs space-y-1">
                  <p className="font-medium">{label}</p>
                  {payload.map(p => {
                    const isCmp = p.dataKey === 'bookings_cmp'
                    const pct   = isCmp ? item?.pct_cmp : item?.pct
                    const days  = isCmp ? item?.avg_days_cmp : item?.avg_days
                    return (
                      <div key={p.dataKey}>
                        {hasCompare && (
                          <span className="inline-block w-2 h-2 rounded-full mr-1.5 align-middle"
                            style={{ background: p.fill }} />
                        )}
                        <span className="tabular-nums">{fmtNum(p.value ?? 0)} rés.</span>
                        <span className="text-muted-foreground ml-1">({pct ?? 0}%)</span>
                        {days != null && <span className="text-muted-foreground ml-1">· moy. {days}j</span>}
                      </div>
                    )
                  })}
                </div>
              )
            }}
          />

          {/* Barre principale */}
          <Bar dataKey="bookings" fill={COLORS.current} fillOpacity={0.8} radius={[0, 3, 3, 0]}
               name="Période">
            {!hasCompare && (
              <LabelList
                dataKey="pct"
                position="right"
                formatter={v => `${v}%`}
                style={{ fontSize: 10, fill: COLORS.axis }}
              />
            )}
          </Bar>

          {/* Barre comparaison */}
          {hasCompare && (
            <Bar dataKey="bookings_cmp" fill={COLORS.grid} fillOpacity={1} radius={[0, 3, 3, 0]}
                 name="Comparaison">
              <LabelList
                dataKey="pct_cmp"
                position="right"
                formatter={v => `${v}%`}
                style={{ fontSize: 10, fill: COLORS.axis }}
              />
            </Bar>
          )}
        </BarChart>
      </ResponsiveContainer>

      {/* Légende inline */}
      {hasCompare && (
        <div className="flex items-center gap-4 mt-3 justify-end">
          <span className="flex items-center gap-1.5 text-[10px] text-muted-foreground">
            <span className="inline-block w-2.5 h-2.5 rounded-sm" style={{ background: COLORS.current, opacity: 0.8 }} />
            Période
          </span>
          <span className="flex items-center gap-1.5 text-[10px] text-muted-foreground">
            <span className="inline-block w-2.5 h-2.5 rounded-sm" style={{ background: COLORS.grid }} />
            Comparaison
          </span>
        </div>
      )}
    </div>
  )
}
