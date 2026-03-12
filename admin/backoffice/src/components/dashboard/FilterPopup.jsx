import { useState, useEffect, useRef } from 'react'
import { cn } from '../../lib/utils'
import { Xmark } from 'iconoir-react'

/**
 * Dynamic filter popup — "Par date, par produit, par X"
 * Bloomberg-style filter drawer that lets users slice data by any dimension.
 */
export function FilterPopup({ open, onClose, onApply, stats }) {
  const ref = useRef(null)
  const [filters, setFilters] = useState({
    products: [],
    channels: [],
    statuses: [],
    paymentMethods: [],
  })

  // Close on Escape
  useEffect(() => {
    if (!open) return
    function onKey(e) { if (e.key === 'Escape') onClose() }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, onClose])

  // Close on outside click
  useEffect(() => {
    if (!open) return
    function onClick(e) {
      if (ref.current && !ref.current.contains(e.target)) onClose()
    }
    document.addEventListener('mousedown', onClick)
    return () => document.removeEventListener('mousedown', onClick)
  }, [open, onClose])

  // Extract available filter values from stats
  const availableProducts = (stats?.by_product ?? []).map(p => p.name).filter(Boolean)
  const availableChannels = (stats?.by_channel ?? []).map(c => c.channel).filter(Boolean)
  const availableStatuses = ['confirmed', 'cancelled', 'pending', 'booked', 'paid', 'refunded']
  const availablePaymentMethods = (stats?.payments?.by_method ?? []).map(p => p.method).filter(Boolean)

  function toggleFilter(key, value) {
    setFilters(prev => {
      const arr = prev[key]
      const next = arr.includes(value) ? arr.filter(v => v !== value) : [...arr, value]
      return { ...prev, [key]: next }
    })
  }

  function clearAll() {
    setFilters({ products: [], channels: [], statuses: [], paymentMethods: [] })
  }

  function apply() {
    onApply(filters)
    onClose()
  }

  const activeCount = Object.values(filters).reduce((s, arr) => s + arr.length, 0)

  if (!open) return null

  return (
    <div className="fixed inset-0 z-[9998] bg-black/40 flex justify-end">
      <div ref={ref} className="w-full max-w-sm bg-card border-l shadow-2xl h-full overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b sticky top-0 bg-card z-10">
          <div>
            <h3 className="text-sm font-semibold">Filtres avancés</h3>
            {activeCount > 0 && (
              <p className="text-[11px] text-muted-foreground mt-0.5">{activeCount} filtre{activeCount > 1 ? 's' : ''} actif{activeCount > 1 ? 's' : ''}</p>
            )}
          </div>
          <button onClick={onClose} className="h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
            <Xmark width={14} height={14} />
          </button>
        </div>

        <div className="p-4 space-y-5">
          {/* Products */}
          {availableProducts.length > 0 && (
            <FilterSection
              title="Produits"
              items={availableProducts}
              selected={filters.products}
              onToggle={v => toggleFilter('products', v)}
            />
          )}

          {/* Channels */}
          {availableChannels.length > 0 && (
            <FilterSection
              title="Canaux"
              items={availableChannels}
              selected={filters.channels}
              onToggle={v => toggleFilter('channels', v)}
            />
          )}

          {/* Statuses */}
          <FilterSection
            title="Statuts"
            items={availableStatuses}
            selected={filters.statuses}
            onToggle={v => toggleFilter('statuses', v)}
          />

          {/* Payment Methods */}
          {availablePaymentMethods.length > 0 && (
            <FilterSection
              title="Méthodes de paiement"
              items={availablePaymentMethods}
              selected={filters.paymentMethods}
              onToggle={v => toggleFilter('paymentMethods', v)}
            />
          )}
        </div>

        {/* Footer actions */}
        <div className="sticky bottom-0 bg-card border-t p-4 flex items-center gap-3">
          <button
            onClick={clearAll}
            className="flex-1 px-3 py-2 rounded-md text-xs font-medium border border-border text-muted-foreground hover:text-foreground transition-colors"
          >
            Tout effacer
          </button>
          <button
            onClick={apply}
            className="flex-1 px-3 py-2 rounded-md text-xs font-medium bg-primary text-primary-foreground hover:bg-primary/90 transition-colors"
          >
            Appliquer {activeCount > 0 ? `(${activeCount})` : ''}
          </button>
        </div>
      </div>
    </div>
  )
}

function FilterSection({ title, items, selected, onToggle }) {
  const [expanded, setExpanded] = useState(items.length <= 6)
  const shown = expanded ? items : items.slice(0, 5)

  return (
    <div>
      <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium mb-2">{title}</p>
      <div className="flex flex-wrap gap-1.5">
        {shown.map(item => (
          <button
            key={item}
            onClick={() => onToggle(item)}
            className={cn(
              'px-2.5 py-1 rounded text-xs font-medium border transition-colors',
              selected.includes(item)
                ? 'bg-primary text-primary-foreground border-primary'
                : 'bg-card border-border text-muted-foreground hover:text-foreground',
            )}
          >
            {item}
          </button>
        ))}
        {!expanded && items.length > 5 && (
          <button
            onClick={() => setExpanded(true)}
            className="px-2.5 py-1 rounded text-xs text-muted-foreground hover:text-foreground"
          >
            +{items.length - 5} autres
          </button>
        )}
      </div>
    </div>
  )
}
