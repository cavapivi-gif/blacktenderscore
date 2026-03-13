import { useState, useCallback, useRef } from 'react'
import { api } from '../lib/api'

const MAX_CHARS = 2000

/**
 * Patterns d'injection côté client — validation UX immédiate.
 * La vraie validation sécurisée est côté PHP.
 */
const INJECTION_PATTERNS = [
  /ignore\s+(previous|all|above)\s+instructions/i,
  /system\s*:/i,
  /you\s+are\s+now/i,
  /jailbreak/i,
]

/** Estimation rapide : ~4 caractères par token (approximation GPT). */
export const estimateTokens = (text) => Math.ceil(text.length / 4)

/**
 * Hook traducteur IA.
 * Gère le state, la validation client, les appels API, et l'historique de session.
 */
export function useTranslator() {
  const [input, setInput]       = useState('')
  const [results, setResults]   = useState([]) // [{ lang, result }]
  const [loading, setLoading]   = useState(false)
  const [error, setError]       = useState(null)
  const [history, setHistory]   = useState([]) // max 10 entrées session
  const abortRef                = useRef(null)

  const tokenEstimate = estimateTokens(input)

  /** Validation légère côté JS — retourne le message d'erreur ou null. */
  const validateInput = useCallback((text) => {
    if (!text.trim()) return 'Texte vide.'
    if (text.length > MAX_CHARS) return `Texte trop long (max ${MAX_CHARS} caractères).`
    for (const pattern of INJECTION_PATTERNS) {
      if (pattern.test(text)) return 'Entrée non valide.'
    }
    return null
  }, [])

  /**
   * Lance la traduction.
   * @param {Object} opts
   * @param {string[]} opts.targetLangs  Codes langue (ex: ['en', 'it'])
   * @param {string}   opts.tone         Ton (neutral|professional|luxury|tourist|casual)
   */
  const translate = useCallback(async ({ targetLangs, tone }) => {
    const err = validateInput(input)
    if (err) { setError(err); return }

    setLoading(true)
    setError(null)
    setResults([])

    try {
      const data = await api.translate({ text: input, targetLangs, tone })
      setResults(data.translations ?? [])

      // Historique session — max 10 entrées
      setHistory(prev => [
        { input, translations: data.translations ?? [], tone, ts: Date.now() },
        ...prev.slice(0, 9),
      ])
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }, [input, validateInput])

  const reset = useCallback(() => {
    setInput('')
    setResults([])
    setError(null)
  }, [])

  return {
    input,
    setInput,
    results,
    loading,
    error,
    history,
    tokenEstimate,
    translate,
    reset,
    maxChars: MAX_CHARS,
  }
}
