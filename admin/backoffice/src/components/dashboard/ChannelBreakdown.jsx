import { COLORS } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'

/**
 * Horizontal bar breakdown — channels, products, etc.
 * Bloomberg-style: monochrome bars with colored dots.
 */
export function ChannelBreakdown({ data = [], title, totalBookings = 0 }) {
  if (!data.length) return null

  const maxCnt = data[0]?.bookings ?? 1

  return (
    <div className="rounded-lg border bg-card p-5">
      <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium mb-4">{title}</p>
      <div className="space-y-2.5">
        {data.map((c, i) => {
          const barPct = Math.round((c.bookings / maxCnt) * 100)
          const totalPct = totalBookings > 0 ? Math.round((c.bookings / totalBookings) * 100) : 0
          return (
            <div key={i} className="flex items-center gap-3">
              <span className="w-2 h-2 rounded-full shrink-0" style={{ background: COLORS.palette[i % COLORS.palette.length] }} />
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between gap-2 mb-1">
                  <span className="text-xs truncate">{c.channel}</span>
                  <div className="flex items-center gap-2 shrink-0">
                    <span className="text-xs font-medium tabular-nums">{fmtNum(c.bookings)}</span>
                    <span className="text-[10px] text-muted-foreground tabular-nums w-8 text-right">{totalPct}%</span>
                  </div>
                </div>
                <div className="h-1.5 rounded-full bg-muted overflow-hidden">
                  <div className="h-full rounded-full transition-all duration-500"
                    style={{ width: `${barPct}%`, background: COLORS.palette[i % COLORS.palette.length] }} />
                </div>
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}
