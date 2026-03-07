import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import { PageHeader, Table, Btn, Input, Badge, Notice, Spinner } from '../components/ui'
import { RefreshDouble, OpenNewWindow, NavArrowDown, NavArrowRight } from 'iconoir-react'

export default function Products() {
  const [products, setProducts]       = useState([])
  const [loading, setLoading]         = useState(true)
  const [syncing, setSyncing]         = useState(null) // product_id or 'all'
  const [syncResult, setSyncResult]   = useState(null)
  const [error, setError]             = useState(null)
  const [search, setSearch]           = useState('')
  const [expanded, setExpanded]       = useState(null)
  const [variations, setVariations]   = useState({})

  useEffect(() => {
    api.products()
      .then(r => setProducts(r.data ?? []))
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  async function handleSync(productId) {
    setSyncing(productId ?? 'all')
    setSyncResult(null)
    try {
      const res = await api.sync(productId)
      setSyncResult(res)
    } catch (e) {
      setError(e.message)
    } finally {
      setSyncing(null)
    }
  }

  async function toggleExpand(productId) {
    if (expanded === productId) {
      setExpanded(null)
      return
    }
    setExpanded(productId)
    if (!variations[productId]) {
      const res = await api.variations(productId).catch(() => ({ data: [] }))
      setVariations(prev => ({ ...prev, [productId]: res.data ?? [] }))
    }
  }

  const filtered = products.filter(p =>
    p.name.toLowerCase().includes(search.toLowerCase()) ||
    String(p.product_id).includes(search)
  )

  const columns = [
    {
      key: 'expand',
      label: '',
      render: r => (
        <button onClick={() => toggleExpand(r.product_id)} className="text-gray-400 hover:text-black p-1">
          {expanded === r.product_id
            ? <NavArrowDown width={13} height={13} />
            : <NavArrowRight width={13} height={13} />}
        </button>
      ),
    },
    { key: 'name', label: 'Nom' },
    {
      key: 'product_id',
      label: 'ID',
      render: r => <code className="text-xs text-gray-400">#{r.product_id}</code>,
    },
    {
      key: 'base_price',
      label: 'Prix',
      render: r => `${r.base_price} ${r.currency ?? 'EUR'}`,
    },
    {
      key: 'wp_post_id',
      label: 'Post WP',
      render: r => r.wp_post_id
        ? (
          <a href={r.wp_post_url} target="_blank" rel="noreferrer"
             className="flex items-center gap-1 text-xs underline text-gray-500 hover:text-black">
            #{r.wp_post_id} <OpenNewWindow width={11} height={11} />
          </a>
        )
        : <span className="text-xs text-gray-300">—</span>,
    },
    {
      key: 'actions',
      label: '',
      render: r => (
        <Btn
          size="sm"
          variant="secondary"
          loading={syncing === r.product_id}
          onClick={() => handleSync(r.product_id)}
        >
          {r.wp_post_id ? 'Mettre à jour' : 'Synchroniser'}
        </Btn>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Produits"
        actions={
          <Btn loading={syncing === 'all'} onClick={() => handleSync()}>
            <RefreshDouble width={13} height={13} />
            Tout synchroniser
          </Btn>
        }
      />

      <div className="px-8 py-5 flex items-center gap-3">
        <div className="w-72">
          <Input
            placeholder="Rechercher un produit…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
        {products.length > 0 && (
          <span className="text-xs text-gray-400">{filtered.length} produit{filtered.length > 1 ? 's' : ''}</span>
        )}
      </div>

      {error && (
        <div className="px-8 pb-4">
          <Notice type="error">{error}</Notice>
        </div>
      )}

      {syncResult && (
        <div className="px-8 pb-4">
          <Notice type="success">
            {syncResult.created} créé{syncResult.created > 1 ? 's' : ''}, {syncResult.updated} mis à jour, {syncResult.errors} erreur{syncResult.errors > 1 ? 's' : ''}
          </Notice>
        </div>
      )}

      <div className="mx-8 border border-gray-200">
        {loading ? (
          <div className="flex items-center justify-center py-20">
            <Spinner size={20} />
          </div>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-200">
                {columns.map(col => (
                  <th key={col.key} className="px-4 py-3 text-left text-xs text-gray-400 uppercase tracking-widest font-normal">
                    {col.label}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filtered.map(row => (
                <>
                  <tr key={row.product_id} className="border-b border-gray-100 hover:bg-gray-50">
                    {columns.map(col => (
                      <td key={col.key} className="px-4 py-3 text-gray-700">
                        {col.render ? col.render(row) : row[col.key] ?? '—'}
                      </td>
                    ))}
                  </tr>

                  {expanded === row.product_id && (
                    <tr key={`${row.product_id}-exp`} className="bg-gray-50 border-b border-gray-100">
                      <td colSpan={columns.length} className="px-8 py-4">
                        <VariationsPanel
                          productId={row.product_id}
                          data={variations[row.product_id]}
                        />
                      </td>
                    </tr>
                  )}
                </>
              ))}

              {!filtered.length && (
                <tr>
                  <td colSpan={columns.length} className="px-4 py-12 text-center text-sm text-gray-400">
                    {search ? 'Aucun résultat.' : 'Aucun produit.'}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        )}
      </div>
    </div>
  )
}

function VariationsPanel({ productId, data }) {
  if (!data) return <Spinner size={14} />
  if (!data.length) return <span className="text-xs text-gray-400">Aucune variante disponible.</span>

  return (
    <div>
      <div className="text-xs text-gray-400 uppercase tracking-widest mb-3">Variantes</div>
      <div className="grid grid-cols-4 gap-2">
        {data.map((v, i) => (
          <div key={i} className="border border-gray-200 px-3 py-2 text-xs bg-white">
            <div className="text-gray-700">{v.name ?? v.label ?? `Variante ${i + 1}`}</div>
            {v.price !== undefined && (
              <div className="text-gray-400 mt-0.5">{v.price} {v.currency ?? 'EUR'}</div>
            )}
            {v.availability !== undefined && (
              <div className="text-gray-400 mt-0.5">Dispo : {v.availability}</div>
            )}
          </div>
        ))}
      </div>
    </div>
  )
}
