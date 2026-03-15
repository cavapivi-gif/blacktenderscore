/**
 * Modal "Corrélateur d'événements IA" — refactoré.
 * Workflow 3 étapes : générer → sélectionner → corréler.
 * Logique dans useEventGeneration + useCorrelation, UI atoms dans ui-atoms.
 */
import { useState } from 'react'
import { AnimatePresence, motion } from 'motion/react'
import { Sparks, Xmark } from 'iconoir-react'

import { Dialog } from '../Dialog'
import { Btn, Notice } from '../ui'
import { api } from '../../lib/api'
import { useEventGeneration } from './useEventGeneration'
import { useCorrelation } from './useCorrelation'
import {
  FADE_UP, FADE_IN,
  LoadingDots, AiLoadingWidget, Checkbox, ProviderBadge,
  WindowPills, CorrelationCard,
} from './ui-atoms'
import AiProviderIcon from '../AiProviderIcon'

const STEP_TITLES = {
  generate:  "Corrélateur d'événements IA",
  select:    'événements détectés',
  correlate: 'Résultats de corrélation',
}

/**
 * @param {boolean}  open         Contrôle l'affichage
 * @param {Function} onClose      Callback fermeture
 * @param {string}   from         Date ISO début
 * @param {string}   to           Date ISO fin
 * @param {Array}    bookingsData Réservations [{key, bookings, cancelled, revenue}]
 */
export default function EventsCorrelator({ open, onClose, from, to, bookingsData }) {
  const [windowFilter, setWindowFilter] = useState('±7j')

  const {
    step, events, selected, generating, importing, imported, error,
    activeProvider, fromCache, generatedAt,
    handleGenerate, handleImport, toggleSelect, selectAll, selectNone, handleReset,
  } = useEventGeneration(open, from, to)

  const correlationData = useCorrelation(events, selected, bookingsData, windowFilter, step === 'correlate')

  const stepTitle = step === 'select'
    ? `${events.length} ${STEP_TITLES.select}`
    : STEP_TITLES[step]

  return (
    <Dialog open={open} onClose={onClose}>
      {/* ── Header ──────────────────────────────────────────────────── */}
      <div className="flex items-start justify-between gap-3 px-6 pt-5 pb-4 border-b">
        <div className="flex flex-col gap-1.5 min-w-0">
          <AnimatePresence>
            <motion.h2 key={step} {...FADE_UP} className="text-base font-semibold">
              {stepTitle}
            </motion.h2>
          </AnimatePresence>
          <div className="flex items-center gap-2">
            <p className="text-xs text-muted-foreground">
              Événements touristiques PACA · {from} → {to}
            </p>
            <ProviderBadge provider={activeProvider} />
          </div>
        </div>
        <motion.button type="button" onClick={onClose} whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.9 }}
          className="text-muted-foreground hover:text-foreground transition-colors mt-0.5 shrink-0">
          <Xmark width={18} height={18} />
        </motion.button>
      </div>

      {/* ── Corps ───────────────────────────────────────────────────── */}
      <div className="px-6 py-5 max-h-[60vh] overflow-y-auto">
        <AnimatePresence>
          {/* Step 1 : Generate */}
          {step === 'generate' && (
            <motion.div key="generate" {...FADE_UP} className="space-y-5">
              {generating ? (
                <motion.div key="loading" {...FADE_IN} className="flex flex-col items-center gap-5 py-6">
                  <AiLoadingWidget provider={activeProvider} />
                  <div className="text-center space-y-1.5">
                    <div className="flex items-center justify-center gap-2">
                      <p className="text-sm font-medium">Analyse en cours</p>
                      <LoadingDots />
                    </div>
                    <p className="text-xs text-muted-foreground">{activeProvider.sublabel} analyse les événements PACA</p>
                  </div>
                  <div className="w-full max-w-xs h-0.5 bg-border rounded-full overflow-hidden">
                    <motion.div className="h-full bg-foreground/60 rounded-full"
                      animate={{ x: ['-100%', '200%'] }}
                      transition={{ duration: 1.6, repeat: Infinity, ease: 'easeInOut' }}
                      style={{ width: '50%' }} />
                  </div>
                </motion.div>
              ) : (
                <motion.div key="idle" {...FADE_IN} className="flex flex-col items-center gap-5 py-6">
                  <div className="relative">
                    <div className="w-16 h-16 rounded-2xl bg-muted/60 border flex items-center justify-center">
                      <AiProviderIcon iconKey={activeProvider.iconKey} size={32} />
                    </div>
                    <motion.div className="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-card border flex items-center justify-center"
                      animate={{ rotate: [0, 15, -15, 0] }} transition={{ duration: 2, repeat: Infinity, delay: 1 }}>
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
              {error && <motion.div {...FADE_IN}><Notice type="error">{error}</Notice></motion.div>}
            </motion.div>
          )}

          {/* Step 2 : Sélection */}
          {step === 'select' && (
            <motion.div key="select" {...FADE_UP} className="space-y-4">
              {/* Badge cache */}
              {fromCache && generatedAt && (
                <div className="flex items-center justify-between rounded-lg border border-border bg-muted/30 px-3 py-2">
                  <p className="text-xs text-muted-foreground">
                    Résultat en cache — généré le {new Date(generatedAt).toLocaleString('fr-FR')}
                  </p>
                  <button type="button" onClick={() => handleGenerate(true)}
                    className="text-xs text-primary hover:underline">
                    Régénérer
                  </button>
                </div>
              )}
              <WindowPills value={windowFilter} onChange={setWindowFilter} />
              <div className="flex items-center justify-between">
                <p className="text-xs text-muted-foreground">
                  {selected.size} / {events.length} sélectionné{selected.size > 1 ? 's' : ''}
                </p>
                <div className="flex gap-3">
                  <button type="button" onClick={selectAll} className="text-xs text-muted-foreground hover:text-foreground transition-colors">Tout</button>
                  <button type="button" onClick={selectNone} className="text-xs text-muted-foreground hover:text-foreground transition-colors">Aucun</button>
                </div>
              </div>
              <div className="space-y-1.5">
                {events.map((ev, i) => (
                  <motion.label key={i} layout whileHover={{ x: 2 }} transition={{ duration: 0.15 }}
                    className={[
                      'flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors select-none',
                      selected.has(i) ? 'bg-muted/60 border-foreground/20' : 'border-border hover:bg-muted/30',
                    ].join(' ')}>
                    <Checkbox checked={selected.has(i)} onChange={() => toggleSelect(i)} />
                    <div className="flex-1 min-w-0 pt-0.5">
                      <p className="text-sm font-medium">{ev.name}</p>
                      <p className="text-xs text-muted-foreground mt-0.5">
                        {ev.date_start}{ev.date_end !== ev.date_start ? ` → ${ev.date_end}` : ''}
                        {ev.location ? ` · ${ev.location}` : ''}
                      </p>
                    </div>
                  </motion.label>
                ))}
              </div>
              {error && <Notice type="error">{error}</Notice>}
            </motion.div>
          )}

          {/* Step 3 : Résultats */}
          {step === 'correlate' && (
            <motion.div key="correlate" {...FADE_UP} className="space-y-4">
              {imported && (
                <motion.div {...FADE_IN}>
                  <Notice type="success">{selected.size} événement{selected.size > 1 ? 's importés' : ' importé'} dans le calendrier.</Notice>
                </motion.div>
              )}
              {bookingsData?.length > 0 && <WindowPills value={windowFilter} onChange={setWindowFilter} />}
              {!bookingsData?.length && (
                <Notice type="info">Pas de données de réservation disponibles pour la corrélation.</Notice>
              )}
              {correlationData.length > 0 && (
                <div className="space-y-2">
                  {correlationData.map((c, i) => (
                    <motion.div key={i} initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.06, duration: 0.2 }}>
                      <CorrelationCard c={c} onDelete={async (id) => {
                        await api.deleteEvent(id)
                        handleReset()
                      }} />
                    </motion.div>
                  ))}
                </div>
              )}
            </motion.div>
          )}
        </AnimatePresence>
      </div>

      {/* ── Footer ──────────────────────────────────────────────────── */}
      <div className="flex items-center justify-between gap-3 px-6 py-4 border-t bg-muted/20 rounded-b-xl">
        <AnimatePresence>
          {step === 'generate' && (
            <motion.div key="f-gen" {...FADE_IN} className="flex items-center justify-between w-full gap-3">
              <Btn variant="ghost" size="sm" onClick={onClose}>Annuler</Btn>
              <span className="bt-ai-wrap">
                <Btn variant="ghost" size="sm" onClick={handleGenerate} loading={generating} disabled={generating} className="gap-1.5">
                  <Sparks width={13} height={13} />
                  {generating ? 'Génération…' : 'Générer les événements'}
                </Btn>
              </span>
            </motion.div>
          )}
          {step === 'select' && (
            <motion.div key="f-sel" {...FADE_IN} className="flex items-center justify-between w-full gap-3">
              <Btn variant="ghost" size="sm" onClick={handleReset}>← Régénérer</Btn>
              <span className="bt-ai-wrap">
                <Btn variant="ghost" size="sm" onClick={handleImport} loading={importing} disabled={selected.size === 0 || importing} className="gap-1.5">
                  <Sparks width={13} height={13} />
                  Importer {selected.size} événement{selected.size > 1 ? 's' : ''}
                </Btn>
              </span>
            </motion.div>
          )}
          {step === 'correlate' && (
            <motion.div key="f-cor" {...FADE_IN} className="flex items-center justify-between w-full gap-3">
              <Btn variant="ghost" size="sm" onClick={onClose}>Fermer</Btn>
              <Btn variant="secondary" size="sm" onClick={handleReset}>Nouvelle analyse</Btn>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </Dialog>
  )
}
