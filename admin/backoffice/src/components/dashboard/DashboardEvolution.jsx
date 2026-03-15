import { memo } from 'react'
import { Expand } from 'iconoir-react'
import { fmtShort } from '../../lib/utils'
import { SectionLabel } from './SectionLabel'
import { BookingsChart } from './BookingsChart'
import { MetricEvolutionChart } from './MetricEvolutionChart'
import { YoYChart } from './YoYChart'
import { TopPeriods } from './TopPeriods'

export const DashboardEvolution = memo(function DashboardEvolution({
  activeKpi, chartData, hasCompare, stats, filterParams,
  cancelRateEvolution, peaks, kpis, kpisCmp,
  handleChartClick, resetPeriod, setFullscreen,
  statsLoading,
}) {
  if (statsLoading) return null

  const periodSub = `${fmtShort(filterParams.from)} \u2013 ${fmtShort(filterParams.to)}`

  return (
    <>
      {/* Chart principal */}
      <div className="px-6 mt-6">
        <SectionLabel sub={periodSub}>
          {activeKpi === 'cancel'    ? "\u00c9volution \u00b7 Taux d'annulation"
         : activeKpi === 'customers' ? '\u00c9volution \u00b7 Clients uniques'
         : activeKpi === 'repeat'    ? '\u00c9volution \u00b7 Taux de repeat'
         : '\u00c9volution \u00b7 R\u00e9servations'}
        </SectionLabel>

        {activeKpi === 'cancel' && (
          <div className="rounded-lg border p-5" style={{ borderColor: '#e3e1db5c' }}>
            <MetricEvolutionChart
              data={cancelRateEvolution}
              dataKey="rate"
              dataKeyPrev={hasCompare ? 'rate_prev' : undefined}
              color="#dc2626"
              colorPrev="#a3a3a3"
              unit="%"
              name="Taux d'annulation"
              namePrev="P\u00e9riode pr\u00e9c."
              formatter={v => `${v}%`}
              height={220}
            />
          </div>
        )}

        {activeKpi === 'customers' && (
          <div className="rounded-lg border p-5" style={{ borderColor: '#e3e1db5c' }}>
            <MetricEvolutionChart
              data={chartData}
              dataKey="bookings"
              dataKeyPrev={hasCompare ? 'bookings_prev' : undefined}
              color="#0a0a0a"
              colorPrev="#a3a3a3"
              unit=""
              name="R\u00e9servations"
              namePrev="P\u00e9riode pr\u00e9c."
              height={220}
            />
          </div>
        )}

        {activeKpi === 'repeat' && (
          <div className="rounded-lg border p-5" style={{ borderColor: '#e3e1db5c' }}>
            <MetricEvolutionChart
              data={stats?.repeat_per_period ?? []}
              dataKey="repeat_rate"
              color="#6366f1"
              unit="%"
              name="Taux de repeat"
              formatter={v => `${v}%`}
              labelKey="period"
              height={220}
            />
          </div>
        )}

        {(activeKpi === 'bookings' || activeKpi == null) && (
          chartData.length === 0 ? (
            <div className="rounded-lg border bg-card p-10 text-center">
              <p className="text-sm font-medium">Aucune r\u00e9servation sur cette p\u00e9riode</p>
              <p className="text-xs text-muted-foreground mt-1">Essayez d'\u00e9largir la plage de dates.</p>
              <button onClick={resetPeriod} className="mt-4 text-xs text-primary underline hover:text-primary/80">
                Voir tout l'historique
              </button>
            </div>
          ) : (
            <div className="rounded-lg border bg-card p-5 relative">
              <button onClick={() => setFullscreen('bookings')}
                className="absolute top-3 right-3 h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors" title="Plein \u00e9cran">
                <Expand width={13} height={13} />
              </button>
              <BookingsChart data={chartData} height={220} hasCompare={hasCompare}
                peakBookings={peaks.bookings} filterParams={filterParams}
                totalBookings={kpis.total_bookings ?? 0} cmpBookings={kpisCmp?.total_bookings}
                onChartClick={handleChartClick} />
            </div>
          )
        )}
      </div>

      {/* YoY */}
      {stats?.yoy?.length > 1 && (
        <div className="px-6 mt-6">
          <YoYChart data={stats.yoy} />
        </div>
      )}

      {/* Top P\u00e9riodes */}
      {chartData.length > 0 && (
        <div className="px-6 mt-4">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <TopPeriods
              from={filterParams.from}
              to={filterParams.to}
              initialData={chartData}
              initialGranularity={filterParams.granularity ?? 'month'}
            />
          </div>
        </div>
      )}
    </>
  )
})
