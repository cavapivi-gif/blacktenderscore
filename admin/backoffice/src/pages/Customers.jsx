import { useState, useEffect, useCallback, useMemo } from 'react'
import { Search, Download } from 'iconoir-react'
import { format, formatDistanceToNowStrict } from 'date-fns'
import { fr } from 'date-fns/locale'
import { api } from '../lib/api'
import { avatarColor } from '../lib/colors'
import { PageHeader, Table, Pagination, Notice, Spinner, Badge, Btn } from '../components/ui'
import CustomerDrawer from '../components/CustomerDrawer'

const SEGMENTS = [
  { label: 'VIP',      test: c => c.total_spent >= 500,   variant: 'active'  },
  { label: 'Fidèle',   test: c => c.bookings_count >= 5,  variant: 'ok'      },
  { label: 'Régulier', test: c => c.bookings_count >= 2,  variant: 'warn'    },
  { label: 'Nouveau',  test: () => true,                   variant: 'default' },
]
const getSegment = c => SEGMENTS.find(s => s.test(c))
const fmtDate = d => {
  if (!d || d === '0000-00-00') return '—'
  const date = new Date(d + 'T12:00:00')
  const days = Math.floor((Date.now() - date.getTime()) / 86400000)
  if (days < 0)  return format(date, 'd MMM yy', { locale: fr })
  if (days === 0) return "auj."
  if (days <= 60) return formatDistanceToNowStrict(date, { locale: fr, addSuffix: true })
  return format(date, 'd MMM yy', { locale: fr })
}
const fmtEur  = n => n > 0 ? `${n.toFixed(2)} €` : '—'

function exportCsv(rows) {
  const hdr = ['Nom', 'Email', 'Réservations', 'CA (€)', 'Dernière activité', 'Newsletter']
  const csv = [hdr, ...rows.map(r => [
    r.name, r.email, r.bookings_count, r.total_spent, r.last_booking, r.newsletter ? 'Oui' : 'Non',
  ])].map(r => r.join(';')).join('\n')
  Object.assign(document.createElement('a'), {
    href: URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' })),
    download: 'clients.csv',
  }).click()
}

export default function Customers() {
  const [data, setData]           = useState([])
  const [total, setTotal]         = useState(0)
  const [loading, setLoading]     = useState(true)
  const [error, setError]         = useState(null)
  const [page, setPage]           = useState(1)
  const [q, setQ]                 = useState('')
  const [search, setSearch]       = useState('')
  const [selected, setSelected]   = useState(null)
  const [sort, setSort]           = useState({ key: null, dir: 'asc' })
  const [segFilter, setSegFilter] = useState('')
  const perPage = 50

  const load = useCallback(() => {
    setLoading(true)
    api.customers({
      page, per_page: perPage,
      search: search || undefined,
      sort_key: sort.key || 'last_booking',
      sort_dir: sort.dir,
    })
      .then(r => { setData(r.data ?? []); setTotal(r.total ?? 0) })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [page, search, sort])

  useEffect(() => { load() }, [load])
  useEffect(() => {
    const t = setTimeout(() => { setSearch(q); setPage(1) }, 400)
    return () => clearTimeout(t)
  }, [q])

  const onSort = key => {
    setSort(s => ({ key, dir: s.key === key && s.dir === 'asc' ? 'desc' : 'asc' }))
    setPage(1)
  }

  // Filtre segment côté client (pas de sort — géré côté SQL)
  const sorted = useMemo(
    () => segFilter ? data.filter(r => getSegment(r).label === segFilter) : data,
    [data, segFilter]
  )

  const columns = [
    {
      key: 'name', label: 'Client', sortable: true,
      render: r => (
        <div className="flex items-center gap-2.5">
          <div
            className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
            style={avatarColor(r.name)}
          >
            {(r.name?.[0] ?? '?').toUpperCase()}
          </div>
          <div>
            <div className="font-medium text-sm leading-tight">{r.name || '—'}</div>
            <div className="text-xs text-muted-foreground">{r.email}</div>
          </div>
        </div>
      ),
    },
    { key: 'bookings_count', label: 'Résa',             sortable: true, render: r => <span className="font-medium tabular-nums">{r.bookings_count}</span> },
    { key: 'total_spent',    label: 'CA Total',          sortable: true, render: r => <span className="font-medium tabular-nums">{fmtEur(r.total_spent)}</span> },
    { key: 'last_booking',   label: 'Dernière activité', sortable: true, render: r => <span className="text-xs text-muted-foreground">{fmtDate(r.last_booking)}</span> },
    { key: 'segment',    label: 'Segment',    render: r => { const s = getSegment(r); return <Badge variant={s.variant}>{s.label}</Badge> } },
    { key: 'newsletter', label: 'Newsletter', render: r => <Badge variant={r.newsletter ? 'ok' : 'default'}>{r.newsletter ? 'Abonné' : 'Non abonné'}</Badge> },
    { key: 'avis_count', label: 'Avis', sortable: true, render: r => r.avis_count > 0 ? <Badge variant="ok">{r.avis_count}</Badge> : <span className="text-xs text-muted-foreground">—</span> },
  ]

  return (
    <div>
      <PageHeader
        title="Clients"
        subtitle="Données CRM Regiondo"
        actions={
          <div className="flex items-center gap-2 flex-wrap">
            <div className="relative">
              <Search className="absolute left-2.5 top-2.5 w-4 h-4 text-muted-foreground pointer-events-none" />
              <input
                className="h-9 pl-8 pr-3 text-sm rounded-md border border-input bg-transparent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring w-44"
                placeholder="Nom ou email…"
                value={q}
                onChange={e => setQ(e.target.value)}
              />
            </div>
            <select
              className="h-9 px-3 text-sm rounded-md border border-input bg-background cursor-pointer focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              value={segFilter}
              onChange={e => { setSegFilter(e.target.value); setPage(1) }}
            >
              <option value="">Tous les segments</option>
              {SEGMENTS.map(s => <option key={s.label} value={s.label}>{s.label}</option>)}
            </select>
            <Btn variant="secondary" size="sm" onClick={() => exportCsv(sorted)} disabled={!sorted.length}>
              <Download className="w-4 h-4" /> Export
            </Btn>
            <span className="text-xs text-muted-foreground">{total} client{total !== 1 ? 's' : ''}</span>
          </div>
        }
      />

      {error && <div className="px-6 pt-5"><Notice type="error">{error}</Notice></div>}

      <div className="mx-6 mt-5 rounded-lg border overflow-hidden">
        {loading
          ? <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
          : <>
              <Table
                columns={columns} data={sorted} empty="Aucun client trouvé."
                onRowClick={setSelected} sortKey={sort.key} sortDir={sort.dir} onSort={onSort}
              />
              <Pagination page={page} total={total} perPage={perPage} onChange={setPage} />
            </>
        }
      </div>

      {selected && <CustomerDrawer customer={selected} onClose={() => setSelected(null)} onUpdate={load} />}
    </div>
  )
}
