import { useState, useMemo, useEffect } from 'react'
import { AnimatePresence, motion } from 'motion/react'
import Lottie from 'lottie-react'
import { Sparks, Xmark, Check } from 'iconoir-react'

import { Dialog } from './Dialog'
import AiProviderIcon from './AiProviderIcon'
import { Btn, Badge, Notice } from './ui'
import { api } from '../lib/api'
import { getProvider } from '../lib/aiProviders'

// ── Animation presets ──────────────────────────────────────────────────────────
const FADE_UP = {
  initial:   { opacity: 0, y: 12 },
  animate:   { opacity: 1, y: 0 },
  exit:      { opacity: 0, y: -8 },
  transition:{ duration: 0.25, ease: 'easeOut' },
}

const FADE_IN = {
  initial:   { opacity: 0 },
  animate:   { opacity: 1 },
  exit:      { opacity: 0 },
  transition:{ duration: 0.2 },
}

// ── Dots de chargement animés ──────────────────────────────────────────────────

function LoadingDots() {
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

// ── Widget IA animé selon le provider actif ────────────────────────────────────

/**
 * Affiche le Lottie du provider si disponible, sinon icône @lobehub avec rotation.
 * @param {object} provider Config du provider (AI_PROVIDERS[key])
 */
function AiLoadingWidget({ provider }) {
  if (provider.lottie) {
    return (
      <motion.div
        animate={{ scale: [1, 1.04, 1] }}
        transition={{ duration: 2.5, repeat: Infinity, ease: 'easeInOut' }}
        className="w-20 h-20"
      >
        <Lottie
          animationData={provider.lottie}
          loop
          autoplay
          style={{ width: '100%', height: '100%' }}
        />
      </motion.div>
    )
  }

  // Pas de Lottie — icône avec halo pulsant
  return (
    <div className="relative flex items-center justify-center w-20 h-20">
      {/* Halo pulsant */}
      <motion.div
        className="absolute inset-0 rounded-full"
        style={{ background: provider.accent + '18' }}
        animate={{ scale: [1, 1.3, 1], opacity: [0.6, 0.1, 0.6] }}
        transition={{ duration: 2, repeat: Infinity, ease: 'easeInOut' }}
      />
      <motion.div
        animate={{ rotate: 360 }}
        transition={{ duration: 8, repeat: Infinity, ease: 'linear' }}
      >
        <AiProviderIcon iconKey={provider.iconKey} size={48} />
      </motion.div>
    </div>
  )
}

// ── Checkbox shadcn-style ──────────────────────────────────────────────────────

function Checkbox({ checked, onChange }) {
  return (
    <motion.button
      type="button"
      role="checkbox"
      aria-checked={checked}
      onClick={onChange}
      whileTap={{ scale: 0.9 }}
      className={[
        'w-4 h-4 shrink-0 rounded border transition-colors flex items-center justify-center',
        checked
          ? 'bg-foreground border-foreground text-background'
          : 'border-input bg-transparent hover:border-foreground/40',
      ].join(' ')}
    >
      <AnimatePresence>
        {checked && (
          <motion.span
            initial={{ scale: 0, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            exit={{ scale: 0, opacity: 0 }}
            transition={{ duration: 0.15 }}
          >
            <Check width={10} height={10} strokeWidth={2.5} />
          </motion.span>
        )}
      </AnimatePresence>
    </motion.button>
  )
}

// ── Stats card résultat (flat, pas d'accordion) ────────────────────────────────

function CorrelationCard({ c }) {
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
        <Badge variant={c.total_bookings > 0 ? 'ok' : 'default'} className="shrink-0 mt-0.5">
          {c.days_in_window}j
        </Badge>
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
          <p className="text-xl font-bold tabular-nums">
            {Math.round(c.total_revenue).toLocaleString('fr-FR')} €
          </p>
          <p className="text-[10px] text-muted-foreground uppercase tracking-wider mt-0.5">CA</p>
        </div>
      </div>
    </div>
  )
}

// ── Pill filtre fenêtre ────────────────────────────────────────────────────────

const WINDOW_LABELS = {
  '±2j':             '± 2 jours',
  '±7j':             '± 7 jours',
  '7j_before_after': '7j avant + durée + 7j après',
  'full':            'Durée complète',
}

function WindowPills({ value, onChange }) {
  return (
    <div className="space-y-1.5">
      <p className="text-xs text-muted-foreground font-medium">Fenêtre d'influence :</p>
      <div className="flex flex-wrap gap-1.5">
        {Object.entries(WINDOW_LABELS).map(([w, label]) => (
          <motion.button
            key={w}
            type="button"
            onClick={() => onChange(w)}
            whileTap={{ scale: 0.95 }}
            className={[
              'text-xs px-2.5 py-1 rounded-full border transition-colors',
              value === w
                ? 'bg-foreground text-background border-foreground'
                : 'border-border text-muted-foreground hover:text-foreground hover:border-foreground/30',
            ].join(' ')}
          >
            {label}
          </motion.button>
        ))}
      </div>
    </div>
  )
}

// ── Badge provider dans le header ─────────────────────────────────────────────

function ProviderBadge({ provider }) {
  return (
    <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-border bg-card text-xs font-medium text-muted-foreground">
      <AiProviderIcon iconKey={provider.iconKey} size={14} />
      {provider.sublabel}
    </div>
  )
}

// ── Composant principal ────────────────────────────────────────────────────────

/**
 * Modal "Corrélateur d'événements IA" — centré, animé avec Motion.
 *
 * @param {boolean}  open         Contrôle l'affichage
 * @param {Function} onClose      Callback fermeture
 * @param {string}   from         Date ISO début de la période
 * @param {string}   to           Date ISO fin de la période
 * @param {Array}    bookingsData Réservations par jour [{key, bookings, revenue}]
 */
export default function EventsCorrelator({ open, onClose, from, to, bookingsData }) {
  const [step, setStep]             = useState('generate')
  const [events, setEvents]         = useState([])
  const [selected, setSelected]     = useState(new Set())
  const [generating, setGenerating] = useState(false)
  const [importing, setImporting]   = useState(false)
  const [imported, setImported]     = useState(false)
  const [error, setError]           = useState(null)
  const [windowFilter, setWindowFilter] = useState('±7j')
  const [provider, setProvider]     = useState(null)

  // Charge le provider actif au montage
  useEffect(() => {
    if (!open) return
    api.settings()
      .then(s => setProvider(getProvider(s.ai_provider ?? 'anthropic')))
      .catch(() => setProvider(getProvider('anthropic')))
  }, [open])

  const activeProvider = provider ?? getProvider('anthropic')

  // ── Actions ──────────────────────────────────────────────────────────────────

  async function handleGenerate() {
    setGenerating(true)
    setError(null)
    try {
      const res = await api.generateEvents({ from, to })
      setEvents(res.events ?? [])
      setSelected(new Set((res.events ?? []).map((_, i) => i)))
      setStep('select')
    } catch (e) {
      setError(e.message ?? 'Erreur lors de la génération.')
    } finally {
      setGenerating(false)
    }
  }

  async function handleImport() {
    if (selected.size === 0) return
    setImporting(true)
    setError(null)
    try {
      await api.importEvents(Array.from(selected).map(i => events[i]))
      setStep('correlate')
      setImported(true)
    } catch (e) {
      setError(e.message ?? 'Erreur lors de l\'import.')
    } finally {
      setImporting(false)
    }
  }

  function toggleSelect(i) {
    setSelected(prev => {
      const next = new Set(prev)
      next.has(i) ? next.delete(i) : next.add(i)
      return next
    })
  }

  function handleReset() {
    setStep('generate')
    setEvents([])
    setSelected(new Set())
    setImported(false)
    setError(null)
  }

  // ── Corrélation ──────────────────────────────────────────────────────────────

  function getEventWindow(event) {
    const DAY = 86400000
    const startMs = new Date(event.date_start + 'T12:00:00').getTime()
    const endMs   = new Date(event.date_end   + 'T12:00:00').getTime()
    if (windowFilter === '±2j')             return { wFrom: startMs - 2 * DAY, wTo: startMs + 2 * DAY }
    if (windowFilter === '±7j')             return { wFrom: startMs - 7 * DAY, wTo: startMs + 7 * DAY }
    if (windowFilter === '7j_before_after') return { wFrom: startMs - 7 * DAY, wTo: endMs + 7 * DAY }
    return { wFrom: startMs, wTo: endMs }
  }

  const correlationData = useMemo(() => {
    if (!bookingsData?.length || step !== 'correlate') return []
    return Array.from(selected).map(i => {
      const event = events[i]
      const { wFrom, wTo } = getEventWindow(event)
      const inWindow = bookingsData.filter(d => {
        const dMs = new Date(d.key + 'T12:00:00').getTime()
        return dMs >= wFrom && dMs <= wTo
      })
      const total_bookings = inWindow.reduce((s, d) => s + (d.bookings ?? 0), 0)
      const cancelled      = inWindow.reduce((s, d) => s + (d.cancelled ?? 0), 0)
      return {
        event,
        total_bookings,
        cancelled,
        cancel_rate:    total_bookings > 0 ? Math.round((cancelled / total_bookings) * 100) : 0,
        peak_bookings:  Math.max(0, ...inWindow.map(d => d.bookings ?? 0)),
        total_revenue:  inWindow.reduce((s, d) => s + (d.revenue ?? 0), 0),
        days_in_window: inWindow.length,
      }
    }).sort((a, b) => b.total_bookings - a.total_bookings)
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selected, events, bookingsData, step, windowFilter])

  // ── Rendu ─────────────────────────────────────────────────────────────────────

  const STEP_TITLES = {
    generate:  'Corrélateur d\'événements IA',
    select:    `${events.length} événement${events.length > 1 ? 's' : ''} détecté${events.length > 1 ? 's' : ''}`,
    correlate: 'Résultats de corrélation',
  }

  return (
    <Dialog open={open} onClose={onClose}>

      {/* ── Header ─────────────────────────────────────────────────────────── */}
      <div className="flex items-start justify-between gap-3 px-6 pt-5 pb-4 border-b">
        <div className="flex flex-col gap-1.5 min-w-0">
          <div className="flex items-center gap-2 flex-wrap">
            <AnimatePresence mode="wait">
              <motion.h2
                key={step}
                {...FADE_UP}
                className="text-base font-semibold"
              >
                {STEP_TITLES[step]}
              </motion.h2>
            </AnimatePresence>
          </div>
          <div className="flex items-center gap-2">
            <p className="text-xs text-muted-foreground">
              Événements touristiques PACA · {from} → {to}
            </p>
            <ProviderBadge provider={activeProvider} />
          </div>
        </div>
        <motion.button
          type="button"
          onClick={onClose}
          whileHover={{ scale: 1.1 }}
          whileTap={{ scale: 0.9 }}
          className="text-muted-foreground hover:text-foreground transition-colors mt-0.5 shrink-0"
        >
          <Xmark width={18} height={18} />
        </motion.button>
      </div>

      {/* ── Corps ──────────────────────────────────────────────────────────── */}
      <div className="px-6 py-5 max-h-[60vh] overflow-y-auto">
        <AnimatePresence mode="wait">

          {/* ── Step 1 : Generate ────────────────────────────────────────── */}
          {step === 'generate' && (
            <motion.div key="generate" {...FADE_UP} className="space-y-5">
              {generating ? (
                /* Animation IA pendant la génération */
                <motion.div
                  key="loading"
                  {...FADE_IN}
                  className="flex flex-col items-center gap-5 py-6"
                >
                  <AiLoadingWidget provider={activeProvider} />

                  <div className="text-center space-y-1.5">
                    <div className="flex items-center justify-center gap-2">
                      <p className="text-sm font-medium">Analyse en cours</p>
                      <LoadingDots />
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {activeProvider.sublabel} analyse les événements PACA
                    </p>
                  </div>

                  {/* Barre de progression indéterminée */}
                  <div className="w-full max-w-xs h-0.5 bg-border rounded-full overflow-hidden">
                    <motion.div
                      className="h-full bg-foreground/60 rounded-full"
                      animate={{ x: ['-100%', '200%'] }}
                      transition={{ duration: 1.6, repeat: Infinity, ease: 'easeInOut' }}
                      style={{ width: '50%' }}
                    />
                  </div>
                </motion.div>
              ) : (
                /* État initial */
                <motion.div
                  key="idle"
                  {...FADE_IN}
                  className="flex flex-col items-center gap-5 py-6"
                >
                  <div className="relative">
                    <div className="w-16 h-16 rounded-2xl bg-muted/60 border flex items-center justify-center">
                      <AiProviderIcon iconKey={activeProvider.iconKey} size={32} />
                    </div>
                    <motion.div
                      className="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-card border flex items-center justify-center"
                      animate={{ rotate: [0, 15, -15, 0] }}
                      transition={{ duration: 2, repeat: Infinity, delay: 1 }}
                    >
                      <Sparks width={12} height={12} className="text-muted-foreground" />
                    </motion.div>
                  </div>

                  <div className="text-center space-y-1.5">
                    <p className="text-sm font-medium">Analyser les événements touristiques</p>
                    <p className="text-xs text-muted-foreground max-w-xs">
                      {activeProvider.label} va détecter les événements majeurs en PACA
                      pour la période <strong>{from} → {to}</strong>.
                    </p>
                  </div>
                </motion.div>
              )}

              {error && (
                <motion.div {...FADE_IN}>
                  <Notice type="error">{error}</Notice>
                </motion.div>
              )}
            </motion.div>
          )}

          {/* ── Step 2 : Sélection ───────────────────────────────────────── */}
          {step === 'select' && (
            <motion.div key="select" {...FADE_UP} className="space-y-4">

              <WindowPills value={windowFilter} onChange={setWindowFilter} />

              {/* Actions bulk */}
              <div className="flex items-center justify-between">
                <p className="text-xs text-muted-foreground">
                  {selected.size} / {events.length} sélectionné{selected.size > 1 ? 's' : ''}
                </p>
                <div className="flex gap-3">
                  <button type="button"
                    onClick={() => setSelected(new Set(events.map((_, i) => i)))}
                    className="text-xs text-muted-foreground hover:text-foreground transition-colors"
                  >Tout</button>
                  <button type="button"
                    onClick={() => setSelected(new Set())}
                    className="text-xs text-muted-foreground hover:text-foreground transition-colors"
                  >Aucun</button>
                </div>
              </div>

              {/* Liste événements */}
              <div className="space-y-1.5">
                {events.map((ev, i) => (
                  <motion.label
                    key={i}
                    layout
                    whileHover={{ x: 2 }}
                    transition={{ duration: 0.15 }}
                    className={[
                      'flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors select-none',
                      selected.has(i)
                        ? 'bg-muted/60 border-foreground/20'
                        : 'border-border hover:bg-muted/30',
                    ].join(' ')}
                  >
                    <Checkbox checked={selected.has(i)} onChange={() => toggleSelect(i)} />
                    <div className="flex-1 min-w-0 pt-0.5">
                      <p className="text-sm font-medium">{ev.name}</p>
                      <p className="text-xs text-muted-foreground mt-0.5">
                        {ev.date_start}
                        {ev.date_end !== ev.date_start ? ` → ${ev.date_end}` : ''}
                        {ev.location ? ` · ${ev.location}` : ''}
                      </p>
                    </div>
                  </motion.label>
                ))}
              </div>

              {error && <Notice type="error">{error}</Notice>}
            </motion.div>
          )}

          {/* ── Step 3 : Résultats ───────────────────────────────────────── */}
          {step === 'correlate' && (
            <motion.div key="correlate" {...FADE_UP} className="space-y-4">

              {imported && (
                <motion.div {...FADE_IN}>
                  <Notice type="success">
                    {selected.size} événement{selected.size > 1 ? 's importés' : ' importé'} dans le calendrier.
                  </Notice>
                </motion.div>
              )}

              {bookingsData?.length > 0 && (
                <WindowPills value={windowFilter} onChange={setWindowFilter} />
              )}

              {!bookingsData?.length && (
                <Notice type="info">
                  Les données de réservations par jour ne sont pas disponibles dans ce contexte.
                  Ouvrez ce panneau depuis le Dashboard pour la corrélation complète.
                </Notice>
              )}

              {correlationData.length > 0 && (
                <div className="space-y-2">
                  {correlationData.map((c, i) => (
                    <motion.div
                      key={i}
                      initial={{ opacity: 0, y: 8 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: i * 0.06, duration: 0.2 }}
                    >
                      <CorrelationCard c={c} />
                    </motion.div>
                  ))}
                </div>
              )}
            </motion.div>
          )}

        </AnimatePresence>
      </div>

      {/* ── Footer ─────────────────────────────────────────────────────────── */}
      <div className="flex items-center justify-between gap-3 px-6 py-4 border-t bg-muted/20 rounded-b-xl">
        <AnimatePresence mode="wait">

          {step === 'generate' && (
            <motion.div key="footer-generate" {...FADE_IN} className="flex items-center justify-between w-full gap-3">
              <Btn variant="ghost" size="sm" onClick={onClose}>Annuler</Btn>
              <span className="bt-ai-wrap">
                <Btn
                  variant="ghost" size="sm"
                  onClick={handleGenerate}
                  loading={generating}
                  disabled={generating}
                  className="gap-1.5"
                >
                  <Sparks width={13} height={13} />
                  {generating ? 'Génération…' : 'Générer les événements'}
                </Btn>
              </span>
            </motion.div>
          )}

          {step === 'select' && (
            <motion.div key="footer-select" {...FADE_IN} className="flex items-center justify-between w-full gap-3">
              <Btn variant="ghost" size="sm" onClick={() => setStep('generate')}>
                ← Régénérer
              </Btn>
              <span className="bt-ai-wrap">
                <Btn
                  variant="ghost" size="sm"
                  onClick={handleImport}
                  loading={importing}
                  disabled={selected.size === 0 || importing}
                  className="gap-1.5"
                >
                  <Sparks width={13} height={13} />
                  Importer {selected.size} événement{selected.size > 1 ? 's' : ''}
                </Btn>
              </span>
            </motion.div>
          )}

          {step === 'correlate' && (
            <motion.div key="footer-correlate" {...FADE_IN} className="flex items-center justify-between w-full gap-3">
              <Btn variant="ghost" size="sm" onClick={onClose}>Fermer</Btn>
              <Btn variant="secondary" size="sm" onClick={handleReset}>
                Nouvelle analyse
              </Btn>
            </motion.div>
          )}

        </AnimatePresence>
      </div>
    </Dialog>
  )
}
