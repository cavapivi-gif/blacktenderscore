import { useMemo } from 'react'
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid,
  Tooltip, ResponsiveContainer,
} from 'recharts'
import { COLORS } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'
import { InfoTooltip } from './InfoTooltip'

// Couleurs hardcodées — CSS vars inopérantes dans SVG (Recharts)
const COLOR_CONFIRMED = '#10b981'  // emerald-500
const COLOR_PENDING   = '#f59e0b'  // amber-500
const COLOR_CANCELLED = '#dc2626'  // red-600

// Groupement des statuts métier en 3 buckets
const STATUS_BUCKET = {
  confirmed: 'confirmed', booked: 'confirmed', approved: 'confirmed',
  completed: 'confirmed', paid: 'confirmed', sent: 'confirmed',
  canceled: 'cancelled', cancelled: 'cancelled', rejected: 'cancelled', refunded: 'cancelled',
  pending: 'pending', processing: 'pending', new: 'pending',
}

const TOOLTIP_INFO = 'Répartition des réservations par canal de vente, ventilée par statut (confirmé / en attente / annulé). Donne une vue rapide des performances par source.'

/**
 * Canal × statut — horizontal stacked BarChart (Recharts).
 * Affiche les top canaux avec la ventilation confirmé/en attente/annulé.
 * Suit le pattern StatCard : legend + dateRange + compareMode.
 *
 * @param {Array}   channelStatus [{channel, status, bookings, revenue}] (stats.channel_status)
 * @param {string}  [legend]      Texte contextuel sous le titre
 * @param {string}  [dateRange]   Plage de dates affichée
 * @param {boolean} [compareMode] TODO: nécessite compare_channel_status côté API
 */
export function ChannelBreakdown({ channelStatus = [], legend, dateRange, compareMode = false }) {
  if (!channelStatus.length) return null

  // Agrège les lignes canal+statut → un objet par canal avec 3 buckets
  const chartData = useMemo(() => {
    const map = {}
    channelStatus.forEach(row => {
      const ch     = row.channel || 'Non renseigné'
      const bucket = STATUS_BUCKET[row.status?.toLowerCase()] ?? 'pending'
      if (!map[ch]) map[ch] = { channel: ch, confirmed: 0, cancelled: 0, pending: 0 }
      map[ch][bucket] += Number(row.bookings) || 0
    })
    // Trie par total desc, top 8 canaux
    return Object.values(map)
      .map(d => ({ ...d, _total: d.confirmed + d.cancelled + d.pending }))
      .sort((a, b) => b._total - a._total)
      .slice(0, 8)
  }, [channelStatus])

  // Hauteur adaptative selon le nombre de canaux
  const chartHeight = Math.max(160, chartData.length * 40)

  return (
    <div className="rounded-lg border bg-card p-5">
      {/* Header StatCard-style */}
      <div className="mb-4">
        <p className="text-[11px] uppercase tracking-wider text-muted-foreground inline-flex items-center">
          Canaux de vente <InfoTooltip text={TOOLTIP_INFO} />
        </p>
        {(legend || dateRange) && (
          <p className="text-[10px] text-muted-foreground/70 mt-0.5">
            {legend && <span>{legend}</span>}
            {legend && dateRange && <span className="mx-1">·</span>}
            {dateRange && <span>{dateRange}</span>}
          </p>
        )}
      </div>

      <ResponsiveContainer width="100%" height={chartHeight}>
        <BarChart
          data={chartData}
          layout="vertical"
          margin={{ top: 0, right: 35, left: 0, bottom: 0 }}
          barSize={13}
        >
          <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke={COLORS.grid} />
          <XAxis
            type="number"
            tick={{ fontSize: 9, fill: COLORS.axis }}
            axisLine={false}
            tickLine={false}
            allowDecimals={false}
          />
          <YAxis
            type="category"
            dataKey="channel"
            width={88}
            tick={{ fontSize: 10, fill: COLORS.axis }}
            axisLine={false}
            tickLine={false}
            tickFormatter={v => v.length > 13 ? v.slice(0, 13) + '…' : v}
          />
          <Tooltip
            cursor={{ fill: 'transparent' }}
            content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const total = payload.reduce((s, p) => s + (Number(p.value) || 0), 0)
              return (
                <div className="rounded-md border bg-card shadow-md px-3 py-2 text-xs min-w-[150px]">
                  <p className="font-medium mb-1.5 truncate max-w-[160px]">{label}</p>
                  {payload.map(p => Number(p.value) > 0 && (
                    <div key={p.dataKey} className="flex items-center justify-between gap-3">
                      <span className="flex items-center gap-1.5 text-muted-foreground">
                        <span className="w-2 h-2 rounded-sm shrink-0" style={{ background: p.fill }} />
                        {p.name}
                      </span>
                      <strong className="tabular-nums">{fmtNum(p.value)}</strong>
                    </div>
                  ))}
                  <div className="mt-1.5 pt-1.5 border-t flex justify-between text-muted-foreground">
                    <span>Total</span>
                    <strong className="tabular-nums">{fmtNum(total)}</strong>
                  </div>
                </div>
              )
            }}
          />
          {/* Ordre des barres : confirmed en premier (ancre gauche), puis pending, puis cancelled */}
          <Bar dataKey="confirmed" name="Confirmé"   stackId="s" fill={COLOR_CONFIRMED} />
          <Bar dataKey="pending"   name="En attente" stackId="s" fill={COLOR_PENDING}   />
          <Bar dataKey="cancelled" name="Annulé"     stackId="s" fill={COLOR_CANCELLED} radius={[0, 3, 3, 0]} />
        </BarChart>
      </ResponsiveContainer>

      {/* Légende couleur */}
      <div className="flex items-center gap-4 mt-3 pt-3 border-t flex-wrap">
        {[
          { color: COLOR_CONFIRMED, label: 'Confirmé' },
          { color: COLOR_PENDING,   label: 'En attente' },
          { color: COLOR_CANCELLED, label: 'Annulé' },
        ].map(({ color, label }) => (
          <span key={label} className="flex items-center gap-1.5 text-[10px] text-muted-foreground">
            <span className="w-3 h-3 rounded-sm shrink-0" style={{ background: color }} />
            {label}
          </span>
        ))}
      </div>
    </div>
  )
}
