import { useMemo } from 'react'
import {
  BarChart, Bar,
  XAxis, YAxis, CartesianGrid, Tooltip,
  ResponsiveContainer, ReferenceLine, Cell,
} from 'recharts'
import { COLORS } from '../../lib/constants'
import { Table } from '../../components/ui'
import { Stars, Delta } from './components'

/**
 * Products tab — table + two horizontal bar charts.
 */
export default function ProductsTab({ stats, compareStats, compareActive }) {
  if (!stats?.by_product?.length) {
    return (
      <div className="rounded-xl border bg-card px-6 py-16 text-center">
        <p className="text-sm text-muted-foreground">Aucune donnée produit disponible.</p>
      </div>
    )
  }

  const { by_product, total } = stats
  const top8 = by_product.slice(0, 8)

  // Color by avg_rating
  const ratingColor = avg => {
    if (avg >= 4) return '#10b981'  // emerald
    if (avg >= 3) return '#f59e0b'  // amber
    return '#ef4444'                // red
  }

  // Compare map by product name
  const cmpMap = useMemo(() => {
    if (!compareActive || !compareStats?.by_product) return {}
    return Object.fromEntries(compareStats.by_product.map(p => [p.product_name, p]))
  }, [compareActive, compareStats])

  const columns = [
    {
      key: 'product_name', label: 'Produit',
      render: r => (
        <span className="text-sm font-medium max-w-[200px] truncate block" title={r.product_name}>
          {r.product_name || '—'}
        </span>
      ),
    },
    {
      key: 'count', label: 'Avis', sortable: true,
      render: r => (
        <div className="flex items-center gap-2">
          <span className="font-bold tabular-nums">{r.count}</span>
          {compareActive && cmpMap[r.product_name] && (
            <Delta current={r.count} previous={cmpMap[r.product_name].count} />
          )}
        </div>
      ),
    },
    {
      key: 'avg_rating', label: 'Moy.', sortable: true,
      render: r => (
        <div className="flex items-center gap-2">
          <span className="font-medium tabular-nums">{Number(r.avg_rating).toFixed(2)}</span>
          <Stars value={Math.round(r.avg_rating)} size={11} />
          {compareActive && cmpMap[r.product_name] && (
            <Delta current={r.avg_rating} previous={cmpMap[r.product_name].avg_rating} />
          )}
        </div>
      ),
    },
    {
      key: 'pct', label: '% total',
      render: r => {
        const pct = total ? Math.round((r.count / total) * 100) : 0
        return (
          <div className="flex items-center gap-2">
            <div className="h-1.5 w-16 rounded-full bg-muted overflow-hidden">
              <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${pct}%` }} />
            </div>
            <span className="text-xs tabular-nums text-muted-foreground">{pct}%</span>
          </div>
        )
      },
    },
  ]

  return (
    <div className="space-y-5">
      {/* Horizontal bar chart: count by product */}
      <div className="rounded-xl border bg-card p-5">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-4 font-semibold">
          Volume d'avis par produit (top 8)
        </p>
        <ResponsiveContainer width="100%" height={Math.max(180, top8.length * 36)}>
          <BarChart data={top8} layout="vertical" margin={{ top: 4, right: 40, bottom: 0, left: 8 }}>
            <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} horizontal={false} />
            <XAxis type="number" tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false} />
            <YAxis type="category" dataKey="product_name" width={140}
              tick={{ fontSize: 11, fill: COLORS.axis }} tickLine={false} axisLine={false}
              tickFormatter={v => v?.length > 22 ? v.slice(0, 22) + '…' : v}
            />
            <Tooltip content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const d = payload[0]?.payload
              return (
                <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs space-y-0.5 max-w-[200px]">
                  <p className="font-medium break-words">{label}</p>
                  <p style={{ color: ratingColor(d.avg_rating) }}>{d.count} avis · {Number(d.avg_rating).toFixed(2)}★</p>
                </div>
              )
            }} />
            <Bar dataKey="count" name="Avis" radius={[0, 3, 3, 0]}>
              {top8.map((entry, i) => (
                <Cell key={i} fill={ratingColor(entry.avg_rating)} opacity={0.82} />
              ))}
            </Bar>
          </BarChart>
        </ResponsiveContainer>
        <div className="flex items-center gap-4 mt-3 text-[11px] text-muted-foreground">
          <span className="flex items-center gap-1"><span className="w-3 h-2 rounded-sm inline-block bg-emerald-500 opacity-80" /> ≥ 4.0★</span>
          <span className="flex items-center gap-1"><span className="w-3 h-2 rounded-sm inline-block bg-amber-500 opacity-80" /> ≥ 3.0★</span>
          <span className="flex items-center gap-1"><span className="w-3 h-2 rounded-sm inline-block bg-red-500 opacity-80" /> &lt; 3.0★</span>
        </div>
      </div>

      {/* Horizontal bar chart: avg_rating by product */}
      <div className="rounded-xl border bg-card p-5">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-4 font-semibold">
          Note moyenne par produit (top 8, trié par note)
        </p>
        <ResponsiveContainer width="100%" height={Math.max(180, top8.length * 36)}>
          <BarChart
            data={[...top8].sort((a, b) => b.avg_rating - a.avg_rating)}
            layout="vertical"
            margin={{ top: 4, right: 40, bottom: 0, left: 8 }}
          >
            <CartesianGrid strokeDasharray="3 3" stroke={COLORS.grid} horizontal={false} />
            <XAxis type="number" domain={[0, 5]} ticks={[0,1,2,3,4,5]}
              tick={{ fontSize: 10, fill: COLORS.axis }} tickLine={false} axisLine={false} />
            <YAxis type="category" dataKey="product_name" width={140}
              tick={{ fontSize: 11, fill: COLORS.axis }} tickLine={false} axisLine={false}
              tickFormatter={v => v?.length > 22 ? v.slice(0, 22) + '…' : v}
            />
            <ReferenceLine x={4.8} stroke={COLORS.basket} strokeDasharray="4 3" strokeWidth={1.5} />
            <Tooltip content={({ active, payload, label }) => {
              if (!active || !payload?.length) return null
              const d = payload[0]?.payload
              return (
                <div className="bg-card border rounded-md shadow-md px-3 py-2 text-xs max-w-[200px]">
                  <p className="font-medium break-words">{label}</p>
                  <p style={{ color: ratingColor(d.avg_rating) }}>{Number(d.avg_rating).toFixed(2)}★ ({d.count} avis)</p>
                </div>
              )
            }} />
            <Bar dataKey="avg_rating" name="Moy." radius={[0, 3, 3, 0]}>
              {[...top8].sort((a, b) => b.avg_rating - a.avg_rating).map((entry, i) => (
                <Cell key={i} fill={ratingColor(entry.avg_rating)} opacity={0.82} />
              ))}
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* Products table */}
      <div className="rounded-xl border bg-card overflow-hidden">
        <div className="px-5 py-3 border-b">
          <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-semibold">
            Détail par produit
          </p>
        </div>
        <Table columns={columns} data={by_product} empty="Aucun produit." />
      </div>
    </div>
  )
}
