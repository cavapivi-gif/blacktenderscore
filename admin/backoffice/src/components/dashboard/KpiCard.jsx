import { cn } from '../../lib/utils'
import { COLORS } from '../../lib/constants'
import { Sparkline } from './Sparkline'

/**
 * Bloomberg-grade KPI card.
 * Monochrome base — color ONLY on delta badges.
 */
export function KpiCard({
  label,
  value,
  sub,
  delta: d,
  invertDelta = false,
  sparkData,
  sparkColor,
  alert = false,
  active = false,
  onClick,
  className,
}) {
  const isNeutral = d == null
  const visualDelta = invertDelta && d != null ? -d : d
  const isPositive = visualDelta != null && visualDelta >= 0

  return (
    <div
      role={onClick ? 'button' : undefined}
      tabIndex={onClick ? 0 : undefined}
      onClick={onClick}
      onKeyDown={onClick ? e => (e.key === 'Enter' || e.key === ' ') && onClick() : undefined}
      style={active ? { borderColor: '#e3e1db' } : undefined}
      className={cn(
        'rounded-lg border bg-card p-4 space-y-1.5 transition-all',
        onClick ? 'cursor-pointer hover:shadow-md select-none' : 'hover:shadow-sm',
        active && 'shadow-sm',
        alert && !active && 'ring-1 ring-red-200',
        className,
      )}
    >
      <div className="flex items-center justify-between">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">{label}</p>
        {sparkData && <Sparkline data={sparkData} color={sparkColor || COLORS.current} w={56} h={20} />}
      </div>
      <div className="flex items-end justify-between gap-2">
        <p className="text-2xl font-bold tabular-nums leading-none tracking-tight">{value}</p>
        {!isNeutral && (
          <span className={cn(
            'text-[11px] font-semibold px-1.5 py-0.5 rounded tabular-nums',
            isPositive
              ? `bg-[${COLORS.delta_pos_bg}] text-[${COLORS.delta_pos_text}]`
              : `bg-[${COLORS.delta_neg_bg}] text-[${COLORS.delta_neg_text}]`,
          )} style={{
            backgroundColor: isPositive ? COLORS.delta_pos_bg : COLORS.delta_neg_bg,
            color: isPositive ? COLORS.delta_pos_text : COLORS.delta_neg_text,
          }}>
            {isPositive ? '+' : ''}{d}%
          </span>
        )}
      </div>
      {sub && <p className="text-[11px] text-muted-foreground truncate">{sub}</p>}
    </div>
  )
}

/**
 * Compact KPI — single line, for secondary metrics.
 */
export function KpiCompact({ label, value, delta: d, invertDelta = false }) {
  const visualDelta = invertDelta && d != null ? -d : d
  const isPositive = visualDelta != null && visualDelta >= 0

  return (
    <div className="flex items-center justify-between py-2 px-3 rounded-md border bg-card">
      <span className="text-xs text-muted-foreground">{label}</span>
      <div className="flex items-center gap-2">
        <span className="text-sm font-semibold tabular-nums">{value}</span>
        {d != null && (
          <span className="text-[10px] font-semibold tabular-nums" style={{
            color: isPositive ? COLORS.delta_pos_text : COLORS.delta_neg_text,
          }}>
            {isPositive ? '+' : ''}{d}%
          </span>
        )}
      </div>
    </div>
  )
}
