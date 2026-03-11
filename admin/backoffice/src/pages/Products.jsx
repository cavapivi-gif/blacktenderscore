import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import { PageHeader, Btn, Input, Notice, Spinner, Badge } from '../components/ui'
import {
  RefreshDouble,
  OpenNewWindow,
  NavArrowDown,
  NavArrowRight,
  MediaImage,
  Clock,
  Group,
  MapPin,
  Star,
  Cart,
  StatsReport,
  Packages,
  Link,
  Xmark,
} from 'iconoir-react'

export default function Products() {
  const [products, setProducts]     = useState([])
  const [loading, setLoading]       = useState(true)
  const [syncing, setSyncing]       = useState(false)
  const [syncResult, setSyncResult] = useState(null)
  const [error, setError]           = useState(null)
  const [search, setSearch]         = useState('')
  const [expanded, setExpanded]     = useState(null)
  const [detail, setDetail]         = useState({})
  const [variations, setVariations] = useState({})
  const [crossselling, setCrossselling] = useState({})
  const [detailLoading, setDetailLoading] = useState({})

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

    const toFetch = []
    if (!detail[productId]) {
      toFetch.push(
        api.product(productId)
          .then(d => setDetail(prev => ({ ...prev, [productId]: d })))
          .catch(() => setDetail(prev => ({ ...prev, [productId]: null })))
      )
    }
    if (!variations[productId]) {
      toFetch.push(
        api.variations(productId)
          .then(r => setVariations(prev => ({ ...prev, [productId]: r.data ?? [] })))
          .catch(() => setVariations(prev => ({ ...prev, [productId]: [] })))
      )
    }
    if (!crossselling[productId]) {
      toFetch.push(
        api.crossselling(productId)
          .then(r => setCrossselling(prev => ({ ...prev, [productId]: r.data ?? [] })))
          .catch(() => setCrossselling(prev => ({ ...prev, [productId]: [] })))
      )
    }

    if (toFetch.length) {
      setDetailLoading(prev => ({ ...prev, [productId]: true }))
      await Promise.all(toFetch)
      setDetailLoading(prev => ({ ...prev, [productId]: false }))
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
        subtitle="Catalogue Regiondo synchronisé"
        actions={
          <Btn loading={syncing} onClick={handleSync}>
            <RefreshDouble width={13} height={13} strokeWidth={1.5} />
            Synchroniser vers WP
          </Btn>
        }
      />

      <div className="px-6 py-4 flex items-center gap-3">
        <div className="w-72">
          <Input
            placeholder="Rechercher par nom ou ID…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
        {products.length > 0 && (
          <span className="text-xs text-muted-foreground">{filtered.length} / {products.length} produit{products.length > 1 ? 's' : ''}</span>
        )}
      </div>

      {error && <div className="px-6 pb-4"><Notice type="error">{error}</Notice></div>}

      {syncResult && (
        <div className="px-6 pb-4">
          <Notice type="success">
            {syncResult.created} créé{syncResult.created !== 1 ? 's' : ''}, {syncResult.updated} mis à jour
            {syncResult.errors > 0 ? `, ${syncResult.errors} erreur${syncResult.errors > 1 ? 's' : ''}` : ''}
          </Notice>
        </div>
      )}

      <div className="mx-6 rounded-lg border overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="w-8" />
                <th className="px-4 py-3 text-left text-xs text-muted-foreground uppercase tracking-wider font-normal">Produit</th>
                <th className="px-4 py-3 text-left text-xs text-muted-foreground uppercase tracking-wider font-normal">ID</th>
                <th className="px-4 py-3 text-left text-xs text-muted-foreground uppercase tracking-wider font-normal">Prix</th>
                <th className="px-4 py-3 text-left text-xs text-muted-foreground uppercase tracking-wider font-normal">Durée</th>
                <th className="px-4 py-3 text-left text-xs text-muted-foreground uppercase tracking-wider font-normal">Lieu</th>
                <th className="px-4 py-3 text-left text-xs text-muted-foreground uppercase tracking-wider font-normal">Post WP</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map(row => (
                <>
                  <tr
                    key={row.product_id}
                    className="border-b hover:bg-muted/50 cursor-pointer transition-colors"
                    onClick={() => toggleExpand(row.product_id)}
                  >
                    <td className="pl-4 py-3 text-muted-foreground">
                      {expanded === row.product_id
                        ? <NavArrowDown width={13} height={13} strokeWidth={1.5} />
                        : <NavArrowRight width={13} height={13} strokeWidth={1.5} />}
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        {row.thumbnail_url
                          ? <img src={row.thumbnail_url} alt="" className="w-10 h-10 object-cover rounded-md border shrink-0" />
                          : <div className="w-10 h-10 rounded-md border bg-muted flex items-center justify-center shrink-0">
                              <MediaImage width={14} height={14} strokeWidth={1.5} className="text-muted-foreground" />
                            </div>
                        }
                        <div>
                          <div className="font-medium">{row.name}</div>
                          {row.category_name && <div className="text-xs text-muted-foreground mt-0.5">{row.category_name}</div>}
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3"><code className="text-xs text-muted-foreground">#{row.product_id}</code></td>
                    <td className="px-4 py-3 text-muted-foreground">
                      {row.base_price != null ? `${row.base_price} ${row.currency ?? 'EUR'}` : '—'}
                    </td>
                    <td className="px-4 py-3 text-muted-foreground text-xs">
                      {row.duration ? `${row.duration} ${row.duration_unit ?? ''}`.trim() : '—'}
                    </td>
                    <td className="px-4 py-3 text-muted-foreground text-xs">{row.location ?? '—'}</td>
                    <td className="px-4 py-3">
                      {row.wp_post_id
                        ? <a href={row.wp_post_url} target="_blank" rel="noreferrer"
                             onClick={e => e.stopPropagation()}
                             className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground underline">
                            #{row.wp_post_id} <OpenNewWindow width={11} height={11} strokeWidth={1.5} />
                          </a>
                        : <span className="text-xs text-muted-foreground/50">—</span>
                      }
                    </td>
                  </tr>

                  {expanded === row.product_id && (
                    <tr key={`${row.product_id}-detail`} className="border-b">
                      <td />
                      <td colSpan={6} className="px-6 py-6 bg-muted/30">
                        <ProductDetail
                          summary={row}
                          detail={detail[row.product_id]}
                          vars={variations[row.product_id]}
                          cross={crossselling[row.product_id]}
                          loading={detailLoading[row.product_id]}
                        />
                      </td>
                    </tr>
                  )}
                </>
              ))}

              {!filtered.length && (
                <tr>
                  <td colSpan={7} className="px-4 py-12 text-center text-sm text-muted-foreground">
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

/* ─── Panel détail produit ──────────────────────────────────────────────── */

function ProductDetail({ summary, detail, vars, cross, loading }) {
  if (loading) {
    return (
      <div className="flex items-center gap-2 text-xs text-muted-foreground py-4">
        <Spinner size={12} /> Chargement des détails…
      </div>
    )
  }

  const d = detail ?? {}

  return (
    <div className="space-y-6">
      <div className="flex gap-6">
        <div className="shrink-0">
          {(d.images?.[0]?.url ?? summary.thumbnail_url)
            ? <img
                src={d.images?.[0]?.url ?? summary.thumbnail_url}
                alt={summary.name}
                className="w-40 h-28 object-cover rounded-lg border"
              />
            : <div className="w-40 h-28 rounded-lg border bg-muted flex items-center justify-center">
                <MediaImage width={24} height={24} strokeWidth={1} className="text-muted-foreground" />
              </div>
          }
          {d.images?.length > 1 && (
            <div className="flex gap-1 mt-1.5">
              {d.images.slice(1, 4).map((img, i) => (
                <img key={i} src={img.url} alt="" className="w-12 h-10 object-cover rounded border" />
              ))}
              {d.images.length > 4 && (
                <div className="w-12 h-10 rounded border bg-muted flex items-center justify-center text-xs text-muted-foreground">
                  +{d.images.length - 4}
                </div>
              )}
            </div>
          )}
        </div>

        <div className="flex-1 space-y-3">
          {(d.description ?? summary.short_description) && (
            <p className="text-xs text-muted-foreground leading-relaxed max-w-2xl">
              {d.description ?? summary.short_description}
            </p>
          )}

          <div className="grid grid-cols-4 gap-3">
            <MetaItem icon={<Clock width={13} height={13} strokeWidth={1.5} />} label="Durée">
              {summary.duration ? `${summary.duration} ${summary.duration_unit ?? ''}`.trim() : '—'}
            </MetaItem>
            <MetaItem icon={<Group width={13} height={13} strokeWidth={1.5} />} label="Capacité">
              {d.capacity ?? summary.capacity ?? '—'}
            </MetaItem>
            <MetaItem icon={<MapPin width={13} height={13} strokeWidth={1.5} />} label="Lieu">
              {d.location ?? summary.location ?? '—'}
            </MetaItem>
            <MetaItem icon={<Star width={13} height={13} strokeWidth={1.5} />} label="Note">
              {summary.rating ? `${summary.rating} (${summary.reviews_count ?? 0} avis)` : '—'}
            </MetaItem>
            <MetaItem icon={<Cart width={13} height={13} strokeWidth={1.5} />} label="Prix de base">
              {summary.base_price} {summary.currency}
            </MetaItem>
            <MetaItem icon={<Packages width={13} height={13} strokeWidth={1.5} />} label="Catégorie">
              {summary.category_name ?? '—'}
            </MetaItem>
            <MetaItem icon={<StatsReport width={13} height={13} strokeWidth={1.5} />} label="Statut">
              {summary.status
                ? <StatusBadge status={summary.status} />
                : '—'}
            </MetaItem>
            <MetaItem icon={<Link width={13} height={13} strokeWidth={1.5} />} label="Post WP">
              {summary.wp_post_id
                ? <a href={summary.wp_post_url} target="_blank" rel="noreferrer"
                     className="underline hover:text-foreground flex items-center gap-1">
                    #{summary.wp_post_id} <OpenNewWindow width={10} height={10} strokeWidth={1.5} />
                  </a>
                : <span className="text-muted-foreground">Non synchronisé</span>
              }
            </MetaItem>
          </div>
        </div>
      </div>

      {(d.includes?.length > 0 || d.excludes?.length > 0) && (
        <div className="grid grid-cols-2 gap-4">
          {d.includes?.length > 0 && (
            <div>
              <div className="text-xs text-muted-foreground uppercase tracking-wider mb-2">Inclus</div>
              <ul className="space-y-1">
                {d.includes.map((item, i) => (
                  <li key={i} className="flex items-start gap-1.5 text-xs text-muted-foreground">
                    <span className="text-emerald-500 mt-0.5">✓</span> {item}
                  </li>
                ))}
              </ul>
            </div>
          )}
          {d.excludes?.length > 0 && (
            <div>
              <div className="text-xs text-muted-foreground uppercase tracking-wider mb-2">Exclus</div>
              <ul className="space-y-1">
                {d.excludes.map((item, i) => (
                  <li key={i} className="flex items-start gap-1.5 text-xs text-muted-foreground">
                    <Xmark width={11} height={11} strokeWidth={1.5} className="mt-0.5 shrink-0 text-destructive" /> {item}
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}

      <VariationsPanel data={vars} />

      {cross?.length > 0 && (
        <div>
          <div className="text-xs text-muted-foreground uppercase tracking-wider mb-3">Produits recommandés (upsell)</div>
          <div className="grid grid-cols-3 gap-2">
            {cross.map((p, i) => (
              <div key={i} className="rounded-md border bg-card p-3 flex items-center gap-3">
                {p.thumbnail_url || p.image_url
                  ? <img src={p.thumbnail_url ?? p.image_url} alt="" className="w-10 h-10 object-cover rounded border shrink-0" />
                  : <div className="w-10 h-10 bg-muted rounded border flex items-center justify-center shrink-0">
                      <MediaImage width={12} height={12} strokeWidth={1.5} className="text-muted-foreground" />
                    </div>
                }
                <div>
                  <div className="text-xs font-medium">{p.name ?? `Produit ${p.product_id}`}</div>
                  {p.base_price != null && (
                    <div className="text-xs text-muted-foreground mt-0.5">{p.base_price} {p.currency_code ?? 'EUR'}</div>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}

function VariationsPanel({ data }) {
  if (!data) return (
    <div className="flex items-center gap-2 text-xs text-muted-foreground">
      <Spinner size={12} /> Chargement des variantes…
    </div>
  )
  if (!data.length) return <span className="text-xs text-muted-foreground">Aucune variante disponible.</span>

  return (
    <div>
      <div className="text-xs text-muted-foreground uppercase tracking-wider mb-3">
        Variantes ({data.length})
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-xs rounded-md border bg-card">
          <thead>
            <tr className="border-b bg-muted/50">
              <th className="px-3 py-2 text-left text-muted-foreground uppercase tracking-wider font-normal">Nom</th>
              <th className="px-3 py-2 text-left text-muted-foreground uppercase tracking-wider font-normal">Prix</th>
              <th className="px-3 py-2 text-left text-muted-foreground uppercase tracking-wider font-normal">Capacité</th>
              <th className="px-3 py-2 text-left text-muted-foreground uppercase tracking-wider font-normal">Durée</th>
              <th className="px-3 py-2 text-left text-muted-foreground uppercase tracking-wider font-normal">Statut</th>
            </tr>
          </thead>
          <tbody>
            {data.map((v, i) => (
              <tr key={i} className="border-b">
                <td className="px-3 py-2">{v.name ?? v.label ?? `Variante ${i + 1}`}</td>
                <td className="px-3 py-2 text-muted-foreground">
                  {v.price != null ? `${v.price} ${v.currency ?? 'EUR'}` : '—'}
                </td>
                <td className="px-3 py-2 text-muted-foreground">{v.capacity ?? '—'}</td>
                <td className="px-3 py-2 text-muted-foreground">
                  {v.duration ? `${v.duration} ${v.duration_unit ?? ''}`.trim() : '—'}
                </td>
                <td className="px-3 py-2">
                  {v.status ? <StatusBadge status={v.status} /> : '—'}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}

function MetaItem({ icon, label, children }) {
  return (
    <div className="rounded-md bg-card border px-3 py-2">
      <div className="flex items-center gap-1.5 text-xs text-muted-foreground mb-1">
        {icon}
        <span className="uppercase tracking-wider">{label}</span>
      </div>
      <div className="text-xs">{children}</div>
    </div>
  )
}

function StatusBadge({ status }) {
  const map = {
    active:    'active',
    inactive:  'inactive',
    archived:  'inactive',
    confirmed: 'confirmed',
  }
  const labels = {
    active: 'Actif', inactive: 'Inactif', archived: 'Archivé', confirmed: 'Confirmé',
  }
  return <Badge variant={map[status] ?? 'default'}>{labels[status] ?? status}</Badge>
}
