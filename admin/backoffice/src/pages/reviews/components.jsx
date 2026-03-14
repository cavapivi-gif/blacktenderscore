import { COLORS } from '../../lib/constants'
import { delta } from '../../lib/utils'

// ── Inline components ─────────────────────────────────────────────────────────

/**
 * SVG star row — filled amber if i <= value, grey outline otherwise.
 * @param {{ value: number, size?: number }} props
 */
export function Stars({ value, size = 12 }) {
  if (!value) return <span className="text-muted-foreground text-xs">—</span>
  return (
    <span className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map(i => (
        <svg key={i} width={size} height={size} viewBox="0 0 24 24"
          fill={i <= value ? '#f59e0b' : 'none'}
          stroke={i <= value ? '#f59e0b' : '#d1d5db'} strokeWidth="1.5"
          strokeLinecap="round" strokeLinejoin="round">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
      ))}
    </span>
  )
}

/**
 * Large numeric rating with label.
 * @param {{ value: number|null }} props
 */
export function BigRating({ value }) {
  if (!value) return <span className="text-3xl font-bold text-muted-foreground">—</span>
  return (
    <div className="flex items-end gap-2">
      <span className="text-3xl font-bold tabular-nums">{Number(value).toFixed(1)}</span>
      <span className="text-sm text-muted-foreground mb-1">/ 5</span>
    </div>
  )
}

/**
 * Delta badge — green for positive delta, red for negative.
 * @param {{ current: number, previous: number|null, invert?: boolean }} props
 */
export function Delta({ current, previous, invert = false }) {
  const pct = delta(current, previous)
  if (pct == null) return null
  const isGood  = invert ? pct <= 0 : pct >= 0
  const sign    = pct > 0 ? '+' : ''
  return (
    <span
      className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold tabular-nums"
      style={{
        background: isGood ? COLORS.delta_pos_bg  : COLORS.delta_neg_bg,
        color:      isGood ? COLORS.delta_pos_text : COLORS.delta_neg_text,
      }}
    >
      {sign}{pct}%
    </span>
  )
}

/**
 * KPI card for the analytics overview row.
 */
export function KpiCard({ label, value, sub, compare, invert = false }) {
  return (
    <div className="px-6 py-5">
      <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-1">{label}</p>
      <div className="flex items-center gap-2 flex-wrap">
        <div className="text-2xl font-bold tabular-nums">{value ?? '—'}</div>
        {compare !== undefined && <Delta current={compare.current} previous={compare.previous} invert={invert} />}
      </div>
      {sub && <p className="text-xs text-muted-foreground mt-0.5">{sub}</p>}
    </div>
  )
}

/**
 * Recharts tooltip — card style matching the design system.
 */
export function ChartTip({ active, payload, label }) {
  if (!active || !payload?.length) return null
  return (
    <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs space-y-0.5 min-w-[110px]">
      <p className="font-medium text-muted-foreground">{label}</p>
      {payload.map(p => (
        <p key={p.dataKey} style={{ color: p.stroke ?? p.fill ?? p.color }}>
          {p.name} : <strong>{typeof p.value === 'number' ? Number(p.value).toFixed(2) : p.value}</strong>
        </p>
      ))}
    </div>
  )
}

/**
 * Distribution bars (5 -> 1).
 */
export function DistributionBars({ distribution, total }) {
  if (!total) return <p className="text-xs text-muted-foreground">Aucune note disponible</p>
  const max = Math.max(...Object.values(distribution), 1)
  return (
    <div className="space-y-1.5 w-full">
      {[5, 4, 3, 2, 1].map(star => {
        const count = distribution[star] ?? 0
        const pct   = total ? Math.round((count / total) * 100) : 0
        return (
          <div key={star} className="flex items-center gap-2 text-xs">
            <span className="w-3 text-right text-muted-foreground shrink-0">{star}</span>
            <svg width="10" height="10" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" strokeWidth="1.5">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <div className="flex-1 h-1.5 rounded-full bg-muted overflow-hidden">
              <div className="h-full rounded-full bg-amber-400 transition-all" style={{ width: `${(count / max) * 100}%` }} />
            </div>
            <span className="w-6 text-right tabular-nums text-muted-foreground">{count}</span>
            <span className="w-8 text-right tabular-nums text-muted-foreground/60">{pct}%</span>
          </div>
        )
      })}
    </div>
  )
}
