import { useState, useEffect, useCallback } from 'react'
import { api } from '../lib/api'
import { STATUS_MAP } from '../lib/status'
import { Sheet, SheetContent, SheetRow } from '../components/Sheet'
import { TooltipProvider, Tooltip } from '../components/Tooltip'
import { PageHeader, Table, Btn, Pagination, Notice, Spinner, Badge } from '../components/ui'

function offsetDate(days) {
  const d = new Date()
  d.setDate(d.getDate() - days)
  return d.toISOString().slice(0, 10)
}

function today() { return new Date().toISOString().slice(0, 10) }

const PRESETS = [
  { label: '1j',      from: offsetDate(1),   to: today() },
  { label: '7j',      from: offsetDate(7),   to: today() },
  { label: '30j',     from: offsetDate(30),  to: today() },
  { label: '3 mois',  from: offsetDate(90),  to: today() },
  { label: '12 mois', from: offsetDate(365), to: today() },
  { label: 'Tout',    from: '',              to: '' },
]

const DEFAULT_PRESET = 2

// Statuts disponibles pour le filtre dropdown
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

  // Sheet état
  const [selected, setSelected] = useState(null)
  const [sheetOpen, setSheetOpen] = useState(false)

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

  function applyPreset(i) {
    setPreset(i)
    setFrom(PRESETS[i].from)
    setTo(PRESETS[i].to)
    setPage(1)
  }

  function openDetail(booking) {
    setSelected(booking)
    setSheetOpen(true)
  }

  // bt_reservations field names (appointment_date, price_total, booking_status, buyer_name/email)
  const columns = [
    {
      key: 'order_increment_id',
      label: 'Référence',
      render: r => r.order_increment_id || '—',
    },
    { key: 'product_name', label: 'Produit' },
    {
      key: 'appointment_date',
      label: 'Date activité',
      render: r => r.appointment_date?.slice(0, 10) ?? '—',
    },
    {
      key: 'buyer_name',
      label: 'Client',
      render: r => r.buyer_name || '—',
    },
    {
      key: 'price_total',
      label: 'Montant',
      render: r => r.price_total != null
        ? `${Number(r.price_total).toLocaleString('fr-FR')} EUR`
        : '—',
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
          subtitle="Solditems importés — DB locale (bt_reservations)"
          actions={
            total > 0 && (
              <span className="text-xs text-muted-foreground">
                {total.toLocaleString('fr-FR')} réservation{total > 1 ? 's' : ''}
              </span>
            )
          }
        />

        {/* ── Filtres ─────────────────────────────────────────────────── */}
        <div className="px-6 py-3 flex items-center gap-3 border-b flex-wrap">
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
          <input
            type="text"
            placeholder="Réf., client, produit…"
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className="rounded-md border border-input px-3 py-1.5 text-xs w-40 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
          />
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
                <div className="px-6 py-12 text-center text-sm text-muted-foreground">
                  Aucune réservation trouvée.
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b">
                        {columns.map(col => (
                          <th key={col.key} className="px-4 py-3 text-left text-xs text-muted-foreground uppercase tracking-wider font-normal">
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
                          className="border-b transition-colors hover:bg-muted/50 cursor-pointer"
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

        <p className="px-6 mt-2 text-[11px] text-muted-foreground">
          Cliquez sur une ligne pour voir le détail. Filtres sur la date d'activité.
        </p>
      </div>

      {/* ── Sheet détail réservation ─────────────────────────────────────── */}
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
                      {selected.price_total != null
                        ? `${Number(selected.price_total).toLocaleString('fr-FR')} EUR`
                        : '—'}
                    </span>
                  </SheetRow>
                  {selected.created_at && (
                    <SheetRow label="Date commande">
                      {selected.created_at?.slice(0, 10)}
                    </SheetRow>
                  )}
                  {selected.channel && (
                    <SheetRow label="Canal">{selected.channel}</SheetRow>
                  )}
                </div>
              </div>

              {/* Infos client */}
              <div className="rounded-lg border bg-muted/30 p-4 space-y-3">
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">Client</p>
                <div className="grid grid-cols-2 gap-x-6 gap-y-3">
                  <SheetRow label="Nom">{selected.buyer_name || '—'}</SheetRow>
                  {selected.buyer_email && (
                    <SheetRow label="Email">
                      <a
                        href={`mailto:${selected.buyer_email}`}
                        className="text-primary underline text-xs break-all"
                      >
                        {selected.buyer_email}
                      </a>
                    </SheetRow>
                  )}
                </div>
              </div>
            </div>
          </SheetContent>
        )}
      </Sheet>
    </TooltipProvider>
  )
}
