import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import { PageHeader, Btn, Input, Notice, Spinner } from '../components/ui'
import { RefreshDouble, OpenNewWindow, NavArrowDown, NavArrowRight } from 'iconoir-react'

export default function Products() {
  const [products, setProducts]     = useState([])
  const [loading, setLoading]       = useState(true)
  const [syncing, setSyncing]       = useState(false)
  const [syncResult, setSyncResult] = useState(null)
  const [error, setError]           = useState(null)
  const [search, setSearch]         = useState('')
  const [expanded, setExpanded]     = useState(null)
  const [variations, setVariations] = useState({})

  useEffect(() => {
    api.products()
      .then(r => setProducts(r.data ?? []))
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  async function handleSync() {
    setSyncing(true)
    setSyncResult(null)
    try {
      const res = await api.sync()
      setSyncResult(res)
      // Refresh list to show updated WP post links
      const r = await api.products()
      setProducts(r.data ?? [])
    } catch (e) {
      setError(e.message)
    } finally {
      setSyncing(false)
    }
  }

  async function toggleExpand(productId) {
    if (expanded === productId) { setExpanded(null); return }
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

  return (
    <div>
      <PageHeader
        title="Produits"
        actions={
          <Btn loading={syncing} onClick={handleSync}>
            <RefreshDouble width={13} height={13} strokeWidth={1.5} />
            Synchroniser vers WP
          </Btn>
        }
      />

      <div className="px-8 py-5 flex items-center gap-3">
        <div className="w-72">
          <Input
            placeholder="Rechercher…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
        {products.length > 0 && (
          <span className="text-xs text-gray-400">{filtered.length} produit{filtered.length > 1 ? 's' : ''}</span>
        )}
      </div>

      {error && <div className="px-8 pb-4"><Notice type="error">{error}</Notice></div>}

      {syncResult && (
        <div className="px-8 pb-4">
          <Notice type="success">
            {syncResult.created} créé{syncResult.created !== 1 ? 's' : ''}, {syncResult.updated} mis à jour
            {syncResult.errors > 0 ? `, ${syncResult.errors} erreur${syncResult.errors > 1 ? 's' : ''}` : ''}
          </Notice>
        </div>
      )}

      <div className="mx-8 border border-gray-200">
        {loading ? (
          <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="w-8" />
                <th className="px-4 py-3 text-left text-xs text-gray-400 uppercase tracking-widest font-normal">Nom</th>
                <th className="px-4 py-3 text-left text-xs text-gray-400 uppercase tracking-widest font-normal">ID</th>
                <th className="px-4 py-3 text-left text-xs text-gray-400 uppercase tracking-widest font-normal">Prix</th>
                <th className="px-4 py-3 text-left text-xs text-gray-400 uppercase tracking-widest font-normal">Post WP</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map(row => (
                <>
                  <tr key={row.product_id} className="border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                      onClick={() => toggleExpand(row.product_id)}>
                    <td className="pl-4 py-3 text-gray-400">
                      {expanded === row.product_id
                        ? <NavArrowDown width={13} height={13} strokeWidth={1.5} />
                        : <NavArrowRight width={13} height={13} strokeWidth={1.5} />}
                    </td>
                    <td className="px-4 py-3 text-gray-800">{row.name}</td>
                    <td className="px-4 py-3"><code className="text-xs text-gray-400">#{row.product_id}</code></td>
                    <td className="px-4 py-3 text-gray-600">{row.base_price} {row.currency ?? 'EUR'}</td>
                    <td className="px-4 py-3">
                      {row.wp_post_id
                        ? <a href={row.wp_post_url} target="_blank" rel="noreferrer"
                             onClick={e => e.stopPropagation()}
                             className="flex items-center gap-1 text-xs text-gray-500 hover:text-black underline">
                            #{row.wp_post_id} <OpenNewWindow width={11} height={11} strokeWidth={1.5} />
                          </a>
                        : <span className="text-xs text-gray-300">—</span>
                      }
                    </td>
                  </tr>

                  {expanded === row.product_id && (
                    <tr key={`${row.product_id}-exp`} className="bg-gray-50 border-b border-gray-100">
                      <td />
                      <td colSpan={4} className="px-6 py-4">
                        <VariationsPanel data={variations[row.product_id]} />
                      </td>
                    </tr>
                  )}
                </>
              ))}

              {!filtered.length && (
                <tr>
                  <td colSpan={5} className="px-4 py-12 text-center text-sm text-gray-400">
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

function VariationsPanel({ data }) {
  if (!data) return <div className="flex items-center gap-2 text-xs text-gray-400"><Spinner size={12} /> Chargement…</div>
  if (!data.length) return <span className="text-xs text-gray-400">Aucune variante.</span>

  return (
    <div>
      <div className="text-xs text-gray-400 uppercase tracking-widest mb-3">Variantes</div>
      <div className="grid grid-cols-4 gap-2">
        {data.map((v, i) => (
          <div key={i} className="border border-gray-200 px-3 py-2 text-xs bg-white">
            <div className="text-gray-700">{v.name ?? v.label ?? `Variante ${i + 1}`}</div>
            {v.price !== undefined && <div className="text-gray-400 mt-0.5">{v.price} {v.currency ?? 'EUR'}</div>}
          </div>
        ))}
      </div>
    </div>
  )
}
