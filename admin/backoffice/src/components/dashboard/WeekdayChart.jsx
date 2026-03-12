import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Cell, ResponsiveContainer } from 'recharts'
import { COLORS } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'

/**
 * Weekday distribution bar chart.
 */
export function WeekdayChart({ data = [] }) {
  if (!data.length) return null

  // Reorder Mon→Sun (DOW MySQL: 1=Dim, 2=Lun ... 7=Sam)
  const sorted = [...data].sort((a, b) => {
    const ord = [2, 3, 4, 5, 6, 7, 1]
    return ord.indexOf(a.dow) - ord.indexOf(b.dow)
  })

  const totalWday = data.reduce((s, d) => s + d.bookings, 0)

  return (
    <div className="rounded-lg border bg-card p-5">
      <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium mb-4">Activité par jour</p>
      <ResponsiveContainer width="100%" height={160}>
        <BarChart data={sorted} margin={{ top: 4, right: 4, left: -28, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
          <XAxis dataKey="label" tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <YAxis allowDecimals={false} tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const v = payload[0]?.value ?? 0
              const pct = totalWday > 0 ? Math.round((v / totalWday) * 100) : 0
              return (
                <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs">
                  <p className="font-medium">{label}</p>
                  <p className="mt-1">{fmtNum(v)} rés. <span className="text-muted-foreground">({pct}%)</span></p>
                </div>
              )
            }}
          />
          <Bar dataKey="bookings" radius={[3, 3, 0, 0]}>
            {sorted.map((_, i) => (
              <Cell key={i} fill={COLORS.current} fillOpacity={0.6 + (i % 3) * 0.13} />
            ))}
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}
