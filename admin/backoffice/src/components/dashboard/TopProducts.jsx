import { useMemo } from 'react'
import { COLORS } from '../../lib/constants'
import { fmtNum, fmtCurrency, fmtShort } from '../../lib/utils'

/**
 * Top products ranked list with revenue + horizontal bars.
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
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">Top produits</p>
        {filterParams && (
          <span className="text-[11px] text-muted-foreground">
            {fmtShort(filterParams.from)} – {fmtShort(filterParams.to)}
          </span>
        )}
      </div>
      <div className="space-y-3">
        {data.map((p, i) => {
          const barPct = data[0]?.count > 0 ? Math.round((p.count / data[0].count) * 100) : 0
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
                  <div className="h-full rounded-full bg-primary transition-all duration-500"
                    style={{ width: `${barPct}%` }} />
                </div>
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}
