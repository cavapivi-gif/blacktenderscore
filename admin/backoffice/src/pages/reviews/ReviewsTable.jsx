import { Table, Pagination, Spinner } from '../../components/ui'
import { Stars } from './components'
import { fmtDate } from './helpers'

/**
 * Reviews data table with pagination.
 */
export default function ReviewsTable({ data, loading, total, page, perPage, sort, onSort, setPage, expanded, setExpanded }) {
  const columns = [
    {
      key: 'customer_name', label: 'Client', sortable: true,
      render: r => (
        <div>
          <div className="font-medium text-sm leading-tight">{r.customer_name || '—'}</div>
          <div className="text-xs text-muted-foreground">{r.customer_email}</div>
        </div>
      ),
    },
    {
      key: 'product_name', label: 'Produit', sortable: true,
      render: r => (
        <span className="text-xs text-muted-foreground max-w-[160px] truncate block" title={r.product_name}>
          {r.product_name || '—'}
        </span>
      ),
    },
    {
      key: 'rating', label: 'Note', sortable: true,
      render: r => <Stars value={r.rating} />,
    },
    {
      key: 'review_title', label: 'Avis',
      render: r => (
        <div className="max-w-[260px]">
          {r.review_title && <p className="text-sm font-medium truncate">{r.review_title}</p>}
          {r.review_body && (
            <p
              className={`text-xs text-muted-foreground cursor-pointer ${expanded === r.id ? '' : 'line-clamp-2'}`}
              onClick={e => { e.stopPropagation(); setExpanded(expanded === r.id ? null : r.id) }}
            >
              {r.review_body}
            </p>
          )}
          {r.response && (
            <p className="text-xs text-primary/70 mt-0.5 line-clamp-1 italic">↩ {r.response}</p>
          )}
        </div>
      ),
    },
    {
      key: 'review_date', label: 'Date avis', sortable: true,
      render: r => <span className="text-xs text-muted-foreground whitespace-nowrap">{fmtDate(r.review_date)}</span>,
    },
    {
      key: 'order_number', label: 'N° commande',
      render: r => <span className="text-xs text-muted-foreground font-mono">{r.order_number}</span>,
    },
  ]

  return (
    <div className="rounded-lg border overflow-hidden">
      {loading
        ? <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
        : (
          <>
            <Table
              columns={columns}
              data={data}
              empty="Aucun avis. Importez un CSV Regiondo pour commencer."
              sortKey={sort.key}
              sortDir={sort.dir}
              onSort={onSort}
            />
            <Pagination page={page} total={total} perPage={perPage} onChange={setPage} />
          </>
        )
      }
    </div>
  )
}
