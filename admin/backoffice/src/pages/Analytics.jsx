/**
 * Analytics — Performance digitale : GA4, Search Console, Réservations.
 * Charge GA4 + réservations en parallèle dès le montage.
 * La CrossKpiStrip et la GlobalView reçoivent les données directement (pas via callbacks d'onglets).
 */
import { useState, useEffect, useCallback } from 'react'
import { api } from '../lib/api'
import { PageHeader } from '../components/ui'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/Tabs'
import { StatsToolbar } from '../components/dashboard/StatsToolbar'
import { today, daysAgo } from '../lib/utils'
import {
  CrossKpiStrip,
  GlobalView,
  GA4Section,
  SearchConsoleSection,
  BusinessSection,
} from '../components/analytics'

const BOOKING_INCLUDE = 'periods,kpis,by_product,by_channel,by_weekday,payments,lead_time_buckets'

export default function Analytics() {
  const [params, setParams] = useState({
    from:        daysAgo(29),
    to:          today(),
    compareFrom: '',
    compareTo:   '',
  })

  // ── Données GA4 ──────────────────────────────────────────────────────────
  const [ga4Data,    setGa4Data]    = useState(null)
  const [ga4Loading, setGa4Loading] = useState(true)

  // ── Données réservations ─────────────────────────────────────────────────
  const [bookingStats,   setBookingStats]   = useState(null)
  const [bookingLoading, setBookingLoading] = useState(true)

  const hasCompare = !!(params.compareFrom && params.compareTo)

  // Charge GA4 au changement de période
  useEffect(() => {
    setGa4Data(null)
    setGa4Loading(true)
    api.ga4Stats({
      from:         params.from,
      to:           params.to,
      compare_from: params.compareFrom || undefined,
      compare_to:   params.compareTo   || undefined,
    })
      .then(d => { if (d?.configured && !d?.error) setGa4Data(d) })
      .catch(() => {})
      .finally(() => setGa4Loading(false))
  }, [params.from, params.to, params.compareFrom, params.compareTo])

  // Charge les stats réservations au changement de période
  useEffect(() => {
    setBookingStats(null)
    setBookingLoading(true)
    const p = { from: params.from, to: params.to, granularity: 'day', include: BOOKING_INCLUDE }
    if (params.compareFrom) p.compare_from = params.compareFrom
    if (params.compareTo)   p.compare_to   = params.compareTo
    api.bookingsStats(p)
      .then(setBookingStats)
      .catch(() => {})
      .finally(() => setBookingLoading(false))
  }, [params.from, params.to, params.compareFrom, params.compareTo])

  const handleApply = useCallback((p) => {
    setParams({
      from:        p.from,
      to:          p.to,
      compareFrom: p.compareFrom ?? '',
      compareTo:   p.compareTo  ?? '',
    })
  }, [])

  const stripLoading = ga4Loading || bookingLoading

  return (
    <div className="px-6 py-6 max-w-screen-xl mx-auto space-y-4">
      <PageHeader
        title="Analytics"
        subtitle="Performance digitale — Google Analytics 4, Search Console & Réservations"
      />

      {/* Toolbar date */}
      <div className="rounded-lg border bg-card px-4 py-3">
        <StatsToolbar
          from={params.from}
          to={params.to}
          granularity="day"
          showCompare={hasCompare}
          onApply={handleApply}
        />
      </div>

      {/* Strip KPI cross-source — toujours visible au-dessus des onglets */}
      <CrossKpiStrip
        ga4Totals={ga4Data?.totals}
        ga4TotalsCompare={ga4Data?.totals_compare}
        bookingKpis={bookingStats?.kpis}
        bookingKpisCmp={bookingStats?.kpis_compare}
        loading={stripLoading}
      />

      {/* Onglets */}
      <Tabs defaultValue="global">
        <TabsList className="rounded-b-none border border-b-0 border-border bg-muted w-full justify-start gap-0 p-0 h-auto">
          <TabsTrigger value="global"   className="rounded-none rounded-tl-md px-4 py-2.5 text-sm data-[state=active]:shadow-none data-[state=active]:bg-white data-[state=active]:text-foreground font-medium">
            Vue globale
          </TabsTrigger>
          <TabsTrigger value="ga4"      className="rounded-none px-4 py-2.5 text-sm data-[state=active]:shadow-none data-[state=active]:bg-white data-[state=active]:text-foreground font-medium">
            Google Analytics 4
          </TabsTrigger>
          <TabsTrigger value="search"   className="rounded-none px-4 py-2.5 text-sm data-[state=active]:shadow-none data-[state=active]:bg-white data-[state=active]:text-foreground font-medium">
            Search Console
          </TabsTrigger>
          <TabsTrigger value="bookings" className="rounded-none px-4 py-2.5 text-sm data-[state=active]:shadow-none data-[state=active]:bg-white data-[state=active]:text-foreground font-medium">
            Réservations
          </TabsTrigger>
        </TabsList>

        {/* Vue globale — timeline + donuts + weekday + top produits */}
        <TabsContent value="global" className="mt-0 border border-t-0 rounded-b-lg p-5">
          <GlobalView
            ga4Data={ga4Data}
            bookingStats={bookingStats}
            from={params.from}
            to={params.to}
            loading={stripLoading}
          />
        </TabsContent>

        {/* GA4 détaillé */}
        <TabsContent value="ga4" className="mt-0 border border-t-0 rounded-b-lg p-5">
          <GA4Section
            from={params.from}
            to={params.to}
            compareFrom={params.compareFrom}
            compareTo={params.compareTo}
          />
        </TabsContent>

        {/* Search Console détaillé */}
        <TabsContent value="search" className="mt-0 border border-t-0 rounded-b-lg p-5">
          <SearchConsoleSection
            from={params.from}
            to={params.to}
            compareFrom={params.compareFrom}
            compareTo={params.compareTo}
          />
        </TabsContent>

        {/* Réservations détaillées */}
        <TabsContent value="bookings" className="mt-0 border border-t-0 rounded-b-lg p-5">
          <BusinessSection
            from={params.from}
            to={params.to}
            compareFrom={params.compareFrom}
            compareTo={params.compareTo}
          />
        </TabsContent>
      </Tabs>
    </div>
  )
}
