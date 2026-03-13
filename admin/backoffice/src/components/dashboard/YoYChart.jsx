import { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts'
import { COLORS, YOY_PALETTE, CHART_INFO } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'
import { InfoTooltip } from './InfoTooltip'

const MONTHS_FR = ['jan', 'fév', 'mar', 'avr', 'mai', 'juin', 'juil', 'aoû', 'sep', 'oct', 'nov', 'déc']

/**
 * Couleur par année — la plus récente en tête de palette (emerald), les antérieures en suivant.
 * Palette définie dans constants.js / YOY_PALETTE.
 */
function yearColor(year, sortedYears) {
  const posFromEnd = sortedYears.length - 1 - sortedYears.indexOf(year)
  return YOY_PALETTE[posFromEnd % YOY_PALETTE.length]
}

/**
 * Saisonnalité Year-over-Year — courbes superposées par année.
 * Palette joyeuse multicolore (emerald, indigo, amber, cyan, rose…).
 *
 * @param {Array} data [{year_num, month_num, bookings}]
 */
export function YoYChart({ data = [] }) {
  if (!data.length) return null

  const years = [...new Set(data.map(d => Number(d.year_num)))].sort()
  if (years.length < 2) return null

  // Pivot: lignes = mois, colonnes = années
  const rows = MONTHS_FR.map((label, i) => {
    const row = { month: label }
    for (const year of years) {
      const found = data.find(d => Number(d.year_num) === year && Number(d.month_num) === i + 1)
      row[String(year)] = found ? Number(found.bookings) : null
    }
    return row
  })

  const visibleRows = rows.filter(r => years.some(y => r[String(y)] != null))

  return (
    <div className="rounded-lg border bg-card p-5">
      <div className="mb-4 flex items-start justify-between">
        <div>
          <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium inline-flex items-center">
            Saisonnalité <InfoTooltip text={CHART_INFO.yoy} />
          </p>
          <p className="text-[10px] text-muted-foreground mt-0.5">
            Réservations par mois · {years[0]}–{years[years.length - 1]}
          </p>
        </div>
        {/* Légende compacte */}
        <div className="flex items-center gap-3 flex-wrap justify-end">
          {[...years].reverse().map(year => (
            <span key={year} className="flex items-center gap-1.5 text-[10px] text-muted-foreground">
              <span
                className="inline-block w-4 h-1 rounded"
                style={{ background: yearColor(year, years), opacity: year === years[years.length - 1] ? 1 : 0.85 }}
              />
              {year}
            </span>
          ))}
        </div>
      </div>

      <ResponsiveContainer width="100%" height={160}>
        <LineChart data={visibleRows} margin={{ top: 8, right: 8, left: -20, bottom: 0 }}>
          <XAxis
            dataKey="month"
            tick={{ fontSize: 9, fill: COLORS.axis }}
            axisLine={false} tickLine={false}
          />
          <YAxis
            tick={{ fontSize: 9, fill: COLORS.axis }}
            axisLine={false} tickLine={false}
            width={28}
            allowDecimals={false}
          />
          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const sorted = [...payload].sort((a, b) => b.dataKey.localeCompare(a.dataKey))
              return (
                <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs space-y-1">
                  <p className="font-medium capitalize">{label}</p>
                  {sorted.filter(p => p.value != null).map(p => (
                    <p key={p.dataKey} className="tabular-nums flex items-center gap-1.5">
                      <span className="inline-block w-2 h-2 rounded-full shrink-0" style={{ background: p.stroke }} />
                      {p.dataKey} : <span className="font-medium">{fmtNum(p.value)}</span>
                    </p>
                  ))}
                </div>
              )
            }}
          />
          {years.map(year => (
            <Line
              key={year}
              type="monotone"
              dataKey={String(year)}
              stroke={yearColor(year, years)}
              strokeWidth={year === years[years.length - 1] ? 2.5 : 1.5}
              dot={false}
              activeDot={{ r: 3.5, strokeWidth: 0, fill: yearColor(year, years) }}
              connectNulls={false}
            />
          ))}
        </LineChart>
      </ResponsiveContainer>
    </div>
  )
}
