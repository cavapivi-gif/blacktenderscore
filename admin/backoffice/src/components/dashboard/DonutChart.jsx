import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from 'recharts'
import { COLORS } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'

/**
 * Donut chart with legend — for channel breakdown, payment methods, etc.
 */
export function DonutChart({ data = [], title, nameKey = 'name', valueKey = 'value', colors = COLORS.palette, showEmpty = false }) {
  if (!data.length) {
    if (!showEmpty) return null
    return (
      <div className="rounded-lg border bg-card p-5">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium mb-4">{title}</p>
        <p className="text-sm text-muted-foreground py-6 text-center">Aucune donnée disponible</p>
      </div>
    )
  }

  const total = data.reduce((s, d) => s + (d[valueKey] || 0), 0)

  return (
    <div className="rounded-lg border bg-card p-5">
      <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium mb-4">{title}</p>
      <div className="flex items-center gap-6">
        {/* Donut */}
        <div className="w-[120px] h-[120px] shrink-0">
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Pie
                data={data}
                dataKey={valueKey}
                nameKey={nameKey}
                cx="50%"
                cy="50%"
                innerRadius={32}
                outerRadius={52}
                paddingAngle={2}
                strokeWidth={0}
              >
                {data.map((_, i) => (
                  <Cell key={i} fill={colors[i % colors.length]} />
                ))}
              </Pie>
              <Tooltip
                content={({ active, payload }) => {
                  if (!active || !payload?.length) return null
                  const item = payload[0]
                  const pct = total > 0 ? Math.round((item.value / total) * 100) : 0
                  return (
                    <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs">
                      <p className="font-medium">{item.name}</p>
                      <p className="mt-1 tabular-nums">{fmtNum(item.value)} <span className="text-muted-foreground">({pct}%)</span></p>
                    </div>
                  )
                }}
              />
            </PieChart>
          </ResponsiveContainer>
        </div>
        {/* Legend */}
        <div className="flex-1 space-y-1.5 min-w-0">
          {data.slice(0, 6).map((d, i) => {
            const pct = total > 0 ? Math.round((d[valueKey] / total) * 100) : 0
            return (
              <div key={i} className="flex items-center gap-2">
                <span className="w-2 h-2 rounded-full shrink-0" style={{ background: colors[i % colors.length] }} />
                <span className="text-xs truncate flex-1">{d[nameKey]}</span>
                <span className="text-xs font-medium tabular-nums shrink-0">{pct}%</span>
              </div>
            )
          })}
          {data.length > 6 && (
            <p className="text-[10px] text-muted-foreground">+{data.length - 6} autres</p>
          )}
        </div>
      </div>
    </div>
  )
}
