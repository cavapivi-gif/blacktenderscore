import { useEffect } from 'react'
import { Xmark } from 'iconoir-react'

export function FullscreenOverlay({ title, onClose, children }) {
  useEffect(() => {
    function onKey(e) { if (e.key === 'Escape') onClose() }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [onClose])

  return (
    <div className="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center p-6" onClick={onClose}>
      <div className="bg-card rounded-xl border w-full max-w-5xl p-6 shadow-2xl" onClick={e => e.stopPropagation()}>
        <div className="flex items-center justify-between mb-4">
          <span className="text-sm font-medium">{title}</span>
          <button onClick={onClose} className="h-7 w-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
            <Xmark width={14} height={14} />
          </button>
        </div>
        {children}
      </div>
    </div>
  )
}
