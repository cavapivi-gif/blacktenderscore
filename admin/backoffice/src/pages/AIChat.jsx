import { useState, useRef, useEffect, useCallback } from 'react'
import { motion, AnimatePresence } from 'motion/react'
import Lottie from 'lottie-react'
import {
  Plus, Trash, Copy, Check, SendDiagonal, MediaImage, Xmark,
  NavArrowDown, Sparks, ChatLines, EditPencil, ShareAndroid, Link,
} from 'iconoir-react'

import {
  AreaChart, Area, BarChart, Bar, PieChart, Pie, Cell, Legend,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
} from 'recharts'

import { streamChat, api } from '../lib/api'
import { today, daysAgo, fmtCurrency, fmtNum } from '../lib/utils'
import { ChatSharePanel } from '../components/chat/ChatSharePanel'
import { syncChat } from '../lib/chatApi'

// Chart colors hardcodées (CSS vars ne marchent pas dans SVG)
const CHART_REVENUE = '#10b981'
const CHART_GRID    = '#e5e5e5'
const CHART_AXIS    = '#737373'
const CHART_CANCEL  = '#ef4444'
const CHART_BOOK    = '#0a0a0a'
const CHART_PALETTE = ['#10b981','#6366f1','#f59e0b','#ef4444','#8b5cf6','#06b6d4']

// ── Intent detection & date parsing ─────────────────────────────────────────

const MONTH_MAP = {
  'janvier':1,'jan':1,'février':2,'fev':2,'fév':2,'mars':3,'mar':3,
  'avril':4,'avr':4,'mai':5,'juin':6,'jun':6,'juillet':7,'jul':7,
  'août':8,'aout':8,'septembre':9,'sep':9,'octobre':10,'oct':10,
  'novembre':11,'nov':11,'décembre':12,'dec':12,'déc':12,
}

function parseMessageDate(text) {
  const lower = text.toLowerCase()
  const names = Object.keys(MONTH_MAP).join('|')
  const rxFull = new RegExp(`\\b(${names})\\s+(\\d{4})\\b`)
  const rxMono = new RegExp(`\\b(${names})\\b`)
  const curYear = new Date().getFullYear()
  let m = lower.match(rxFull)
  if (!m) m = lower.match(rxMono)
  if (!m) return null
  const month = MONTH_MAP[m[1]]
  const year  = m[2] ? parseInt(m[2]) : curYear
  const from  = `${year}-${String(month).padStart(2,'0')}-01`
  const to    = `${year}-${String(month).padStart(2,'0')}-${new Date(year, month, 0).getDate()}`
  return { from, to }
}

function detectDataIntent(text) {
  const intents = new Set()
  if (/annulation|annul[ée]|cancel/i.test(text))                          intents.add('cancellation')
  if (/\bca\b|chiffre.d.affaire|revenu|recette/i.test(text))              intents.add('revenue')
  if (/réservation|booking|commande|vendu/i.test(text))                   intents.add('bookings')
  if (/produit|activit|offre|article|\btop\b/i.test(text))                intents.add('products')
  if (/saisonnali|évolution|progression|tendance|trend/i.test(text))      intents.add('trend')
  if (/panier|basket|moyen/i.test(text))                                  intents.add('basket')
  return [...intents]
}

function buildDataContext(stats, from, to) {
  const kpis     = stats.kpis     ?? {}
  const chart    = stats.chart    ?? stats.monthly ?? []
  const products = stats.by_product ?? []
  const lines = [
    `[DONNÉES RÉELLES — ${from} → ${to}]`,
    `CA total : ${fmtCurrency(kpis.revenue_total ?? 0)}`,
    `Réservations : ${kpis.bookings_count ?? 0}`,
    `Annulations : ${kpis.cancellations_count ?? 0} (taux : ${kpis.cancellation_rate ?? 0}%)`,
    `Panier moyen : ${fmtCurrency(kpis.avg_basket ?? 0)}`,
  ]
  if (chart.length) {
    lines.push('\nÉvolution par période :')
    chart.forEach(p => lines.push(`  ${p.label} → CA: ${fmtCurrency(p.revenue)}, rés: ${p.bookings}, annul: ${p.cancellations}`))
  }
  if (products.length) {
    lines.push('\nTop produits :')
    products.slice(0,5).forEach((p,i) => lines.push(`  ${i+1}. ${p.product_name ?? p.name} — ${p.bookings} rés, CA: ${fmtCurrency(p.revenue)}`))
  }
  return lines.join('\n')
}
import { useSearchParams } from 'react-router-dom'
import { useConversations } from '../hooks/useConversations'
import { PROVIDER_LIST, getProvider } from '../lib/aiProviders'
import AiProviderIcon from '../components/AiProviderIcon'
import { Dialog } from '../components/Dialog'
// react-resizable-panels remplacé par resize natif (v4.7.2 incompatible avec notre layout flex)
import { KpiCard } from '../components/dashboard'

// ─────────────────────────────────────────────────────────────────────────────
// Toast system
// ─────────────────────────────────────────────────────────────────────────────

function useToast() {
  const [toasts, setToasts] = useState([])
  const push = useCallback((msg, type = 'success') => {
    const id = Date.now()
    setToasts(p => [...p.slice(-2), { id, msg, type }])
    setTimeout(() => setToasts(p => p.filter(t => t.id !== id)), 2800)
  }, [])
  return { toasts, push }
}

function ToastStack({ toasts }) {
  return (
    <div className="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none">
      <AnimatePresence>
        {toasts.map(t => (
          <motion.div key={t.id}
            initial={{ opacity: 0, y: 12, scale: 0.95 }}
            animate={{ opacity: 1, y: 0,  scale: 1    }}
            exit={{    opacity: 0, y: 8,  scale: 0.95 }}
            transition={{ duration: 0.2, ease: 'easeOut' }}
            className={`px-4 py-2.5 rounded-xl text-xs font-medium shadow-lg border backdrop-blur-sm ${
              t.type === 'error'   ? 'bg-red-50    border-red-200    text-red-700'
            : t.type === 'success' ? 'bg-white      border-border     text-foreground'
            :                        'bg-white      border-border     text-muted-foreground'
            }`}
          >
            {t.msg}
          </motion.div>
        ))}
      </AnimatePresence>
    </div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Markdown renderer
// ─────────────────────────────────────────────────────────────────────────────

function Inline({ t }) {
  if (!t) return null
  const tokens = []
  let rest = t, k = 0
  while (rest.length) {
    const bold   = rest.match(/^([\s\S]*?)\*\*(.+?)\*\*/)
    const italic = rest.match(/^([\s\S]*?)(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/)
    const code   = rest.match(/^([\s\S]*?)`(.+?)`/)
    const all    = [bold, italic, code].filter(Boolean)
    if (!all.length) { tokens.push(<span key={k++}>{rest}</span>); break }
    const pick = all.reduce((a, b) => (a[1].length <= b[1].length ? a : b))
    if (pick[1]) tokens.push(<span key={k++}>{pick[1]}</span>)
    if (pick === bold)        tokens.push(<strong key={k++} className="font-semibold text-foreground">{pick[2]}</strong>)
    else if (pick === italic) tokens.push(<em key={k++}>{pick[2]}</em>)
    else                      tokens.push(<code key={k++} className="bg-neutral-100 px-1 py-0.5 rounded text-[0.82em] font-mono">{pick[2]}</code>)
    rest = rest.slice(pick[0].length)
  }
  return <>{tokens}</>
}

function Markdown({ content }) {
  if (!content) return null
  const lines = content.split('\n')
  const blocks = []
  let i = 0
  while (i < lines.length) {
    const line = lines[i]
    if (line.startsWith('```')) {
      const cl = []; i++
      while (i < lines.length && !lines[i].startsWith('```')) { cl.push(lines[i]); i++ }
      blocks.push({ t: 'code', c: cl.join('\n') }); i++; continue
    }
    const h = line.match(/^(#{1,3})\s+(.+)$/)
    if (h) { blocks.push({ t: 'h', lvl: h[1].length, c: h[2] }); i++; continue }
    if (/^[-*•]\s/.test(line)) {
      const items = []
      while (i < lines.length && /^[-*•]\s/.test(lines[i])) { items.push(lines[i].replace(/^[-*•]\s+/, '')); i++ }
      blocks.push({ t: 'ul', items }); continue
    }
    if (/^\d+\.\s/.test(line)) {
      const items = []
      while (i < lines.length && /^\d+\.\s/.test(lines[i])) { items.push(lines[i].replace(/^\d+\.\s+/, '')); i++ }
      blocks.push({ t: 'ol', items }); continue
    }
    if (/^>\s?/.test(line)) {
      const rows = []
      while (i < lines.length && /^>\s?/.test(lines[i])) { rows.push(lines[i].replace(/^>\s?/, '')); i++ }
      blocks.push({ t: 'bq', rows }); continue
    }
    if (line.startsWith('|')) {
      const rows = []
      while (i < lines.length && lines[i].startsWith('|')) { rows.push(lines[i]); i++ }
      const parseRow = r => r.split('|').filter((_, j, a) => j > 0 && j < a.length - 1).map(c => c.trim())
      if (rows.length >= 2) {
        const headers = parseRow(rows[0])
        const data = rows.slice(2).map(parseRow)
        blocks.push({ t: 'table', headers, data })
      }
      continue
    }
    if (!line.trim()) { i++; continue }
    const para = []
    while (i < lines.length && lines[i].trim() && !lines[i].startsWith('```') && !lines[i].startsWith('#') && !/^[-*•]\s/.test(lines[i]) && !/^\d+\.\s/.test(lines[i]) && !/^>\s?/.test(lines[i]) && !lines[i].startsWith('|')) {
      para.push(lines[i]); i++
    }
    if (para.length) blocks.push({ t: 'p', c: para.join('\n') })
  }
  return (
    <div className="space-y-2.5 text-base leading-relaxed text-foreground">
      {blocks.map((b, idx) => {
        if (b.t === 'code') return (
          <pre key={idx} className="bg-neutral-950 text-neutral-100 rounded-xl px-4 py-3 overflow-x-auto text-[13px] leading-relaxed font-mono my-1">
            <code>{b.c}</code>
          </pre>
        )
        if (b.t === 'h') return (
          <p key={idx} className={`font-semibold mt-3 first:mt-0 ${b.lvl === 1 ? 'text-lg' : 'text-base'}`}>
            <Inline t={b.c} />
          </p>
        )
        if (b.t === 'ul') return (
          <ul key={idx} className="space-y-1.5">
            {b.items.map((item, j) => (
              <li key={j} className="flex gap-2">
                <span className="text-muted-foreground shrink-0 mt-[2px] select-none">—</span>
                <span><Inline t={item} /></span>
              </li>
            ))}
          </ul>
        )
        if (b.t === 'ol') return (
          <ol key={idx} className="space-y-1.5">
            {b.items.map((item, j) => (
              <li key={j} className="flex gap-2">
                <span className="text-muted-foreground shrink-0 font-mono text-[11px] mt-0.5 min-w-[18px] select-none">{j + 1}.</span>
                <span><Inline t={item} /></span>
              </li>
            ))}
          </ol>
        )
        if (b.t === 'bq') {
          const full = b.rows.join(' ')
          const isDanger = /🔴|❌|critique|urgent/i.test(full)
          const isGood   = /✅|💡|recomman/i.test(full)
          const isWarn   = /⚠️|⚡|attention|alerte|inhabituel|anormal/i.test(full)
          const border = isDanger ? '#ef4444' : isWarn ? '#f59e0b' : isGood ? '#10b981' : '#6366f1'
          const bg     = isDanger ? '#fef2f2' : isWarn ? '#fffbeb' : isGood ? '#f0fdf4' : '#eef2ff'
          return (
            <div key={idx} className="pl-3 pr-3 py-2.5 rounded-r-lg my-0.5 space-y-0.5" style={{ borderLeft: `3px solid ${border}`, background: bg }}>
              {b.rows.map((row, j) => <p key={j} className="text-sm leading-relaxed"><Inline t={row} /></p>)}
            </div>
          )
        }
        if (b.t === 'table') return (
          <div key={idx} className="overflow-x-auto rounded-lg border border-border my-1">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-muted/40 border-b border-border">
                  {b.headers.map((h, j) => (
                    <th key={j} className="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-muted-foreground whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {b.data.map((row, j) => (
                  <tr key={j} className="hover:bg-muted/20 transition-colors">
                    {row.map((cell, k) => (
                      <td key={k} className="px-3 py-2 text-xs"><Inline t={cell} /></td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )
        return <p key={idx}><Inline t={b.c} /></p>
      })}
    </div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Stats widget — affiché automatiquement quand l'intent est détecté
// ─────────────────────────────────────────────────────────────────────────────

function StatsWidget({ data, intents, range }) {
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

// ─────────────────────────────────────────────────────────────────────────────
// Suggested replies — questions de relance contextuelles
// ─────────────────────────────────────────────────────────────────────────────

function getSuggestions(intents = []) {
  if (intents.includes('cancellation')) return [
    'Quelles causes expliquent ces annulations ?',
    "Comparer le taux d'annulation par produit",
    'Comment réduire les annulations ?',
  ]
  if (intents.includes('revenue') || intents.includes('trend')) return [
    "Comparer avec la même période l'an dernier",
    'Détailler l\'évolution par produit',
    'Quels leviers pour augmenter le CA ?',
  ]
  if (intents.includes('products')) return [
    'Quel produit a le meilleur panier moyen ?',
    'Quels produits sous-performent ?',
    'Évolution des ventes sur 12 mois',
  ]
  if (intents.includes('bookings') || intents.includes('basket')) return [
    'Analyser la saisonnalité des réservations',
    'Top produits de cette période',
    'Quels canaux génèrent le plus de réservations ?',
  ]
  return [
    'Quels leviers prioritaires pour progresser ?',
    'Analyse les tendances sur 6 mois',
    'Identifie les opportunités non exploitées',
  ]
}

function SuggestedReplies({ intents, onSend }) {
  const suggestions = getSuggestions(intents)
  return (
    <motion.div
      initial={{ opacity: 0, y: 6 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.25, delay: 0.35, ease: 'easeOut' }}
      className="flex flex-wrap gap-2 mt-2 pl-10"
    >
      {suggestions.map((s, i) => (
        <button
          key={i}
          onClick={() => onSend(s)}
          className="text-xs px-3 py-1.5 rounded-full border border-border/70 text-muted-foreground hover:text-foreground hover:border-foreground/30 hover:bg-accent transition-all"
        >
          {s}
        </button>
      ))}
    </motion.div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Conversation sidebar — with inline rename
// ─────────────────────────────────────────────────────────────────────────────

function ConvItem({ conv, isActive, onSelect, onDelete, onRename }) {
  const [editing, setEditing] = useState(false)
  const [draft,   setDraft]   = useState('')
  const inputRef = useRef(null)

  function startEdit(e) {
    e.stopPropagation()
    setDraft(conv.title)
    setEditing(true)
    setTimeout(() => inputRef.current?.select(), 10)
  }

  function commitEdit() {
    if (draft.trim()) onRename(conv.id, draft.trim())
    setEditing(false)
  }

  function onKeyDown(e) {
    if (e.key === 'Enter')  { e.preventDefault(); commitEdit() }
    if (e.key === 'Escape') { setEditing(false) }
    e.stopPropagation()
  }

  return (
    <motion.div
      initial={{ opacity: 0, x: -8 }}
      animate={{ opacity: 1, x: 0 }}
      className={`group flex items-center gap-2 px-3 py-2 rounded-lg mx-1 cursor-pointer transition-colors text-sm relative ${
        isActive
          ? 'bg-accent text-foreground font-medium'
          : 'text-muted-foreground hover:text-foreground hover:bg-accent/60'
      }`}
      onClick={() => !editing && onSelect(conv.id)}
    >
      <ChatLines width={13} height={13} strokeWidth={1.5} className="shrink-0 opacity-50" />

      {editing ? (
        <input
          ref={inputRef}
          value={draft}
          onChange={e => setDraft(e.target.value)}
          onKeyDown={onKeyDown}
          onBlur={commitEdit}
          className="flex-1 text-xs bg-background border border-ring rounded px-1.5 py-0.5 focus:outline-none min-w-0"
          onClick={e => e.stopPropagation()}
        />
      ) : (
        <span className="flex-1 truncate text-xs">{conv.title}</span>
      )}

      {/* Badge conversations partagées / collaborateurs */}
      {!editing && conv.permission && conv.permission !== 'owner' && (
        <span className="shrink-0 text-[9px] px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-600 font-medium leading-none">
          Partagé
        </span>
      )}
      {!editing && conv.permission === 'owner' && conv.db_id && (conv.participants?.length ?? 0) > 1 && (
        <div className="shrink-0 flex -space-x-1">
          {(conv.participants ?? []).filter(p => p.user_id !== window.btBackoffice?.current_user?.id).slice(0, 2).map(p => (
            <img key={p.user_id} src={p.avatar} alt={p.display_name} title={p.display_name}
              className="w-3.5 h-3.5 rounded-full border border-background" />
          ))}
        </div>
      )}

      {!editing && (
        <div className="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-all shrink-0">
          <button
            onClick={startEdit}
            title="Renommer"
            className="p-0.5 rounded hover:text-foreground transition-colors"
          >
            <EditPencil width={10} height={10} strokeWidth={1.5} />
          </button>
          <button
            onClick={e => { e.stopPropagation(); onDelete(conv.id) }}
            title="Supprimer"
            className="p-0.5 rounded hover:text-destructive transition-colors"
          >
            <Trash width={11} height={11} strokeWidth={1.5} />
          </button>
        </div>
      )}
    </motion.div>
  )
}

function ConvGroup({ label, convs, activeId, onSelect, onDelete, onRename }) {
  if (!convs.length) return null
  return (
    <div className="mb-4">
      <p className="text-[10px] uppercase tracking-widest text-muted-foreground/50 font-medium px-3 mb-1">{label}</p>
      {convs.map(c => (
        <ConvItem
          key={c.id}
          conv={c}
          isActive={c.id === activeId}
          onSelect={onSelect}
          onDelete={onDelete}
          onRename={onRename}
        />
      ))}
    </div>
  )
}

function ConvSidebar({ grouped, activeId, onSelect, onNew, onDelete, onRename, onClear }) {
  const total = Object.values(grouped).flat().length
  return (
    <aside className="w-full flex flex-col bg-background overflow-hidden">
      <div className="p-3 border-b">
        <button
          onClick={onNew}
          className="w-full flex items-center gap-2 px-3 py-2 rounded-lg border border-dashed border-border text-xs text-muted-foreground hover:text-foreground hover:border-foreground/30 hover:bg-accent/50 transition-all"
        >
          <Plus width={13} height={13} strokeWidth={2} />
          Nouvelle conversation
        </button>
      </div>

      <div className="flex-1 overflow-y-auto py-2">
        {total === 0 ? (
          <div className="flex flex-col items-center gap-2 py-10 px-4 text-center">
            <Sparks width={20} height={20} strokeWidth={1.5} className="text-muted-foreground/40" />
            <p className="text-[11px] text-muted-foreground/60 leading-relaxed">
              Vos conversations apparaîtront ici
            </p>
          </div>
        ) : (
          <>
            <ConvGroup label="Aujourd'hui"       convs={grouped.today}     activeId={activeId} onSelect={onSelect} onDelete={onDelete} onRename={onRename} />
            <ConvGroup label="Hier"              convs={grouped.yesterday} activeId={activeId} onSelect={onSelect} onDelete={onDelete} onRename={onRename} />
            <ConvGroup label="7 derniers jours"  convs={grouped.week}      activeId={activeId} onSelect={onSelect} onDelete={onDelete} onRename={onRename} />
            <ConvGroup label="Plus ancien"       convs={grouped.older}     activeId={activeId} onSelect={onSelect} onDelete={onDelete} onRename={onRename} />
          </>
        )}
      </div>

      {total > 0 && (
        <div className="p-3 border-t">
          <button
            onClick={onClear}
            className="text-[11px] text-muted-foreground/60 hover:text-destructive transition-colors"
          >
            Effacer l'historique
          </button>
        </div>
      )}
    </aside>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// useTypewriter — révèle le texte progressivement, lisse les gros chunks SSE
// ─────────────────────────────────────────────────────────────────────────────

function useTypewriter(target, active) {
  const [text, setText]   = useState('')
  const rafRef    = useRef(null)
  const targetRef = useRef(target)

  // Garde la cible à jour sans re-déclencher la boucle
  useEffect(() => { targetRef.current = target }, [target])

  useEffect(() => {
    if (!active) {
      // Streaming terminé → affichage immédiat du texte final
      if (rafRef.current) { cancelAnimationFrame(rafRef.current); rafRef.current = null }
      setText(target)
      return
    }
    function tick() {
      setText(prev => {
        const t   = targetRef.current
        if (prev.length >= t.length) return prev
        // Accélère si on a du retard pour ne jamais être trop en arrière
        const lag  = t.length - prev.length
        const step = lag > 300 ? 12 : lag > 80 ? 5 : 2
        return t.slice(0, prev.length + step)
      })
      rafRef.current = requestAnimationFrame(tick)
    }
    rafRef.current = requestAnimationFrame(tick)
    return () => { if (rafRef.current) { cancelAnimationFrame(rafRef.current); rafRef.current = null } }
  }, [active]) // eslint-disable-line react-hooks/exhaustive-deps

  return text
}

// ─────────────────────────────────────────────────────────────────────────────
// Message components
// ─────────────────────────────────────────────────────────────────────────────

function CopyBtn({ text, onCopy }) {
  const [done, setDone] = useState(false)
  function copy() {
    navigator.clipboard.writeText(text).then(() => {
      setDone(true); setTimeout(() => setDone(false), 2000)
      onCopy?.()
    })
  }
  return (
    <button onClick={copy} title="Copier"
      className="p-1 rounded hover:bg-muted/80 text-muted-foreground hover:text-foreground transition-colors opacity-0 group-hover:opacity-100"
    >
      {done
        ? <Check width={12} height={12} strokeWidth={2} className="text-emerald-500" />
        : <Copy  width={12} height={12} strokeWidth={1.5} />}
    </button>
  )
}

function UserMsg({ msg, participants, currentUserId }) {
  // Normalise les IDs en string pour éviter number/string mismatch (PHP ARRAY_A vs JSON)
  const myId    = currentUserId != null ? String(currentUserId) : null
  const msgUid  = msg.user_id   != null ? String(msg.user_id)   : null

  // Message de l'utilisateur courant si pas de user_id (message local) ou user_id == moi
  const isMe        = !msgUid || msgUid === myId
  const participant = participants?.find(p => String(p.user_id) === (isMe ? myId : msgUid))
  const bubbleColor = participant?.color ?? '#dbd2c0'

  return (
    <motion.div
      initial={{ opacity: 0, y: 10, scale: 0.98 }}
      animate={{ opacity: 1, y: 0,  scale: 1    }}
      transition={{ duration: 0.18, ease: 'easeOut' }}
      className="flex justify-end gap-3"
    >
      <div className="max-w-[76%] space-y-1.5">
        {/* Nom + avatar si c'est un autre participant */}
        {!isMe && participant && (
          <div className="flex items-center gap-1.5 justify-end mb-0.5">
            <span className="text-[10px] text-muted-foreground font-medium">{participant.display_name}</span>
            <img src={participant.avatar} alt={participant.display_name}
              className="w-4 h-4 rounded-full border border-background shrink-0" />
          </div>
        )}
        {msg.images?.length > 0 && (
          <div className="flex gap-1.5 justify-end flex-wrap">
            {msg.images.map((img, i) => (
              <img key={i} src={`data:${img.type};base64,${img.data}`}
                className="h-20 w-20 object-cover rounded-xl border border-white/20 shadow-sm" alt="" />
            ))}
          </div>
        )}
        <div
          className="rounded-2xl rounded-tr-sm px-4 py-3 text-sm leading-relaxed text-foreground"
          style={{ backgroundColor: bubbleColor }}
        >
          {msg.content}
        </div>
      </div>
    </motion.div>
  )
}

function AssistantMsg({ msg, streaming = false, onCopy, onShare, isLast = false, onSend }) {
  const cfg         = getProvider(msg.provider || 'anthropic')
  const displayText = useTypewriter(msg.content, streaming)
  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0  }}
      transition={{ duration: 0.22, ease: 'easeOut' }}
      className="flex gap-3 group"
    >
      {/* Avatar */}
      <div
        className="w-7 h-7 rounded-full flex items-center justify-center shrink-0 mt-0.5 shadow-sm"
        style={{ background: cfg.accent + '22', border: `1.5px solid ${cfg.accent}44` }}
      >
        <AiProviderIcon iconKey={cfg.iconKey} variant="Color" size={15} />
      </div>

      {/* Bubble */}
      <div className="flex-1 min-w-0">
        <div className="bg-card border border-border/70 rounded-2xl rounded-tl-sm px-4 py-3.5 shadow-sm">
          {streaming
            ? (
              <div className="text-base leading-relaxed whitespace-pre-wrap">
                {displayText}
                <motion.span
                  animate={{ opacity: [1, 0, 1] }}
                  transition={{ duration: 0.6, repeat: Infinity }}
                  className="inline-block w-[2px] h-[1em] bg-foreground/50 ml-0.5 rounded-sm align-text-bottom"
                />
              </div>
            )
            : <Markdown content={msg.content} />}
        </div>

        {/* Actions + KPIs */}
        {!streaming && msg.content && (
          <>
            <div className="flex items-center gap-1 mt-1.5 pl-1">
              <CopyBtn text={msg.content} onCopy={onCopy} />
              {onShare && (
                <button
                  onClick={() => onShare(msg.content)}
                  title="Partager cette réponse"
                  className="p-1 rounded hover:bg-muted/80 text-muted-foreground hover:text-foreground transition-colors opacity-0 group-hover:opacity-100"
                >
                  <ShareAndroid width={12} height={12} strokeWidth={1.5} />
                </button>
              )}
              <span className="text-[10px] text-muted-foreground/50 self-center ml-1">{cfg.label}</span>
            </div>
            {msg.statsData && (
              <StatsWidget
                data={msg.statsData.data}
                intents={msg.statsData.intents}
                range={msg.statsData.range}
              />
            )}
            {isLast && onSend && (
              <SuggestedReplies
                intents={msg.statsData?.intents ?? []}
                onSend={onSend}
              />
            )}
          </>
        )}
      </div>
    </motion.div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Empty state / Welcome screen
// ─────────────────────────────────────────────────────────────────────────────

const SUGGESTIONS = [
  'Quels leviers pour augmenter le revenu moyen par réservation ?',
  'Identifie les signaux faibles dans mes données.',
  'Analyse ma saisonnalité et les pics de demande.',
  '3 actions concrètes pour améliorer les performances ce mois-ci.',
  'Quels canaux sous-performent et pourquoi ?',
  'Quelles opportunités de croissance non exploitées vois-tu ?',
]

function WelcomeScreen({ activeProvider, onSuggestion }) {
  const cfg = getProvider(activeProvider)
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0  }}
      transition={{ duration: 0.35, ease: 'easeOut' }}
      className="flex flex-col items-center justify-center h-full gap-8 py-10 px-6"
    >
      <div className="flex flex-col items-center gap-3">
        <motion.div
          animate={{ scale: [1, 1.03, 1] }}
          transition={{ duration: 3, repeat: Infinity, ease: 'easeInOut' }}
          className="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg"
          style={{ background: cfg.accent + '18', border: `1.5px solid ${cfg.accent}33` }}
        >
          <AiProviderIcon iconKey={cfg.iconKey} variant="Color" size={32} />
        </motion.div>
        <div className="text-center">
          <h2 className="text-base font-semibold text-foreground">Conseiller IA — BlackTenders</h2>
          <p className="text-xs text-muted-foreground mt-0.5">Analyse commerciale · Stratégie · Insights</p>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 w-full max-w-2xl">
        {SUGGESTIONS.map((text, i) => (
          <motion.button
            key={i}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0  }}
            transition={{ duration: 0.25, delay: i * 0.05, ease: 'easeOut' }}
            onClick={() => onSuggestion(text)}
            className="text-left text-xs px-4 py-3 rounded-xl border border-border bg-card hover:bg-accent hover:border-foreground/10 transition-all leading-snug text-muted-foreground hover:text-foreground shadow-sm"
          >
            {text}
          </motion.button>
        ))}
      </div>
    </motion.div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Model picker
// ─────────────────────────────────────────────────────────────────────────────

function ModelPicker({ active, available, onChange }) {
  const [open, setOpen] = useState(false)
  const ref = useRef(null)
  const cfg = getProvider(active)

  useEffect(() => {
    if (!open) return
    function handler(e) { if (ref.current && !ref.current.contains(e.target)) setOpen(false) }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [open])

  return (
    <div ref={ref} className="relative">
      <button
        type="button"
        onClick={() => setOpen(o => !o)}
        className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs text-muted-foreground hover:text-foreground hover:bg-muted/50 transition-colors"
      >
        <AiProviderIcon iconKey={cfg.iconKey} variant="Color" size={12} />
        <span className="font-medium">{cfg.label}</span>
        <NavArrowDown width={9} height={9} strokeWidth={2.5} className={`opacity-60 transition-transform duration-200 ${open ? 'rotate-180' : ''}`} />
      </button>

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, y: 6, scale: 0.97 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{    opacity: 0, y: 6, scale: 0.97 }}
            transition={{ duration: 0.15, ease: 'easeOut' }}
            className="absolute bottom-full left-0 mb-2 w-72 bg-card border border-border rounded-xl shadow-xl overflow-hidden z-50"
          >
            <div className="px-4 py-2.5 border-b bg-muted/30">
              <p className="text-[10px] uppercase tracking-widest text-muted-foreground/60 font-semibold">Modèle</p>
            </div>
            <div className="p-1.5 space-y-0.5">
              {PROVIDER_LIST.map(p => {
                const avail = available[p.key]
                const isActive = p.key === active
                return (
                  <button
                    key={p.key}
                    type="button"
                    disabled={!avail}
                    onClick={() => { if (avail) { onChange(p.key); setOpen(false) } }}
                    className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-colors ${
                      isActive && avail  ? 'bg-accent text-foreground'
                      : avail            ? 'text-muted-foreground hover:text-foreground hover:bg-accent/60'
                      :                    'opacity-35 cursor-not-allowed'
                    }`}
                  >
                    <div
                      className="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                      style={{ background: p.accent + '18', border: `1px solid ${p.accent}30` }}
                    >
                      <AiProviderIcon iconKey={p.iconKey} variant="Color" size={15} />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-xs font-medium leading-tight truncate">{p.label}</p>
                      <p className="text-[10px] text-muted-foreground/60 font-mono truncate mt-0.5">{p.model}</p>
                    </div>
                    {isActive && avail
                      ? <Check width={13} height={13} strokeWidth={2.5} className="text-primary shrink-0" />
                      : !avail && <span className="text-[10px] text-muted-foreground/40 shrink-0 font-medium">clé manquante</span>
                    }
                  </button>
                )
              })}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Image previews
// ─────────────────────────────────────────────────────────────────────────────

function ImagePreviews({ images, onRemove }) {
  if (!images.length) return null
  return (
    <div className="flex gap-2 px-4 pt-3 flex-wrap">
      {images.map((img, i) => (
        <div key={i} className="relative group/img">
          <img src={`data:${img.type};base64,${img.data}`}
            className="h-14 w-14 object-cover rounded-lg border border-input shadow-sm" alt="" />
          <button type="button" onClick={() => onRemove(i)}
            className="absolute -top-1.5 -right-1.5 w-5 h-5 bg-foreground text-background rounded-full flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-opacity shadow">
            <Xmark width={10} height={10} strokeWidth={2.5} />
          </button>
        </div>
      ))}
    </div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Thinking indicator
// ─────────────────────────────────────────────────────────────────────────────

function ThinkingIndicator({ provider }) {
  const cfg = getProvider(provider)
  return (
    <motion.div
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      className="flex gap-3 items-start"
    >
      <div
        className="w-7 h-7 rounded-full flex items-center justify-center shrink-0 shadow-sm"
        style={{ background: cfg.accent + '22', border: `1.5px solid ${cfg.accent}44` }}
      >
        {cfg.lottie ? (
          <Lottie animationData={cfg.lottie} loop className="w-5 h-5" />
        ) : (
          <AiProviderIcon iconKey={cfg.iconKey} variant="Color" size={15} />
        )}
      </div>
      <div className="bg-card border border-border/70 rounded-2xl rounded-tl-sm px-4 py-3.5 shadow-sm">
        <div className="flex items-center gap-1.5">
          {[0,1,2].map(i => (
            <motion.span key={i}
              className="w-1.5 h-1.5 rounded-full"
              style={{ background: cfg.accent }}
              animate={{ opacity: [0.3, 1, 0.3], scale: [0.7, 1, 0.7] }}
              transition={{ duration: 1.2, repeat: Infinity, delay: i * 0.2, ease: 'easeInOut' }}
            />
          ))}
        </div>
      </div>
    </motion.div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Share Modal — conversation entière ou réponse individuelle
// ─────────────────────────────────────────────────────────────────────────────

function ShareModal({ mode, conv, msgContent, onClose, onToast }) {
  const [loading,   setLoading]   = useState(false)
  const [shareUrl,  setShareUrl]  = useState('')
  const [copied,    setCopied]    = useState(false)

  async function generate() {
    setLoading(true)
    try {
      const payload = mode === 'msg'
        ? {
            title:    `Réponse IA — ${conv?.title ?? 'Conversation'}`,
            provider: conv?.provider ?? 'anthropic',
            messages: [{ role: 'assistant', content: msgContent, provider: conv?.provider }],
          }
        : {
            title:    conv.title,
            provider: conv.provider,
            messages: conv.messages,
          }
      const res = await api.shareChat(payload)
      setShareUrl(res.url)
    } catch (e) {
      onToast?.(e.message || 'Erreur lors de la génération du lien', 'error')
      onClose()
    } finally {
      setLoading(false)
    }
  }

  function copyUrl() {
    navigator.clipboard.writeText(shareUrl).then(() => {
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
      onToast?.('Lien copié !')
    })
  }

  return (
    <Dialog
      open
      onClose={onClose}
      title={mode === 'msg' ? 'Partager cette réponse' : 'Partager la conversation'}
      description="Génère un lien d'accès pour un administrateur connecté."
      size="md"
    >
      {!shareUrl ? (
        <div className="space-y-4">
          {/* Preview */}
          <div className="bg-muted/40 rounded-xl p-3 max-h-40 overflow-y-auto">
            {mode === 'msg' ? (
              <p className="text-xs text-muted-foreground leading-relaxed line-clamp-6">{msgContent}</p>
            ) : (
              <div className="space-y-1.5">
                {(conv?.messages ?? []).slice(0, 4).map((m, i) => (
                  <div key={i} className={`text-[11px] leading-relaxed ${m.role === 'user' ? 'text-foreground/70' : 'text-muted-foreground'}`}>
                    <span className="font-medium uppercase tracking-wide text-[9px] opacity-50">{m.role === 'user' ? 'Vous' : 'IA'}</span>
                    <p className="truncate">{m.content?.slice(0, 120)}{(m.content?.length ?? 0) > 120 ? '…' : ''}</p>
                  </div>
                ))}
                {(conv?.messages?.length ?? 0) > 4 && (
                  <p className="text-[10px] text-muted-foreground/50">+ {conv.messages.length - 4} autres messages</p>
                )}
              </div>
            )}
          </div>

          <button
            onClick={generate}
            disabled={loading}
            className="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors"
          >
            {loading ? (
              <span className="w-4 h-4 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin" />
            ) : (
              <Link width={14} height={14} strokeWidth={2} />
            )}
            {loading ? 'Génération…' : 'Générer le lien'}
          </button>
        </div>
      ) : (
        <div className="space-y-4">
          <div className="flex items-center gap-2 bg-muted/40 rounded-xl px-3 py-2.5 border border-border">
            <Link width={13} height={13} strokeWidth={1.5} className="text-muted-foreground shrink-0" />
            <span className="flex-1 text-xs font-mono text-foreground truncate">{shareUrl}</span>
          </div>
          <button
            onClick={copyUrl}
            className={`w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all ${
              copied
                ? 'bg-emerald-50 border border-emerald-200 text-emerald-700'
                : 'bg-primary text-primary-foreground hover:bg-primary/90'
            }`}
          >
            {copied
              ? <><Check width={14} height={14} strokeWidth={2.5} /> Copié !</>
              : <><Copy  width={14} height={14} strokeWidth={1.5} /> Copier le lien</>
            }
          </button>
          <p className="text-[11px] text-muted-foreground/60 text-center leading-relaxed">
            Ce lien est accessible aux administrateurs connectés. Il expire après 30 jours.
          </p>
        </div>
      )}
    </Dialog>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Main component
// ─────────────────────────────────────────────────────────────────────────────

export default function AIChat() {
  const {
    conversations, grouped, activeId, activeConv, dbLoading,
    setActiveId, create, updateMessages, updateProvider, remove, rename, clearAll, loadRemoteMessages, refreshMessages,
  } = useConversations()
  const [searchParams] = useSearchParams()

  const [sidebarWidth,  setSidebarWidth]  = useState(280)
  const isResizing = useRef(false)

  const startResize = useCallback((e) => {
    isResizing.current = true
    const startX = e.clientX
    const startW = sidebarWidth
    function onMove(ev) {
      if (!isResizing.current) return
      const w = Math.min(520, Math.max(200, startW + ev.clientX - startX))
      setSidebarWidth(w)
    }
    function onUp() {
      isResizing.current = false
      document.removeEventListener('mousemove', onMove)
      document.removeEventListener('mouseup', onUp)
      document.body.style.cursor = ''
      document.body.style.userSelect = ''
    }
    document.body.style.cursor = 'col-resize'
    document.body.style.userSelect = 'none'
    document.addEventListener('mousemove', onMove)
    document.addEventListener('mouseup', onUp)
  }, [sidebarWidth])

  const [input,         setInput]         = useState('')
  const [images,        setImages]        = useState([])
  const [streaming,     setStreaming]     = useState(false)
  const [streamText,    setStreamText]    = useState('')
  const [error,         setError]         = useState(null)
  const [filterParams,  setFilterParams]  = useState({ from: daysAgo(365), to: today() })
  const [activeProvider,setActiveProvider]= useState('anthropic')
  const [availProviders,setAvailProviders]= useState({ anthropic: false, openai: false, gemini: false })
  const [showThinking,  setShowThinking]  = useState(false)

  // Share panel (nouveau système granulaire) + ancien modal message
  const [shareModal,  setShareModal]  = useState({ open: false, mode: 'conv', msgContent: '' })
  const [sharePanelOpen, setSharePanelOpen] = useState(false)

  // Shared chat token from URL (e.g. ?bt_chat=TOKEN)
  const [sharedLoading, setSharedLoading] = useState(false)

  const { toasts, push: toast } = useToast()
  const scrollRef = useRef(null)
  const inputRef  = useRef(null)
  const fileRef   = useRef(null)

  const messages = activeConv?.messages ?? []

  // Load AI status
  useEffect(() => {
    api.aiStatus().then(res => {
      setAvailProviders(res.providers ?? {})
      setActiveProvider(res.active ?? 'anthropic')
    }).catch(() => setAvailProviders({ anthropic: true }))
  }, [])

  // Sync provider with active conversation
  useEffect(() => {
    if (activeConv?.provider) setActiveProvider(activeConv.provider)
  }, [activeId])

  // Charge les messages + participants à la sélection :
  // - conversation remote (partagée, non locale)
  // - conversation owner synced en DB mais participants pas encore chargés
  useEffect(() => {
    if (!activeConv) return
    if (activeConv.remote || (activeConv.db_id && !activeConv.participants?.length)) {
      loadRemoteMessages(activeConv.id)
    }
  }, [activeId])

  // Polling temps réel — démarre dès qu'une conversation DB est active.
  // Condition : partagée (non-owner) OU collaborateurs connus OU participants pas encore chargés.
  // S'arrête automatiquement si on découvre que c'est une conversation solo.
  useEffect(() => {
    if (!activeConv?.db_id) return
    const isShared      = activeConv.permission !== 'owner'
    const hasCollabs    = (activeConv.participants?.length ?? 0) > 1
    const notYetLoaded  = !activeConv.participants?.length   // participants pas encore récupérés
    if (!isShared && !hasCollabs && !notYetLoaded) return
    const interval = setInterval(() => refreshMessages(activeConv.id), 3000)
    return () => clearInterval(interval)
  }, [activeId, activeConv?.db_id, activeConv?.participants?.length])

  // Load shared conversation from URL param (?share=uuid or legacy ?bt_chat=token)
  useEffect(() => {
    const shareUuid = searchParams.get('share')
    const legacyToken = searchParams.get('bt_chat')

    if (shareUuid) {
      // New system: load by UUID from DB
      import('../lib/chatApi').then(({ getChat }) => {
        setSharedLoading(true)
        getChat(shareUuid)
          .then(data => {
            setActiveId(shareUuid)
            toast('Conversation partagée chargée')
          })
          .catch(() => toast('Lien de partage introuvable ou accès refusé', 'error'))
          .finally(() => setSharedLoading(false))
      })
      return
    }

    if (legacyToken) {
      setSharedLoading(true)
      api.getSharedChat(legacyToken)
        .then(data => {
          const id = create(data.provider ?? 'anthropic', null)
          updateMessages(id, data.messages ?? [], data.title ?? 'Conversation partagée')
          toast('Conversation partagée chargée')
        })
        .catch(() => toast('Lien de partage introuvable ou expiré', 'error'))
        .finally(() => setSharedLoading(false))
    }
  }, []) // run once on mount

  // Auto-scroll — messages nouveaux + changement de conversation
  useEffect(() => {
    const el = scrollRef.current
    if (el) el.scrollTop = el.scrollHeight
  }, [messages, streamText, showThinking, activeId])

  // ── Send message ────────────────────────────────────────────────────────────
  const send = useCallback(async (content, attachedImages = []) => {
    if (!content.trim() || streaming) return

    let convId = activeId
    if (!convId) convId = create(activeProvider, filterParams)

    const userMsg = { role: 'user', content, images: attachedImages, id: `m_${Date.now()}`, user_id: window.btBackoffice?.current_user?.id }
    const updatedMsgs = [...messages, userMsg]

    const isFirst  = messages.length === 0
    const autoTitle = isFirst ? content.slice(0, 50) + (content.length > 50 ? '…' : '') : null
    updateMessages(convId, updatedMsgs, autoTitle)

    setInput(''); setImages([])
    setStreaming(true); setShowThinking(true); setStreamText(''); setError(null)

    // ── Auto-detect data intent + fetch stats ────────────────────────────────
    const intents     = detectDataIntent(content)
    const parsedRange = parseMessageDate(content)
    const statsRange  = parsedRange ?? filterParams
    let statsData     = null
    let contextBlock  = ''

    if (intents.length > 0) {
      try {
        const res = await api.bookingsStats({ from: statsRange.from, to: statsRange.to, granularity: 'month' })
        statsData    = res
        contextBlock = buildDataContext(res, statsRange.from, statsRange.to)
      } catch {}
    }

    // Injecte le contexte données dans le dernier message user (invisible en UI)
    const history = updatedMsgs.slice(-20).map((m, idx, arr) => {
      const base = { role: m.role, content: m.content }
      if (idx === arr.length - 1 && m.images?.length) base.images = m.images
      if (idx === arr.length - 1 && m.role === 'user' && contextBlock) {
        base.content = `${contextBlock}\n\n---\n${m.content}`
      }
      return base
    })

    try {
      const reader  = await streamChat(history, statsRange.from, statsRange.to, { provider: activeProvider })
      const decoder = new TextDecoder()
      let buf = '', full = ''

      while (true) {
        const { done, value } = await reader.read()
        if (done) break
        buf += decoder.decode(value, { stream: true })
        const lines = buf.split('\n')
        buf = lines.pop() ?? ''
        for (const line of lines) {
          if (!line.startsWith('data: ')) continue
          const raw = line.slice(6).trim()
          if (raw === '[DONE]') break
          try {
            const json = JSON.parse(raw)
            if (json.error) throw new Error(json.error)
            if (json.text) {
              setShowThinking(false)
              full += json.text
              setStreamText(full)
            }
          } catch (pe) {
            if (!pe.message?.startsWith('JSON')) throw pe
          }
        }
      }

      if (full) {
        const finalMsgs = [...updatedMsgs, {
          role: 'assistant', content: full, provider: activeProvider,
          id: `m_${Date.now()}`,
          statsData: statsData ? { data: statsData, intents, range: statsRange } : null,
        }]
        updateMessages(convId, finalMsgs)
        // Auto-sync vers DB si conversation partagée (db_id présent)
        const conv = conversations.find(c => c.id === convId)
        if (conv?.db_id) syncChat({ ...conv, messages: finalMsgs }).catch(() => {})
      }
    } catch (e) {
      if (full) {
        const finalMsgs = [...updatedMsgs, {
          role: 'assistant', content: full, provider: activeProvider,
          id: `m_${Date.now()}`,
          statsData: statsData ? { data: statsData, intents, range: statsRange } : null,
        }]
        updateMessages(convId, finalMsgs)
        const conv = conversations.find(c => c.id === convId)
        if (conv?.db_id) syncChat({ ...conv, messages: finalMsgs }).catch(() => {})
      }
      setError(e.message || "Erreur de connexion à l'IA.")
      setShowThinking(false)
    } finally {
      setStreaming(false); setShowThinking(false); setStreamText('')
    }
  }, [messages, streaming, filterParams, activeProvider, activeId, create, updateMessages, conversations])

  function handleSubmit(e)   { e.preventDefault(); send(input, images) }
  function handleKeyDown(e)  { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input, images) } }

  function handleFileChange(e) {
    Array.from(e.target.files ?? []).forEach(file => {
      if (!file.type.startsWith('image/')) return
      const reader = new FileReader()
      reader.onload = ev => setImages(prev => [...prev, { data: ev.target.result.split(',')[1], type: file.type }])
      reader.readAsDataURL(file)
    })
    e.target.value = ''
  }

  function handleProviderChange(key) {
    setActiveProvider(key)
    if (activeId) updateProvider(activeId, key)
  }

  function handleNewConv() { setActiveId(null); setInput(''); setImages([]); setError(null) }

  const hasMessages = messages.length > 0 || streaming

  return (
    <div className="flex" style={{ height: 'calc(100vh - 56px)' }}>

      {/* ── Conversation sidebar (redimensionnable) ────────────────────────── */}
      <div style={{ width: sidebarWidth, minWidth: sidebarWidth }} className="border-r bg-background overflow-hidden flex flex-col shrink-0">
        <ConvSidebar
          grouped={grouped}
          activeId={activeId}
          onSelect={id => { setActiveId(id); setError(null) }}
          onNew={handleNewConv}
          onDelete={id => { remove(id); toast('Conversation supprimée') }}
          onRename={rename}
          onClear={() => { clearAll(); toast('Historique effacé') }}
        />
      </div>

      {/* Poignée de redimensionnement */}
      <div
        onMouseDown={startResize}
        className="w-1 shrink-0 hover:bg-border/60 active:bg-border transition-colors cursor-col-resize"
      />

      {/* ── Main chat ────────────────────────────────────────────────────────── */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">

        {/* Toolbar */}
        <div className="flex items-center gap-3 px-5 py-2.5 border-b bg-background shrink-0">
          {/* Chat title */}
          {activeConv?.title && (
            <span className="text-xs font-medium text-foreground/70 truncate max-w-[180px] shrink-0" title={activeConv.title}>
              {activeConv.title}
            </span>
          )}
          {activeConv?.title && <span className="text-border shrink-0">·</span>}

          <span className="text-xs text-muted-foreground shrink-0">Période :</span>
          <input type="date" value={filterParams.from}
            onChange={e => setFilterParams(p => ({ ...p, from: e.target.value }))}
            className="h-7 rounded-md border border-input bg-transparent px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" />
          <span className="text-xs text-muted-foreground">→</span>
          <input type="date" value={filterParams.to}
            onChange={e => setFilterParams(p => ({ ...p, to: e.target.value }))}
            className="h-7 rounded-md border border-input bg-transparent px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" />

          <div className="ml-auto flex items-center gap-2 shrink-0">
            {/* Avatars des participants de la conversation active */}
            {activeConv && (activeConv.participants?.length ?? 0) > 0 && (
              <div className="flex -space-x-1.5 items-center">
                {(activeConv.participants ?? []).slice(0, 5).map(p => (
                  <img
                    key={p.user_id}
                    src={p.avatar}
                    alt={p.display_name}
                    title={`${p.display_name}${p.permission === 'owner' ? ' (propriétaire)' : p.permission === 'write' ? ' (écriture)' : ' (lecture)'}`}
                    className="w-6 h-6 rounded-full border-2 border-background shadow-sm"
                    style={{ outline: `2px solid ${p.color ?? '#e5e5e5'}` }}
                  />
                ))}
                {(activeConv.participants?.length ?? 0) > 5 && (
                  <span className="w-6 h-6 rounded-full bg-muted border-2 border-background text-[9px] font-medium text-muted-foreground flex items-center justify-center">
                    +{activeConv.participants.length - 5}
                  </span>
                )}
              </div>
            )}

            {/* Share button */}
            {activeConv && messages.length > 0 && (
              <button
                onClick={() => setSharePanelOpen(true)}
                title="Partager cette conversation"
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-foreground text-background hover:bg-foreground/85 transition-colors shrink-0"
              >
                <ShareAndroid width={12} height={12} strokeWidth={2} />
                <span className="hidden sm:inline">Partager</span>
              </button>
            )}
          </div>
        </div>

        {/* Messages */}
        <div ref={scrollRef} className="flex-1 overflow-y-auto px-6 py-6" style={{ backgroundColor: '#F7F5F1' }}>
          {sharedLoading ? (
            <div className="flex items-center justify-center h-full gap-3 text-sm text-muted-foreground">
              <span className="w-4 h-4 border-2 border-muted border-t-foreground/50 rounded-full animate-spin" />
              Chargement de la conversation…
            </div>
          ) : (
            <AnimatePresence mode="wait">
              {!hasMessages ? (
                <WelcomeScreen
                  key="welcome"
                  activeProvider={activeProvider}
                  onSuggestion={text => send(text, [])}
                />
              ) : (
                <motion.div key="chat" className="space-y-6 max-w-3xl mx-auto">
                  {(() => {
                    const lastAsstIdx = messages.map(m => m.role).lastIndexOf('assistant')
                    return messages.map((msg, idx) => (
                      msg.role === 'user'
                        ? <UserMsg key={msg.id} msg={msg} participants={activeConv?.participants} currentUserId={window.btBackoffice?.current_user?.id} />
                        : <AssistantMsg
                            key={msg.id}
                            msg={msg}
                            isLast={idx === lastAsstIdx && !streaming}
                            onSend={idx === lastAsstIdx && !streaming ? send : undefined}
                            onCopy={() => toast('Copié dans le presse-papier')}
                            onShare={content => setShareModal({ open: true, mode: 'msg', msgContent: content })}
                          />
                    ))
                  })()}

                  <AnimatePresence>
                    {showThinking && <ThinkingIndicator key="think" provider={activeProvider} />}
                  </AnimatePresence>

                  <AnimatePresence>
                    {streaming && !showThinking && streamText && (
                      <AssistantMsg
                        key="stream"
                        msg={{ content: streamText, provider: activeProvider, id: 'stream' }}
                        streaming
                      />
                    )}
                  </AnimatePresence>
                </motion.div>
              )}
            </AnimatePresence>
          )}
        </div>

        {/* Error */}
        <AnimatePresence>
          {error && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: 'auto' }}
              exit={{    opacity: 0, height: 0     }}
              className="px-6 pb-1"
            >
              <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">
                <span className="flex-1">{error}</span>
                <button onClick={() => setError(null)}><Xmark width={13} height={13} strokeWidth={2} /></button>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Input */}
        <div className="px-4 py-3 shrink-0 border-t bg-background">
          <form onSubmit={handleSubmit} className="max-w-3xl mx-auto">
            <div className="rounded-2xl bg-card shadow-sm focus-within:shadow-[0_0_0_3px_rgba(26,25,23,0.06)] transition-all">
              <ImagePreviews images={images} onRemove={i => setImages(p => p.filter((_, idx) => idx !== i))} />
              <textarea
                ref={inputRef} rows={1} value={input}
                onChange={e => setInput(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder="Posez votre question… (Entrée pour envoyer)"
                disabled={streaming}
                className="w-full resize-none bg-transparent px-4 py-3 text-sm focus-visible:outline-none placeholder:text-muted-foreground disabled:opacity-50 leading-relaxed"
                style={{ fieldSizing: 'content', minHeight: '44px', maxHeight: '180px' }}
              />
              <div className="flex items-center justify-between px-3 pb-3 gap-2">
                <div className="flex items-center gap-2">
                  <button type="button" onClick={() => fileRef.current?.click()}
                    title="Joindre une image"
                    className="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/60 transition-colors shrink-0">
                    <MediaImage width={15} height={15} strokeWidth={1.5} />
                  </button>
                  <input ref={fileRef} type="file" accept="image/*" multiple className="hidden" onChange={handleFileChange} />
                  <ModelPicker active={activeProvider} available={availProviders} onChange={handleProviderChange} />
                </div>
                <button type="submit"
                  disabled={(!input.trim() && !images.length) || streaming}
                  className="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center hover:bg-primary/90 disabled:opacity-30 disabled:pointer-events-none transition-all shadow-sm shrink-0"
                >
                  {streaming
                    ? <span className="w-3 h-3 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin" />
                    : <SendDiagonal width={13} height={13} strokeWidth={2.5} />}
                </button>
              </div>
            </div>
            <p className="text-[10px] text-muted-foreground mt-1.5 px-1">
              Shift+Entrée pour un saut de ligne
              {images.length > 0 && <span className="ml-2 font-medium">{images.length} image{images.length > 1 ? 's' : ''} jointe{images.length > 1 ? 's' : ''}</span>}
            </p>
          </form>
        </div>
      </div>

      {/* Share panel (conversation) */}
      <ChatSharePanel
        open={sharePanelOpen}
        onClose={() => { setSharePanelOpen(false); if (activeConv?.db_id) loadRemoteMessages(activeConv.id) }}
        conv={activeConv}
      />

      {/* Share modal (single message) */}
      {shareModal.open && shareModal.mode === 'msg' && (
        <ShareModal
          mode={shareModal.mode}
          conv={activeConv}
          msgContent={shareModal.msgContent}
          onClose={() => setShareModal(p => ({ ...p, open: false }))}
          onToast={toast}
        />
      )}

      {/* Toast stack */}
      <ToastStack toasts={toasts} />
    </div>
  )
}
