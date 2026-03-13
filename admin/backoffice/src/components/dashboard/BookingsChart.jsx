import {
  AreaChart, Area, XAxis, YAxis, CartesianGrid,
  Tooltip, ResponsiveContainer, ReferenceLine,
} from 'recharts'
import { COLORS } from '../../lib/constants'
import { fmtShort, fmtNum, fmtCurrency } from '../../lib/utils'
import { ChartTooltip } from './ChartTooltip'

export function BookingsChart({
  data,
  height = 220,
  hasCompare,
  peakBookings = 0,
  filterParams,
  totalBookings,
  cmpBookings,
  onChartClick,
}) {
  return (
    <>
      <ResponsiveContainer width="100%" height={height}>
        <AreaChart data={data} margin={{ top: 8, right: 4, left: -28, bottom: 0 }}
          onClick={onChartClick}
          className={filterParams.granularity === 'day' ? 'cursor-pointer' : ''}>
          <defs>
            <linearGradient id="gradBookings" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={COLORS.current} stopOpacity={0.15} />
              <stop offset="95%" stopColor={COLORS.current} stopOpacity={0} />
            </linearGradient>
            <linearGradient id="gradBookingsPrev" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={COLORS.compare} stopOpacity={0.18} />
              <stop offset="95%" stopColor={COLORS.compare} stopOpacity={0.02} />
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
          <XAxis dataKey="label" tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <YAxis allowDecimals={false} tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <Tooltip content={p => <ChartTooltip {...p} compareFrom={filterParams.compareFrom} compareTo={filterParams.compareTo} />} />
          {peakBookings > 0 && (
            <ReferenceLine y={peakBookings} stroke={COLORS.peak} strokeDasharray="4 3" strokeWidth={1.5}
              label={{ value: `Pic ${peakBookings}`, fill: COLORS.peak, fontSize: 9, position: 'insideTopRight' }} />
          )}
          {hasCompare && (
            <Area type="monotone" dataKey="bookings_prev" name="Préc."
              stroke={COLORS.compare} strokeWidth={2} strokeDasharray="6 3"
              fill="url(#gradBookingsPrev)" dot={false} connectNulls />
          )}
          <Area type="monotone" dataKey="bookings" name="Actuel"
            stroke={COLORS.current} strokeWidth={2}
            fill="url(#gradBookings)" dot={false} />
        </AreaChart>
      </ResponsiveContainer>
      <ChartLegend
        from={filterParams.from} to={filterParams.to}
        compareFrom={filterParams.compareFrom} compareTo={filterParams.compareTo}
        hasCompare={hasCompare} total={totalBookings} prevTotal={cmpBookings ?? 0}
      />
      {filterParams.granularity === 'day' && (
        <p className="text-[10px] text-muted-foreground mt-2 text-center">
          Cliquez sur un jour pour voir dans le Planificateur
        </p>
      )}
    </>
  )
}

/**
 * AreaChart CA + panier moyen dans le temps.
 * Enrichi avec header StatCard (CA total, delta %, panier moyen).
 * Le panier moyen est sur un axe Y secondaire (droite) — échelle distincte du CA.
 *
 * @param {Array}   data            chartData (periods mergés courant + comparaison)
 * @param {number}  [height]        Hauteur du chart en px
 * @param {boolean} hasCompare      Mode comparatif actif
 * @param {number}  [peakRevenue]   Valeur de pic pour ReferenceLine
 * @param {Object}  filterParams    Paramètres de filtre (from, to, compareFrom, compareTo, granularity)
 * @param {number}  [totalRevenue]  CA total période courante — active le header si fourni
 * @param {number}  [cmpRevenue]    CA total période comparée
 * @param {number}  [avgBasket]     Panier moyen période courante
 * @param {number}  [avgBasketDelta] Delta % panier moyen vs période comparée
 */
export function RevenueChart({
  data,
  height = 220,
  hasCompare,
  peakRevenue = 0,
  filterParams,
  totalRevenue,
  cmpRevenue,
  avgBasket,
  avgBasketDelta,
  onChartClick,
}) {
  // Delta CA % — calculé ici pour le header StatCard
  const revenueD = hasCompare && cmpRevenue > 0
    ? Math.round(((totalRevenue - cmpRevenue) / cmpRevenue) * 100)
    : null

  return (
    <>
      {/* Header StatCard — CA total + delta + panier moyen */}
      {totalRevenue != null && (
        <div className="mb-4 pb-4 border-b">
          <div className="flex items-start justify-between gap-4">
            <div>
              <p className="text-[11px] uppercase tracking-wider text-muted-foreground">Chiffre d'affaires</p>
              <p className="text-[10px] text-muted-foreground/70 mt-0.5 mb-2">
                CA confirmé · {fmtShort(filterParams.from)} – {fmtShort(filterParams.to)}
              </p>
              <div className="flex items-baseline gap-3">
                <span className="text-2xl tabular-nums">{fmtCurrency(totalRevenue)}</span>
                {revenueD !== null && (
                  <span className="text-[10px] font-bold px-1.5 py-0.5 rounded" style={{
                    backgroundColor: revenueD >= 0 ? COLORS.delta_pos_bg : COLORS.delta_neg_bg,
                    color: revenueD >= 0 ? COLORS.delta_pos_text : COLORS.delta_neg_text,
                  }}>{revenueD >= 0 ? '+' : ''}{revenueD}%</span>
                )}
              </div>
              {hasCompare && cmpRevenue != null && (
                <p className="text-[10px] text-muted-foreground/60 mt-1.5">
                  vs {fmtCurrency(cmpRevenue)} · {fmtShort(filterParams.compareFrom)} – {fmtShort(filterParams.compareTo)}
                </p>
              )}
            </div>
            {/* Panier moyen — affiché en dehors de l'axe pour éviter la confusion avec CA */}
            {avgBasket != null && (
              <div className="text-right shrink-0">
                <p className="text-[10px] text-muted-foreground/70 mb-0.5">Panier moyen</p>
                <p className="text-lg tabular-nums font-medium" style={{ color: COLORS.basket }}>
                  {fmtCurrency(avgBasket)}
                </p>
                {avgBasketDelta != null && (
                  <p className="text-[9px] mt-0.5" style={{
                    color: avgBasketDelta >= 0 ? COLORS.delta_pos_text : COLORS.delta_neg_text,
                  }}>
                    {avgBasketDelta >= 0 ? '+' : ''}{avgBasketDelta}% vs préc.
                  </p>
                )}
              </div>
            )}
          </div>
        </div>
      )}

      <ResponsiveContainer width="100%" height={height}>
        {/* margin right=55 pour laisser de la place à l'axe Y panier (droite) */}
        <AreaChart data={data} margin={{ top: 8, right: 55, left: -20, bottom: 0 }}
          onClick={onChartClick}
          className={filterParams.granularity === 'day' ? 'cursor-pointer' : ''}>
          <defs>
            <linearGradient id="gradRevenue" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={COLORS.current} stopOpacity={0.15} />
              <stop offset="95%" stopColor={COLORS.current} stopOpacity={0} />
            </linearGradient>
            <linearGradient id="gradRevenuePrev" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={COLORS.compare} stopOpacity={0.18} />
              <stop offset="95%" stopColor={COLORS.compare} stopOpacity={0.02} />
            </linearGradient>
            <linearGradient id="gradBasket" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={COLORS.basket} stopOpacity={0.10} />
              <stop offset="95%" stopColor={COLORS.basket} stopOpacity={0} />
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
          <XAxis dataKey="label" tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          {/* Axe gauche : CA en euros */}
          <YAxis yAxisId="revenue" tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          {/* Axe droit : panier moyen — échelle distincte pour ne pas écraser la courbe CA */}
          <YAxis yAxisId="basket" orientation="right" tick={{ fontSize: 9, fill: COLORS.basket }} axisLine={false} tickLine={false} />
          <Tooltip content={p => <ChartTooltip {...p} suffix=" €" compareFrom={filterParams.compareFrom} compareTo={filterParams.compareTo} />} />
          {peakRevenue > 0 && (
            <ReferenceLine yAxisId="revenue" y={peakRevenue} stroke={COLORS.peak} strokeDasharray="4 3" strokeWidth={1.5}
              label={{ value: `Pic ${Number(peakRevenue).toLocaleString('fr-FR')} €`, fill: COLORS.peak, fontSize: 9, position: 'insideTopRight' }} />
          )}
          {hasCompare && (
            <Area yAxisId="revenue" type="monotone" dataKey="revenue_prev" name="CA préc."
              stroke={COLORS.compare} strokeWidth={2} strokeDasharray="6 3"
              fill="url(#gradRevenuePrev)" dot={false} connectNulls />
          )}
          <Area yAxisId="revenue" type="monotone" dataKey="revenue" name="CA"
            stroke={COLORS.current} strokeWidth={2}
            fill="url(#gradRevenue)" dot={false} />
          <Area yAxisId="basket" type="monotone" dataKey="avg_basket" name="Panier moy."
            stroke={COLORS.basket} strokeWidth={1.5} strokeDasharray="3 2"
            fill="url(#gradBasket)" dot={false} />
        </AreaChart>
      </ResponsiveContainer>
      <ChartLegend
        from={filterParams.from} to={filterParams.to}
        compareFrom={filterParams.compareFrom} compareTo={filterParams.compareTo}
        hasCompare={hasCompare} total={totalRevenue} prevTotal={cmpRevenue ?? 0} suffix=" €"
      />
    </>
  )
}

function ChartLegend({ from, to, compareFrom, compareTo, hasCompare, total, prevTotal, suffix = '' }) {
  const fmt = v => suffix ? `${Number(v).toLocaleString('fr-FR')}${suffix}` : Number(v).toLocaleString('fr-FR')
  const d = hasCompare && prevTotal > 0
    ? Math.round(((total - prevTotal) / prevTotal) * 100)
    : null
  return (
    <div className="flex flex-wrap items-center gap-x-6 gap-y-2 mt-4 pt-4 border-t text-xs">
      <span className="flex items-center gap-2">
        <span className="w-6 h-0.5 rounded-full inline-block shrink-0" style={{ background: COLORS.current }} />
        <span className="font-medium tabular-nums">{fmt(total)}</span>
        <span className="text-muted-foreground">{fmtShort(from)} – {fmtShort(to)}</span>
        {d !== null && (
          <span className="text-[10px] font-bold px-1.5 py-0.5 rounded shrink-0" style={{
            backgroundColor: d >= 0 ? COLORS.delta_pos_bg : COLORS.delta_neg_bg,
            color: d >= 0 ? COLORS.delta_pos_text : COLORS.delta_neg_text,
          }}>
            {d >= 0 ? '+' : ''}{d}%
          </span>
        )}
      </span>
      {hasCompare && compareFrom && (
        <span className="flex items-center gap-2">
          <span className="w-6 shrink-0" style={{ display: 'inline-block', borderTop: `2px dashed ${COLORS.compare}` }} />
          <span className="font-medium text-muted-foreground tabular-nums">{fmt(prevTotal ?? 0)}</span>
          <span className="text-muted-foreground opacity-70">{fmtShort(compareFrom)} – {fmtShort(compareTo)}</span>
        </span>
      )}
    </div>
  )
}
