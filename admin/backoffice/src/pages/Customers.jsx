import { useState, useEffect, useCallback } from 'react'
import { api } from '../lib/api'
import { PageHeader, Table, Pagination, Notice, Spinner, Badge } from '../components/ui'

export default function Customers() {
  const [data, setData]       = useState([])
  const [total, setTotal]     = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState(null)
  const [page, setPage]       = useState(1)

  const perPage = 50

  const load = useCallback(() => {
    setLoading(true)
    api.customers({ page, per_page: perPage })
      .then(r => { setData(r.data ?? []); setTotal(r.total ?? 0) })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [page])

  useEffect(() => { load() }, [load])

  const columns = [
    {
      key: 'name',
      label: 'Nom',
      render: r => {
        const name = `${r.first_name ?? ''} ${r.last_name ?? ''}`.trim() || '—'
        return (
          <div className="flex items-center gap-2">
            <div className="w-7 h-7 rounded-full bg-secondary flex items-center justify-center text-xs text-muted-foreground font-medium">
              {name[0]?.toUpperCase() ?? '?'}
            </div>
            <span>{name}</span>
          </div>
        )
      },
    },
    { key: 'email', label: 'Email' },
    {
      key: 'newsletter',
      label: 'Newsletter',
      render: r => (
        <Badge variant={r.newsletter ? 'ok' : 'default'}>
          {r.newsletter ? 'Abonné' : 'Non abonné'}
        </Badge>
      ),
    },
    {
      key: 'avis_count',
      label: 'Avis',
      render: r => r.avis_count > 0
        ? <Badge variant="ok">{r.avis_count} avis</Badge>
        : <span className="text-xs text-muted-foreground">—</span>,
    },
  ]

  return (
    <div>
      <PageHeader
        title="Clients"
        subtitle="Données CRM Regiondo"
        actions={
          <span className="text-xs text-muted-foreground">{total} client{total > 1 ? 's' : ''}</span>
        }
      />

      {error && <div className="px-6 pt-5"><Notice type="error">{error}</Notice></div>}

      <div className="mx-6 mt-5 rounded-lg border overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
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
