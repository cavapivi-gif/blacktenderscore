import { useMemo } from 'react'
import {
  ResponsiveContainer, ComposedChart,
  Line, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend,
} from 'recharts'
import { fmtShort } from '../../lib/utils'

// Couleurs hardcodées — CSS vars inopérantes dans SVG Recharts
const COLOR_RATE     = '#6366f1'  // indigo-500
const COLOR_UNIQUE   = '#d4d4d4'  // neutral-300
const COLOR_REPEAT   = '#0a0a0a'  // foreground
const COLOR_GRID     = '#e5e5e5'
const COLOR_AXIS     = '#737373'

function CustomTooltip({ active, payload, label }) {
  if (!active || !payload?.length) return null
  const d = payload[0]?.payload
  if (!d) return null
  return (
    <div className="rounded-md border bg-card shadow-lg px-3 py-2.5 text-xs space-y-1 min-w-[160px]">
      <p className="font-semibold text-foreground mb-1">{label}</p>
      <div className="flex justify-between gap-4">
        <span className="text-muted-foreground">Taux repeat</span>
        <span className="font-bold tabular-nums" style={{ color: COLOR_RATE }}>{d.repeat_rate}%</span>
      </div>
      <div className="flex justify-between gap-4">
        <span className="text-muted-foreground">Clients uniques</span>
        <span className="font-semibold tabular-nums">{d.unique_customers}</span>
      </div>
      <div className="flex justify-between gap-4">
        <span className="text-muted-foreground">dont fidèles</span>
        <span className="font-semibold tabular-nums" style={{ color: COLOR_REPEAT }}>{d.repeat_customers}</span>
      </div>
    </div>
  )
}

/**
 * Évolution mensuelle du taux de repeat client.
 * Barre grise = clients uniques, barre noire = clients fidèles (multi-visites),
 * ligne indigo = taux de repeat (%).
 *
 * @param {Array}  data  [{period, unique_customers, repeat_customers, repeat_rate}]
 */
export function RepeatRateChart({ data = [] }) {
  const chartData = useMemo(() =>
    data.map(d => ({
      ...d,
      label: fmtShort(d.period + '-01'),
    }))
  , [data])

  if (!chartData.length) return (
    <p className="text-xs text-muted-foreground text-center py-6">Aucune donnée de fidélité disponible.</p>
  )

  const maxCustomers = Math.max(...chartData.map(d => d.unique_customers), 1)

  return (
    <div className="mt-4">
      <ResponsiveContainer width="100%" height={200}>
        <ComposedChart data={chartData} margin={{ top: 4, right: 48, left: 0, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" stroke={COLOR_GRID} vertical={false} />
          <XAxis
            dataKey="label"
            tick={{ fontSize: 10, fill: COLOR_AXIS }}
            axisLine={false} tickLine={false}
            interval="preserveStartEnd"
          />
          {/* Axe gauche — clients (0 → max) */}
          <YAxis
            yAxisId="customers"
            domain={[0, maxCustomers]}
            tick={{ fontSize: 10, fill: COLOR_AXIS }}
            axisLine={false} tickLine={false}
            width={32}
          />
          {/* Axe droit — taux % (0 → 100) */}
          <YAxis
            yAxisId="rate"
            orientation="right"
            domain={[0, 100]}
            tick={{ fontSize: 10, fill: COLOR_AXIS }}
            axisLine={false} tickLine={false}
            width={36}
            tickFormatter={v => `${v}%`}
          />
          <Tooltip content={<CustomTooltip />} cursor={{ fill: '#f5f5f5' }} />

          {/* Clients uniques (fond gris) */}
          <Bar yAxisId="customers" dataKey="unique_customers" name="Clients uniques"
            fill={COLOR_UNIQUE} radius={[2, 2, 0, 0]} maxBarSize={24} />
          {/* Clients fidèles (noir, superposé) */}
          <Bar yAxisId="customers" dataKey="repeat_customers" name="Clients fidèles"
            fill={COLOR_REPEAT} radius={[2, 2, 0, 0]} maxBarSize={24} />
          {/* Taux repeat (ligne indigo) */}
          <Line
            yAxisId="rate"
            dataKey="repeat_rate"
            name="Taux repeat %"
            stroke={COLOR_RATE}
            strokeWidth={2}
            dot={{ r: 3, fill: COLOR_RATE, strokeWidth: 0 }}
            activeDot={{ r: 5 }}
          />
        </ComposedChart>
      </ResponsiveContainer>

      {/* Légende manuelle */}
      <div className="flex items-center justify-center gap-4 mt-2 text-[10px] text-muted-foreground">
        <span className="flex items-center gap-1.5">
          <span className="w-3 h-3 rounded-sm inline-block" style={{ background: COLOR_UNIQUE }} />
          Clients uniques
        </span>
        <span className="flex items-center gap-1.5">
          <span className="w-3 h-3 rounded-sm inline-block" style={{ background: COLOR_REPEAT }} />
          Clients fidèles
        </span>
        <span className="flex items-center gap-1.5">
          <span className="w-5 h-0.5 inline-block" style={{ background: COLOR_RATE }} />
          Taux repeat %
        </span>
      </div>
    </div>
  )
}
