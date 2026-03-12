import { useState, useCallback, useMemo } from 'react'
import { useNavigate } from 'react-router-dom'
import { Expand, FilterList } from 'iconoir-react'

import { cn, fmtCurrency, fmtNum, fmtPercent, fmtDecimal, fmtShort, delta } from '../lib/utils'
import { COLORS, FEATURES } from '../lib/constants'
import { STATUS_MAP } from '../lib/status'
import { useDashboard } from '../hooks/useDashboard'

import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/Tabs'
import { TooltipProvider } from '../components/Tooltip'
import { Table, Notice, Spinner, Badge } from '../components/ui'

import {
  KpiCard, KpiCompact,
  StatsToolbar, FilterPopup,
  BookingsChart, RevenueChart,
  ChannelBreakdown, WeekdayChart,
  HeatmapChart, DonutChart,
  LeadTimeChart, CancellationChart,
  TopProducts, FullscreenOverlay,
} from '../components/dashboard'

// ── SectionLabel ──────────────────────────────────────────────────────────────
function SectionLabel({ children, sub, action }) {
  return (
    <div className="flex items-center justify-between mb-3">
      <div>
        <span className="text-[11px] font-semibold text-muted-foreground uppercase tracking-widest">
          {children}
        </span>
        {sub && <span className="ml-2 text-[10px] text-muted-foreground normal-case tracking-normal">{sub}</span>}
      </div>
      {action}
    </div>
  )
}

// ── ContextPill ───────────────────────────────────────────────────────────────
function ContextPill({ label, value, onClick }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        'flex items-center gap-1.5 px-2.5 py-1 rounded-md border text-xs transition-colors',
        onClick
          ? 'border-border bg-background hover:bg-accent text-foreground cursor-pointer'
          : 'border-transparent bg-transparent text-muted-foreground cursor-default',
      )}
    >
      <span className="font-semibold tabular-nums">{value}</span>
      <span className="text-muted-foreground">{label}</span>
    </button>
  )
}

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
  const bookingsDelta    = delta(kpis.total_bookings,    kpisCmp?.total_bookings)
  const revenueDelta     = delta(kpis.total_revenue,     kpisCmp?.total_revenue)
  const avgBasketDelta   = delta(kpis.avg_basket,        kpisCmp?.avg_basket)
  const cancelRateDeltaR = delta(kpis.cancellation_rate, kpisCmp?.cancellation_rate)
  const cancelRateDelta  = cancelRateDeltaR != null ? -cancelRateDeltaR : null
  const uniqueCustDelta  = delta(kpis.unique_customers,  kpisCmp?.unique_customers)
  const repeatDelta      = delta(kpis.repeat_rate,       kpisCmp?.repeat_rate)

  const handleChartClick = useCallback((clickData) => {
    if (!clickData?.activePayload?.length || filterParams.granularity !== 'day') return
    const key = clickData.activePayload[0]?.payload?.key
    const month = key?.slice(0, 7)
    navigate(month ? `/planner?month=${month}` : '/planner')
  }, [filterParams.granularity, navigate])

  const channelDonutData  = (stats?.by_channel ?? []).map(c => ({ name: c.channel, value: c.bookings }))
  const paymentDonutData  = (stats?.payments?.by_method ?? []).map(p => ({ name: p.method, value: p.count }))
  const periodSub         = `${fmtShort(filterParams.from)} – ${fmtShort(filterParams.to)}`

  // Taux d'annulation par période — dérivé de stats.periods (déjà granularisé)
  const cancellationData = useMemo(() =>
    (stats?.periods ?? []).map(p => ({
      label:     p.label,
      total:     p.bookings    ?? 0,
      cancelled: p.cancelled   ?? 0,
    }))
  , [stats])

  const bookingCols = [
    { key: 'booking_ref',   label: 'Référence' },
    { key: 'product_name',  label: 'Produit' },
    { key: 'booking_date',  label: 'Date',    render: r => r.booking_date?.slice(0, 10) ?? '—' },
    { key: 'customer_name', label: 'Client' },
    { key: 'total_price',   label: 'Montant', render: r => r.total_price ? fmtCurrency(r.total_price) : '—' },
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

        {/* ── Header ────────────────────────────────────────────────────── */}
        <div className="px-6 pt-5 pb-4 flex items-start justify-between gap-4">
          <div>
            <h1 className="text-base font-semibold">Tableau de bord</h1>
            <p className="text-xs text-muted-foreground mt-0.5">
              {fmtShort(filterParams.from)} – {fmtShort(filterParams.to)}
            </p>
          </div>

          {/* Context pills — DB overview in the header */}
          {!loading && (
            <div className="flex items-center gap-1 flex-wrap justify-end">
              <ContextPill
                value={fmtNum(data?.products_count ?? 0)}
                label="produits"
                onClick={() => navigate('/products')}
              />
              <ContextPill
                value={fmtNum(data?.customers_total ?? 0)}
                label="clients"
                onClick={() => navigate('/customers')}
              />
              <ContextPill
                value={fmtNum(data?.total_in_db ?? 0)}
                label="en DB"
              />
              <ContextPill
                value={data?.api_status === 'ok' ? 'API ✓' : 'API ✗'}
                label=""
              />
              <button
                onClick={() => setFilterOpen(true)}
                className={cn(
                  'ml-1 flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium border transition-colors',
                  'bg-card border-border text-muted-foreground hover:text-foreground hover:bg-accent',
                )}
              >
                <FilterList width={13} height={13} />
                Filtres
              </button>
            </div>
          )}
        </div>

        {error && (
          <div className="px-6 pb-4">
            <Notice type="error">{error}</Notice>
          </div>
        )}

        {/* ── Filter strip — full-width, distinct background ──────────── */}
        <div className="border-y bg-muted/30 px-6 py-3">
          <StatsToolbar
            from={filterParams.from}
            to={filterParams.to}
            granularity={filterParams.granularity}
            showCompare={!!filterParams.compareFrom}
            onApply={applyFilters}
          />
        </div>

        {/* ── KPI sections ──────────────────────────────────────────────── */}
        {statsLoading ? (
          <div className="px-6 pt-6 flex items-center gap-2 text-sm text-muted-foreground">
            <Spinner size={14} /> Calcul des indicateurs…
          </div>
        ) : (
          <div className="px-6 pt-5 space-y-6">

            {/* Section 1 — Activité */}
            <div>
              <SectionLabel sub={periodSub}>Activité</SectionLabel>
              <div className="grid grid-cols-2 lg:grid-cols-3 gap-3">
                {FEATURES.revenue && (
                  <KpiCard
                    label="CA total"
                    value={fmtCurrency(kpis.total_revenue)}
                    delta={revenueDelta}
                    sparkData={sparkRevenue}
                    sparkColor={COLORS.current}
                    sub={hasCompare ? `vs ${fmtCurrency(kpisCmp?.total_revenue)} préc.` : 'CA confirmé'}
                  />
                )}
                <KpiCard
                  label="Réservations"
                  value={fmtNum(kpis.total_bookings)}
                  delta={bookingsDelta}
                  sparkData={sparkBookings}
                  sparkColor={COLORS.current}
                  sub={hasCompare ? `vs ${fmtNum(kpisCmp?.total_bookings)} préc.` : `${fmtNum(kpis.total_confirmed ?? 0)} confirmées`}
                />
                {FEATURES.revenue && (
                  <KpiCard
                    label="Panier moyen"
                    value={fmtCurrency(kpis.avg_basket)}
                    delta={avgBasketDelta}
                    sparkData={sparkAvgBasket}
                    sparkColor={COLORS.basket}
                    sub={kpis.paid_bookings > 0
                      ? `Sur ${fmtNum(kpis.paid_bookings)} rés. payantes`
                      : 'Rés. avec prix renseigné'}
                  />
                )}
                <KpiCard
                  label="Taux annulation"
                  value={fmtPercent(kpis.cancellation_rate)}
                  delta={cancelRateDelta}
                  invertDelta
                  alert={kpis.cancellation_rate > 10}
                  sub={`${fmtNum(kpis.total_cancelled ?? 0)} annulées`}
                />
              </div>
            </div>

            {/* Section 2 — Cadence (secondary metrics strip) */}
            <div>
              <SectionLabel>Cadence &amp; mix</SectionLabel>
              <div className="rounded-lg border bg-muted/20 px-4 py-3">
                <div className="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-x-4 gap-y-3 divide-x-0">
                  {FEATURES.revenue && <KpiCompact label="CA / jour"      value={fmtCurrency(kpis.revenue_per_day)} />}
                  <KpiCompact label="Rés. / jour"    value={fmtDecimal(kpis.bookings_per_day)} />
                  <KpiCompact label="Lead time"      value={kpis.avg_lead_time_days ? `${fmtDecimal(kpis.avg_lead_time_days)}j` : '—'} />
                  <KpiCompact label="Qté moyenne"    value={fmtDecimal(kpis.avg_quantity)} />
                  <KpiCompact label="Produits actifs" value={fmtNum(kpis.unique_products)} />
                  <KpiCompact label="Taux impayés"   value={fmtPercent(kpis.unpaid_rate)} invertDelta />
                  <KpiCompact label="Jour de pic"    value={kpis.peak_weekday ?? '—'} />
                  <KpiCompact
                    label="Top produit"
                    value={kpis.top_product_name
                      ? (kpis.top_product_name.length > 15
                          ? kpis.top_product_name.slice(0, 15) + '…'
                          : kpis.top_product_name)
                      : '—'}
                  />
                </div>
              </div>
            </div>

            {/* Section 3 — Clientèle */}
            <div>
              <SectionLabel>Clientèle</SectionLabel>
              <div className="grid grid-cols-2 gap-3 max-w-sm">
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
            </div>

          </div>
        )}

        {/* ── Charts ────────────────────────────────────────────────────── */}
        {!statsLoading && (
          <div className="px-6 mt-6">
            <SectionLabel sub={periodSub}>Évolution sur la période</SectionLabel>
            {chartData.length === 0 ? (
              <div className="rounded-lg border bg-card p-10 text-center">
                <p className="text-sm font-medium">Aucune réservation sur cette période</p>
                <p className="text-xs text-muted-foreground mt-1">Essayez d'élargir la plage de dates.</p>
                <button onClick={resetPeriod} className="mt-4 text-xs text-primary underline hover:text-primary/80">
                  Voir tout l'historique
                </button>
              </div>
            ) : (
              <Tabs defaultValue="bookings">
                <div className="flex items-center justify-between mb-4">
                  <TabsList>
                    <TabsTrigger value="bookings">Réservations</TabsTrigger>
                    {FEATURES.revenue && <TabsTrigger value="revenue">CA + Panier moyen</TabsTrigger>}
                  </TabsList>
                </div>
                <TabsContent value="bookings">
                  <div className="rounded-lg border bg-card p-5 relative">
                    <button onClick={() => setFullscreen('bookings')}
                      className="absolute top-3 right-3 h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors" title="Plein écran">
                      <Expand width={13} height={13} />
                    </button>
                    <BookingsChart
                      data={chartData} height={220} hasCompare={hasCompare}
                      peakBookings={peaks.bookings} filterParams={filterParams}
                      totalBookings={kpis.total_bookings ?? 0} cmpBookings={kpisCmp?.total_bookings}
                      onChartClick={handleChartClick}
                    />
                  </div>
                </TabsContent>
                {FEATURES.revenue && (
                  <TabsContent value="revenue">
                    <div className="rounded-lg border bg-card p-5 relative">
                      <button onClick={() => setFullscreen('revenue')}
                        className="absolute top-3 right-3 h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors" title="Plein écran">
                        <Expand width={13} height={13} />
                      </button>
                      <RevenueChart
                        data={chartData} height={220} hasCompare={hasCompare}
                        peakRevenue={peaks.revenue} filterParams={filterParams}
                        totalRevenue={kpis.total_revenue ?? 0} cmpRevenue={kpisCmp?.total_revenue}
                        avgBasket={kpis.avg_basket} avgBasketDelta={avgBasketDelta}
                        onChartClick={handleChartClick}
                      />
                    </div>
                  </TabsContent>
                )}
              </Tabs>
            )}
          </div>
        )}

        {/* ── Breakdown — Canaux + Jours + Annulations ─────────────── */}
        {!statsLoading && (stats?.by_channel?.length > 0 || stats?.by_weekday?.length > 0 || cancellationData.length > 0) && (
          <div className="px-6 mt-6">
            <SectionLabel sub={periodSub}>Répartition</SectionLabel>
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
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
              {cancellationData.length > 0 && (
                <CancellationChart data={cancellationData} />
              )}
            </div>
          </div>
        )}

        {/* ── Donuts ────────────────────────────────────────────────── */}
        {!statsLoading && channelDonutData.length > 0 && (
          <div className="px-6 mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
            <DonutChart data={channelDonutData} title="Canaux — répartition %" />
            <DonutChart data={paymentDonutData} title="Méthodes de paiement" showEmpty />
          </div>
        )}

        {/* ── Heatmap + Lead Time + Top Products ───────────────────── */}
        {!statsLoading && (stats?.heatmap?.length > 0 || stats?.lead_time_buckets?.length > 0 || stats?.by_product?.length > 0) && (
          <div className="px-6 mt-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
            {stats?.heatmap?.length > 0 && <HeatmapChart data={stats.heatmap} />}
            {stats?.lead_time_buckets?.length > 0 && (
              <LeadTimeChart
                data={stats.lead_time_buckets}
                dataCmp={stats?.lead_time_buckets_compare ?? null}
              />
            )}
            {stats?.by_product?.length > 0 && (
              <TopProducts data={stats.by_product} filterParams={filterParams} />
            )}
          </div>
        )}

        {/* ── Recent bookings ──────────────────────────────────────── */}
        {!loading && (
          <div className="px-6 mt-6 mb-10">
            <SectionLabel
              action={
                <button onClick={() => navigate('/bookings')}
                  className="text-xs text-muted-foreground underline hover:text-foreground">
                  Tout voir
                </button>
              }
            >
              Dernières réservations
            </SectionLabel>
            <div className="rounded-lg border overflow-hidden">
              <Table columns={bookingCols} data={data?.recent_bookings ?? []} empty="Aucune réservation trouvée." />
            </div>
          </div>
        )}

        {/* ── Fullscreen overlays ───────────────────────────────────── */}
        {fullscreen === 'bookings' && (
          <FullscreenOverlay title="Réservations" onClose={() => setFullscreen(null)}>
            <BookingsChart
              data={chartData} height={420} hasCompare={hasCompare}
              peakBookings={peaks.bookings} filterParams={filterParams}
              totalBookings={kpis.total_bookings ?? 0} cmpBookings={kpisCmp?.total_bookings}
              onChartClick={handleChartClick}
            />
          </FullscreenOverlay>
        )}
        {fullscreen === 'revenue' && (
          <FullscreenOverlay title="CA + Panier moyen" onClose={() => setFullscreen(null)}>
            <RevenueChart
              data={chartData} height={420} hasCompare={hasCompare}
              peakRevenue={peaks.revenue} filterParams={filterParams}
              totalRevenue={kpis.total_revenue ?? 0} cmpRevenue={kpisCmp?.total_revenue}
              avgBasket={kpis.avg_basket} avgBasketDelta={avgBasketDelta}
              onChartClick={handleChartClick}
            />
          </FullscreenOverlay>
        )}

        {/* ── Filter Popup ──────────────────────────────────────────── */}
        <FilterPopup
          open={filterOpen}
          onClose={() => setFilterOpen(false)}
          onApply={(filters) => {
            const params = {}
            if (filters.products?.length)       params.products        = filters.products.join(',')
            if (filters.channels?.length)       params.channels        = filters.channels.join(',')
            if (filters.statuses?.length)       params.statuses        = filters.statuses.join(',')
            if (filters.paymentMethods?.length) params.payment_methods = filters.paymentMethods.join(',')
            applyFilters(params)
          }}
          stats={stats}
        />

      </div>
    </TooltipProvider>
  )
}
