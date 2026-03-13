import { BarChart, Bar, XAxis, YAxis, Tooltip, LabelList, Cell, ResponsiveContainer } from 'recharts'
import { COLORS, CHART_INFO } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'
import { InfoTooltip } from './InfoTooltip'

const BUCKET_ORDER  = ['1 visite', '2 visites', '3-4 visites', '5+ visites (VIP)']
const BUCKET_LABELS = ['Nouveaux', 'Récurrents', 'Fidèles', 'VIP']
const BUCKET_COLORS = ['#e2e8f0', '#94a3b8', '#34d399', COLORS.current]

/** Build normalized buckets array from raw API data */
function buildBuckets(data, total) {
  return BUCKET_ORDER.map((bucket, i) => {
    const found     = data.find(d => d.freq_bucket === bucket)
    const customers = found ? Number(found.customers) : 0
    return {
      bucket:    BUCKET_LABELS[i],
      label:     bucket,
      customers,
      pct:       total > 0 ? Math.round((customers / total) * 100) : 0,
      color:     BUCKET_COLORS[i],
    }
  })
}

/**
 * Répartition de la fidélité client par fréquence de visite.
 *
 * Quand dataCmp est fourni, affiche des barres groupées (période vs comparaison).
 *
 * @param {Array}      data    [{freq_bucket, customers, bookings}]
 * @param {Array|null} dataCmp Données comparaison (même format, optionnel)
 */
export function RepeatChart({ data = [], dataCmp = null }) {
  if (!data.length) return null

  const total = data.reduce((s, d) => s + Number(d.customers), 0)
  if (!total) return null

  const isCompare  = dataCmp?.length > 0
  const totalCmp   = isCompare ? dataCmp.reduce((s, d) => s + Number(d.customers), 0) : 0
  const buckets    = buildBuckets(data, total).filter(b => b.customers > 0 || (isCompare))
  const bucketsCmp = isCompare ? buildBuckets(dataCmp, totalCmp) : []

  // Merge into [{bucket, label, customers, pct, color, customers_cmp, pct_cmp}]
  const merged = buckets.map((b, i) => ({
    ...b,
    customers_cmp: bucketsCmp[i]?.customers ?? null,
    pct_cmp:       bucketsCmp[i]?.pct ?? null,
  })).filter(b => b.customers > 0 || (b.customers_cmp ?? 0) > 0)

  const loyalPct = merged
    .filter(b => b.label !== '1 visite')
    .reduce((s, b) => s + b.pct, 0)

  const chartHeight = Math.max(isCompare ? 100 : 80, merged.length * (isCompare ? 44 : 32))

  return (
    <div className="rounded-lg border bg-card p-5">
      <div className="mb-4">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium inline-flex items-center">
          Fidélité client <InfoTooltip text={CHART_INFO.repeat} />
        </p>
        <p className="text-[10px] text-muted-foreground mt-0.5">
          {fmtNum(total)} clients · {loyalPct}% avec 2+ visites
        </p>
        {isCompare && (
          <div className="flex gap-3 mt-1">
            <span className="flex items-center gap-1 text-[10px] text-muted-foreground">
              <span className="w-2 h-2 rounded-sm shrink-0" style={{ background: COLORS.current }} />
              Période
            </span>
            <span className="flex items-center gap-1 text-[10px] text-muted-foreground">
              <span className="w-2 h-2 rounded-sm shrink-0" style={{ background: COLORS.compare, opacity: 0.75 }} />
              Comparaison
            </span>
          </div>
        )}
      </div>

      <ResponsiveContainer width="100%" height={chartHeight}>
        <BarChart data={merged} layout="vertical" margin={{ top: 0, right: 44, left: 0, bottom: 0 }}>
          <XAxis type="number" hide />
          <YAxis
            type="category" dataKey="bucket" width={58}
            tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false}
          />
          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const item = merged.find(b => b.bucket === label)
              return (
                <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs">
                  <p className="font-medium">{item?.label}</p>
                  <p className="mt-0.5 tabular-nums">
                    {fmtNum(item?.customers ?? 0)} clients
                    <span className="text-muted-foreground ml-1">({item?.pct}%)</span>
                  </p>
                  {item?.customers_cmp != null && (
                    <p className="text-muted-foreground mt-0.5 pt-0.5 border-t">
                      Comparaison : {fmtNum(item.customers_cmp)} <span className="ml-1">({item.pct_cmp}%)</span>
                    </p>
                  )}
                </div>
              )
            }}
          />
          {isCompare ? (
            <>
              <Bar dataKey="customers"     fill={COLORS.current} radius={[0, 3, 3, 0]}>
                <LabelList dataKey="pct"     position="right" formatter={v => `${v}%`} style={{ fontSize: 9, fill: COLORS.axis }} />
              </Bar>
              <Bar dataKey="customers_cmp" fill={COLORS.compare} fillOpacity={0.75} radius={[0, 3, 3, 0]}>
                <LabelList dataKey="pct_cmp" position="right" formatter={v => v != null ? `${v}%` : ''} style={{ fontSize: 9, fill: COLORS.axis }} />
              </Bar>
            </>
          ) : (
            <Bar dataKey="customers" radius={[0, 3, 3, 0]}>
              <LabelList
                dataKey="pct"
                position="right"
                formatter={v => `${v}%`}
                style={{ fontSize: 10, fill: COLORS.axis }}
              />
              {merged.map((b, i) => (
                <Cell key={i} fill={b.color} />
              ))}
            </Bar>
          )}
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}
