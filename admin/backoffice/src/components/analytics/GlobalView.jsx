/**
 * GlobalView — Contenu de l'onglet "Vue globale" dans Analytics.
 * Combine données GA4 + réservations : timeline duale, donuts, weekday, top produits.
 */
import { useMemo } from 'react'
import { UnifiedTimeline } from './UnifiedTimeline'
import { DonutChart }       from '../dashboard/DonutChart'
import { WeekdayChart }     from '../dashboard/WeekdayChart'
import { TopProducts }      from '../dashboard/TopProducts'
import { Spinner }          from '../ui'

// Couleurs donut GA4 canaux
const GA4_CHANNEL_COLORS = ['#10b981','#6366f1','#f59e0b','#0ea5e9','#ec4899','#8b5cf6','#f97316','#64748b']
// Couleurs donut statuts réservations
const STATUS_COLORS = { confirmed: '#10b981', cancelled: '#ef4444', pending: '#f59e0b' }

/**
 * @param {object} ga4Data        - Réponse complète GA4 (totals, timeline, by_channel, top_pages)
 * @param {object} bookingStats   - Réponse complète bookingsStats (kpis, periods, by_product, by_weekday, by_channel)
 * @param {string} from
 * @param {string} to
 * @param {boolean} loading       - Chargement en cours
 */
export function GlobalView({ ga4Data, bookingStats, from, to, loading }) {
  const hasGA4      = !!(ga4Data?.configured && !ga4Data?.error && ga4Data?.totals)
  const hasBookings = !!(bookingStats?.kpis)

  // ── Donut 1 : canaux d'acquisition GA4 ─────────────────────────────────────
  const channelData = useMemo(() => {
    if (!ga4Data?.by_channel?.length) return []
    return ga4Data.by_channel
      .filter(c => c.sessions > 0)
      .sort((a, b) => b.sessions - a.sessions)
      .slice(0, 8)
      .map((c, i) => ({
        name:  c.channel,
        value: c.sessions,
        color: GA4_CHANNEL_COLORS[i % GA4_CHANNEL_COLORS.length],
      }))
  }, [ga4Data])

  // ── Donut 2 : statuts réservations (confirmées / annulées) ─────────────────
  const statusData = useMemo(() => {
    const k = bookingStats?.kpis ?? {}
    const total = k.total_bookings ?? 0
    if (!total) return []
    const cancelled  = Math.round((k.cancellation_rate ?? 0) / 100 * total)
    const confirmed  = total - cancelled
    return [
      { name: 'Confirmées', value: confirmed, color: '#10b981' },
      { name: 'Annulées',   value: cancelled, color: '#ef4444' },
    ].filter(d => d.value > 0)
  }, [bookingStats])

  // ── Donut 3 : méthodes de paiement (si dispo) ──────────────────────────────
  const paymentData = useMemo(() => {
    if (!bookingStats?.payments?.length) return []
    return bookingStats.payments
      .filter(p => p.count > 0)
      .slice(0, 6)
      .map((p, i) => ({
        name:  p.method || 'Autre',
        value: p.count,
        color: GA4_CHANNEL_COLORS[i % GA4_CHANNEL_COLORS.length],
      }))
  }, [bookingStats])

  // ── WeekdayChart — format attendu: [{dow, label, bookings}] ────────────────
  const weekdayData = useMemo(() => bookingStats?.by_weekday ?? [], [bookingStats])

  if (loading) {
    return (
      <div className="flex justify-center py-20">
        <Spinner size={20} />
      </div>
    )
  }

  if (!hasGA4 && !hasBookings) {
    return (
      <p className="text-sm text-muted-foreground text-center py-12">
        Aucune donnée disponible. Configurez GA4 dans Réglages → API et synchronisez vos réservations.
      </p>
    )
  }

  return (
    <div className="space-y-5">

      {/* ── Timeline unifiée — dual axe ──────────────────────────────────── */}
      <UnifiedTimeline
        ga4Timeline={ga4Data?.timeline ?? null}
        bookingPeriods={bookingStats?.periods ?? null}
        from={from}
        to={to}
      />

      {/* ── Row 2 : Donuts côte à côte ───────────────────────────────────── */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

        {/* Canaux GA4 */}
        {channelData.length > 0 && (
          <DonutChart
            data={channelData}
            title="Acquisition GA4 — Sessions par canal"
            nameKey="name"
            valueKey="value"
            colors={channelData.map(c => c.color)}
            showEmpty
          />
        )}

        {/* Statuts réservations */}
        {statusData.length > 0 && (
          <DonutChart
            data={statusData}
            title="Réservations — Confirmées vs Annulées"
            nameKey="name"
            valueKey="value"
            colors={statusData.map(d => d.color)}
            showEmpty
          />
        )}

        {/* Méthodes de paiement */}
        {paymentData.length > 0 && (
          <DonutChart
            data={paymentData}
            title="Moyens de paiement"
            nameKey="name"
            valueKey="value"
            colors={paymentData.map(d => d.color)}
            showEmpty
          />
        )}
      </div>

      {/* ── Row 3 : WeekdayChart + Top pages GA4 ─────────────────────────── */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {/* Répartition par jour de semaine */}
        {weekdayData.length > 0 && (
          <div className="rounded-lg border bg-card p-5">
            <WeekdayChart data={weekdayData} height={160} />
          </div>
        )}

        {/* Top pages GA4 — mini version */}
        {ga4Data?.top_pages?.length > 0 && (
          <div className="rounded-lg border bg-card overflow-hidden">
            <div className="px-4 py-3 bg-muted/30 border-b">
              <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">Top pages — Vues</p>
            </div>
            <div className="divide-y">
              {ga4Data.top_pages.slice(0, 8).map((p, i) => {
                const maxViews = ga4Data.top_pages[0]?.views || 1
                const pct = Math.round((p.views / maxViews) * 100)
                const shortPath = p.page.length > 38 ? '…' + p.page.slice(-38) : p.page
                return (
                  <div key={i} className="px-4 py-2 hover:bg-muted/20 transition-colors">
                    <div className="flex items-center gap-2 justify-between">
                      <div className="flex items-center gap-2 min-w-0 flex-1">
                        <span className="text-[10px] text-muted-foreground w-4 text-right tabular-nums shrink-0">{i + 1}</span>
                        <span className="text-xs font-mono truncate text-muted-foreground" title={p.page}>{shortPath}</span>
                      </div>
                      <span className="text-xs font-semibold tabular-nums shrink-0">{Number(p.views).toLocaleString('fr-FR')}</span>
                    </div>
                    <div className="ml-6 mt-0.5 h-1 bg-muted rounded-full overflow-hidden">
                      <div className="h-full rounded-full bg-sky-400" style={{ width: `${pct}%` }} />
                    </div>
                  </div>
                )
              })}
            </div>
          </div>
        )}
      </div>

      {/* ── Row 4 : Top produits réservations ────────────────────────────── */}
      {bookingStats?.by_product?.length > 0 && (
        <TopProducts data={bookingStats.by_product} filterParams={{ from, to }} />
      )}

    </div>
  )
}
