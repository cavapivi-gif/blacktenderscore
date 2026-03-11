import { useState, useEffect, useCallback } from 'react'
import { api } from '../lib/api'
import { PageHeader, Table, Btn, Pagination, Notice, Spinner, Badge } from '../components/ui'

const STATUS_MAP = {
  confirmed: { variant: 'confirmed', label: 'Confirmé' },
  cancelled: { variant: 'cancelled', label: 'Annulé' },
  pending:   { variant: 'pending',   label: 'En attente' },
}

function offsetDate(days) {
  const d = new Date()
  d.setDate(d.getDate() - days)
  return d.toISOString().slice(0, 10)
}
function today() { return new Date().toISOString().slice(0, 10) }

const PRESETS = [
  { label: '1j',     from: offsetDate(1),   to: today() },
  { label: '7j',     from: offsetDate(7),   to: today() },
  { label: '30j',    from: offsetDate(30),  to: today() },
  { label: '3 mois', from: offsetDate(90),  to: today() },
  { label: '12 mois',from: offsetDate(365), to: today() },
  { label: 'Tout',   from: '',              to: '' },
]

const DEFAULT_PRESET = 2  // 30j

export default function Bookings() {
  const [data, setData]         = useState([])
  const [total, setTotal]       = useState(0)
  const [loading, setLoading]   = useState(true)
  const [error, setError]       = useState(null)
  const [page, setPage]         = useState(1)
  const [search, setSearch]     = useState('')
  const [preset, setPreset]     = useState(DEFAULT_PRESET)
  const [from, setFrom]         = useState(PRESETS[DEFAULT_PRESET].from)
  const [to, setTo]             = useState(PRESETS[DEFAULT_PRESET].to)

  const perPage = 50

  const load = useCallback(() => {
    setLoading(true)
    const params = { page, per_page: perPage }
    if (from)   params.from = from
    if (to)     params.to   = to
    if (search) params.order_number = search
    api.bookings(params)
      .then(r => { setData(r.data ?? []); setTotal(r.total ?? 0) })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [page, from, to, search])

  useEffect(() => { load() }, [load])

  function applyPreset(i) {
    setPreset(i)
    setFrom(PRESETS[i].from)
    setTo(PRESETS[i].to)
    setPage(1)
  }

  const columns = [
    { key: 'booking_ref',   label: 'Référence' },
    { key: 'product_name',  label: 'Produit' },
    { key: 'booking_date',  label: 'Date', render: r => r.booking_date?.slice(0, 10) ?? '—' },
    { key: 'customer_name', label: 'Client' },
    {
      key: 'total_price',
      label: 'Montant',
      render: r => r.total_price != null ? `${r.total_price} ${r.currency_code ?? 'EUR'}` : '—',
    },
    {
      key: 'status',
      label: 'Statut',
      render: r => {
        const s = STATUS_MAP[r.status] ?? { variant: 'default', label: r.status ?? '—' }
        return <Badge variant={s.variant}>{s.label}</Badge>
      },
    },
  ]

  return (
    <div>
      <PageHeader
        title="Réservations"
        subtitle="Historique des réservations Regiondo"
        actions={total > 0 && <span className="text-xs text-muted-foreground">{total} réservation{total > 1 ? 's' : ''}</span>}
      />

      {/* Filtres */}
      <div className="px-6 py-4 flex items-center gap-4 border-b">
        {/* Presets */}
        <div className="flex items-center gap-1">
          {PRESETS.map((p, i) => (
            <button
              key={i}
              onClick={() => applyPreset(i)}
              className={`px-3 py-1.5 text-xs border rounded-md transition-colors ${
                preset === i
                  ? 'bg-primary text-primary-foreground border-primary'
                  : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground'
              }`}
            >
              {p.label}
            </button>
          ))}
        </div>

        <div className="h-4 w-px bg-border" />

        {/* Custom dates */}
        <input
          type="date"
          value={from}
          onChange={e => { setFrom(e.target.value); setPreset(-1); setPage(1) }}
          className="rounded-md border border-input px-2 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        />
        <span className="text-xs text-muted-foreground">→</span>
        <input
          type="date"
          value={to}
          onChange={e => { setTo(e.target.value); setPreset(-1); setPage(1) }}
          className="rounded-md border border-input px-2 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        />

        <div className="h-4 w-px bg-border" />

        {/* Search */}
        <input
          type="text"
          placeholder="N° commande…"
          value={search}
          onChange={e => { setSearch(e.target.value); setPage(1) }}
          className="rounded-md border border-input px-3 py-1.5 text-xs w-36 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        />
      </div>

      {error && <div className="px-6 pt-4"><Notice type="error">{error}</Notice></div>}

      <div className="mx-6 mt-5 rounded-lg border overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
        ) : (
          <>
            <Table columns={columns} data={data} empty="Aucune réservation trouvée." />
            <Pagination page={page} total={total} perPage={perPage} onChange={setPage} />
          </>
        )}
      </div>
    </div>
  )
}
