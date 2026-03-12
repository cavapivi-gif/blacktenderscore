import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'
import { COLORS } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'

/**
 * 24h booking distribution — when do customers book?
 */
export function BookingHoursChart({ data = [] }) {
  if (!data.length) return null

  // Ensure all 24 hours present
  const hours = Array.from({ length: 24 }, (_, i) => {
    const found = data.find(d => d.hour === i)
    return { hour: i, label: `${i}h`, bookings: found?.bookings ?? 0 }
  })

  return (
    <div className="rounded-lg border bg-card p-5">
      <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium mb-4">
        Heures de réservation
      </p>
      <ResponsiveContainer width="100%" height={160}>
        <BarChart data={hours} margin={{ top: 4, right: 4, left: -28, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
          <XAxis
            dataKey="label"
            tick={{ fontSize: 9, fill: COLORS.axis }}
            axisLine={false}
            tickLine={false}
            interval={2}
          />
          <YAxis allowDecimals={false} tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              return (
                <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs">
                  <p className="font-medium">{label}</p>
                  <p className="mt-1 tabular-nums">{fmtNum(payload[0]?.value ?? 0)} réservations</p>
                </div>
              )
            }}
          />
          <Bar dataKey="bookings" fill={COLORS.current} fillOpacity={0.7} radius={[2, 2, 0, 0]} />
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}
