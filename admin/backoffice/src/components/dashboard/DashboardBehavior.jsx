import { memo } from 'react'
import { fmtNum, fmtShort } from '../../lib/utils'
import { SectionLabel } from './SectionLabel'
import { WeekdayChart } from './WeekdayChart'
import { LeadTimeChart } from './LeadTimeChart'
import { CancellationChart } from './CancellationChart'
import { RepeatChart } from './RepeatChart'
import { AvisWidget } from './AvisWidget'
import { TopDays } from './TopDays'

export const DashboardBehavior = memo(function DashboardBehavior({
  stats, hasCompare, kpis, filterParams,
  cancellationData, cancellationDataCmp,
  statsLoading,
}) {
  if (statsLoading) return null

  const periodSub = `${fmtShort(filterParams.from)} \u2013 ${fmtShort(filterParams.to)}`
  const hasAnalysis = stats?.by_weekday?.length > 0 || stats?.lead_time_buckets?.length > 0 || cancellationData.length > 0
  const hasClients  = stats?.repeat_customers?.length > 0 || stats?.top_dates?.length > 0

  if (!hasAnalysis && !hasClients) return null

  return (
    <>
      {/* Analyse comportementale */}
      {hasAnalysis && (
        <div className="px-6 mt-6">
          <SectionLabel sub={periodSub}>Analyse</SectionLabel>
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
            {stats?.by_weekday?.length > 0 && (
              <WeekdayChart
                data={stats.by_weekday}
                dataCmp={hasCompare ? (stats?.by_weekday_compare ?? null) : null}
              />
            )}
            {stats?.lead_time_buckets?.length > 0 && (
              <LeadTimeChart
                data={stats.lead_time_buckets}
                dataCmp={stats?.lead_time_buckets_compare ?? null}
              />
            )}
            {cancellationData.length > 0 && (
              <CancellationChart data={cancellationData} dataCmp={cancellationDataCmp} />
            )}
          </div>
        </div>
      )}

      {/* Fid\u00e9lit\u00e9 & avis & top jours */}
      <div className="px-6 mt-6">
        <SectionLabel sub={periodSub}>Clients &amp; avis</SectionLabel>
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
          {stats?.repeat_customers?.length > 0 && (
            <RepeatChart
              data={stats.repeat_customers}
              dataCmp={hasCompare ? (stats?.repeat_customers_compare ?? null) : null}
            />
          )}
          <AvisWidget
            from={filterParams.from}
            to={filterParams.to}
            compareFrom={hasCompare ? filterParams.compareFrom : null}
            compareTo={hasCompare ? filterParams.compareTo : null}
          />
          {stats?.top_dates?.length > 0 && (
            <TopDays
              data={stats.top_dates}
              dataCancellations={stats?.top_cancellation_dates ?? []}
              total={kpis.total_bookings ?? 0}
              compareMode={hasCompare}
              dataCompare={hasCompare ? (stats?.top_dates_compare ?? []) : []}
              comparePeriod={hasCompare ? `${fmtShort(filterParams.compareFrom)} \u2013 ${fmtShort(filterParams.compareTo)}` : undefined}
            />
          )}
        </div>
      </div>
    </>
  )
})
