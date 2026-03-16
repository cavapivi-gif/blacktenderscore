import { useState, useRef, useEffect } from 'react'
import { motion, AnimatePresence } from 'motion/react'
import { NavArrowDown, Check } from 'iconoir-react'
import { PROVIDER_LIST, getProvider } from '../../lib/aiProviders'
import AiProviderIcon from '../../components/AiProviderIcon'

// ─────────────────────────────────────────────────────────────────────────────
// Model picker
// ─────────────────────────────────────────────────────────────────────────────

export function ModelPicker({ active, available, onChange }) {
  const [open, setOpen] = useState(false)
  const ref = useRef(null)
  const cfg = getProvider(active)

  useEffect(() => {
    if (!open) return
    function handler(e) { if (ref.current && !ref.current.contains(e.target)) setOpen(false) }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [open])

  return (
    <div ref={ref} className="relative">
      <button
        type="button"
        onClick={() => setOpen(o => !o)}
        className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs text-muted-foreground hover:text-foreground hover:bg-muted/50 transition-colors"
      >
        <AiProviderIcon iconKey={cfg.iconKey} variant="Color" size={12} />
        <span className="font-medium">{cfg.label}</span>
        <NavArrowDown width={9} height={9} strokeWidth={2.5} className={`opacity-60 transition-transform duration-200 ${open ? 'rotate-180' : ''}`} />
      </button>

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, y: 6, scale: 0.97 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{    opacity: 0, y: 6, scale: 0.97 }}
            transition={{ duration: 0.15, ease: 'easeOut' }}
            className="absolute bottom-full left-0 mb-2 w-72 bg-card border border-border rounded-xl shadow-xl overflow-hidden z-50"
          >
            <div className="px-4 py-2.5 border-b bg-muted/30">
              <p className="text-[10px] uppercase tracking-widest text-muted-foreground/60 font-semibold">Modèle</p>
            </div>
            <div className="p-1.5 space-y-0.5">
              {PROVIDER_LIST.map(p => {
                const avail = available[p.key]
                const isActive = p.key === active
                return (
                  <button
                    key={p.key}
                    type="button"
                    disabled={!avail}
                    onClick={() => { if (avail) { onChange(p.key); setOpen(false) } }}
                    className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-colors ${
                      isActive && avail  ? 'bg-accent text-foreground'
                      : avail            ? 'text-muted-foreground hover:text-foreground hover:bg-accent/60'
                      :                    'opacity-35 cursor-not-allowed'
                    }`}
                  >
                    <div
                      className="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                      style={{ background: p.accent + '18', border: `1px solid ${p.accent}30` }}
                    >
                      <AiProviderIcon iconKey={p.iconKey} variant="Color" size={15} />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-xs font-medium leading-tight truncate">{p.label}</p>
                      <p className="text-[10px] text-muted-foreground/60 font-mono truncate mt-0.5">{p.model}</p>
                    </div>
                    {isActive && avail
                      ? <Check width={13} height={13} strokeWidth={2.5} className="text-primary shrink-0" />
                      : !avail && <span className="text-[10px] text-muted-foreground/40 shrink-0 font-medium">clé manquante</span>
                    }
                  </button>
                )
              })}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}
