import { motion } from 'motion/react'
import {
  AreaChart, Area, BarChart, Bar, PieChart, Pie, Cell,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
} from 'recharts'
import { fmtCurrency, fmtNum } from '../../lib/utils'
import { KpiCard } from '../dashboard'

// Chart colors hardcodées (CSS vars ne marchent pas dans SVG)
const CHART_REVENUE = '#10b981'
const CHART_GRID    = '#e5e5e5'
const CHART_AXIS    = '#737373'
const CHART_CANCEL  = '#ef4444'
const CHART_BOOK    = '#0a0a0a'
const CHART_PALETTE = ['#10b981','#6366f1','#f59e0b','#ef4444','#8b5cf6','#06b6d4']

export function StatsWidget({ data, intents, range }) {
  const chart    = data.chart    ?? data.monthly   ?? []
  const kpis     = data.kpis     ?? {}
  const products = data.by_product ?? []

  const hasCancel   = intents.includes('cancellation')
  const hasRevenue  = intents.includes('revenue') || intents.includes('trend')
  const hasProducts = intents.includes('products')
  const showFallback = !hasCancel && !hasRevenue && !hasProducts

  const periodLabel = range ? `${range.from} → ${range.to}` : ''

  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3, ease: 'easeOut', delay: 0.05 }}
      className="mt-3 border border-border/50 rounded-2xl bg-card overflow-hidden shadow-sm"
    >
      {/* Header */}
      <div className="flex items-center gap-2 px-4 py-2.5 border-b border-border/40 bg-muted/20">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="text-muted-foreground shrink-0">
          <path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>
        </svg>
        <span className="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground/70">
          Données — {periodLabel}
        </span>
      </div>

      <div className="p-4 space-y-4">
        {/* Taux annulation highlight */}
        {hasCancel && (
          <div className="grid grid-cols-3 gap-3">
            <div className="col-span-1 flex flex-col items-center justify-center bg-red-50 border border-red-100 rounded-xl p-3">
              <span className="text-2xl font-bold text-red-600 leading-none">{kpis.cancellation_rate ?? 0}%</span>
              <span className="text-[10px] text-red-400 mt-1 text-center leading-tight">Taux<br/>annulation</span>
            </div>
            <div className="col-span-2 grid grid-cols-2 gap-2">
              <div className="bg-muted/40 rounded-xl p-3 text-center">
                <span className="text-lg font-bold text-foreground block leading-none">{fmtNum(kpis.bookings_count ?? 0)}</span>
                <span className="text-[10px] text-muted-foreground mt-1 block">Réservations</span>
              </div>
              <div className="bg-muted/40 rounded-xl p-3 text-center">
                <span className="text-lg font-bold text-foreground block leading-none">{fmtNum(kpis.cancellations_count ?? 0)}</span>
                <span className="text-[10px] text-muted-foreground mt-1 block">Annulations</span>
              </div>
            </div>
          </div>
        )}

        {/* Bar chart — réservations vs annulations */}
        {hasCancel && chart.length > 0 && (
          <div>
            <p className="text-[10px] uppercase tracking-widest text-muted-foreground/50 font-semibold mb-2">
              Réservations vs Annulations
            </p>
            <ResponsiveContainer width="100%" height={130}>
              <BarChart data={chart} margin={{ top: 4, right: 0, left: -28, bottom: 0 }} barGap={2}>
                <CartesianGrid strokeDasharray="3 3" stroke={CHART_GRID} vertical={false} />
                <XAxis dataKey="label" tick={{ fontSize: 10, fill: CHART_AXIS }} tickLine={false} axisLine={false} />
                <YAxis tick={{ fontSize: 10, fill: CHART_AXIS }} tickLine={false} axisLine={false} />
                <Tooltip contentStyle={{ fontSize: 11, borderRadius: 8, border: '1px solid #e5e5e5', background: '#fff' }} labelStyle={{ fontWeight: 600 }} />
                <Bar dataKey="bookings"      name="Réservations" fill={CHART_BOOK}   radius={[3,3,0,0]} maxBarSize={28} />
                <Bar dataKey="cancellations" name="Annulations"  fill={CHART_CANCEL} radius={[3,3,0,0]} maxBarSize={28} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        )}

        {/* Area chart — CA */}
        {(hasRevenue || showFallback) && chart.length > 0 && (
          <div>
            <p className="text-[10px] uppercase tracking-widest text-muted-foreground/50 font-semibold mb-2">
              {hasRevenue ? 'Évolution du CA' : 'Chiffre d\'affaires'}
            </p>
            <ResponsiveContainer width="100%" height={120}>
              <AreaChart data={chart} margin={{ top: 4, right: 0, left: -28, bottom: 0 }}>
                <defs>
                  <linearGradient id="gRev" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%"  stopColor={CHART_REVENUE} stopOpacity={0.18} />
                    <stop offset="95%" stopColor={CHART_REVENUE} stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke={CHART_GRID} vertical={false} />
                <XAxis dataKey="label" tick={{ fontSize: 10, fill: CHART_AXIS }} tickLine={false} axisLine={false} />
                <YAxis tick={{ fontSize: 10, fill: CHART_AXIS }} tickLine={false} axisLine={false} tickFormatter={v => `${Math.round(v/1000)}k`} />
                <Tooltip contentStyle={{ fontSize: 11, borderRadius: 8, border: '1px solid #e5e5e5', background: '#fff' }} formatter={v => [fmtCurrency(v), 'CA']} />
                <Area type="monotone" dataKey="revenue" stroke={CHART_REVENUE} strokeWidth={2} fill="url(#gRev)" dot={false} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        )}

        {/* Top produits — donut + liste */}
        {hasProducts && products.length > 0 && (
          <div>
            <p className="text-[10px] uppercase tracking-widest text-muted-foreground/50 font-semibold mb-3">Top produits</p>
            <div className="flex gap-4 items-start">
              {/* Donut */}
              <div className="shrink-0" style={{ width: 130, height: 130 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={products.slice(0, 5).map(p => ({ name: p.product_name ?? p.name, value: p.bookings ?? 0 }))}
                      dataKey="value"
                      innerRadius={36}
                      outerRadius={56}
                      paddingAngle={2}
                      startAngle={90}
                      endAngle={-270}
                    >
                      {products.slice(0, 5).map((_, i) => (
                        <Cell key={i} fill={CHART_PALETTE[i % CHART_PALETTE.length]} stroke="none" />
                      ))}
                    </Pie>
                    <Tooltip
                      contentStyle={{ fontSize: 11, borderRadius: 8, border: '1px solid #e5e5e5', background: '#fff' }}
                      formatter={v => [`${v} rés.`]}
                    />
                  </PieChart>
                </ResponsiveContainer>
              </div>
              {/* Barres */}
              <div className="flex-1 min-w-0 space-y-2.5 pt-1">
                {products.slice(0,5).map((p, i) => {
                  const maxB = Math.max(...products.map(x => x.bookings ?? 0), 1)
                  return (
                    <div key={i} className="flex items-center gap-2.5">
                      <span className="w-2 h-2 rounded-full shrink-0" style={{ background: CHART_PALETTE[i % CHART_PALETTE.length] }} />
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between mb-1">
                          <span className="text-xs truncate font-medium">{p.product_name ?? p.name}</span>
                          <span className="text-[10px] text-muted-foreground shrink-0 ml-2">{p.bookings} rés.</span>
                        </div>
                        <div className="h-1 bg-muted rounded-full overflow-hidden">
                          <motion.div
                            initial={{ width: 0 }}
                            animate={{ width: `${((p.bookings ?? 0) / maxB) * 100}%` }}
                            transition={{ duration: 0.5, delay: i * 0.06, ease: 'easeOut' }}
                            className="h-full rounded-full"
                            style={{ background: CHART_PALETTE[i % CHART_PALETTE.length] }}
                          />
                        </div>
                      </div>
                    </div>
                  )
                })}
              </div>
            </div>
          </div>
        )}

        {/* Fallback KPIs grid */}
        {showFallback && (
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <KpiCard label="CA période"      value={fmtCurrency(kpis.revenue_total ?? 0)} />
            <KpiCard label="Réservations"    value={fmtNum(kpis.bookings_count ?? 0)} />
            <KpiCard label="Panier moyen"    value={fmtCurrency(kpis.avg_basket ?? 0)} />
            <KpiCard label="Taux annulation" value={`${kpis.cancellation_rate ?? 0}%`} />
          </div>
        )}
      </div>
    </motion.div>
  )
}
