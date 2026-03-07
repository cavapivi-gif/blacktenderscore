import { useState, useEffect, useCallback } from 'react'
import { api } from '../lib/api'
import { PageHeader, Table, Input, Btn, Pagination, Notice, Spinner } from '../components/ui'

export default function Customers() {
  const [data, setData]       = useState([])
  const [total, setTotal]     = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState(null)
  const [page, setPage]       = useState(1)
  const [toggling, setToggling] = useState(null)

  const perPage = 50

  const load = useCallback(() => {
    setLoading(true)
    api.customers({ page, per_page: perPage })
      .then(r => { setData(r.data ?? []); setTotal(r.total ?? 0) })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [page])

  useEffect(() => { load() }, [load])

  async function toggleNewsletter(customer) {
    const newVal = !customer.newsletter
    setToggling(customer.email)
    try {
      await api.newsletter(customer.email, newVal)
      setData(prev => prev.map(c =>
        c.email === customer.email ? { ...c, newsletter: newVal } : c
      ))
    } catch (e) {
      setError(e.message)
    } finally {
      setToggling(null)
    }
  }

  const columns = [
    {
      key: 'name',
      label: 'Nom',
      render: r => `${r.first_name ?? ''} ${r.last_name ?? ''}`.trim() || '—',
    },
    { key: 'email', label: 'Email' },
    {
      key: 'newsletter',
      label: 'Newsletter',
      render: r => (
        <button
          onClick={() => toggleNewsletter(r)}
          disabled={toggling === r.email}
          className={`text-xs px-2 py-1 border transition-colors ${
            r.newsletter
              ? 'border-black bg-black text-white hover:bg-gray-800'
              : 'border-gray-200 text-gray-400 hover:border-black hover:text-black'
          }`}
        >
          {toggling === r.email ? '…' : r.newsletter ? 'Abonné' : 'Non abonné'}
        </button>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Clients"
        actions={
          <span className="text-xs text-gray-400">{total} client{total > 1 ? 's' : ''}</span>
        }
      />

      {error && (
        <div className="px-8 pt-5">
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
            <Table columns={columns} data={data} empty="Aucun client." />
            <Pagination page={page} total={total} perPage={perPage} onChange={setPage} />
          </>
        )}
      </div>
    </div>
  )
}
