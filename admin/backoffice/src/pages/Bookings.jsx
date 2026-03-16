import { useState, useEffect, useCallback } from 'react'
import { NavArrowRight, Star, Mail, Phone, Calendar, Search } from 'iconoir-react'
import { format, formatDistanceToNowStrict } from 'date-fns'
import { fr } from 'date-fns/locale'
import { api } from '../lib/api'
import { avatarColor } from '../lib/colors'
import { STATUS_MAP } from '../lib/status'
import { fmtCurrency } from '../lib/utils'
import { Sheet, SheetContent, SheetRow } from '../components/Sheet'
import { TooltipProvider, Tooltip } from '../components/Tooltip'
import { PageHeader, Pagination, Notice, Spinner, Badge, Btn } from '../components/ui'

function offsetDate(days) {
  const d = new Date()
  d.setDate(d.getDate() - days)
  return d.toISOString().slice(0, 10)
}

function todayStr() { return new Date().toISOString().slice(0, 10) }

/** Date relative lisible. */
const fmtDate = d => {
  if (!d || d === '0000-00-00') return '—'
  const date = new Date(d.includes('T') ? d : d + 'T12:00:00')
  const days = Math.floor((Date.now() - date.getTime()) / 86400000)
  if (days < 0)  return format(date, 'd MMM yy', { locale: fr })
  if (days === 0) return "auj."
  if (days <= 60) return formatDistanceToNowStrict(date, { locale: fr, addSuffix: true })
  return format(date, 'd MMM yy', { locale: fr })
}

const PRESETS = [
  { label: '1j',      from: offsetDate(1),   to: todayStr() },
  { label: '7j',      from: offsetDate(7),   to: todayStr() },
  { label: '30j',     from: offsetDate(30),  to: todayStr() },
  { label: '3 mois',  from: offsetDate(90),  to: todayStr() },
  { label: '12 mois', from: offsetDate(365), to: todayStr() },
  { label: 'Tout',    from: '',              to: '' },
]

const DEFAULT_PRESET = 2

const STATUS_OPTIONS = [
  { value: '',            label: 'Tous les statuts' },
  { value: 'confirmed',   label: 'Confirmé' },
  { value: 'booked',      label: 'Réservé' },
  { value: 'approved',    label: 'Approuvé' },
  { value: 'pending',     label: 'En attente' },
  { value: 'processing',  label: 'En cours' },
  { value: 'sent',        label: 'Envoyé' },
  { value: 'cancelled',   label: 'Annulé' },
  { value: 'canceled',    label: 'Annulé (canceled)' },
  { value: 'rejected',    label: 'Rejeté' },
]

export default function Bookings() {
  const [data, setData]         = useState([])
  const [total, setTotal]       = useState(0)
  const [loading, setLoading]   = useState(true)
  const [error, setError]       = useState(null)
  const [page, setPage]         = useState(1)
  const [search, setSearch]     = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [preset, setPreset]     = useState(DEFAULT_PRESET)
  const [from, setFrom]         = useState(PRESETS[DEFAULT_PRESET].from)
  const [to, setTo]             = useState(PRESETS[DEFAULT_PRESET].to)

  // Sheet
  const [selected, setSelected] = useState(null)
  const [sheetOpen, setSheetOpen] = useState(false)

  // Sheet enrichment (avis + devis pour le client sélectionné)
  const [clientAvis, setClientAvis]   = useState([])
  const [clientForms, setClientForms] = useState([])
  const [enrichLoading, setEnrichLoading] = useState(false)

  // Stats globales (lightweight)
  const [stats, setStats] = useState(null)

  const perPage = 50

  const load = useCallback(() => {
    setLoading(true)
    setError(null)
    const params = { page, per_page: perPage }
    if (from)         params.from   = from
    if (to)           params.to     = to
    if (search)       params.search = search
    if (statusFilter) params.status = statusFilter
    api.reservations(params)
      .then(r => { setData(r.data ?? []); setTotal(r.total ?? 0) })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [page, from, to, search, statusFilter])

  useEffect(() => { load() }, [load])

  /** Calcul des stats inline à partir des données chargées. */
  useEffect(() => {
    if (!data.length && total === 0) { setStats(null); return }
    const confirmed = data.filter(r => ['confirmed','booked','approved','completed','paid','sent'].includes(r.booking_status)).length
    const cancelled = data.filter(r => ['canceled','cancelled','rejected','refunded'].includes(r.booking_status)).length
    const revenue   = data.reduce((s, r) => s + (parseFloat(r.price_total) || 0), 0)
    setStats({ total, confirmed, cancelled, pending: data.length - confirmed - cancelled, revenue })
  }, [data, total])

  function applyPreset(i) {
    setPreset(i)
    setFrom(PRESETS[i].from)
    setTo(PRESETS[i].to)
    setPage(1)
  }

  /** Ouvre le détail d'une réservation et charge le contexte client. */
  function openDetail(booking) {
    setSelected(booking)
    setSheetOpen(true)
    setClientAvis([])
    setClientForms([])

    // Enrichissement async si on a un email
    if (booking.buyer_email) {
      setEnrichLoading(true)
      Promise.allSettled([
        api.avisByEmail(booking.buyer_email),
        api.forms({ search: booking.buyer_email, per_page: 10 }),
      ]).then(([avisRes, formsRes]) => {
        setClientAvis(avisRes.status === 'fulfilled' ? (avisRes.value?.data ?? avisRes.value ?? []) : [])
        setClientForms(formsRes.status === 'fulfilled' ? (formsRes.value?.data ?? []) : [])
      }).finally(() => setEnrichLoading(false))
    }
  }

  const columns = [
    {
      key: 'buyer_name',
      label: 'Client',
      render: r => {
        const name = r.buyer_name || '—'
        return (
          <div className="flex items-center gap-2.5">
            <div
              className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
              style={avatarColor(name)}
            >
              {(name[0] ?? '?').toUpperCase()}
            </div>
            <div className="min-w-0">
              <div className="font-medium text-sm leading-tight truncate">{name}</div>
              {r.buyer_email && (
                <div className="text-[11px] text-muted-foreground truncate">{r.buyer_email}</div>
              )}
            </div>
          </div>
        )
      },
    },
    {
      key: 'product_name', label: 'Produit',
      render: r => <span className="text-sm truncate max-w-[200px] block">{r.product_name || '—'}</span>,
    },
    {
      key: 'appointment_date',
      label: 'Activité',
      render: r => (
        <span className="text-xs text-muted-foreground whitespace-nowrap">{fmtDate(r.appointment_date)}</span>
      ),
    },
    {
      key: 'price_total',
      label: 'Montant',
      render: r => (
        <span className="font-semibold text-sm tabular-nums">
          {r.price_total != null ? fmtCurrency(r.price_total) : '—'}
        </span>
      ),
    },
    {
      key: 'channel',
      label: 'Canal',
      render: r => r.channel
        ? <span className="text-xs text-muted-foreground">{r.channel}</span>
        : <span className="text-xs text-muted-foreground">—</span>,
    },
    {
      key: 'booking_status',
      label: 'Statut',
      render: r => {
        const s = STATUS_MAP[r.booking_status] ?? { variant: 'default', label: r.booking_status ?? '—' }
        return <Badge variant={s.variant}>{s.label}</Badge>
      },
    },
  ]

  const selectedStatus = selected
    ? (STATUS_MAP[selected.booking_status] ?? { variant: 'default', label: selected.booking_status ?? '—' })
    : null

  return (
    <TooltipProvider>
      <div>
        <PageHeader
          title="Réservations"
          subtitle="Historique des commandes Regiondo"
          actions={
            total > 0 && (
              <span className="text-xs text-muted-foreground">
                {total.toLocaleString('fr-FR')} réservation{total > 1 ? 's' : ''}
              </span>
            )
          }
        />

        {/* ── Stats bar ──────────────────────────────────────────── */}
        {stats && stats.total > 0 && (
          <div className="mx-6 mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
            {[
              { label: 'Total',      value: stats.total.toLocaleString('fr-FR'),     cls: '' },
              { label: 'Confirmées', value: stats.confirmed,                          cls: 'text-emerald-600' },
              { label: 'Annulées',   value: stats.cancelled,                          cls: 'text-red-500' },
              { label: 'CA (page)',  value: fmtCurrency(stats.revenue),               cls: 'text-primary' },
            ].map(s => (
              <div key={s.label} className="rounded-lg border bg-card p-4">
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider">{s.label}</p>
                <p className={`text-xl font-bold tabular-nums mt-1 ${s.cls}`}>{s.value}</p>
              </div>
            ))}
          </div>
        )}

        {/* ── Filtres ─────────────────────────────────────────────────── */}
        <div className="px-6 py-3 mt-4 flex items-center gap-3 border-y flex-wrap">
          {/* Presets période */}
          <div className="flex items-center gap-1">
            {PRESETS.map((p, i) => (
              <button
                key={i}
                onClick={() => applyPreset(i)}
                className={[
                  'px-3 py-1.5 text-xs border rounded-md transition-colors',
                  preset === i
                    ? 'bg-primary text-primary-foreground border-primary'
                    : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground',
                ].join(' ')}
              >
                {p.label}
              </button>
            ))}
          </div>

          <div className="h-4 w-px bg-border" />

          {/* Dates custom */}
          <Tooltip content="Date de début">
            <input
              type="date"
              value={from}
              onChange={e => { setFrom(e.target.value); setPreset(-1); setPage(1) }}
              className="rounded-md border border-input px-2 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            />
          </Tooltip>
          <span className="text-xs text-muted-foreground">→</span>
          <Tooltip content="Date de fin">
            <input
              type="date"
              value={to}
              onChange={e => { setTo(e.target.value); setPreset(-1); setPage(1) }}
              className="rounded-md border border-input px-2 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            />
          </Tooltip>

          <div className="h-4 w-px bg-border" />

          {/* Filtre statut */}
          <select
            value={statusFilter}
            onChange={e => { setStatusFilter(e.target.value); setPage(1) }}
            className="rounded-md border border-input px-2 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring bg-transparent"
          >
            {STATUS_OPTIONS.map(o => (
              <option key={o.value} value={o.value}>{o.label}</option>
            ))}
          </select>

          <div className="h-4 w-px bg-border" />

          {/* Recherche */}
          <div className="relative">
            <Search className="absolute left-2.5 top-2 w-3.5 h-3.5 text-muted-foreground pointer-events-none" />
            <input
              type="text"
              placeholder="Réf., client, produit…"
              value={search}
              onChange={e => { setSearch(e.target.value); setPage(1) }}
              className="rounded-md border border-input pl-7 pr-3 py-1.5 text-xs w-44 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            />
          </div>
        </div>

        {/* ── Chips filtres actifs ─────────────────────────────────── */}
        {(() => {
          const chips = [
            preset >= 0 && preset !== 5 && { label: PRESETS[preset].label, clear: () => applyPreset(5) },
            statusFilter && { label: STATUS_OPTIONS.find(o => o.value === statusFilter)?.label, clear: () => { setStatusFilter(''); setPage(1) } },
            search && { label: `"${search}"`, clear: () => { setSearch(''); setPage(1) } },
          ].filter(Boolean)
          if (!chips.length) return null
          return (
            <div className="px-6 py-2 border-b flex items-center gap-2 flex-wrap">
              <span className="text-xs text-muted-foreground shrink-0">Filtres actifs :</span>
              {chips.map((c, i) => (
                <button key={i} onClick={c.clear}
                  className="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs bg-primary text-primary-foreground rounded-full hover:opacity-75 transition-opacity"
                >
                  {c.label} <span className="opacity-70">×</span>
                </button>
              ))}
              {chips.length > 1 && (
                <button onClick={() => { applyPreset(5); setStatusFilter(''); setSearch(''); setPage(1) }}
                  className="text-xs text-muted-foreground hover:text-foreground underline underline-offset-2 ml-1"
                >
                  Tout effacer
                </button>
              )}
            </div>
          )
        })()}

        {error && <div className="px-6 pt-4"><Notice type="error">{error}</Notice></div>}

        {/* ── Table cliquable ─────────────────────────────────────────── */}
        <div className="mx-6 mt-5 rounded-lg border overflow-hidden">
          {loading ? (
            <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
          ) : (
            <>
              {data.length === 0 ? (
                <EmptyState />
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b bg-muted/40">
                        {columns.map(col => (
                          <th key={col.key} className="px-4 py-3 text-left text-[11px] text-muted-foreground uppercase tracking-wider font-medium">
                            {col.label}
                          </th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {data.map((row, i) => (
                        <tr
                          key={row.id ?? i}
                          onClick={() => openDetail(row)}
                          className="border-b transition-colors hover:bg-muted/50 cursor-pointer group"
                        >
                          {columns.map(col => (
                            <td key={col.key} className="px-4 py-3">
                              {col.render ? col.render(row) : (row[col.key] ?? '—')}
                            </td>
                          ))}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
              <Pagination page={page} total={total} perPage={perPage} onChange={setPage} />
            </>
          )}
        </div>

        <p className="px-6 mt-2 mb-8 text-[11px] text-muted-foreground">
          Cliquez sur une ligne pour voir le détail client. Filtres sur la date de commande.
        </p>
      </div>

      {/* ── Sheet détail réservation + contexte client ───────────────── */}
      <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
        {selected && (
          <SheetContent
            title={selected.order_increment_id || 'Réservation'}
            description={selected.product_name}
          >
            <div className="space-y-5">
              {/* Statut */}
              <div className="flex items-center gap-3">
                <Badge variant={selectedStatus.variant} className="text-xs px-3 py-1">
                  {selectedStatus.label}
                </Badge>
                {selected.channel && (
                  <span className="text-xs text-muted-foreground border rounded px-2 py-0.5">{selected.channel}</span>
                )}
              </div>

              {/* Infos réservation */}
              <div className="rounded-lg border bg-muted/30 p-4 space-y-3">
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">Réservation</p>
                <div className="grid grid-cols-2 gap-x-6 gap-y-3">
                  <SheetRow label="Référence">
                    <code className="text-xs">{selected.order_increment_id || '—'}</code>
                  </SheetRow>
                  <SheetRow label="Date d'activité">
                    {selected.appointment_date?.slice(0, 10) || '—'}
                  </SheetRow>
                  <SheetRow label="Produit">
                    <span className="font-medium">{selected.product_name || '—'}</span>
                  </SheetRow>
                  <SheetRow label="Montant">
                    <span className="font-semibold text-base">
                      {selected.price_total != null ? fmtCurrency(selected.price_total) : '—'}
                    </span>
                  </SheetRow>
                  {selected.quantity && (
                    <SheetRow label="Quantité">{selected.quantity}</SheetRow>
                  )}
                  {selected.created_at && (
                    <SheetRow label="Date commande">{fmtDate(selected.created_at)}</SheetRow>
                  )}
                  {selected.payment_method && (
                    <SheetRow label="Paiement">{selected.payment_method}</SheetRow>
                  )}
                  {selected.payment_status && (
                    <SheetRow label="Statut paiement">
                      <Badge variant={selected.payment_status === 'paid' ? 'confirmed' : 'pending'}>
                        {selected.payment_status}
                      </Badge>
                    </SheetRow>
                  )}
                </div>
              </div>

              {/* Infos client */}
              <div className="rounded-lg border bg-muted/30 p-4 space-y-3">
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">Client</p>
                <div className="flex items-center gap-3 mb-2">
                  <div
                    className="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                    style={avatarColor(selected.buyer_name)}
                  >
                    {(selected.buyer_name?.[0] ?? '?').toUpperCase()}
                  </div>
                  <div>
                    <div className="font-medium">{selected.buyer_name || '—'}</div>
                    {selected.buyer_country && (
                      <span className="text-xs text-muted-foreground">{selected.buyer_country}</span>
                    )}
                  </div>
                </div>
                <div className="grid grid-cols-1 gap-y-2">
                  {selected.buyer_email && (
                    <div className="flex items-center gap-2 text-sm">
                      <Mail width={14} height={14} className="text-muted-foreground shrink-0" />
                      <a href={`mailto:${selected.buyer_email}`} className="text-primary underline text-xs break-all">
                        {selected.buyer_email}
                      </a>
                    </div>
                  )}
                </div>
              </div>

              {/* Contexte client — Avis & Devis */}
              {enrichLoading ? (
                <div className="flex items-center justify-center py-4">
                  <Spinner size={16} />
                  <span className="text-xs text-muted-foreground ml-2">Chargement du contexte client…</span>
                </div>
              ) : (
                <>
                  {/* Avis client */}
                  {clientAvis.length > 0 && (
                    <div className="rounded-lg border bg-muted/30 p-4 space-y-2">
                      <div className="flex items-center gap-2">
                        <Star width={14} height={14} className="text-amber-500" />
                        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">
                          Avis client ({clientAvis.length})
                        </p>
                      </div>
                      <div className="space-y-2 max-h-48 overflow-y-auto">
                        {clientAvis.map((avis, i) => (
                          <div key={i} className="rounded border bg-card p-3 text-xs space-y-1">
                            <div className="flex items-center gap-2">
                              <span className="font-medium">{avis.rating ? '★'.repeat(avis.rating) : '—'}</span>
                              <span className="text-muted-foreground">{avis.product_name || avis.review_title || ''}</span>
                            </div>
                            {avis.review_body && (
                              <p className="text-muted-foreground line-clamp-2">{avis.review_body}</p>
                            )}
                            {avis.review_date && (
                              <p className="text-[10px] text-muted-foreground/60">{fmtDate(avis.review_date)}</p>
                            )}
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Demandes de devis */}
                  {clientForms.length > 0 && (
                    <div className="rounded-lg border bg-muted/30 p-4 space-y-2">
                      <div className="flex items-center gap-2">
                        <Calendar width={14} height={14} className="text-blue-500" />
                        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">
                          Demandes de devis ({clientForms.length})
                        </p>
                      </div>
                      <div className="space-y-2 max-h-48 overflow-y-auto">
                        {clientForms.map((form, i) => (
                          <div key={i} className="rounded border bg-card p-3 text-xs space-y-1">
                            <div className="flex items-center justify-between">
                              <span className="font-medium">{form.excursion_name || form.form_type || 'Devis'}</span>
                              <Badge variant={form.email_sent ? 'ok' : 'error'} className="text-[10px]">
                                {form.email_sent ? 'Email envoyé' : 'Email échoué'}
                              </Badge>
                            </div>
                            {form.boat_name && (
                              <p className="text-muted-foreground">{form.boat_name}</p>
                            )}
                            <p className="text-[10px] text-muted-foreground/60">{fmtDate(form.created_at)}</p>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Aucun contexte */}
                  {clientAvis.length === 0 && clientForms.length === 0 && selected.buyer_email && (
                    <div className="rounded-lg border border-dashed p-4 text-center">
                      <p className="text-xs text-muted-foreground">Aucun avis ni demande de devis pour ce client.</p>
                    </div>
                  )}
                </>
              )}
            </div>
          </SheetContent>
        )}
      </Sheet>
    </TooltipProvider>
  )
}

/**
 * Empty state — invite à créer un devis via le widget front-end.
 */
function EmptyState() {
  return (
    <div className="px-6 py-16 text-center space-y-4">
      <div className="mx-auto w-12 h-12 rounded-full bg-muted flex items-center justify-center">
        <Calendar width={20} height={20} className="text-muted-foreground" />
      </div>
      <div>
        <p className="text-sm font-medium">Aucune réservation sur cette période</p>
        <p className="text-xs text-muted-foreground mt-1">
          Les réservations Regiondo apparaissent ici après synchronisation.
        </p>
      </div>
      <div className="flex items-center justify-center gap-3 pt-2">
        <a
          href="#/forms"
          className="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90 transition-opacity"
        >
          <Mail width={14} height={14} />
          Voir les demandes de devis
          <NavArrowRight width={12} height={12} />
        </a>
      </div>
      <p className="text-[11px] text-muted-foreground max-w-sm mx-auto">
        Les clients peuvent demander un devis via le widget <strong>BT — Tarifs</strong> sur votre site.
        Ces demandes sont disponibles dans l'onglet Formulaires.
      </p>
    </div>
  )
}
