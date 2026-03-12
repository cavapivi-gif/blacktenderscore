import { cn, fmtShort } from '../../lib/utils'
import { COLORS } from '../../lib/constants'

/**
 * Enriched recharts tooltip — current + comparison + delta + basket overlay.
 */
export function ChartTooltip({ active, payload, label, suffix = '', compareFrom = '', compareTo = '' }) {
  if (!active || !payload?.length) return null
  const fmt = v => suffix ? `${Number(v).toLocaleString('fr-FR')}${suffix}` : Number(v).toLocaleString('fr-FR')

  const currP = payload.find(p => !p.dataKey?.includes('_prev') && !p.dataKey?.includes('basket'))
  const prevP = payload.find(p => p.dataKey?.includes('_prev'))
  const basketP = payload.find(p => p.dataKey?.includes('basket'))
  const curr = currP?.value ?? 0
  const prev = prevP?.value
  const basket = basketP?.value
  const d = prev != null && prev > 0 ? Math.round(((curr - prev) / prev) * 100) : null

  const prevRange = compareFrom && compareTo
    ? `${fmtShort(compareFrom)} – ${fmtShort(compareTo)}`
    : null

  return (
    <div className="rounded-md border bg-card shadow-md px-3 py-2.5 text-xs min-w-[180px]">
      <p className="font-medium mb-2 text-foreground">{label}</p>
      <div className="space-y-1">
        <div className="flex items-center justify-between gap-4">
          <span className="flex items-center gap-1.5 text-muted-foreground">
            <span className="w-3 h-0.5 rounded-full" style={{ background: currP?.color ?? COLORS.current }} />
            Actuel
          </span>
          <strong className="tabular-nums">{fmt(curr)}</strong>
        </div>
        {prev != null && (
          <div className="flex items-center justify-between gap-4 text-muted-foreground">
            <span className="flex items-center gap-1.5">
              <span className="w-3 inline-block" style={{ borderTop: `1.5px dashed ${COLORS.compare}` }} />
              Préc.
            </span>
            <span className="tabular-nums">{fmt(prev)}</span>
          </div>
        )}
        {prevRange && <p className="text-[9px] text-muted-foreground/60 pl-[18px]">{prevRange}</p>}
        {basket != null && (
          <div className="flex items-center justify-between gap-4 mt-1 pt-1 border-t">
            <span className="flex items-center gap-1.5" style={{ color: COLORS.basket }}>
              <span className="w-3 h-0.5 rounded-full" style={{ background: COLORS.basket }} />
              Panier moy.
            </span>
            <span className="tabular-nums font-medium" style={{ color: COLORS.basket }}>
              {Number(basket).toLocaleString('fr-FR')} €
            </span>
          </div>
        )}
      </div>
      {d !== null && (
        <div className={cn(
          'mt-2 pt-1.5 border-t text-center text-[10px] font-bold',
          d >= 0 ? 'text-emerald-600' : 'text-red-500',
        )}>
          {d >= 0 ? '↑' : '↓'} {Math.abs(d)}% vs période préc.
        </div>
      )}
    </div>
  )
}

/**
 * Simple tooltip for bar/donut charts.
 */
export function SimpleTooltip({ active, payload, label, formatter }) {
  if (!active || !payload?.length) return null
  const v = payload[0]?.value ?? 0
  return (
    <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs">
      <p className="font-medium">{label}</p>
      <p className="mt-1 tabular-nums">{formatter ? formatter(v) : v}</p>
    </div>
  )
}
