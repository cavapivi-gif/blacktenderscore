/**
 * CrossKpiStrip — Bande KPI Bloomberg-style combinant web + réservations.
 * Chaque métrique a une couleur, une explication et un delta vs période précédente.
 */
import { calcDelta, fmtNum, fmtPct } from './analyticsUtils'

// Couleur + explication de chaque métrique
const META = {
  sessions:    { color: '#10b981', label: 'Sessions',      tip: 'Visites totales du site web' },
  users:       { color: '#6366f1', label: 'Utilisateurs',  tip: 'Visiteurs uniques actifs' },
  pageviews:   { color: '#0ea5e9', label: 'Pages vues',    tip: 'Pages consultées au total' },
  pagesPerSes: { color: '#06b6d4', label: 'Pages/Session', tip: 'Pages vues ÷ Sessions — engagement moyen par visite' },
  bounce:      { color: '#f59e0b', label: 'Taux rebond',   tip: '% visiteurs quittant sans interaction — plus bas = mieux' },
  reservations:{ color: '#8b5cf6', label: 'Réservations',  tip: 'Réservations enregistrées dans la période' },
  revenue:     { color: '#f97316', label: 'CA',             tip: 'Chiffre d\'affaires total sur la période' },
  basket:      { color: '#ec4899', label: 'Panier moy.',   tip: 'Montant moyen par réservation' },
  cancel:      { color: '#ef4444', label: 'Annulations',   tip: 'Taux d\'annulation sur les réservations' },
  conv:        { color: '#a855f7', label: 'Conversion',    tip: 'Sessions → Réservation (estimation web-to-booking)' },
}

function KpiCell({ metaKey, value, delta, invertDelta, sub }) {
  const m   = META[metaKey]
  const vis = invertDelta && delta != null ? -delta : delta
  const pos = vis != null ? vis >= 0 : null

  return (
    <div
      className="flex flex-col justify-between px-4 py-3"
      style={{ borderLeft: `3px solid ${m.color}30` }}
      title={m.tip}
    >
      {/* Label coloré */}
      <div className="flex items-center gap-1.5 mb-1">
        <span className="w-1.5 h-1.5 rounded-full shrink-0" style={{ background: m.color }} />
        <span className="text-[10px] text-muted-foreground uppercase tracking-wider font-medium whitespace-nowrap">{m.label}</span>
      </div>

      {/* Valeur + delta */}
      <div className="flex items-end gap-1.5">
        <span className="text-lg font-bold tabular-nums">{value}</span>
        {delta != null && (
          <span
            className="text-[10px] font-semibold px-1 py-0.5 rounded tabular-nums mb-0.5"
            style={{
              background: pos ? '#dcfce7' : '#fee2e2',
              color:      pos ? '#15803d' : '#dc2626',
            }}
          >
            {delta > 0 ? '+' : ''}{delta}%
          </span>
        )}
      </div>

      {/* Explication courte */}
      <span className="text-[10px] text-muted-foreground mt-0.5 whitespace-nowrap">{sub ?? m.tip}</span>
    </div>
  )
}

/**
 * @param {object}  ga4Totals        - Totaux GA4 période courante (sessions, activeUsers, screenPageViews, bounceRate...)
 * @param {object}  ga4TotalsCompare - Totaux GA4 période de comparaison
 * @param {object}  bookingKpis      - KPIs réservations (total_bookings, total_revenue, avg_basket, cancellation_rate)
 * @param {object}  bookingKpisCmp   - KPIs réservations comparaison
 * @param {boolean} loading          - Chargement en cours
 */
export function CrossKpiStrip({ ga4Totals, ga4TotalsCompare, bookingKpis, bookingKpisCmp, loading }) {
  const t  = ga4Totals    ?? {}
  const tc = ga4TotalsCompare
  const k  = bookingKpis  ?? {}
  const kc = bookingKpisCmp

  const hasGA4      = t.sessions != null
  const hasBookings = k.total_bookings != null

  // Ratios calculés
  const pps    = t.sessions > 0 && t.screenPageViews > 0 ? +(t.screenPageViews / t.sessions).toFixed(2) : null
  const ppsCmp = tc?.sessions > 0 && tc?.screenPageViews > 0 ? +(tc.screenPageViews / tc.sessions).toFixed(2) : null

  const conv    = t.sessions > 0 && k.total_bookings > 0 ? +((k.total_bookings / t.sessions) * 100).toFixed(2) : null
  const convCmp = tc?.sessions > 0 && kc?.total_bookings > 0 ? +((kc.total_bookings / tc.sessions) * 100).toFixed(2) : null

  if (!hasGA4 && !hasBookings && !loading) return null

  return (
    <div className="rounded-lg border bg-card overflow-hidden">
      <div className="px-4 py-2 border-b bg-muted/20 flex items-center justify-between">
        <span className="text-[10px] text-muted-foreground uppercase tracking-wider font-medium">
          Vue croisée — Web + Réservations
        </span>
        <div className="flex items-center gap-2">
          {!hasGA4 && !loading && (
            <span className="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">
              GA4 non configuré
            </span>
          )}
          {!hasBookings && !loading && (
            <span className="text-[10px] text-sky-700 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded">
              Réservations en cours…
            </span>
          )}
        </div>
      </div>

      {loading ? (
        <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 divide-x divide-y sm:divide-y-0">
          {Array.from({ length: 8 }).map((_, i) => (
            <div key={i} className="flex flex-col gap-2 px-4 py-3">
              <div className="h-2 w-16 bg-muted rounded animate-pulse" />
              <div className="h-5 w-20 bg-muted rounded animate-pulse" />
              <div className="h-2 w-24 bg-muted rounded animate-pulse" />
            </div>
          ))}
        </div>
      ) : (
        <div className="divide-y">
          {/* Ligne 1 — GA4 */}
          {hasGA4 && (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 divide-x">
              <div className="px-3 py-1.5 col-span-full sm:col-span-1 lg:col-span-1 flex items-center">
                <span className="text-[9px] uppercase tracking-wider font-semibold text-emerald-600">Google Analytics 4</span>
              </div>
              <KpiCell metaKey="sessions"    value={fmtNum(t.sessions)}       delta={calcDelta(t.sessions, tc?.sessions)} />
              <KpiCell metaKey="users"       value={fmtNum(t.activeUsers)}     delta={calcDelta(t.activeUsers, tc?.activeUsers)} />
              <KpiCell metaKey="pageviews"   value={fmtNum(t.screenPageViews)} delta={calcDelta(t.screenPageViews, tc?.screenPageViews)} />
              {pps != null && <KpiCell metaKey="pagesPerSes" value={pps}       delta={calcDelta(pps, ppsCmp)} />}
              <KpiCell metaKey="bounce"      value={fmtPct(t.bounceRate)}      delta={calcDelta(t.bounceRate, tc?.bounceRate)} invertDelta />
            </div>
          )}

          {/* Ligne 2 — Réservations */}
          {(hasBookings || conv != null) && (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 divide-x">
              <div className="px-3 py-1.5 col-span-full sm:col-span-1 lg:col-span-1 flex items-center">
                <span className="text-[9px] uppercase tracking-wider font-semibold text-violet-600">Réservations</span>
              </div>
              {hasBookings && <>
                <KpiCell metaKey="reservations" value={fmtNum(k.total_bookings)} delta={calcDelta(k.total_bookings, kc?.total_bookings)} />
                <KpiCell
                  metaKey="revenue"
                  value={k.total_revenue != null ? Number(k.total_revenue).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }) : '—'}
                  delta={calcDelta(k.total_revenue, kc?.total_revenue)}
                />
                {k.avg_basket != null && (
                  <KpiCell
                    metaKey="basket"
                    value={Number(k.avg_basket).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 })}
                    delta={calcDelta(k.avg_basket, kc?.avg_basket)}
                  />
                )}
                {k.cancellation_rate != null && (
                  <KpiCell metaKey="cancel" value={`${Number(k.cancellation_rate).toFixed(1)}%`} delta={calcDelta(k.cancellation_rate, kc?.cancellation_rate)} invertDelta />
                )}
              </>}
              {conv != null && (
                <KpiCell metaKey="conv" value={`${conv}%`} delta={calcDelta(conv, convCmp)} sub="Sessions → Réservation" />
              )}
            </div>
          )}
        </div>
      )}
    </div>
  )
}
