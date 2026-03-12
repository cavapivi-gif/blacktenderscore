import { useEffect, useRef } from 'react'

/**
 * Minimal Sheet (slide-over panel) stub.
 * Exports: Sheet, SheetContent, SheetRow
 */

export function Sheet({ open, onOpenChange, children }) {
  const overlayRef = useRef(null)

  useEffect(() => {
    if (!open) return
    function onKey(e) {
      if (e.key === 'Escape') onOpenChange?.(false)
    }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, onOpenChange])

  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex justify-end">
      {/* Backdrop */}
      <div
        ref={overlayRef}
        className="absolute inset-0 bg-black/40"
        onClick={() => onOpenChange?.(false)}
      />
      {/* Panel */}
      <div className="relative w-full max-w-md bg-card border-l shadow-xl overflow-y-auto animate-in slide-in-from-right">
        {children}
      </div>
    </div>
  )
}

export function SheetContent({ title, description, children }) {
  return (
    <div className="p-6">
      {title && <h2 className="text-lg font-semibold">{title}</h2>}
      {description && <p className="text-sm text-muted-foreground mt-1 mb-4">{description}</p>}
      {!description && title && <div className="mb-4" />}
      {children}
    </div>
  )
}

export function SheetRow({ label, children }) {
  return (
    <div>
      <dt className="text-[11px] text-muted-foreground uppercase tracking-wider">{label}</dt>
      <dd className="text-sm mt-0.5">{children}</dd>
    </div>
  )
}
