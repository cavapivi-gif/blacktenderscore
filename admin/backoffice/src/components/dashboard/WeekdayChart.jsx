import { BarChart, Bar, XAxis, YAxis, Tooltip, Cell, LabelList, Legend, ResponsiveContainer } from 'recharts'
import { COLORS, CHART_INFO } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'
import { InfoTooltip } from './InfoTooltip'

// MySQL DOW: 1=Dim, 2=Lun ... 7=Sam → tri Lun→Dim
const DOW_ORDER = [2, 3, 4, 5, 6, 7, 1]

/**
 * Distribution des réservations par jour de semaine.
 *
 * Accepte deux formats de données :
 *  - API stats  : [{dow: 1-7, label: 'Lun', bookings: N}]   (tri par dow automatique)
 *  - Planner    : [{label: 'Lun', count: N}]                 (déjà trié Lun→Dim)
 *
 * Quand dataCmp est fourni : affiche des barres groupées (période principale vs comparaison).
 *
 * @param {Array}       data    - Données de répartition par jour
 * @param {Array|null}  dataCmp - Données comparaison (même format, optionnel)
 * @param {number}      height  - Hauteur du graphique (défaut 130)
 */
export function WeekdayChart({ data = [], dataCmp = null, height = 130 }) {
  if (!data.length) return null

  const hasDow = data[0]?.dow != null

  /** Normalize + sort → [{label, bookings}] */
  function normalize(arr) {
    if (!arr?.length) return []
    return hasDow
      ? [...arr]
          .sort((a, b) => DOW_ORDER.indexOf(a.dow) - DOW_ORDER.indexOf(b.dow))
          .map(d => ({ label: d.label, bookings: Number(d.bookings ?? 0) }))
      : arr.map(d => ({ label: d.label, bookings: Number(d.bookings ?? d.count ?? 0) }))
  }

  const normalized    = normalize(data)
  const normalizedCmp = normalize(dataCmp)
  const isCompare     = normalizedCmp.length > 0

  const total   = normalized.reduce((s, d) => s + d.bookings, 0)
  const peakIdx = normalized.reduce((mi, d, i, arr) => d.bookings > arr[mi].bookings ? i : mi, 0)

  // Merge current + compare by index
  const merged = normalized.map((d, i) => ({
    ...d,
    bookings_cmp: normalizedCmp[i]?.bookings ?? null,
    pct: total > 0 ? Math.round((d.bookings / total) * 100) : 0,
  }))

  return (
    <div className="rounded-lg border bg-card p-5">
      <div className="mb-4">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium inline-flex items-center">
          Activité par jour <InfoTooltip text={CHART_INFO.weekday} />
        </p>
        <p className="text-[10px] text-muted-foreground mt-0.5">
          Jours préférés de réservation
          {!isCompare && merged[peakIdx] && (
            <> — pic : <span className="font-semibold text-foreground">{merged[peakIdx].label}</span></>
          )}
        </p>
        {isCompare && (
          <div className="flex gap-3 mt-1">
            <span className="flex items-center gap-1 text-[10px] text-muted-foreground">
              <span className="w-2 h-2 rounded-sm shrink-0" style={{ background: COLORS.current }} />
              Période
            </span>
            <span className="flex items-center gap-1 text-[10px] text-muted-foreground">
              <span className="w-2 h-2 rounded-sm shrink-0" style={{ background: COLORS.compare, opacity: 0.75 }} />
              Comparaison
            </span>
          </div>
        )}
      </div>
      <ResponsiveContainer width="100%" height={height}>
        <BarChart data={merged} margin={{ top: 16, right: 4, left: -28, bottom: 0 }} barCategoryGap="20%">
          <XAxis dataKey="label" tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <YAxis allowDecimals={false} tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <Tooltip
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const cur = payload.find(p => p.dataKey === 'bookings')?.value ?? 0
              const cmp = payload.find(p => p.dataKey === 'bookings_cmp')?.value
              const pct = total > 0 ? Math.round((cur / total) * 100) : 0
              return (
                <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs">
                  <p className="font-medium">{label}</p>
                  <p className="mt-1">{fmtNum(cur)} rés. <span className="text-muted-foreground">({pct}%)</span></p>
                  {cmp != null && (
                    <p className="text-muted-foreground mt-0.5">Comparaison : {fmtNum(cmp)} rés.</p>
                  )}
                </div>
              )
            }}
          />
          {isCompare ? (
            <>
              <Bar dataKey="bookings"     name="Période"      fill={COLORS.current}          radius={[3, 3, 0, 0]} />
              <Bar dataKey="bookings_cmp" name="Comparaison"  fill={COLORS.compare} fillOpacity={0.75} radius={[3, 3, 0, 0]} />
            </>
          ) : (
            <Bar dataKey="bookings" radius={[3, 3, 0, 0]}>
              <LabelList
                dataKey="pct"
                position="top"
                formatter={v => v > 0 ? `${v}%` : ''}
                style={{ fontSize: 9, fill: COLORS.axis }}
              />
              {merged.map((_, i) => (
                <Cell key={i} fill={COLORS.current} fillOpacity={i === peakIdx ? 1 : 0.45} />
              ))}
            </Bar>
          )}
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}
