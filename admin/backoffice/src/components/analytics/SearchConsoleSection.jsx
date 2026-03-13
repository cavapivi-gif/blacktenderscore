/**
 * SearchConsoleSection — Section Search Console avec onglets:
 * Vue d'ensemble | Requêtes | Top pages | Quick Wins | Cannibalisation.
 * Extrait de Analytics.jsx, logique inchangée. Imports centralisés dans analyticsUtils.js.
 */
import { useState, useEffect } from 'react'
import {
  AreaChart, Area,
  XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid,
} from 'recharts'
import { api } from '../../lib/api'
import { Spinner, Notice } from '../ui'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../Tabs'
import {
  C_CURRENT, C_COMPARE, C_GRID, C_AXIS,
  fmtNum, fmtPctRaw, fmtDate, calcDelta,
  KpiCard, RelBar, PosBadge, NotConfigured, CustomTooltip,
} from './analyticsUtils'

/**
 * @param {string} from
 * @param {string} to
 * @param {string} compareFrom
 * @param {string} compareTo
 */
export function SearchConsoleSection({ from, to, compareFrom, compareTo }) {
  const [data,    setData]    = useState(null)
  const [loading, setLoading] = useState(true)
  const [error,   setError]   = useState(null)

  useEffect(() => {
    setLoading(true); setError(null)
    api.searchConsoleStats({
      from,
      to,
      compare_from: compareFrom || undefined,
      compare_to:   compareTo   || undefined,
    })
      .then(setData)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [from, to, compareFrom, compareTo])

  if (loading) return <div className="flex justify-center py-16"><Spinner size={20} /></div>
  if (error)   return <Notice type="error">{error}</Notice>
  if (!data?.configured) return <NotConfigured service="Search Console" />
  if (data.error) return <Notice type="error">{data.error}</Notice>

  const t  = data.totals ?? {}
  const tc = data.totals_compare

  // Deltas (CTR et position sont des valeurs brutes, pas des ratios)
  const d = {
    clicks:      calcDelta(t.clicks,      tc?.clicks),
    impressions: calcDelta(t.impressions, tc?.impressions),
    ctr:         tc ? parseFloat((t.ctr - tc.ctr).toFixed(1)) : null,
    position:    tc ? parseFloat((t.position - tc.position).toFixed(1)) : null,
  }

  return (
    <Tabs defaultValue="overview">
      <TabsList className="rounded-b-none border border-b-0 border-border bg-muted w-full justify-start gap-0 p-0 h-auto mb-0">
        <TabsTrigger value="overview"        className="rounded-none rounded-tl-md px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:bg-card data-[state=active]:text-foreground font-medium">Vue d'ensemble</TabsTrigger>
        <TabsTrigger value="queries"         className="rounded-none px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:bg-card data-[state=active]:text-foreground font-medium">Requêtes</TabsTrigger>
        <TabsTrigger value="pages"           className="rounded-none px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:bg-card data-[state=active]:text-foreground font-medium">Top pages</TabsTrigger>
        <TabsTrigger value="quickwins"       className="rounded-none px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:bg-card data-[state=active]:text-foreground font-medium">Quick Wins</TabsTrigger>
        <TabsTrigger value="cannibalisation" className="rounded-none px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:bg-card data-[state=active]:text-foreground font-medium">Cannibalisation</TabsTrigger>
      </TabsList>

      {/* ── Vue d'ensemble ── */}
      <TabsContent value="overview" className="mt-0 border border-t-0 rounded-b-lg p-5 space-y-5">
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <KpiCard label="Clics"        value={fmtNum(t.clicks)}       sub="clics organiques" delta={d.clicks} />
          <KpiCard label="Impressions"  value={fmtNum(t.impressions)}   sub="affichages SERP"  delta={d.impressions} />
          <KpiCard label="CTR"          value={fmtPctRaw(t.ctr)}        sub="taux de clic"     delta={d.ctr} />
          <KpiCard label="Position moy." value={fmtNum(t.position, 1)} sub="rang Google"      delta={d.position} invertDelta />
        </div>

        {data.timeline?.length > 0 && (() => {
          const hasCmp = data.timeline_compare?.length > 0
          const timelineData = data.timeline.map((entry, i) => {
            if (!hasCmp) return entry
            const cmp = data.timeline_compare[i] ?? {}
            return { ...entry, clicks_cmp: cmp.clicks ?? null, impressions_cmp: cmp.impressions ?? null }
          })
          return (
            <div className="rounded-lg border bg-card p-5">
              <div className="flex items-center justify-between mb-4">
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">Clics & Impressions</p>
                {hasCmp && (
                  <p className="text-[10px] text-muted-foreground">
                    — vs {fmtDate(compareFrom)} – {fmtDate(compareTo)}
                  </p>
                )}
              </div>
              <ResponsiveContainer width="100%" height={220}>
                <AreaChart data={timelineData} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                  <defs>
                    <linearGradient id="sc-clicks" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%"  stopColor={C_CURRENT} stopOpacity={0.18} />
                      <stop offset="95%" stopColor={C_CURRENT} stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid stroke={C_GRID} vertical={false} />
                  <XAxis dataKey="date" tick={{ fontSize: 10, fill: C_AXIS }} tickFormatter={fmtDate} interval="preserveStartEnd" />
                  <YAxis tick={{ fontSize: 10, fill: C_AXIS }} />
                  <Tooltip content={<CustomTooltip formatter={(v, n) => n === 'CTR' ? `${v} %` : fmtNum(v)} />} />
                  <Area type="monotone" dataKey="clicks"      name="Clics"      stroke={C_CURRENT} fill="url(#sc-clicks)" strokeWidth={2}   dot={false} />
                  <Area type="monotone" dataKey="impressions" name="Impressions" stroke={C_COMPARE} fill="none"            strokeWidth={1.5} strokeDasharray="4 2" dot={false} />
                  {hasCmp && <Area type="monotone" dataKey="clicks_cmp"      name="Clics (comp.)"      stroke={C_CURRENT} fill="none" strokeWidth={1.5} strokeDasharray="3 3" strokeOpacity={0.45} dot={false} />}
                  {hasCmp && <Area type="monotone" dataKey="impressions_cmp" name="Impressions (comp.)" stroke={C_COMPARE} fill="none" strokeWidth={1}   strokeDasharray="3 3" strokeOpacity={0.45} dot={false} />}
                </AreaChart>
              </ResponsiveContainer>
            </div>
          )
        })()}
      </TabsContent>

      {/* ── Requêtes ── */}
      <TabsContent value="queries" className="mt-0 border border-t-0 rounded-b-lg p-5">
        {data.queries?.length > 0 ? (
          <div className="rounded-lg border bg-card overflow-hidden">
            <div className="px-4 py-3 bg-muted/40 border-b">
              <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">Top {data.queries.length} requêtes</p>
            </div>
            <div className="divide-y">
              {data.queries.map((q, i) => (
                <div key={i} className="px-4 py-2.5 hover:bg-muted/20 transition-colors">
                  <div className="flex items-center gap-3">
                    <span className="text-[10px] text-muted-foreground w-5 text-right shrink-0 tabular-nums">{i + 1}</span>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center justify-between gap-2">
                        <span className="text-xs font-medium truncate">{q.query}</span>
                        <div className="flex items-center gap-2 shrink-0 text-[10px] tabular-nums">
                          <span className="font-semibold text-foreground">{q.clicks} clics</span>
                          <span className="text-muted-foreground">· {q.impressions} imp.</span>
                          <span className="text-muted-foreground">· {fmtPctRaw(q.ctr)} CTR</span>
                          <PosBadge pos={q.position} />
                        </div>
                      </div>
                      <RelBar value={q.clicks} max={data.queries[0]?.clicks || 1} />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ) : (
          <Notice type="info">Aucune requête disponible pour cette période.</Notice>
        )}
      </TabsContent>

      {/* ── Top pages ── */}
      <TabsContent value="pages" className="mt-0 border border-t-0 rounded-b-lg p-5">
        {data.top_pages?.length > 0 ? (
          <div className="rounded-lg border bg-card overflow-hidden">
            <div className="px-4 py-3 bg-muted/40 border-b">
              <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">Top pages par clics organiques</p>
            </div>
            <div className="divide-y">
              {data.top_pages.map((p, i) => {
                const shortPage = p.page.length > 50 ? '…' + p.page.slice(-50) : p.page
                return (
                  <div key={i} className="px-4 py-2.5 hover:bg-muted/20 transition-colors">
                    <div className="flex items-center gap-3">
                      <span className="text-[10px] text-muted-foreground w-5 text-right shrink-0 tabular-nums">{i + 1}</span>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between gap-2">
                          <span className="text-xs font-medium font-mono truncate" title={p.page}>{shortPage}</span>
                          <div className="flex items-center gap-2 shrink-0 text-[10px] tabular-nums">
                            <span className="font-semibold text-foreground">{p.clicks} clics</span>
                            <span className="text-muted-foreground hidden sm:inline">· {p.impressions} imp.</span>
                            <span className="text-muted-foreground hidden md:inline">· {fmtPctRaw(p.ctr)} CTR</span>
                            <PosBadge pos={p.position} />
                          </div>
                        </div>
                        <RelBar value={p.clicks} max={data.top_pages[0]?.clicks || 1} />
                      </div>
                    </div>
                  </div>
                )
              })}
            </div>
          </div>
        ) : (
          <Notice type="info">Aucune page disponible pour cette période.</Notice>
        )}
      </TabsContent>

      {/* ── Quick Wins ── */}
      <TabsContent value="quickwins" className="mt-0 border border-t-0 rounded-b-lg p-5 space-y-3">
        <div className="flex items-start gap-2 px-3 py-2 rounded-md bg-amber-50 border border-amber-200 text-xs text-amber-800">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          <span><strong>Requêtes en position 4–15 avec fort potentiel</strong> — optimisez ces pages pour grimper en top 3 et multiplier vos clics (estimation à 3% CTR).</span>
        </div>

        {data.quick_wins?.length > 0 ? (
          <div className="rounded-lg border bg-card overflow-hidden">
            <div className="grid grid-cols-[1fr_auto_auto_auto_auto_auto] gap-0">
              <div className="contents text-[10px] text-muted-foreground uppercase tracking-wider font-medium">
                <div className="px-4 py-2.5 bg-muted/40 border-b">Requête / Page</div>
                <div className="px-3 py-2.5 bg-muted/40 border-b text-right">Pos.</div>
                <div className="px-3 py-2.5 bg-muted/40 border-b text-right">Imp.</div>
                <div className="px-3 py-2.5 bg-muted/40 border-b text-right hidden sm:block">CTR</div>
                <div className="px-3 py-2.5 bg-muted/40 border-b text-right hidden md:block">Clics act.</div>
                <div className="px-3 py-2.5 bg-muted/40 border-b text-right">Potentiel</div>
              </div>
              {data.quick_wins.map((w, i) => {
                const shortPage = w.page.length > 35 ? '…' + w.page.slice(-35) : w.page
                return (
                  <div key={i} className="contents text-xs">
                    <div className="px-4 py-2.5 border-b hover:bg-muted/20 transition-colors">
                      <p className="font-medium truncate">{w.query}</p>
                      <p className="text-[10px] text-muted-foreground font-mono truncate">{shortPage}</p>
                    </div>
                    <div className="px-3 py-2.5 border-b text-right align-middle flex items-center justify-end"><PosBadge pos={w.position} /></div>
                    <div className="px-3 py-2.5 border-b text-right tabular-nums">{fmtNum(w.impressions)}</div>
                    <div className="px-3 py-2.5 border-b text-right tabular-nums hidden sm:block">{fmtPctRaw(w.ctr)}</div>
                    <div className="px-3 py-2.5 border-b text-right tabular-nums hidden md:block">{w.clicks}</div>
                    <div className="px-3 py-2.5 border-b text-right tabular-nums font-bold" style={{ color: '#15803d' }}>{w.potential_clicks}</div>
                  </div>
                )
              })}
            </div>
          </div>
        ) : (
          <Notice type="info">Aucun quick win détecté (aucune requête entre la position 4–15 avec 50+ impressions).</Notice>
        )}
      </TabsContent>

      {/* ── Cannibalisation ── */}
      <TabsContent value="cannibalisation" className="mt-0 border border-t-0 rounded-b-lg p-5 space-y-3">
        <div className="flex items-start gap-2 px-3 py-2 rounded-md bg-red-50 border border-red-200 text-xs text-red-800">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          <span><strong>Cannibalisation de mots-clés</strong> — ces requêtes sont positionnées sur plusieurs pages. Google choisit aléatoirement, diluant votre autorité. Consolidez ou différenciez ces contenus.</span>
        </div>

        {data.cannibalisation?.length > 0 ? (
          <div className="space-y-3">
            {data.cannibalisation.map((item, i) => (
              <div key={i} className="rounded-lg border bg-card overflow-hidden">
                <div className="px-4 py-2.5 bg-muted/40 border-b flex items-center gap-2">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#dc2626" strokeWidth="2">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                  </svg>
                  <span className="text-xs font-semibold">{item.query}</span>
                  <span className="text-[10px] text-muted-foreground">({item.pages.length} pages en compétition)</span>
                </div>
                <div className="divide-y">
                  {item.pages.map((pg, j) => {
                    const shortP = pg.page.length > 55 ? '…' + pg.page.slice(-55) : pg.page
                    return (
                      <div key={j} className="px-4 py-2 flex items-center justify-between gap-3 hover:bg-muted/20 text-xs">
                        <span className="font-mono text-[10px] text-muted-foreground truncate" title={pg.page}>{shortP}</span>
                        <div className="flex items-center gap-2 shrink-0 tabular-nums text-[10px]">
                          <span>{pg.clicks} clics</span>
                          <span className="text-muted-foreground">· {pg.impressions} imp.</span>
                          <PosBadge pos={pg.position} />
                        </div>
                      </div>
                    )
                  })}
                </div>
              </div>
            ))}
          </div>
        ) : (
          <Notice type="info">Aucune cannibalisation détectée sur cette période.</Notice>
        )}
      </TabsContent>
    </Tabs>
  )
}
