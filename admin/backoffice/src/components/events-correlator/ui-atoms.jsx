/**
 * Petits composants UI réutilisés dans le corrélateur d'événements.
 * LoadingDots, AiLoadingWidget, Checkbox, ProviderBadge, WindowPills, CorrelationCard.
 */
import { AnimatePresence, motion } from 'motion/react'
import Lottie from 'lottie-react'
import { Check } from 'iconoir-react'
import AiProviderIcon from '../AiProviderIcon'
import { Badge } from '../ui'

// ── Animation presets ────────────────────────────────────────────────────────
export const FADE_UP = {
  initial:   { opacity: 0, y: 12 },
  animate:   { opacity: 1, y: 0 },
  exit:      { opacity: 0, y: -8 },
  transition:{ duration: 0.25, ease: 'easeOut' },
}

export const FADE_IN = {
  initial:   { opacity: 0 },
  animate:   { opacity: 1 },
  exit:      { opacity: 0 },
  transition:{ duration: 0.2 },
}

// ── LoadingDots ──────────────────────────────────────────────────────────────
export function LoadingDots() {
  return (
    <div className="flex items-center gap-1.5">
      {[0, 1, 2].map(i => (
        <motion.span
          key={i}
          className="w-1.5 h-1.5 rounded-full bg-foreground/30"
          animate={{ opacity: [0.3, 1, 0.3], scale: [0.8, 1, 0.8] }}
          transition={{ duration: 1.2, repeat: Infinity, delay: i * 0.2, ease: 'easeInOut' }}
        />
      ))}
    </div>
  )
}

// ── AiLoadingWidget ──────────────────────────────────────────────────────────
export function AiLoadingWidget({ provider }) {
  if (provider.lottie) {
    return (
      <motion.div
        animate={{ scale: [1, 1.04, 1] }}
        transition={{ duration: 2.5, repeat: Infinity, ease: 'easeInOut' }}
        className="w-20 h-20"
      >
        <Lottie animationData={provider.lottie} loop autoplay style={{ width: '100%', height: '100%' }} />
      </motion.div>
    )
  }
  return (
    <div className="relative flex items-center justify-center w-20 h-20">
      <motion.div
        className="absolute inset-0 rounded-full"
        style={{ background: provider.accent + '18' }}
        animate={{ scale: [1, 1.3, 1], opacity: [0.6, 0.1, 0.6] }}
        transition={{ duration: 2, repeat: Infinity, ease: 'easeInOut' }}
      />
      <motion.div animate={{ rotate: 360 }} transition={{ duration: 8, repeat: Infinity, ease: 'linear' }}>
        <AiProviderIcon iconKey={provider.iconKey} size={48} />
      </motion.div>
    </div>
  )
}

// ── Checkbox ─────────────────────────────────────────────────────────────────
export function Checkbox({ checked, onChange }) {
  return (
    <motion.button
      type="button" role="checkbox" aria-checked={checked}
      onClick={onChange} whileTap={{ scale: 0.9 }}
      className={[
        'w-4 h-4 shrink-0 rounded border transition-colors flex items-center justify-center',
        checked ? 'bg-foreground border-foreground text-background' : 'border-input bg-transparent hover:border-foreground/40',
      ].join(' ')}
    >
      <AnimatePresence>
        {checked && (
          <motion.span initial={{ scale: 0, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0, opacity: 0 }} transition={{ duration: 0.15 }}>
            <Check width={10} height={10} strokeWidth={2.5} />
          </motion.span>
        )}
      </AnimatePresence>
    </motion.button>
  )
}

// ── ProviderBadge ────────────────────────────────────────────────────────────
export function ProviderBadge({ provider }) {
  return (
    <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-border bg-card text-xs font-medium text-muted-foreground">
      <AiProviderIcon iconKey={provider.iconKey} size={14} />
      {provider.sublabel}
    </div>
  )
}

// ── WindowPills ──────────────────────────────────────────────────────────────
const WINDOW_LABELS = {
  '±2j':             '± 2 jours',
  '±7j':             '± 7 jours',
  '7j_before_after': '7j avant + durée + 7j après',
  'full':            'Durée complète',
}

export function WindowPills({ value, onChange }) {
  return (
    <div className="space-y-1.5">
      <p className="text-xs text-muted-foreground font-medium">Fenêtre d'influence :</p>
      <div className="flex flex-wrap gap-1.5">
        {Object.entries(WINDOW_LABELS).map(([w, label]) => (
          <motion.button
            key={w} type="button" onClick={() => onChange(w)} whileTap={{ scale: 0.95 }}
            className={[
              'text-xs px-2.5 py-1 rounded-full border transition-colors',
              value === w ? 'bg-foreground text-background border-foreground' : 'border-border text-muted-foreground hover:text-foreground hover:border-foreground/30',
            ].join(' ')}
          >
            {label}
          </motion.button>
        ))}
      </div>
    </div>
  )
}

// ── CorrelationCard ──────────────────────────────────────────────────────────
export function CorrelationCard({ c, onDelete }) {
  const cancelColor = c.cancel_rate > 30 ? 'text-red-500' : c.cancel_rate > 15 ? 'text-amber-500' : 'text-emerald-600'
  return (
    <div className="rounded-lg border border-border overflow-hidden">
      <div className="px-4 pt-3 pb-1 flex items-start justify-between gap-2">
        <div className="min-w-0">
          <p className="text-sm font-medium truncate">{c.event.name}</p>
          <p className="text-xs text-muted-foreground mt-0.5">
            {c.event.date_start}
            {c.event.date_end !== c.event.date_start ? ` → ${c.event.date_end}` : ''}
            {c.event.location ? ` · ${c.event.location}` : ''}
          </p>
        </div>
        <div className="flex items-center gap-1.5 shrink-0 mt-0.5">
          <Badge variant={c.total_bookings > 0 ? 'ok' : 'default'}>
            {c.days_in_window}j
          </Badge>
          {onDelete && c.event.id && (
            <button type="button" onClick={() => onDelete(c.event.id)}
              className="text-muted-foreground/50 hover:text-destructive transition-colors p-0.5" title="Supprimer cet événement">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M18 6 6 18M6 6l12 12" />
              </svg>
            </button>
          )}
        </div>
      </div>
      <div className="px-4 pb-3 grid grid-cols-3 gap-2 mt-2">
        <div className="text-center p-2.5 bg-muted/30 rounded-lg">
          <p className="text-xl font-bold tabular-nums">{c.total_bookings}</p>
          <p className="text-[10px] text-muted-foreground uppercase tracking-wider mt-0.5">Réservations</p>
        </div>
        <div className="text-center p-2.5 bg-muted/30 rounded-lg">
          <p className={`text-xl font-bold tabular-nums ${cancelColor}`}>{c.cancel_rate}%</p>
          <p className="text-[10px] text-muted-foreground uppercase tracking-wider mt-0.5">Annulations</p>
        </div>
        <div className="text-center p-2.5 bg-muted/30 rounded-lg">
          <p className="text-xl font-bold tabular-nums">{Math.round(c.total_revenue).toLocaleString('fr-FR')} €</p>
          <p className="text-[10px] text-muted-foreground uppercase tracking-wider mt-0.5">CA</p>
        </div>
      </div>
    </div>
  )
}
