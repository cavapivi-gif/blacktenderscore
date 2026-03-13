/**
 * BusinessSection — Onglet "Réservations" dans la page Analytics.
 * Charge api.bookingsStats() et affiche KPIs, TopProducts, et BarChart top produits par CA.
 */
import { useState, useEffect } from 'react'
import {
  BarChart, Bar,
  XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid, Cell,
} from 'recharts'
import { api } from '../../lib/api'
import { Spinner, Notice } from '../ui'
import { TopProducts } from '../dashboard/TopProducts'
import { fmtCurrency, fmtNum, fmtPercent } from '../../lib/utils'
import { KpiCard, C_PALETTE, C_GRID, C_AXIS, calcDelta } from './analyticsUtils'

/**
 * @param {string} from
 * @param {string} to
 * @param {string} compareFrom
 * @param {string} compareTo
 * @param {Function} onDataLoaded - Callback (kpis, kpisCmp) pour remonter les données au parent
 */
export function BusinessSection({ from, to, compareFrom, compareTo, onDataLoaded }) {
  const [stats,   setStats]   = useState(null)
  const [loading, setLoading] = useState(true)
  const [error,   setError]   = useState(null)

  useEffect(() => {
    setLoading(true); setError(null)
    const params = {
      from,
      to,
      granularity: 'day',
      include: 'periods,kpis,by_product',
    }
    if (compareFrom) params.compare_from = compareFrom
    if (compareTo)   params.compare_to   = compareTo

    api.bookingsStats(params)
      .then(d => {
        setStats(d)
        if (onDataLoaded) onDataLoaded(d.kpis ?? null, d.kpis_compare ?? null)
      })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [from, to, compareFrom, compareTo])

  if (loading) return <div className="flex justify-center py-16"><Spinner size={20} /></div>
  if (error)   return <Notice type="error">{error}</Notice>

  const kpis    = stats?.kpis     ?? {}
  const kpisCmp = stats?.kpis_compare ?? null

  // Delta helpers
  const d = {
    bookings:  calcDelta(kpis.total_bookings,    kpisCmp?.total_bookings),
    revenue:   calcDelta(kpis.total_revenue,     kpisCmp?.total_revenue),
    basket:    calcDelta(kpis.avg_basket,        kpisCmp?.avg_basket),
    cancel:    calcDelta(kpis.cancellation_rate, kpisCmp?.cancellation_rate),
    customers: calcDelta(kpis.unique_customers,  kpisCmp?.unique_customers),
  }

  // Top 10 produits par CA pour le BarChart
  const topByRevenue = [...(stats?.by_product ?? [])]
    .filter(p => p.revenue > 0)
    .sort((a, b) => b.revenue - a.revenue)
    .slice(0, 10)
    .map(p => ({
      ...p,
      // Raccourcir le nom pour le label d'axe
      shortName: p.name.length > 22 ? p.name.slice(0, 22) + '…' : p.name,
    }))

  return (
    <div className="space-y-5">

      {/* ── KPIs principaux ── */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <KpiCard
          label="Réservations"
          value={fmtNum(kpis.total_bookings)}
          sub="total période"
          delta={d.bookings}
        />
        <KpiCard
          label="CA total"
          value={kpis.total_revenue != null ? fmtCurrency(kpis.total_revenue) : '—'}
          sub="hors annulations"
          delta={d.revenue}
        />
        <KpiCard
          label="Panier moyen"
          value={kpis.avg_basket != null ? fmtCurrency(kpis.avg_basket) : '—'}
          sub="par réservation"
          delta={d.basket}
        />
        <KpiCard
          label="Taux annulation"
          value={kpis.cancellation_rate != null ? fmtPercent(kpis.cancellation_rate) : '—'}
          sub={kpis.cancellation_rate > 10 ? 'élevé' : 'nominal'}
          delta={d.cancel}
          invertDelta
        />
        <KpiCard
          label="Clients uniques"
          value={fmtNum(kpis.unique_customers)}
          sub="acheteurs distincts"
          delta={d.customers}
        />
      </div>

      {/* ── Deux colonnes : Top produits (volume) + BarChart (CA) ── */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {/* Top produits par volume (composant dashboard réutilisé) */}
        {stats?.by_product?.length > 0 && (
          <TopProducts
            data={stats.by_product}
            filterParams={{ from, to }}
          />
        )}

        {/* BarChart top 10 produits par CA */}
        {topByRevenue.length > 0 && (
          <div className="rounded-lg border bg-card p-5">
            <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium mb-4">Top produits par CA</p>
            <ResponsiveContainer width="100%" height={Math.max(220, topByRevenue.length * 36)}>
              <BarChart
                data={topByRevenue}
                layout="vertical"
                margin={{ top: 2, right: 60, left: 8, bottom: 2 }}
              >
                <CartesianGrid stroke={C_GRID} horizontal={false} />
                <XAxis
                  type="number"
                  tick={{ fontSize: 10, fill: C_AXIS }}
                  tickFormatter={v => `${Math.round(v / 1000)}k€`}
                />
                <YAxis
                  type="category"
                  dataKey="shortName"
                  tick={{ fontSize: 10, fill: C_AXIS }}
                  width={120}
                />
                <Tooltip
                  content={({ active, payload }) => {
                    if (!active || !payload?.length) return null
                    const row = payload[0]?.payload
                    return (
                      <div className="rounded-lg border bg-white shadow-md px-3 py-2 text-xs space-y-0.5">
                        <p className="font-medium">{row?.name}</p>
                        <p>CA : <strong>{fmtCurrency(row?.revenue)}</strong></p>
                        <p>Réservations : <strong>{fmtNum(row?.count)}</strong></p>
                      </div>
                    )
                  }}
                />
                <Bar dataKey="revenue" name="CA" radius={[0, 4, 4, 0]}>
                  {topByRevenue.map((_, i) => (
                    <Cell key={i} fill={C_PALETTE[i % C_PALETTE.length]} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        )}
      </div>

      {/* ── Message si aucune donnée ── */}
      {!stats?.by_product?.length && !loading && (
        <Notice type="info">
          Aucune donnée de réservation disponible pour cette période.
          Assurez-vous d'avoir synchronisé les réservations depuis l'onglet Réservations.
        </Notice>
      )}
    </div>
  )
}
