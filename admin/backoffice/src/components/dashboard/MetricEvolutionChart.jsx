import { useMemo } from 'react'
import {
  ResponsiveContainer, AreaChart, Area,
  XAxis, YAxis, CartesianGrid, Tooltip, ReferenceLine,
} from 'recharts'

// Couleurs hardcodées — CSS vars inopérantes dans SVG Recharts
const COLOR_GRID = '#e5e5e5'
const COLOR_AXIS = '#737373'

function CustomTooltip({ active, payload, label, unit, formatter }) {
  if (!active || !payload?.length) return null
  return (
    <div className="rounded-md border bg-card shadow-lg px-3 py-2 text-xs space-y-1 min-w-[140px]">
      <p className="font-semibold text-foreground">{label}</p>
      {payload.map((p, i) => (
        <div key={i} className="flex justify-between gap-4">
          <span className="text-muted-foreground">{p.name}</span>
          <span className="font-bold tabular-nums" style={{ color: p.color }}>
            {formatter ? formatter(p.value) : p.value}{unit}
          </span>
        </div>
      ))}
    </div>
  )
}

/**
 * Courbe d'évolution générique — branchable sur n'importe quelle métrique temporelle.
 * Respecte la granularité (J/S/M) et le compare mode via les données passées.
 *
 * @param {Array}    data       [{label, value, value_prev?}] — clés configurables via dataKey/dataKeyPrev
 * @param {string}   dataKey       Clé de la valeur principale (défaut 'value')
 * @param {string}   [dataKeyPrev] Clé de la valeur de comparaison (optionnel)
 * @param {string}   color         Couleur principale (hex)
 * @param {string}   [colorPrev]   Couleur comparaison (hex)
 * @param {string}   [unit]        Unité affichée dans tooltip ('', '%', ...)
 * @param {string}   [name]        Label série principale
 * @param {string}   [namePrev]    Label série comparaison
 * @param {Function} [formatter]   Formateur valeur tooltip (v => string)
 * @param {number}   [height]      Hauteur du chart (défaut 220)
 * @param {number}   [peak]        Valeur de référence (ReferenceLine horizontal)
 * @param {string}   [labelKey]    Clé label dans data (défaut 'label')
 */
export function MetricEvolutionChart({
  data = [],
  dataKey = 'value',
  dataKeyPrev,
  color = '#0a0a0a',
  colorPrev = '#a3a3a3',
  unit = '',
  name = 'Valeur',
  namePrev,
  formatter,
  height = 220,
  peak,
  labelKey = 'label',
}) {
  const gradId    = `mec-${dataKey}`
  const gradIdPrev = `mec-${dataKeyPrev}-prev`

  const hasPrev = !!(dataKeyPrev && data.some(d => d[dataKeyPrev] != null))

  if (!data.length) return (
    <p className="text-xs text-muted-foreground text-center py-8">Aucune donnée sur cette période.</p>
  )

  return (
    <ResponsiveContainer width="100%" height={height}>
      <AreaChart data={data} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
        <defs>
          <linearGradient id={gradId} x1="0" y1="0" x2="0" y2="1">
            <stop offset="5%"  stopColor={color} stopOpacity={0.15} />
            <stop offset="95%" stopColor={color} stopOpacity={0} />
          </linearGradient>
          {hasPrev && (
            <linearGradient id={gradIdPrev} x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%"  stopColor={colorPrev} stopOpacity={0.1} />
              <stop offset="95%" stopColor={colorPrev} stopOpacity={0} />
            </linearGradient>
          )}
        </defs>

        <CartesianGrid strokeDasharray="3 3" stroke={COLOR_GRID} vertical={false} />
        <XAxis
          dataKey={labelKey}
          tick={{ fontSize: 10, fill: COLOR_AXIS }}
          axisLine={false} tickLine={false}
          interval="preserveStartEnd"
        />
        <YAxis
          tick={{ fontSize: 10, fill: COLOR_AXIS }}
          axisLine={false} tickLine={false}
          width={36}
          tickFormatter={v => `${v}${unit}`}
        />
        <Tooltip content={<CustomTooltip unit={unit} formatter={formatter} />} />

        {peak != null && (
          <ReferenceLine y={peak} stroke={color} strokeDasharray="4 2" strokeOpacity={0.4} />
        )}

        {/* Série comparaison en arrière-plan */}
        {hasPrev && (
          <Area
            type="monotone"
            dataKey={dataKeyPrev}
            name={namePrev ?? 'Période préc.'}
            stroke={colorPrev}
            fill={`url(#${gradIdPrev})`}
            strokeWidth={1.5}
            strokeDasharray="4 2"
            dot={false}
            activeDot={false}
          />
        )}

        {/* Série principale */}
        <Area
          type="monotone"
          dataKey={dataKey}
          name={name}
          stroke={color}
          fill={`url(#${gradId})`}
          strokeWidth={2}
          dot={false}
          activeDot={{ r: 4, strokeWidth: 0 }}
        />
      </AreaChart>
    </ResponsiveContainer>
  )
}
