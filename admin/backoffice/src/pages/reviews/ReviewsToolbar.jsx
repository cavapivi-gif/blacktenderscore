import { Search } from 'iconoir-react'

/**
 * Filters toolbar for the reviews table — search, product select, rating select.
 */
export default function ReviewsToolbar({ q, setQ, product, setProduct, ratingFilter, setRatingFilter, setPage, products, total }) {
  return (
    <div className="flex items-center gap-2 flex-wrap mb-3">
      <div className="relative">
        <Search className="absolute left-2.5 top-2.5 w-4 h-4 text-muted-foreground pointer-events-none" />
        <input
          className="h-9 pl-8 pr-3 text-sm rounded-md border border-input bg-transparent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring w-44"
          placeholder="Rechercher…"
          value={q}
          onChange={e => setQ(e.target.value)}
        />
      </div>

      {products.length > 0 && (
        <select
          className="h-9 px-3 text-sm rounded-md border border-input bg-background cursor-pointer focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring max-w-[220px]"
          value={product}
          onChange={e => { setProduct(e.target.value); setPage(1) }}
        >
          <option value="">Tous les produits</option>
          {products.map(p => <option key={p} value={p}>{p}</option>)}
        </select>
      )}

      <select
        className="h-9 px-3 text-sm rounded-md border border-input bg-background cursor-pointer focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        value={ratingFilter}
        onChange={e => { setRatingFilter(e.target.value); setPage(1) }}
      >
        <option value="">Toutes les notes</option>
        {[5, 4, 3, 2, 1].map(n => (
          <option key={n} value={n}>{n} étoile{n > 1 ? 's' : ''}</option>
        ))}
      </select>

      <span className="text-xs text-muted-foreground ml-auto">
        {total.toLocaleString('fr-FR')} avis
      </span>
    </div>
  )
}
