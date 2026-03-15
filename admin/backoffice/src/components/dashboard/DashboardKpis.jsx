import { memo } from 'react'
import { fmtNum, fmtPercent, fmtDecimal } from '../../lib/utils'
import { COLORS } from '../../lib/constants'
import { Spinner } from '../ui'
import { KpiCard, KpiCompact } from './KpiCard'
import { SectionLabel } from './SectionLabel'

export const DashboardKpis = memo(function DashboardKpis({
  kpis, kpisCmp, hasCompare, statsLoading,
  bookingsDelta, cancelRateDelta, uniqueCustDelta, repeatDelta,
  sparkBookings, activeKpi, toggleKpi, periodSub,
}) {
  if (statsLoading) {
    return (
      <div className="px-6 pt-6 flex items-center gap-2 text-sm text-muted-foreground">
        <Spinner size={14} /> Calcul des indicateurs…
      </div>
    )
  }

  return (
    <div className="px-6 pt-5">
      <SectionLabel sub={periodSub}>Indicateurs cl&#233;s</SectionLabel>

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-3">
        <KpiCard
          label="R&#233;servations"
          value={fmtNum(kpis.total_bookings)}
          delta={bookingsDelta}
          sparkData={sparkBookings}
          sparkColor={COLORS.current}
          sub={hasCompare
            ? `vs ${fmtNum(kpisCmp?.total_bookings)} pr\u00e9c.`
            : `${fmtNum(kpis.total_confirmed ?? 0)} confirm\u00e9es`}
          active={activeKpi === 'bookings'}
          onClick={() => toggleKpi('bookings')}
        />
        <KpiCard
          label="Taux d'annulation"
          value={fmtPercent(kpis.cancellation_rate)}
          delta={cancelRateDelta}
          invertDelta
          alert={kpis.cancellation_rate > 10 && activeKpi !== 'cancel'}
          sub={`${fmtNum(kpis.total_cancelled ?? 0)} annul\u00e9es sur la p\u00e9riode`}
          active={activeKpi === 'cancel'}
          onClick={() => toggleKpi('cancel')}
        />
        <KpiCard
          label="Clients uniques"
          value={fmtNum(kpis.unique_customers)}
          delta={uniqueCustDelta}
          sub={kpis.repeat_rate
            ? `${fmtPercent(kpis.repeat_rate)} taux de repeat`
            : 'Clients distincts'}
          active={activeKpi === 'customers'}
          onClick={() => toggleKpi('customers')}
        />
        <KpiCard
          label="Taux de repeat"
          value={fmtPercent(kpis.repeat_rate)}
          delta={repeatDelta}
          sub={kpis.unique_customers > 0
            ? `sur ${fmtNum(kpis.unique_customers)} clients`
            : 'Clients r\u00e9currents'}
          active={activeKpi === 'repeat'}
          onClick={() => toggleKpi('repeat')}
        />
      </div>

      <div className="mt-3 rounded-lg border bg-muted/20 px-4 py-3">
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-4 gap-y-2">
          <KpiCompact label="R\u00e9s. / jour"     value={fmtDecimal(kpis.bookings_per_day)} />
          <KpiCompact label="Avance moy."     value={kpis.avg_lead_time_days ? `${fmtDecimal(kpis.avg_lead_time_days)}j` : '\u2014'} />
          <KpiCompact label="Qt\u00e9 / r\u00e9s."      value={fmtDecimal(kpis.avg_quantity)} />
          <KpiCompact label="Produits actifs" value={fmtNum(kpis.unique_products)} />
          <KpiCompact label="Jour de pic"     value={kpis.peak_weekday ?? '\u2014'} />
        </div>
      </div>
    </div>
  )
})
