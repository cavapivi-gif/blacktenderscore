/**
 * GA4Section — Section Google Analytics 4 avec onglets Vue d'ensemble / Acquisition / Contenu.
 * Extrait de Analytics.jsx, logique inchangée. Imports centralisés dans analyticsUtils.js.
 */
import { useState, useEffect } from 'react'
import {
  AreaChart, Area, BarChart, Bar,
  XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid, Cell,
} from 'recharts'
import { api } from '../../lib/api'
import { Spinner, Notice } from '../ui'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../Tabs'
import {
  C_CURRENT, C_COMPARE, C_GRID, C_AXIS, C_PALETTE,
  fmtNum, fmtDuration, fmtPct, fmtDate, calcDelta,
  KpiCard, RelBar, NotConfigured, CustomTooltip,
} from './analyticsUtils'

/**
 * @param {string}  from
 * @param {string}  to
 * @param {string}  compareFrom
 * @param {string}  compareTo
 * @param {Function} onDataLoaded - Callback (data) pour remonter les totaux au parent
 */
export function GA4Section({ from, to, compareFrom, compareTo, onDataLoaded }) {
  const [data,    setData]    = useState(null)
  const [loading, setLoading] = useState(true)
  const [error,   setError]   = useState(null)

  useEffect(() => {
    setLoading(true); setError(null)
    api.ga4Stats({
      from,
      to,
      compare_from: compareFrom || undefined,
      compare_to:   compareTo   || undefined,
    })
      .then(d => {
        setData(d)
        // Remonter les totaux au parent pour la CrossKpiStrip
        if (onDataLoaded) onDataLoaded(d)
      })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [from, to, compareFrom, compareTo])

  if (loading) return <div className="flex justify-center py-16"><Spinner size={20} /></div>
  if (error)   return <Notice type="error">{error}</Notice>
  if (!data?.configured) return <NotConfigured service="Google Analytics 4" />
  if (data.error) return <Notice type="error">{data.error}</Notice>

  const t  = data.totals ?? {}
  const tc = data.totals_compare

  const d = {
    sessions:    calcDelta(t.sessions,    tc?.sessions),
    users:       calcDelta(t.activeUsers, tc?.activeUsers),
    newUsers:    calcDelta(t.newUsers,    tc?.newUsers),
    views:       calcDelta(t.screenPageViews, tc?.screenPageViews),
    bounce:      calcDelta(t.bounceRate,  tc?.bounceRate),
    duration:    calcDelta(t.averageSessionDuration, tc?.averageSessionDuration),
    conversions: calcDelta(t.conversions, tc?.conversions),
  }

  return (
    <Tabs defaultValue="overview">
      <TabsList className="rounded-b-none border border-b-0 border-border bg-muted w-full justify-start gap-0 p-0 h-auto mb-0">
        <TabsTrigger value="overview"    className="rounded-none rounded-tl-md px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:bg-card data-[state=active]:text-foreground font-medium">Vue d'ensemble</TabsTrigger>
        <TabsTrigger value="acquisition" className="rounded-none px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:bg-card data-[state=active]:text-foreground font-medium">Acquisition</TabsTrigger>
        <TabsTrigger value="content"     className="rounded-none px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:bg-card data-[state=active]:text-foreground font-medium">Contenu</TabsTrigger>
      </TabsList>

      {/* ── Vue d'ensemble ── */}
      <TabsContent value="overview" className="mt-0 border border-t-0 rounded-b-lg p-5 space-y-5">
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3">
          <KpiCard label="Sessions"           value={fmtNum(t.sessions)}                     sub="visites totales"     delta={d.sessions} />
          <KpiCard label="Utilisateurs"       value={fmtNum(t.activeUsers)}                  sub="actifs"              delta={d.users} />
          <KpiCard label="Nouveaux util."     value={fmtNum(t.newUsers)}                     sub="premières visites"   delta={d.newUsers} />
          <KpiCard label="Pages vues"         value={fmtNum(t.screenPageViews)}              sub="impressions"         delta={d.views} />
          <KpiCard label="Taux de rebond"     value={fmtPct(t.bounceRate)}                   sub={t.bounceRate > 0.6 ? 'élevé' : 'moyen'} delta={d.bounce} invertDelta />
          <KpiCard label="Durée moy. session" value={fmtDuration(t.averageSessionDuration)} sub="engagement"          delta={d.duration} />
          <KpiCard label="Conversions"        value={fmtNum(t.conversions)}                  sub="événements objectif" delta={d.conversions} />
        </div>

        {data.timeline?.length > 0 && (() => {
          const hasCmp = data.timeline_compare?.length > 0
          const timelineData = data.timeline.map((entry, i) => {
            if (!hasCmp) return entry
            const cmp = data.timeline_compare[i] ?? {}
            return { ...entry, sessions_cmp: cmp.sessions ?? null, activeUsers_cmp: cmp.activeUsers ?? null }
          })
          return (
            <div className="rounded-lg border bg-card p-5">
              <div className="flex items-center justify-between mb-4">
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">Sessions & Utilisateurs</p>
                {hasCmp && (
                  <p className="text-[10px] text-muted-foreground">
                    — vs {fmtDate(compareFrom)} – {fmtDate(compareTo)}
                  </p>
                )}
              </div>
              <ResponsiveContainer width="100%" height={220}>
                <AreaChart data={timelineData} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                  <defs>
                    <linearGradient id="ga4-sessions" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%"  stopColor={C_CURRENT} stopOpacity={0.18} />
                      <stop offset="95%" stopColor={C_CURRENT} stopOpacity={0} />
                    </linearGradient>
                    <linearGradient id="ga4-users" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%"  stopColor={C_COMPARE} stopOpacity={0.15} />
                      <stop offset="95%" stopColor={C_COMPARE} stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid stroke={C_GRID} vertical={false} />
                  <XAxis dataKey="date" tick={{ fontSize: 10, fill: C_AXIS }} tickFormatter={fmtDate} interval="preserveStartEnd" />
                  <YAxis tick={{ fontSize: 10, fill: C_AXIS }} />
                  <Tooltip content={<CustomTooltip />} />
                  <Area type="monotone" dataKey="sessions"    name="Sessions"     stroke={C_CURRENT} fill="url(#ga4-sessions)" strokeWidth={2}   dot={false} />
                  <Area type="monotone" dataKey="activeUsers" name="Utilisateurs" stroke={C_COMPARE} fill="url(#ga4-users)"    strokeWidth={1.5} strokeDasharray="4 2" dot={false} />
                  {hasCmp && <Area type="monotone" dataKey="sessions_cmp"    name="Sessions (comp.)"     stroke={C_CURRENT} fill="none" strokeWidth={1.5} strokeDasharray="3 3" strokeOpacity={0.45} dot={false} />}
                  {hasCmp && <Area type="monotone" dataKey="activeUsers_cmp" name="Utilisateurs (comp.)" stroke={C_COMPARE} fill="none" strokeWidth={1}   strokeDasharray="3 3" strokeOpacity={0.45} dot={false} />}
                </AreaChart>
              </ResponsiveContainer>
            </div>
          )
        })()}
      </TabsContent>

      {/* ── Acquisition ── */}
      <TabsContent value="acquisition" className="mt-0 border border-t-0 rounded-b-lg p-5 space-y-5">
        {data.by_channel?.length > 0 ? (
          <div className="rounded-lg border bg-card p-5">
            <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium mb-4">Sessions par canal</p>
            <ResponsiveContainer width="100%" height={Math.max(220, data.by_channel.length * 36)}>
              <BarChart
                data={[...data.by_channel].sort((a, b) => b.sessions - a.sessions)}
                layout="vertical"
                margin={{ top: 2, right: 30, left: 10, bottom: 2 }}
              >
                <CartesianGrid stroke={C_GRID} horizontal={false} />
                <XAxis type="number" tick={{ fontSize: 10, fill: C_AXIS }} />
                <YAxis type="category" dataKey="channel" tick={{ fontSize: 11, fill: C_AXIS }} width={130} />
                <Tooltip
                  content={({ active, payload }) => {
                    if (!active || !payload?.length) return null
                    const row = payload[0]?.payload
                    return (
                      <div className="rounded-lg border bg-white shadow-md px-3 py-2 text-xs space-y-0.5">
                        <p className="font-medium">{row?.channel}</p>
                        <p>Sessions : <strong>{fmtNum(row?.sessions)}</strong></p>
                        <p>Utilisateurs : <strong>{fmtNum(row?.users)}</strong></p>
                        <p>Conversions : <strong>{fmtNum(row?.conversions)}</strong></p>
                      </div>
                    )
                  }}
                />
                <Bar dataKey="sessions" name="Sessions" radius={[0, 4, 4, 0]}>
                  {data.by_channel.map((_, i) => (
                    <Cell key={i} fill={C_PALETTE[i % C_PALETTE.length]} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        ) : (
          <Notice type="info">Aucune donnée de canal disponible pour cette période.</Notice>
        )}
      </TabsContent>

      {/* ── Contenu (Top pages) ── */}
      <TabsContent value="content" className="mt-0 border border-t-0 rounded-b-lg p-5 space-y-5">
        {data.top_pages?.length > 0 ? (
          <div className="rounded-lg border bg-card overflow-hidden">
            <div className="px-4 py-3 bg-muted/40 border-b">
              <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">Top pages par vues</p>
            </div>
            <div className="divide-y">
              {data.top_pages.map((p, i) => {
                const maxViews  = data.top_pages[0]?.views || 1
                const shortPath = p.page.length > 40 ? '…' + p.page.slice(-40) : p.page
                return (
                  <div key={i} className="px-4 py-3 hover:bg-muted/20 transition-colors">
                    <div className="flex items-center justify-between gap-3">
                      <div className="flex items-center gap-2 min-w-0 flex-1">
                        <span className="text-[10px] text-muted-foreground w-5 text-right shrink-0 tabular-nums">{i + 1}</span>
                        <div className="min-w-0 flex-1">
                          <p className="text-xs font-medium font-mono truncate" title={p.page}>{shortPath}</p>
                          <RelBar value={p.views} max={maxViews} />
                        </div>
                      </div>
                      <div className="flex items-center gap-4 shrink-0 text-[11px] text-muted-foreground tabular-nums">
                        <span className="text-foreground font-semibold">{fmtNum(p.views)} vues</span>
                        <span className="hidden sm:inline">{fmtNum(p.sessions)} sessions</span>
                        <span className="hidden md:inline">{fmtPct(p.bounceRate)} rebond</span>
                        <span className="hidden lg:inline">{fmtDuration(p.avgDuration)}</span>
                      </div>
                    </div>
                  </div>
                )
              })}
            </div>
          </div>
        ) : (
          <Notice type="info">Aucune donnée de page disponible pour cette période.</Notice>
        )}
      </TabsContent>
    </Tabs>
  )
}
