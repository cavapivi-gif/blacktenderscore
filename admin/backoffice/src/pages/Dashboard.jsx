import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { api } from '../lib/api'
import { PageHeader, StatCard, Table, Btn, Notice, Spinner } from '../components/ui'
import { RefreshDouble, Book, Box, Group } from 'iconoir-react'

export default function Dashboard() {
  const navigate = useNavigate()
  const [data, setData]       = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState(null)
  const [syncing, setSyncing] = useState(false)
  const [flushing, setFlushing] = useState(false)
  const [syncResult, setSyncResult] = useState(null)

  useEffect(() => {
    api.dashboard()
      .then(setData)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  async function handleSync() {
    setSyncing(true)
    setSyncResult(null)
    try {
      const res = await api.sync()
      setSyncResult(res)
    } catch (e) {
      setError(e.message)
    } finally {
      setSyncing(false)
    }
  }

  async function handleFlush() {
    setFlushing(true)
    try {
      await api.flushCache()
    } finally {
      setFlushing(false)
    }
  }

  const bookingCols = [
    { key: 'booking_ref',   label: 'Référence' },
    { key: 'product_name',  label: 'Produit' },
    { key: 'booking_date',  label: 'Date', render: r => r.booking_date?.slice(0, 10) ?? '—' },
    { key: 'customer_name', label: 'Client' },
    {
      key: 'total_price',
      label: 'Montant',
      render: r => r.total_price ? `${r.total_price} ${r.currency_code ?? 'EUR'}` : '—',
    },
    {
      key: 'status',
      label: 'Statut',
      render: r => <span className="text-xs text-gray-500">{r.status ?? '—'}</span>,
    },
  ]

  return (
    <div>
      <PageHeader
        title="Tableau de bord"
        actions={
          <>
            <Btn variant="secondary" size="sm" loading={flushing} onClick={handleFlush}>
              Vider le cache
            </Btn>
            <Btn size="sm" loading={syncing} onClick={handleSync}>
              <RefreshDouble width={13} height={13} />
              Synchroniser
            </Btn>
          </>
        }
      />

      {error && (
        <div className="px-8 pt-6">
          <Notice type="error">{error}</Notice>
        </div>
      )}

      {syncResult && (
        <div className="px-8 pt-6">
          <Notice type="success">
            Sync terminée — {syncResult.created} créés, {syncResult.updated} mis à jour, {syncResult.errors} erreurs
          </Notice>
        </div>
      )}

      {loading ? (
        <div className="flex items-center justify-center py-20">
          <Spinner size={20} />
        </div>
      ) : (
        <>
          {/* Stats */}
          <div className="grid grid-cols-3 gap-px bg-gray-200 mx-8 mt-8 border border-gray-200">
            <StatCard
              label="Produits"
              value={data?.products_count ?? 0}
              sub={<button onClick={() => navigate('/products')} className="text-xs underline">Voir tout</button>}
            />
            <StatCard
              label="Réservations ce mois"
              value={data?.bookings_month ?? 0}
              sub={<button onClick={() => navigate('/bookings')} className="text-xs underline">Voir tout</button>}
            />
            <StatCard
              label="Clients CRM"
              value={data?.customers_total ?? 0}
              sub={<button onClick={() => navigate('/customers')} className="text-xs underline">Voir tout</button>}
            />
          </div>

          {/* Recent bookings */}
          <div className="mx-8 mt-8">
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs text-gray-400 uppercase tracking-widest">Réservations récentes</span>
              <button onClick={() => navigate('/bookings')} className="text-xs text-gray-400 underline hover:text-black">
                Tout voir
              </button>
            </div>
            <div className="border border-gray-200">
              <Table
                columns={bookingCols}
                data={data?.recent_bookings ?? []}
                empty="Aucune réservation ce mois."
              />
            </div>
          </div>
        </>
      )}
    </div>
  )
}
