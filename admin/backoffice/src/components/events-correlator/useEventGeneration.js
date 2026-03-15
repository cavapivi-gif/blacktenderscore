/**
 * Hook gérant la génération et l'import d'événements IA.
 * Encapsule les appels API + state machine (generate → select → correlate).
 */
import { useState, useEffect, useCallback } from 'react'
import { api } from '../../lib/api'
import { getProvider } from '../../lib/aiProviders'

/**
 * @param {boolean} open - modal ouverte
 * @param {string} from  - date ISO début
 * @param {string} to    - date ISO fin
 */
export function useEventGeneration(open, from, to) {
  const [step, setStep]             = useState('generate')
  const [events, setEvents]         = useState([])
  const [selected, setSelected]     = useState(new Set())
  const [generating, setGenerating] = useState(false)
  const [importing, setImporting]   = useState(false)
  const [imported, setImported]     = useState(false)
  const [error, setError]           = useState(null)
  const [provider, setProvider]     = useState(null)
  const [generatedAt, setGeneratedAt] = useState(null)
  const [fromCache, setFromCache]     = useState(false)

  // Charge le provider actif au montage
  useEffect(() => {
    if (!open) return
    api.settings()
      .then(s => setProvider(getProvider(s.ai_provider ?? 'anthropic')))
      .catch(() => setProvider(getProvider('anthropic')))
  }, [open])

  const activeProvider = provider ?? getProvider('anthropic')

  const handleGenerate = useCallback(async (force = false) => {
    setGenerating(true)
    setError(null)
    try {
      const res = await api.generateEvents({ from, to, force })
      setEvents(res.events ?? [])
      setSelected(new Set((res.events ?? []).map((_, i) => i)))
      setFromCache(res.cached ?? false)
      setGeneratedAt(res.generated_at ?? null)
      setStep('select')
    } catch (e) {
      setError(e.message ?? 'Erreur lors de la génération.')
    } finally {
      setGenerating(false)
    }
  }, [from, to])

  const handleImport = useCallback(async () => {
    if (selected.size === 0) return
    setImporting(true)
    setError(null)
    try {
      await api.importEvents(Array.from(selected).map(i => events[i]))
      setStep('correlate')
      setImported(true)
    } catch (e) {
      setError(e.message ?? "Erreur lors de l'import.")
    } finally {
      setImporting(false)
    }
  }, [selected, events])

  const toggleSelect = useCallback((i) => {
    setSelected(prev => {
      const next = new Set(prev)
      next.has(i) ? next.delete(i) : next.add(i)
      return next
    })
  }, [])

  const selectAll = useCallback(() => {
    setSelected(new Set(events.map((_, i) => i)))
  }, [events])

  const selectNone = useCallback(() => {
    setSelected(new Set())
  }, [])

  const handleReset = useCallback(() => {
    setStep('generate')
    setEvents([])
    setSelected(new Set())
    setImported(false)
    setError(null)
  }, [])

  return {
    step, events, selected, generating, importing, imported, error,
    activeProvider,
    handleGenerate, handleImport, toggleSelect, selectAll, selectNone, handleReset,
    setStep, fromCache, generatedAt,
  }
}
