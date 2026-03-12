import { BarChart, Bar, XAxis, YAxis, Tooltip, Cell, LabelList, ResponsiveContainer } from 'recharts'
import { COLORS } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'

// MySQL DOW: 1=Dim, 2=Lun ... 7=Sam → tri Lun→Dim
const DOW_ORDER = [2, 3, 4, 5, 6, 7, 1]

/**
 * Distribution des réservations par jour de semaine.
 *
 * Accepte deux formats de données :
 *  - API stats  : [{dow: 1-7, label: 'Lun', bookings: N}]   (tri par dow automatique)
 *  - Planner    : [{label: 'Lun', count: N}]                 (déjà trié Lun→Dim)
 *
 * @param {Array}  data   - Données de répartition par jour
 * @param {number} height - Hauteur du graphique (défaut 130)
 */
export function WeekdayChart({ data = [], height = 130 }) {
  if (!data.length) return null

  // Normaliser les deux formats → [{label, bookings}]
  const hasDow = data[0]?.dow != null
  const normalized = hasDow
    ? [...data]
        .sort((a, b) => DOW_ORDER.indexOf(a.dow) - DOW_ORDER.indexOf(b.dow))
        .map(d => ({ label: d.label, bookings: Number(d.bookings ?? 0) }))
    : data.map(d => ({ label: d.label, bookings: Number(d.bookings ?? d.count ?? 0) }))

  const total   = normalized.reduce((s, d) => s + d.bookings, 0)
  const peakIdx = normalized.reduce((mi, d, i, arr) => d.bookings > arr[mi].bookings ? i : mi, 0)

  const withPct = normalized.map(d => ({
    ...d,
    pct: total > 0 ? Math.round((d.bookings / total) * 100) : 0,
  }))

  return (
    <div className="rounded-lg border bg-card p-5">
      <div className="mb-4">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">
          Activité par jour
        </p>
        <p className="text-[10px] text-muted-foreground mt-0.5">
          Jours préférés de réservation
          {withPct[peakIdx] && (
            <> — pic : <span className="font-semibold text-foreground">{withPct[peakIdx].label}</span></>
          )}
        </p>
      </div>
      <ResponsiveContainer width="100%" height={height}>
        <BarChart data={withPct} margin={{ top: 16, right: 4, left: -28, bottom: 0 }}>
          <XAxis dataKey="label" tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <YAxis allowDecimals={false} tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const v = payload[0]?.value ?? 0
              const pct = total > 0 ? Math.round((v / total) * 100) : 0
              return (
                <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs">
                  <p className="font-medium">{label}</p>
                  <p className="mt-1">{fmtNum(v)} rés. <span className="text-muted-foreground">({pct}%)</span></p>
                </div>
              )
            }}
          />
          <Bar dataKey="bookings" radius={[3, 3, 0, 0]}>
            <LabelList
              dataKey="pct"
              position="top"
              formatter={v => v > 0 ? `${v}%` : ''}
              style={{ fontSize: 9, fill: COLORS.axis }}
            />
            {withPct.map((_, i) => (
              <Cell key={i} fill={COLORS.current} fillOpacity={i === peakIdx ? 1 : 0.45} />
            ))}
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}
