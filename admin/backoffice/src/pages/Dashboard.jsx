import { useState, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { Expand, FilterList } from 'iconoir-react'

import { cn, fmtCurrency, fmtNum, fmtPercent, fmtDecimal, fmtShort, delta } from '../lib/utils'
import { COLORS } from '../lib/constants'
import { STATUS_MAP } from '../lib/status'
import { useDashboard } from '../hooks/useDashboard'

import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/Tabs'
import { TooltipProvider, Tooltip as UiTooltip } from '../components/Tooltip'
import { PageHeader, StatCard, Table, Notice, Spinner, Badge } from '../components/ui'

import {
  KpiCard, KpiCompact,
  StatsToolbar, FilterPopup,
  BookingsChart, RevenueChart,
  ChannelBreakdown, WeekdayChart,
  HeatmapChart, DonutChart,
  BookingHoursChart, TopProducts,
  FullscreenOverlay,
} from '../components/dashboard'

// ── Main Dashboard ──────────────────────────────────────────────────────────

export default function Dashboard() {
  const navigate = useNavigate()
  const {
    data, stats, loading, statsLoading, error,
    filterParams, applyFilters, resetPeriod,
    chartData, hasCompare, kpis, kpisCmp,
    sparkBookings, sparkRevenue, sparkAvgBasket,
    peaks,
  } = useDashboard()

  const [fullscreen, setFullscreen] = useState(null)
  const [filterOpen, setFilterOpen] = useState(false)

  // KPI deltas
  const bookingsDelta    = delta(kpis.total_bookings, kpisCmp?.total_bookings)
  const revenueDelta     = delta(kpis.total_revenue, kpisCmp?.total_revenue)
  const avgBasketDelta   = delta(kpis.avg_basket, kpisCmp?.avg_basket)
  const cancelRateDeltaR = delta(kpis.cancellation_rate, kpisCmp?.cancellation_rate)
  const cancelRateDelta  = cancelRateDeltaR != null ? -cancelRateDeltaR : null
  const uniqueCustDelta  = delta(kpis.unique_customers, kpisCmp?.unique_customers)
  const repeatDelta      = delta(kpis.repeat_rate, kpisCmp?.repeat_rate)

  // Drill-down chart → planner
  const handleChartClick = useCallback((clickData) => {
    if (!clickData?.activePayload?.length || filterParams.granularity !== 'day') return
    const key = clickData.activePayload[0]?.payload?.key
    const month = key?.slice(0, 7)
    navigate(month ? `/planner?month=${month}` : '/planner')
  }, [filterParams.granularity, navigate])

  // Prepare donut data for channels
  const channelDonutData = (stats?.by_channel ?? []).map(c => ({
    name: c.channel,
    value: c.bookings,
  }))

  // Prepare payment method donut
  const paymentDonutData = (stats?.payments?.by_method ?? []).map(p => ({
    name: p.method,
    value: p.count,
  }))

  // Booking columns for recent bookings table
  const bookingCols = [
    { key: 'booking_ref', label: 'Référence' },
    { key: 'product_name', label: 'Produit' },
    { key: 'booking_date', label: 'Date', render: r => r.booking_date?.slice(0, 10) ?? '—' },
    { key: 'customer_name', label: 'Client' },
    {
      key: 'total_price', label: 'Montant',
      render: r => r.total_price ? fmtCurrency(r.total_price) : '—',
    },
    {
      key: 'status', label: 'Statut',
      render: r => {
        const s = STATUS_MAP[r.status] ?? { variant: 'default', label: r.status ?? '—' }
        return <Badge variant={s.variant}>{s.label}</Badge>
      },
    },
  ]

  return (
    <TooltipProvider>
      <div>
        <PageHeader
          title="Tableau de bord"
          subtitle={`Données solditems · ${fmtShort(filterParams.from)} – ${fmtShort(filterParams.to)}`}
          actions={
            <button
              onClick={() => setFilterOpen(true)}
              className={cn(
                'flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium border transition-colors',
                'bg-card border-border text-muted-foreground hover:text-foreground hover:bg-accent',
              )}
            >
              <FilterList width={14} height={14} />
              Filtres
            </button>
          }
        />

        {error && (
          <div className="px-6 pt-5">
            <Notice type="error">{error}</Notice>
          </div>
        )}

        {/* ── Row 1: Static overview cards ─────────────────────────────── */}
        {!loading && (
          <div className="mx-6 mt-5 grid grid-cols-2 lg:grid-cols-4 gap-px bg-border border rounded-lg overflow-hidden">
            <UiTooltip content="Produits synchronisés depuis Regiondo">
              <div>
                <StatCard
                  label="Produits"
                  value={data?.products_count ?? 0}
                  sub={<button onClick={() => navigate('/products')} className="text-xs underline">Voir tout</button>}
                />
              </div>
            </UiTooltip>
            <UiTooltip content="Clients dans le CRM Regiondo">
              <div>
                <StatCard label="Clients CRM" value={data?.customers_total ?? 0}
                  sub={<button onClick={() => navigate('/customers')} className="text-xs underline">Voir tout</button>} />
              </div>
            </UiTooltip>
            <UiTooltip content="Total de réservations en base de données">
              <div>
                <StatCard label="Total en DB" value={fmtNum(data?.total_in_db ?? data?.bookings_total ?? 0)}
                  sub="Réservations importées" />
              </div>
            </UiTooltip>
            <UiTooltip content="Statut de la connexion API Regiondo">
              <div>
                <StatCard
                  label="API Regiondo"
                  value={data?.api_status === 'ok' ? '✓ OK' : '✗ Erreur'}
                  sub={data?.api_status === 'ok' ? 'Connexion active' : data?.api_error ?? 'Vérifier les clés'}
                />
              </div>
            </UiTooltip>
          </div>
        )}

        {/* ── Toolbar ──────────────────────────────────────────────────── */}
        <div className="mx-6 mt-5">
          <StatsToolbar
            from={filterParams.from}
            to={filterParams.to}
            granularity={filterParams.granularity}
            showCompare={!!filterParams.compareFrom}
            onApply={applyFilters}
          />
        </div>

        {/* ── Row 2: Primary KPIs (Bloomberg-grade) ────────────────────── */}
        {statsLoading ? (
          <div className="mx-6 mt-4 flex items-center gap-3 text-sm text-muted-foreground">
            <Spinner size={14} /> Calcul des indicateurs…
          </div>
        ) : (
          <>
            {/* Primary KPIs with sparklines */}
            <div className="mx-6 mt-4 grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
              <KpiCard
                label="CA total"
                value={fmtCurrency(kpis.total_revenue)}
                delta={revenueDelta}
                sparkData={sparkRevenue}
                sparkColor={COLORS.current}
                sub={hasCompare ? `vs ${fmtCurrency(kpisCmp?.total_revenue)} préc.` : 'CA confirmé'}
              />
              <KpiCard
                label="Réservations"
                value={fmtNum(kpis.total_bookings)}
                delta={bookingsDelta}
                sparkData={sparkBookings}
                sparkColor={COLORS.current}
                sub={hasCompare ? `vs ${fmtNum(kpisCmp?.total_bookings)} préc.` : `${fmtNum(kpis.unique_products ?? 0)} produits`}
              />
              <KpiCard
                label="Panier moyen"
                value={fmtCurrency(kpis.avg_basket)}
                delta={avgBasketDelta}
                sparkData={sparkAvgBasket}
                sparkColor={COLORS.basket}
                sub={kpis.paid_bookings > 0 ? `Sur ${fmtNum(kpis.paid_bookings)} rés. payantes` : ''}
              />
              <KpiCard
                label="Taux annulation"
                value={fmtPercent(kpis.cancellation_rate)}
                delta={cancelRateDelta}
                invertDelta
                alert={kpis.cancellation_rate > 10}
                sub={`${fmtNum(kpis.total_cancelled ?? 0)} annulées`}
              />
              <KpiCard
                label="Clients uniques"
                value={fmtNum(kpis.unique_customers)}
                delta={uniqueCustDelta}
                sub={kpis.repeat_rate ? `${fmtPercent(kpis.repeat_rate)} repeat` : ''}
              />
              <KpiCard
                label="Taux de repeat"
                value={fmtPercent(kpis.repeat_rate)}
                delta={repeatDelta}
                sub={kpis.unique_customers > 0 ? `${fmtNum(kpis.unique_customers)} clients` : ''}
              />
            </div>

            {/* Secondary KPIs — compact row */}
            <div className="mx-6 mt-3 grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-2">
              <KpiCompact label="CA / jour" value={fmtCurrency(kpis.revenue_per_day)} />
              <KpiCompact label="Rés. / jour" value={fmtDecimal(kpis.bookings_per_day)} />
              <KpiCompact label="Lead time" value={kpis.avg_lead_time_days ? `${fmtDecimal(kpis.avg_lead_time_days)}j` : '—'} />
              <KpiCompact label="Qté moyenne" value={fmtDecimal(kpis.avg_quantity)} />
              <KpiCompact label="Produits actifs" value={fmtNum(kpis.unique_products)} />
              <KpiCompact label="Taux impayés" value={fmtPercent(kpis.unpaid_rate)} invertDelta />
              <KpiCompact label="Jour de pic" value={kpis.peak_weekday ?? '—'} />
              <KpiCompact label="Top produit" value={kpis.top_product_name ? (kpis.top_product_name.length > 15 ? kpis.top_product_name.slice(0, 15) + '…' : kpis.top_product_name) : '—'} />
            </div>
          </>
        )}

        {/* ── Main Charts ──────────────────────────────────────────────── */}
        {!statsLoading && (
          chartData.length === 0
            ? (
              <div className="mx-6 mt-4 rounded-lg border bg-card p-10 text-center">
                <p className="text-sm font-medium">Aucune réservation sur cette période</p>
                <p className="text-xs text-muted-foreground mt-1">Essayez d'élargir la plage de dates.</p>
                <button onClick={resetPeriod} className="mt-4 text-xs text-primary underline hover:text-primary/80">
                  Réinitialiser sur l'historique complet
                </button>
              </div>
            )
            : (
              <div className="mx-6 mt-4">
                <Tabs defaultValue="bookings">
                  <div className="flex items-center justify-between mb-4">
                    <TabsList>
                      <TabsTrigger value="bookings">Réservations</TabsTrigger>
                      <TabsTrigger value="revenue">CA + Panier moyen</TabsTrigger>
                    </TabsList>
                  </div>
                  <TabsContent value="bookings">
                    <div className="rounded-lg border bg-card p-5 relative">
                      <button onClick={() => setFullscreen('bookings')}
                        className="absolute top-3 right-3 h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors" title="Plein écran">
                        <Expand width={13} height={13} />
                      </button>
                      <BookingsChart
                        data={chartData}
                        height={220}
                        hasCompare={hasCompare}
                        peakBookings={peaks.bookings}
                        filterParams={filterParams}
                        totalBookings={kpis.total_bookings ?? 0}
                        cmpBookings={kpisCmp?.total_bookings}
                        onChartClick={handleChartClick}
                      />
                    </div>
                  </TabsContent>
                  <TabsContent value="revenue">
                    <div className="rounded-lg border bg-card p-5 relative">
                      <button onClick={() => setFullscreen('revenue')}
                        className="absolute top-3 right-3 h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors" title="Plein écran">
                        <Expand width={13} height={13} />
                      </button>
                      <RevenueChart
                        data={chartData}
                        height={220}
                        hasCompare={hasCompare}
                        peakRevenue={peaks.revenue}
                        filterParams={filterParams}
                        totalRevenue={kpis.total_revenue ?? 0}
                        cmpRevenue={kpisCmp?.total_revenue}
                        avgBasket={kpis.avg_basket}
                        avgBasketDelta={avgBasketDelta}
                        onChartClick={handleChartClick}
                      />
                    </div>
                  </TabsContent>
                </Tabs>
              </div>
            )
        )}

        {/* ── Cross-data row 1: Channels + Weekday ─────────────────────── */}
        {!statsLoading && (stats?.by_channel?.length > 0 || stats?.by_weekday?.length > 0) && (
          <div className="mx-6 mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
            {stats?.by_channel?.length > 0 && (
              <ChannelBreakdown
                data={stats.by_channel}
                title="Canaux de vente"
                totalBookings={kpis.total_bookings ?? 0}
              />
            )}
            {stats?.by_weekday?.length > 0 && (
              <WeekdayChart data={stats.by_weekday} />
            )}
          </div>
        )}

        {/* ── Cross-data row 2: Donuts (channels + payments) ───────────── */}
        {!statsLoading && (channelDonutData.length > 0 || paymentDonutData.length > 0) && (
          <div className="mx-6 mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
            {channelDonutData.length > 0 && (
              <DonutChart data={channelDonutData} title="Répartition canaux" />
            )}
            {paymentDonutData.length > 0 && (
              <DonutChart data={paymentDonutData} title="Méthodes de paiement" />
            )}
          </div>
        )}

        {/* ── Heatmap ──────────────────────────────────────────────────── */}
        {!statsLoading && stats?.heatmap?.length > 0 && (
          <div className="mx-6 mt-4">
            <HeatmapChart data={stats.heatmap} />
          </div>
        )}

        {/* ── Advanced analytics: Booking hours + Top products ──────────── */}
        {!statsLoading && (
          <div className="mx-6 mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
            {stats?.booking_hours?.length > 0 && (
              <BookingHoursChart data={stats.booking_hours} />
            )}
            {stats?.by_product?.length > 0 && (
              <TopProducts data={stats.by_product} filterParams={filterParams} />
            )}
          </div>
        )}

        {/* ── Recent bookings table ────────────────────────────────────── */}
        {!loading && (
          <div className="mx-6 mt-6 mb-10">
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs text-muted-foreground uppercase tracking-wider font-medium">Dernières réservations importées</span>
              <button onClick={() => navigate('/bookings')} className="text-xs text-muted-foreground underline hover:text-foreground">
                Tout voir
              </button>
            </div>
            <div className="rounded-lg border overflow-hidden">
              <Table columns={bookingCols} data={data?.recent_bookings ?? []} empty="Aucune réservation trouvée." />
            </div>
          </div>
        )}
      </div>

      {/* ── Fullscreen overlays ──────────────────────────────────────── */}
      {fullscreen === 'bookings' && (
        <FullscreenOverlay title="Réservations" onClose={() => setFullscreen(null)}>
          <BookingsChart
            data={chartData}
            height={420}
            hasCompare={hasCompare}
            peakBookings={peaks.bookings}
            filterParams={filterParams}
            totalBookings={kpis.total_bookings ?? 0}
            cmpBookings={kpisCmp?.total_bookings}
            onChartClick={handleChartClick}
          />
        </FullscreenOverlay>
      )}
      {fullscreen === 'revenue' && (
        <FullscreenOverlay title="CA + Panier moyen" onClose={() => setFullscreen(null)}>
          <RevenueChart
            data={chartData}
            height={420}
            hasCompare={hasCompare}
            peakRevenue={peaks.revenue}
            filterParams={filterParams}
            totalRevenue={kpis.total_revenue ?? 0}
            cmpRevenue={kpisCmp?.total_revenue}
            avgBasket={kpis.avg_basket}
            avgBasketDelta={avgBasketDelta}
            onChartClick={handleChartClick}
          />
        </FullscreenOverlay>
      )}

      {/* ── Filter Popup ─────────────────────────────────────────────── */}
      <FilterPopup
        open={filterOpen}
        onClose={() => setFilterOpen(false)}
        onApply={(filters) => {
          const params = {}
          if (filters.products?.length) params.products = filters.products.join(',')
          if (filters.channels?.length) params.channels = filters.channels.join(',')
          if (filters.statuses?.length) params.statuses = filters.statuses.join(',')
          if (filters.paymentMethods?.length) params.payment_methods = filters.paymentMethods.join(',')
          applyFilters(params)
        }}
        stats={stats}
      />
    </TooltipProvider>
  )
}
