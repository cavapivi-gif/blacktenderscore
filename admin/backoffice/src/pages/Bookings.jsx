import { useState, useEffect, useCallback } from 'react'
import { api } from '../lib/api'
import { PageHeader, Table, Btn, Input, Pagination, Notice, Spinner } from '../components/ui'

const STATUS_LABELS = {
  confirmed: 'Confirmé',
  cancelled: 'Annulé',
  pending:   'En attente',
}

const PRESETS = [
  { label: 'Tout', from: '', to: '' },
  { label: 'Ce mois', from: getDate('month-start'), to: getDate('today') },
  { label: 'Cette année', from: getDate('year-start'), to: getDate('today') },
]

function getDate(type) {
  const d = new Date()
  if (type === 'today')       return d.toISOString().slice(0, 10)
  if (type === 'month-start') return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`
  if (type === 'year-start')  return `${d.getFullYear()}-01-01`
  return ''
}

export default function Bookings() {
  const [data, setData]         = useState([])
  const [total, setTotal]       = useState(0)
  const [loading, setLoading]   = useState(true)
  const [error, setError]       = useState(null)
  const [page, setPage]         = useState(1)
  const [search, setSearch]     = useState('')
  const [preset, setPreset]     = useState(0)          // index in PRESETS
  const [from, setFrom]         = useState('')
  const [to, setTo]             = useState('')

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
      render: r => (
        <span className={`text-xs ${r.status === 'confirmed' ? 'text-gray-700' : 'text-gray-400'}`}>
          {STATUS_LABELS[r.status] ?? r.status ?? '—'}
        </span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Réservations"
        actions={total > 0 && <span className="text-xs text-gray-400">{total} réservation{total > 1 ? 's' : ''}</span>}
      />

      {/* Filtres */}
      <div className="px-8 py-5 flex items-center gap-4 border-b border-gray-100">
        {/* Presets */}
        <div className="flex items-center gap-1">
          {PRESETS.map((p, i) => (
            <button
              key={i}
              onClick={() => applyPreset(i)}
              className={`px-3 py-1.5 text-xs border transition-colors ${
                preset === i ? 'bg-black text-white border-black' : 'border-gray-200 text-gray-500 hover:border-black hover:text-black'
              }`}
            >
              {p.label}
            </button>
          ))}
        </div>

        <div className="h-4 w-px bg-gray-200" />

        {/* Custom dates */}
        <input
          type="date"
          value={from}
          onChange={e => { setFrom(e.target.value); setPreset(-1); setPage(1) }}
          className="border border-gray-200 px-2 py-1.5 text-xs outline-none focus:border-black"
        />
        <span className="text-xs text-gray-300">→</span>
        <input
          type="date"
          value={to}
          onChange={e => { setTo(e.target.value); setPreset(-1); setPage(1) }}
          className="border border-gray-200 px-2 py-1.5 text-xs outline-none focus:border-black"
        />

        <div className="h-4 w-px bg-gray-200" />

        {/* Search */}
        <input
          type="text"
          placeholder="N° commande…"
          value={search}
          onChange={e => { setSearch(e.target.value); setPage(1) }}
          className="border border-gray-200 px-3 py-1.5 text-xs w-36 outline-none focus:border-black"
        />
      </div>

      {error && <div className="px-8 pt-4"><Notice type="error">{error}</Notice></div>}

      <div className="mx-8 mt-6 border border-gray-200">
        {loading ? (
          <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
        ) : (
          <>
            <Table columns={columns} data={data} empty="Aucune réservation." />
            <Pagination page={page} total={total} perPage={perPage} onChange={setPage} />
          </>
        )}
      </div>
    </div>
  )
}
