import { memo } from 'react'
import { DAYS, fmtCol, cellColor } from './heatmap-utils'

/**
 * Grille col \u00d7 jour pour une seule s\u00e9rie.
 */
export const HeatmapGrid = memo(function HeatmapGrid({ grid, cols, maxVal, label, accentColor, palette, unit, isNormalized }) {
  const visible = cols.slice(-12)
  if (!visible.length) return null

  const periodLabel = visible.length > 1
    ? `${fmtCol(visible[0])} \u2013 ${fmtCol(visible[visible.length - 1])}`
    : fmtCol(visible[0])

  return (
    <div className="min-w-0 flex-1">
      {label && (
        <div className="flex items-center gap-2 mb-2">
          <span className="w-2.5 h-2.5 rounded-sm shrink-0" style={{ background: accentColor }} />
          <span className="text-[11px] font-semibold" style={{ color: accentColor }}>{label}</span>
          <span className="text-[9px] text-muted-foreground">{periodLabel}</span>
        </div>
      )}
      <div className="w-full">
        <div className="flex w-full gap-0.5">
          <div className="flex flex-col gap-0.5 mr-1 shrink-0 justify-end">
            {DAYS.map(d => (
              <div key={d} className="flex items-center" style={{ height: 'calc((100% - 14px) / 7)' }}>
                <span className="text-[9px] text-muted-foreground w-6 leading-none">{d}</span>
              </div>
            ))}
            <div className="h-3.5" />
          </div>
          {visible.map(col => (
            <div key={col} className="flex flex-col gap-0.5 flex-1 min-w-[14px]">
              {DAYS.map((day, dayIdx) => {
                const val = grid[`${col}-${dayIdx}`] || 0
                const tip = isNormalized
                  ? `${fmtCol(col)} \u00b7 ${day} : ${val}%`
                  : `${fmtCol(col)} \u00b7 ${day} : ${val} ${unit}`
                return (
                  <div
                    key={dayIdx}
                    className="w-full aspect-square rounded-[2px] transition-colors cursor-default hover:ring-1 hover:ring-offset-1 hover:ring-current"
                    style={{ backgroundColor: cellColor(val, maxVal, palette) }}
                    title={tip}
                  />
                )
              })}
              <span className="text-[8px] text-muted-foreground text-center mt-0.5 truncate leading-none">
                {fmtCol(col)}
              </span>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
})
