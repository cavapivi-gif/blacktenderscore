import { useMemo } from 'react'
import { COLORS } from '../../lib/constants'

const DAYS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
// MySQL DOW: 1=Dim, 2=Lun ... 7=Sam → remap to Mon-first
const DOW_MAP = { 2: 0, 3: 1, 4: 2, 5: 3, 6: 4, 7: 5, 1: 6 }

/**
 * Month × Weekday heatmap — Bloomberg-grade.
 * Data format: [{ month: '2024-01', dow: 2, total: 15 }, ...]
 */
export function HeatmapChart({ data = [] }) {
  const { grid, months, maxVal } = useMemo(() => {
    if (!data.length) return { grid: {}, months: [], maxVal: 1 }

    const monthSet = new Set()
    let maxVal = 1
    const grid = {}

    data.forEach(d => {
      monthSet.add(d.month)
      const dayIdx = DOW_MAP[d.dow] ?? 0
      const key = `${d.month}-${dayIdx}`
      grid[key] = (grid[key] || 0) + d.total
      maxVal = Math.max(maxVal, grid[key])
    })

    const months = [...monthSet].sort()
    return { grid, months, maxVal }
  }, [data])

  if (!months.length) return null

  // Color scale: white → emerald
  function cellColor(val) {
    if (!val) return COLORS.map_empty
    const t = val / maxVal
    if (t < 0.25) return COLORS.map_low
    if (t < 0.5) return COLORS.map_mid
    if (t < 0.75) return COLORS.map_high
    return COLORS.map_peak
  }

  // Trim to last 12 months max for readability
  const visibleMonths = months.slice(-12)

  return (
    <div className="rounded-lg border bg-card p-5">
      <div className="mb-4">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">
          Heatmap réservations
        </p>
        <p className="text-[10px] text-muted-foreground mt-0.5">Mois × jour de semaine</p>
      </div>
      <div className="overflow-x-auto">
        <div className="inline-flex gap-0.5">
          {/* Day labels */}
          <div className="flex flex-col gap-0.5 mr-1 justify-end">
            {DAYS.map(d => (
              <div key={d} className="h-5 flex items-center">
                <span className="text-[9px] text-muted-foreground w-6">{d}</span>
              </div>
            ))}
          </div>
          {/* Grid */}
          {visibleMonths.map(month => (
            <div key={month} className="flex flex-col gap-0.5">
              {DAYS.map((_, dayIdx) => {
                const key = `${month}-${dayIdx}`
                const val = grid[key] || 0
                return (
                  <div
                    key={dayIdx}
                    className="w-5 h-5 rounded-sm transition-colors"
                    style={{ backgroundColor: cellColor(val) }}
                    title={`${month} ${DAYS[dayIdx]}: ${val} rés.`}
                  />
                )
              })}
              <span className="text-[8px] text-muted-foreground text-center mt-0.5 whitespace-nowrap">
                {(([y, m]) => `${['jan','fév','mar','avr','mai','jun','jul','aoû','sep','oct','nov','déc'][+m-1]} ${y.slice(2)}`)(month.split('-'))}
              </span>
            </div>
          ))}
        </div>
      </div>
      {/* Legend */}
      <div className="flex items-center gap-2 mt-3">
        <span className="text-[9px] text-muted-foreground">Moins</span>
        {[COLORS.map_empty, COLORS.map_low, COLORS.map_mid, COLORS.map_high, COLORS.map_peak].map((c, i) => (
          <div key={i} className="w-3 h-3 rounded-sm" style={{ backgroundColor: c }} />
        ))}
        <span className="text-[9px] text-muted-foreground">Plus</span>
      </div>
    </div>
  )
}
