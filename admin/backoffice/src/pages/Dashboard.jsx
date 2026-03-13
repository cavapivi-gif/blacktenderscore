import { useState, useCallback, useMemo, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import { Expand, Sparks } from 'iconoir-react'

import EventsCorrelator from '../components/EventsCorrelator'

import { cn, fmtCurrency, fmtNum, fmtPercent, fmtDecimal, fmtShort, delta } from '../lib/utils'
import { COLORS, API_STATUS, PAYMENT_METHOD_LABELS } from '../lib/constants'
import { STATUS_MAP } from '../lib/status'
import { useDashboard } from '../hooks/useDashboard'

import { TooltipProvider } from '../components/Tooltip'
import { Btn, Table, Notice, Spinner, Badge } from '../components/ui'

import {
  KpiCard, KpiCompact,
  StatsToolbar, FilterPopup,
  BookingsChart, RevenueChart,
  WeekdayChart, HeatmapChart, DonutChart, ChannelBreakdown,
  LeadTimeChart, CancellationChart,
  YoYChart, RepeatChart, RepeatRateChart, MetricEvolutionChart, AvisWidget, TopDays,
  TopProducts, FullscreenOverlay, TopPeriods,
} from '../components/dashboard'

// ── Helpers UI locaux ──────────────────────────────────────────────────────────

/** Titre de section avec sous-titre optionnel et action (lien) */
function SectionLabel({ children, sub, action }) {
  return (
    <div className="flex items-center justify-between mb-3">
      <div>
        <span className="text-[11px] text-muted-foreground uppercase tracking-widest" style={{ fontWeight: 500 }}>
          {children}
        </span>
        {sub && (
          <span className="ml-2 text-[10px] text-muted-foreground normal-case tracking-normal">
            {sub}
          </span>
        )}
      </div>
      {action}
    </div>
  )
}

/** Pill cliquable dans le header (produits, clients, DB, statut API) */
function ContextPill({ label, value, onClick, variant }) {
  const variantCls =
    variant === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-400' :
    variant === 'error'   ? 'border-red-200 bg-red-50 text-red-600 dark:bg-red-950/30 dark:border-red-800 dark:text-red-400' :
    onClick               ? 'border-border bg-background hover:bg-accent text-foreground cursor-pointer' :
                            'border-transparent bg-transparent text-muted-foreground cursor-default'
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn('flex items-center gap-1.5 px-2.5 py-1 rounded-md border text-xs transition-colors', variantCls)}
    >
      <span className="font-semibold tabular-nums">{value}</span>
      {label && <span className="opacity-75">{label}</span>}
    </button>
  )
}

// ── Dashboard ─────────────────────────────────────────────────────────────────

export default function Dashboard() {
  const navigate = useNavigate()
  const {
    data, stats, loading, statsLoading, error,
    filterParams, applyFilters, resetPeriod,
    chartData, hasCompare, kpis, kpisCmp,
    sparkBookings,
    peaks,
  } = useDashboard()

  const [fullscreen,   setFullscreen]   = useState(null)
  const [filterOpen,   setFilterOpen]   = useState(false)
  const [filterCount,  setFilterCount]  = useState(0)
  const [eventsOpen,   setEventsOpen]   = useState(false)
  const [activeKpi,    setActiveKpi]    = useState('bookings')
  const evolutionRef = useRef(null)

  /** Switche le chart Évolution ; reclique = retour réservations. */
  const toggleKpi = useCallback((key) => {
    setActiveKpi(prev => prev === key ? 'bookings' : key)
  }, [])

  // ── Deltas de comparaison ──────────────────────────────────────────────────
  const bookingsDelta   = delta(kpis.total_bookings,    kpisCmp?.total_bookings)
  const avgBasketDelta  = delta(kpis.avg_basket,        kpisCmp?.avg_basket)
  const cancelRateDelta = (() => {
    const d = delta(kpis.cancellation_rate, kpisCmp?.cancellation_rate)
    return d != null ? -d : null
  })()
  const uniqueCustDelta = delta(kpis.unique_customers,  kpisCmp?.unique_customers)
  const repeatDelta     = delta(kpis.repeat_rate,       kpisCmp?.repeat_rate)

  // ── Handlers ──────────────────────────────────────────────────────────────
  const handleChartClick = useCallback((clickData) => {
    if (!clickData?.activePayload?.length || filterParams.granularity !== 'day') return
    const key   = clickData.activePayload[0]?.payload?.key
    const month = key?.slice(0, 7)
    navigate(month ? `/planner?month=${month}` : '/planner')
  }, [filterParams.granularity, navigate])

  // ── Données dérivées ───────────────────────────────────────────────────────
  // PHP retourne le champ "bookings" (pas "count") — mapping + label lisibles
  const paymentDonutData = (stats?.payments?.by_method ?? []).map(p => ({
    name:  PAYMENT_METHOD_LABELS[p.method] ?? p.method,
    value: Number(p.bookings ?? p.count ?? 0),
  })).filter(p => p.value > 0)
  const channelDonutData = (stats?.by_channel ?? []).map(c => ({
    name: c.channel, value: c.bookings,
  }))

  const cancellationData = useMemo(() =>
    (stats?.periods ?? []).map(p => ({
      label:     p.label,
      total:     p.bookings  ?? 0,
      cancelled: p.cancelled ?? 0,
    }))
  , [stats])

  const cancellationDataCmp = useMemo(() =>
    hasCompare
      ? (stats?.compare ?? []).map(p => ({
          label:     p.label,
          total:     p.bookings  ?? 0,
          cancelled: p.cancelled ?? 0,
        }))
      : null
  , [stats, hasCompare])

  // Courbe taux d'annulation — dérivée de chartData (bookings + cancelled déjà présents)
  const cancelRateEvolution = useMemo(() =>
    chartData.map((p, i) => ({
      label:       p.label,
      rate:        p.bookings > 0 ? Math.round((p.cancelled / p.bookings) * 1000) / 10 : 0,
      rate_prev:   (hasCompare && p.bookings_prev != null)
                     ? Math.round(((stats?.compare?.[i]?.cancelled ?? 0) / Math.max(p.bookings_prev, 1)) * 1000) / 10
                     : null,
    }))
  , [chartData, hasCompare, stats])

  const periodSub = `${fmtShort(filterParams.from)} – ${fmtShort(filterParams.to)}`

  // ── Colonnes tableau récent ────────────────────────────────────────────────
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

  // ── Rendu ─────────────────────────────────────────────────────────────────
  return (
    <TooltipProvider>
      <div className="pb-12">

        {/* ═══════════════════════════════════════════════════════════════════
            ZONE 1 — HEADER
        ════════════════════════════════════════════════════════════════════ */}
        <div className="px-6 pt-5 pb-3 flex items-center justify-between gap-4 flex-wrap">
          <div>
            <h1 className="text-base font-semibold">Tableau de bord</h1>
            <p className="text-xs text-muted-foreground mt-0.5">{periodSub}</p>
          </div>

          {!loading && (
            <div className="flex items-center gap-1 flex-wrap">
              <ContextPill value={fmtNum(data?.products_count ?? 0)} label="produits" onClick={() => navigate('/products')} />
              <ContextPill value={fmtNum(data?.customers_total ?? 0)} label="clients"  onClick={() => navigate('/customers')} />
              <ContextPill value={fmtNum(data?.total_in_db ?? 0)} label="en DB" />
              {(() => {
                const s = API_STATUS[data?.api_status] ?? API_STATUS.ko
                return <ContextPill value={s.label} variant={s.variant} title={s.description} />
              })()}
            </div>
          )}
        </div>

        {error && (
          <div className="px-6 pb-3">
            <Notice type="error">{error}</Notice>
          </div>
        )}

        {/* Barre de filtre période */}
        <div className="border-y bg-muted/30 px-6 py-3">
          <StatsToolbar
            from={filterParams.from}
            to={filterParams.to}
            granularity={filterParams.granularity}
            showCompare={!!filterParams.compareFrom}
            onApply={applyFilters}
            filterCount={filterCount}
            onOpenFilters={() => setFilterOpen(true)}
          />
        </div>

        {/* ── Bouton IA événements ────────────────────────────────────────────── */}
        <div className="px-6 pt-4 pb-1">
          <button
            onClick={() => setEventsOpen(true)}
            className="bt-ai-btn-solid w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm"
          >
            <Sparks width={15} height={15} />
            Analyser l'impact des événements PACA sur vos réservations
          </button>
        </div>

        {/* ═══════════════════════════════════════════════════════════════════
            ZONE 2 — KPIs
        ════════════════════════════════════════════════════════════════════ */}
        {statsLoading ? (
          <div className="px-6 pt-6 flex items-center gap-2 text-sm text-muted-foreground">
            <Spinner size={14} /> Calcul des indicateurs…
          </div>
        ) : (
          <div className="px-6 pt-5">
            <SectionLabel sub={periodSub}>Indicateurs clés</SectionLabel>

            {/* 4 KpiCards principales */}
            <div className="grid grid-cols-2 xl:grid-cols-4 gap-3">
              <KpiCard
                label="Réservations"
                value={fmtNum(kpis.total_bookings)}
                delta={bookingsDelta}
                sparkData={sparkBookings}
                sparkColor={COLORS.current}
                sub={hasCompare
                  ? `vs ${fmtNum(kpisCmp?.total_bookings)} préc.`
                  : `${fmtNum(kpis.total_confirmed ?? 0)} confirmées`}
                active={activeKpi === 'bookings'}
                onClick={() => toggleKpi('bookings')}
              />
              <KpiCard
                label="Taux d'annulation"
                value={fmtPercent(kpis.cancellation_rate)}
                delta={cancelRateDelta}
                invertDelta
                alert={kpis.cancellation_rate > 10 && activeKpi !== 'cancel'}
                sub={`${fmtNum(kpis.total_cancelled ?? 0)} annulées sur la période`}
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
                  : 'Clients récurrents'}
                active={activeKpi === 'repeat'}
                onClick={() => toggleKpi('repeat')}
              />
            </div>

            {/* Strip cadence — métriques secondaires */}
            <div className="mt-3 rounded-lg border bg-muted/20 px-4 py-3">
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-4 gap-y-2">
                <KpiCompact label="Rés. / jour"     value={fmtDecimal(kpis.bookings_per_day)} />
                <KpiCompact label="Avance moy."     value={kpis.avg_lead_time_days ? `${fmtDecimal(kpis.avg_lead_time_days)}j` : '—'} />
                <KpiCompact label="Qté / rés."      value={fmtDecimal(kpis.avg_quantity)} />
                <KpiCompact label="Produits actifs" value={fmtNum(kpis.unique_products)} />
                <KpiCompact label="Jour de pic"     value={kpis.peak_weekday ?? '—'} />
              </div>
            </div>
          </div>
        )}

        {/* ═══════════════════════════════════════════════════════════════════
            ZONE 3 — CHART PRINCIPAL
        ════════════════════════════════════════════════════════════════════ */}
        {!statsLoading && (
          <div className="px-6 mt-6" ref={evolutionRef}>
            {/* Titre dynamique selon KPI actif */}
            <SectionLabel sub={periodSub}>
              {activeKpi === 'cancel'    ? "Évolution · Taux d'annulation"
             : activeKpi === 'customers' ? 'Évolution · Clients uniques'
             : activeKpi === 'repeat'    ? 'Évolution · Taux de repeat'
             : 'Évolution · Réservations'}
            </SectionLabel>

            {/* ── Vue taux d'annulation — courbe temporelle ───────────────────── */}
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
                  namePrev="Période préc."
                  formatter={v => `${v}%`}
                  height={220}
                />
              </div>
            )}

            {/* ── Vue clients — évolution réservations (proxy volume) ──────────── */}
            {activeKpi === 'customers' && (
              <div className="rounded-lg border p-5" style={{ borderColor: '#e3e1db5c' }}>
                <MetricEvolutionChart
                  data={chartData}
                  dataKey="bookings"
                  dataKeyPrev={hasCompare ? 'bookings_prev' : undefined}
                  color="#0a0a0a"
                  colorPrev="#a3a3a3"
                  unit=""
                  name="Réservations"
                  namePrev="Période préc."
                  height={220}
                />
              </div>
            )}

            {/* ── Vue taux de repeat — courbe mensuelle ───────────────────────── */}
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

            {/* ── Vue réservations (défaut) ────────────────────────────────────── */}
            {(activeKpi === 'bookings' || activeKpi == null) && (
              chartData.length === 0 ? (
                <div className="rounded-lg border bg-card p-10 text-center">
                  <p className="text-sm font-medium">Aucune réservation sur cette période</p>
                  <p className="text-xs text-muted-foreground mt-1">Essayez d'élargir la plage de dates.</p>
                  <button onClick={resetPeriod} className="mt-4 text-xs text-primary underline hover:text-primary/80">
                    Voir tout l'historique
                  </button>
                </div>
              ) : (
                <div className="rounded-lg border bg-card p-5 relative">
                  <button onClick={() => setFullscreen('bookings')}
                    className="absolute top-3 right-3 h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors" title="Plein écran">
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
        )}

        {/* ═══════════════════════════════════════════════════════════════════
            ZONE 3b — SAISONNALITÉ YoY
        ════════════════════════════════════════════════════════════════════ */}
        {!statsLoading && stats?.yoy?.length > 1 && (
          <div className="px-6 mt-6">
            <YoYChart data={stats.yoy} />
          </div>
        )}

        {/* ═══════════════════════════════════════════════════════════════════
            ZONE 3c — TOP PÉRIODES (33 % width)
        ════════════════════════════════════════════════════════════════════ */}
        {!statsLoading && chartData.length > 0 && (
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

        {/* ═══════════════════════════════════════════════════════════════════
            ZONE 4 — ANALYSE COMPORTEMENTALE
            Jours de la semaine · Avance de réservation · Taux d'annulation
        ════════════════════════════════════════════════════════════════════ */}
        {!statsLoading && (stats?.by_weekday?.length > 0 || stats?.lead_time_buckets?.length > 0 || cancellationData.length > 0) && (
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

        {/* ═══════════════════════════════════════════════════════════════════
            ZONE 4b — FIDÉLITÉ & AVIS & TOP JOURS
        ════════════════════════════════════════════════════════════════════ */}
        {!statsLoading && (
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
                  comparePeriod={hasCompare ? `${fmtShort(filterParams.compareFrom)} – ${fmtShort(filterParams.compareTo)}` : undefined}
                />
              )}
            </div>
          </div>
        )}

        {/* ═══════════════════════════════════════════════════════════════════
            ZONE 5 — DISTRIBUTION
            Heatmap · Top produits · Canaux + Paiements
        ════════════════════════════════════════════════════════════════════ */}
        {!statsLoading && (stats?.heatmap?.length > 0 || stats?.by_product?.length > 0 || stats?.channel_status?.length > 0 || paymentDonutData.length > 0) && (
          <div className="px-6 mt-6">
            <SectionLabel sub={periodSub}>Distribution</SectionLabel>
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

              {/* Heatmap activité */}
              {stats?.heatmap?.length > 0 && (
                <HeatmapChart
                  data={stats.heatmap}
                  dataCmp={hasCompare ? (stats?.heatmap_compare ?? null) : null}
                  dataCancellations={stats?.heatmap_cancellations ?? null}
                />
              )}

              {/* Top produits */}
              {stats?.by_product?.length > 0 && (
                <TopProducts data={stats.by_product} filterParams={filterParams} />
              )}

              {/* Canaux (BarChart status) + Paiements (Donut) empilés */}
              {(stats?.channel_status?.length > 0 || paymentDonutData.length > 0) && (
                <div className="space-y-4">
                  {/* ChannelBreakdown — canal × statut (confirmé/en attente/annulé) */}
                  {stats?.channel_status?.length > 0 && (
                    <ChannelBreakdown
                      channelStatus={stats.channel_status}
                      dateRange={periodSub}
                    />
                  )}
                  <DonutChart data={paymentDonutData} title="Méthodes de paiement" showEmpty />
                </div>
              )}

            </div>
          </div>
        )}

        {/* ═══════════════════════════════════════════════════════════════════
            ZONE 6 — DERNIÈRES RÉSERVATIONS
        ════════════════════════════════════════════════════════════════════ */}
        {!loading && (
          <div className="px-6 mt-6">
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

        {/* ── Overlays & popups ─────────────────────────────────────────── */}
        {fullscreen === 'bookings' && (
          <FullscreenOverlay title="Réservations" onClose={() => setFullscreen(null)}>
            <BookingsChart data={chartData} height={420} hasCompare={hasCompare}
              peakBookings={peaks.bookings} filterParams={filterParams}
              totalBookings={kpis.total_bookings ?? 0} cmpBookings={kpisCmp?.total_bookings}
              onChartClick={handleChartClick} />
          </FullscreenOverlay>
        )}
        {fullscreen === 'revenue' && (
          <FullscreenOverlay title="CA + Panier moyen" onClose={() => setFullscreen(null)}>
            <RevenueChart data={chartData} height={420} hasCompare={hasCompare}
              peakRevenue={peaks.revenue} filterParams={filterParams}
              totalRevenue={kpis.total_revenue ?? 0} cmpRevenue={kpisCmp?.total_revenue}
              avgBasket={kpis.avg_basket} avgBasketDelta={avgBasketDelta}
              onChartClick={handleChartClick} />
          </FullscreenOverlay>
        )}

        <FilterPopup
          open={filterOpen}
          onClose={() => setFilterOpen(false)}
          onApply={(filters) => {
            const total = (filters.products?.length ?? 0) + (filters.channels?.length ?? 0)
              + (filters.statuses?.length ?? 0) + (filters.paymentMethods?.length ?? 0)
            setFilterCount(total)
            const params = {}
            if (filters.products?.length)       params.products        = filters.products.join(',')
            if (filters.channels?.length)       params.channels        = filters.channels.join(',')
            if (filters.statuses?.length)       params.statuses        = filters.statuses.join(',')
            if (filters.paymentMethods?.length) params.payment_methods = filters.paymentMethods.join(',')
            applyFilters(params)
          }}
          stats={stats}
        />

        <EventsCorrelator
          open={eventsOpen}
          onClose={() => setEventsOpen(false)}
          from={filterParams.from}
          to={filterParams.to}
          bookingsData={stats?.periods ?? null}
        />

      </div>
    </TooltipProvider>
  )
}
