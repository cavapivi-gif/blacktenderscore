import { useState, useEffect, useCallback } from 'react'
import { api } from '../lib/api'
import { PageHeader, Table, Btn, Input, Select, Pagination, Notice, Spinner } from '../components/ui'

const STATUS_LABELS = {
  confirmed:  'Confirmé',
  cancelled:  'Annulé',
  pending:    'En attente',
}

export default function Bookings() {
  const [data, setData]       = useState([])
  const [total, setTotal]     = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState(null)
  const [page, setPage]       = useState(1)
  const [filters, setFilters] = useState({ from: '', to: '', order_number: '' })

  const perPage = 30

  const load = useCallback(() => {
    setLoading(true)
    api.bookings({ page, per_page: perPage, ...filters })
      .then(r => { setData(r.data ?? []); setTotal(r.total ?? 0) })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [page, filters])

  useEffect(() => { load() }, [load])

  function setFilter(key, value) {
    setFilters(prev => ({ ...prev, [key]: value }))
    setPage(1)
  }

  const columns = [
    { key: 'booking_ref',  label: 'Référence' },
    { key: 'product_name', label: 'Produit' },
    {
      key: 'booking_date',
      label: 'Date',
      render: r => r.booking_date?.slice(0, 10) ?? '—',
    },
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
      <PageHeader title="Réservations" />

      {/* Filtres */}
      <div className="px-8 py-5 flex items-end gap-3 border-b border-gray-100">
        <div className="w-40">
          <Input
            label="Du"
            type="date"
            value={filters.from}
            onChange={e => setFilter('from', e.target.value)}
          />
        </div>
        <div className="w-40">
          <Input
            label="Au"
            type="date"
            value={filters.to}
            onChange={e => setFilter('to', e.target.value)}
          />
        </div>
        <div className="w-52">
          <Input
            label="N° commande"
            placeholder="REF-…"
            value={filters.order_number}
            onChange={e => setFilter('order_number', e.target.value)}
          />
        </div>
        <Btn variant="secondary" size="md" onClick={() => { setFilters({ from: '', to: '', order_number: '' }); setPage(1) }}>
          Réinitialiser
        </Btn>
      </div>

      {error && (
        <div className="px-8 pt-4">
          <Notice type="error">{error}</Notice>
        </div>
      )}

      <div className="mx-8 mt-6 border border-gray-200">
        {loading ? (
          <div className="flex items-center justify-center py-20">
            <Spinner size={20} />
          </div>
        ) : (
          <>
            <Table columns={columns} data={data} empty="Aucune réservation pour ces filtres." />
            <Pagination page={page} total={total} perPage={perPage} onChange={setPage} />
          </>
        )}
      </div>
    </div>
  )
}
