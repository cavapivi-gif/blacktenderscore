import {
  AreaChart, Area, XAxis, YAxis, CartesianGrid,
  Tooltip, ResponsiveContainer, ReferenceLine,
} from 'recharts'
import { COLORS } from '../../lib/constants'
import { fmtShort, fmtNum } from '../../lib/utils'
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
              <stop offset="5%" stopColor={COLORS.compare} stopOpacity={0.08} />
              <stop offset="95%" stopColor={COLORS.compare} stopOpacity={0} />
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
              stroke={COLORS.compare} strokeWidth={1.5} strokeDasharray="5 3"
              fill="url(#gradBookingsPrev)" dot={false} />
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
  return (
    <>
      <ResponsiveContainer width="100%" height={height}>
        <AreaChart data={data} margin={{ top: 8, right: 4, left: -20, bottom: 0 }}
          onClick={onChartClick}
          className={filterParams.granularity === 'day' ? 'cursor-pointer' : ''}>
          <defs>
            <linearGradient id="gradRevenue" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={COLORS.current} stopOpacity={0.15} />
              <stop offset="95%" stopColor={COLORS.current} stopOpacity={0} />
            </linearGradient>
            <linearGradient id="gradRevenuePrev" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={COLORS.compare} stopOpacity={0.08} />
              <stop offset="95%" stopColor={COLORS.compare} stopOpacity={0} />
            </linearGradient>
            <linearGradient id="gradBasket" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={COLORS.basket} stopOpacity={0.10} />
              <stop offset="95%" stopColor={COLORS.basket} stopOpacity={0} />
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} vertical={false} />
          <XAxis dataKey="label" tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <YAxis tick={{ fontSize: 10, fill: COLORS.axis }} axisLine={false} tickLine={false} />
          <Tooltip content={p => <ChartTooltip {...p} suffix=" €" compareFrom={filterParams.compareFrom} compareTo={filterParams.compareTo} />} />
          {peakRevenue > 0 && (
            <ReferenceLine y={peakRevenue} stroke={COLORS.peak} strokeDasharray="4 3" strokeWidth={1.5}
              label={{ value: `Pic ${Number(peakRevenue).toLocaleString('fr-FR')} €`, fill: COLORS.peak, fontSize: 9, position: 'insideTopRight' }} />
          )}
          {hasCompare && (
            <Area type="monotone" dataKey="revenue_prev" name="CA préc."
              stroke={COLORS.compare} strokeWidth={1.5} strokeDasharray="5 3"
              fill="url(#gradRevenuePrev)" dot={false} />
          )}
          <Area type="monotone" dataKey="revenue" name="CA"
            stroke={COLORS.current} strokeWidth={2}
            fill="url(#gradRevenue)" dot={false} />
          <Area type="monotone" dataKey="avg_basket" name="Panier moy."
            stroke={COLORS.basket} strokeWidth={1.5} strokeDasharray="3 2"
            fill="url(#gradBasket)" dot={false} yAxisId={0} />
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
