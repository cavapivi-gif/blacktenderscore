import { memo } from 'react'
import { fmtShort } from '../../lib/utils'
import { PAYMENT_METHOD_LABELS } from '../../lib/constants'
import { SectionLabel } from './SectionLabel'
import { HeatmapChart } from './HeatmapChart'
import { TopProducts } from './TopProducts'
import { ChannelBreakdown } from './ChannelBreakdown'
import { DonutChart } from './DonutChart'

export const DashboardDistribution = memo(function DashboardDistribution({
  stats, hasCompare, filterParams, statsLoading,
}) {
  if (statsLoading) return null

  const paymentDonutData = (stats?.payments?.by_method ?? []).map(p => ({
    name:  PAYMENT_METHOD_LABELS[p.method] ?? p.method,
    value: Number(p.bookings ?? p.count ?? 0),
  })).filter(p => p.value > 0)

  const hasData = stats?.heatmap?.length > 0 || stats?.by_product?.length > 0
    || stats?.channel_status?.length > 0 || paymentDonutData.length > 0

  if (!hasData) return null

  const periodSub = `${fmtShort(filterParams.from)} \u2013 ${fmtShort(filterParams.to)}`

  return (
    <div className="px-6 mt-6">
      <SectionLabel sub={periodSub}>Distribution</SectionLabel>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

        {stats?.heatmap?.length > 0 && (
          <HeatmapChart
            data={stats.heatmap}
            dataCmp={hasCompare ? (stats?.heatmap_compare ?? null) : null}
            dataCancellations={stats?.heatmap_cancellations ?? null}
          />
        )}

        {stats?.by_product?.length > 0 && (
          <TopProducts data={stats.by_product} filterParams={filterParams} />
        )}

        {(stats?.channel_status?.length > 0 || paymentDonutData.length > 0) && (
          <div className="space-y-4">
            {stats?.channel_status?.length > 0 && (
              <ChannelBreakdown
                channelStatus={stats.channel_status}
                dateRange={periodSub}
              />
            )}
            <DonutChart data={paymentDonutData} title="M\u00e9thodes de paiement" showEmpty />
          </div>
        )}

      </div>
    </div>
  )
})
