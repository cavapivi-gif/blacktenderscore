import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Cell, ResponsiveContainer } from 'recharts'
import { COLORS } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'

/**
 * 24h booking distribution — EU 24h format, peak hour highlighted.
 */
export function BookingHoursChart({ data = [] }) {
  if (!data.length) return null

  // Ensure all 24 hours present, EU 24h format (00h–23h)
  const hours = Array.from({ length: 24 }, (_, i) => {
    const found = data.find(d => d.hour === i)
    return { hour: i, label: `${String(i).padStart(2, '0')}h`, bookings: found?.bookings ?? 0 }
  })

  const total = hours.reduce((s, h) => s + h.bookings, 0)
  const peak  = hours.reduce((m, h) => h.bookings > m.bookings ? h : m, hours[0])

  return (
    <div className="rounded-lg border bg-card p-5">
      <div className="flex items-center justify-between mb-4">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">
          Heures de réservation
        </p>
        {peak.bookings > 0 && (
          <span className="text-[11px] text-muted-foreground tabular-nums">
            Pic : <span className="font-semibold text-foreground">{peak.label}</span>
          </span>
        )}
      </div>
      <ResponsiveContainer width="100%" height={150}>
        <BarChart data={hours} margin={{ top: 4, right: 4, left: -28, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
          <XAxis
            dataKey="label"
            tick={{ fontSize: 9, fill: COLORS.axis }}
            axisLine={false}
            tickLine={false}
            interval={3}
          />
          <YAxis allowDecimals={false} tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const v = payload[0]?.value ?? 0
              const pct = total > 0 ? Math.round((v / total) * 100) : 0
              return (
                <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs">
                  <p className="font-medium">{label}</p>
                  <p className="mt-1 tabular-nums">{fmtNum(v)} rés. <span className="text-muted-foreground">({pct}%)</span></p>
                </div>
              )
            }}
          />
          <Bar dataKey="bookings" radius={[2, 2, 0, 0]}>
            {hours.map((h, i) => (
              <Cell
                key={i}
                fill={COLORS.current}
                fillOpacity={h.hour === peak.hour && peak.bookings > 0 ? 1 : 0.5}
              />
            ))}
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}
