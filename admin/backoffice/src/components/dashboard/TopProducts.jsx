import { useMemo } from 'react'
import { COLORS, CHART_INFO } from '../../lib/constants'

// Palette multi-couleurs pour les barres (cohérente avec les autres charts)
const BAR_PALETTE = ['#10b981','#6366f1','#f59e0b','#0ea5e9','#ec4899','#8b5cf6','#f97316','#64748b','#14b8a6','#a855f7']
import { fmtNum, fmtCurrency, fmtShort } from '../../lib/utils'
import { InfoTooltip } from './InfoTooltip'

/**
 * Recommandations automatiques basées sur la concentration des réservations.
 */
function PlanDAction({ data, totalCount }) {
  const tips = useMemo(() => {
    if (!data.length || !totalCount) return []
    const result = []
    const topPct = Math.round((data[0].count / totalCount) * 100)

    if (topPct > 60) {
      result.push(`"${data[0].name}" concentre ${topPct}% de vos réservations — risque de dépendance. Envisagez de promouvoir les autres prestations.`)
    } else if (topPct > 40) {
      result.push(`"${data[0].name}" domine à ${topPct}%. Renforcer les produits #2 et #3 pourrait diversifier l'activité.`)
    }

    if (data.length >= 3) {
      const top3Count = data.slice(0, 3).reduce((s, p) => s + p.count, 0)
      const top3Pct = Math.round((top3Count / totalCount) * 100)
      if (top3Pct > 85) {
        result.push(`Vos 3 premiers produits représentent ${top3Pct}% de l'activité. Les autres prestations sont peu visibles — une mise en avant ciblée pourrait rééquilibrer les ventes.`)
      }
    }

    if (data.length >= 2) {
      const last = data[data.length - 1]
      const lastPct = Math.round((last.count / totalCount) * 100)
      if (lastPct <= 2 && data.length > 3) {
        result.push(`"${last.name}" représente ${lastPct}% des réservations. Envisagez de le retirer du catalogue ou de le relancer avec une promotion.`)
      }
    }

    return result
  }, [data, totalCount])

  if (!tips.length) return null

  return (
    <div className="mt-4 pt-3 border-t">
      <p className="text-[10px] text-muted-foreground uppercase tracking-wider font-medium mb-2 inline-flex items-center">
        Plan d'action <InfoTooltip text={CHART_INFO.top_products_action} />
      </p>
      <div className="space-y-1.5">
        {tips.map((tip, i) => (
          <p key={i} className="text-[11px] text-muted-foreground flex items-start gap-1.5">
            <span className="text-amber-500 shrink-0 leading-tight mt-px">→</span>
            {tip}
          </p>
        ))}
      </div>
    </div>
  )
}

/**
 * Top produits classés par volume de réservations + plan d'action contextuel.
 */
export function TopProducts({ data = [], filterParams }) {
  const totalCount = useMemo(
    () => data.reduce((s, p) => s + p.count, 0),
    [data],
  )

  if (!data.length) return null

  return (
    <div className="rounded-lg border bg-card p-5">
      <div className="flex items-center justify-between mb-4">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium inline-flex items-center">
          Top produits <InfoTooltip text={CHART_INFO.top_products} />
        </p>
        {filterParams && (
          <span className="text-[11px] text-muted-foreground">
            {fmtShort(filterParams.from)} – {fmtShort(filterParams.to)}
          </span>
        )}
      </div>
      <div className="space-y-3">
        {data.map((p, i) => {
          const barPct   = data[0]?.count > 0 ? Math.round((p.count / data[0].count) * 100) : 0
          const totalPct = totalCount > 0 ? Math.round((p.count / totalCount) * 100) : 0
          return (
            <div key={i} className="flex items-center gap-3">
              <span className="text-xs text-muted-foreground w-4 shrink-0 tabular-nums font-medium">{i + 1}</span>
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between gap-2 mb-1">
                  <span className="text-xs truncate">{p.name}</span>
                  <div className="flex items-center gap-3 shrink-0">
                    {p.revenue > 0 && (
                      <span className="text-[10px] font-medium tabular-nums" style={{ color: COLORS.current }}>
                        {fmtCurrency(p.revenue)}
                      </span>
                    )}
                    <span className="text-xs font-medium tabular-nums">{fmtNum(p.count)}</span>
                    <span className="text-[10px] text-muted-foreground tabular-nums w-8 text-right">{totalPct}%</span>
                  </div>
                </div>
                <div className="h-1.5 rounded-full bg-muted overflow-hidden">
                  <div
                    className="h-full rounded-full transition-all duration-500"
                    style={{ width: `${barPct}%`, background: BAR_PALETTE[i % BAR_PALETTE.length] }}
                  />
                </div>
              </div>
            </div>
          )
        })}
      </div>

      <PlanDAction data={data} totalCount={totalCount} />
    </div>
  )
}
