import { useState, useCallback } from 'react'
import { api } from '../lib/api'

const MAX_CHARS = 2000

const INJECTION_PATTERNS = [
  /ignore\s+(previous|all|above)\s+instructions/i,
  /system\s*:/i,
  /you\s+are\s+now/i,
  /jailbreak/i,
]

/**
 * Diff mot-à-mot entre deux textes.
 * Retourne un tableau de segments : { text, type: 'kept'|'removed'|'added' }
 * Limité aux textes < 500 mots pour éviter les lag UI.
 */
export function wordDiff(original, corrected) {
  const tokenize = (str) => str.split(/(\s+)/)
  const a = tokenize(original)
  const b = tokenize(corrected)

  if (a.length + b.length > 1000) {
    // Texte trop long pour un diff précis — retour simple
    return [
      { text: original,  type: 'removed' },
      { text: corrected, type: 'added'   },
    ]
  }

  // LCS dynamique
  const m = a.length, n = b.length
  const dp = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0))
  for (let i = 1; i <= m; i++) {
    for (let j = 1; j <= n; j++) {
      dp[i][j] = a[i - 1] === b[j - 1]
        ? dp[i - 1][j - 1] + 1
        : Math.max(dp[i - 1][j], dp[i][j - 1])
    }
  }

  // Backtrack
  const segments = []
  let i = m, j = n
  while (i > 0 || j > 0) {
    if (i > 0 && j > 0 && a[i - 1] === b[j - 1]) {
      segments.unshift({ text: a[i - 1], type: 'kept' })
      i--; j--
    } else if (j > 0 && (i === 0 || dp[i][j - 1] >= dp[i - 1][j])) {
      segments.unshift({ text: b[j - 1], type: 'added' })
      j--
    } else {
      segments.unshift({ text: a[i - 1], type: 'removed' })
      i--
    }
  }

  // Fusionner les segments consécutifs de même type
  return segments.reduce((acc, seg) => {
    const prev = acc[acc.length - 1]
    if (prev && prev.type === seg.type) {
      prev.text += seg.text
    } else {
      acc.push({ ...seg })
    }
    return acc
  }, [])
}

/**
 * Hook correcteur IA.
 * Gère le state, la validation, l'appel API et le diff visuel.
 */
export function useCorrector() {
  const [input, setCorrectorInput] = useState('')
  const [corrected, setCorrected]  = useState('')
  const [diff, setDiff]            = useState(null)  // segments wordDiff ou null
  const [showDiff, setShowDiff]    = useState(false)
  const [loading, setLoading]      = useState(false)
  const [error, setError]          = useState(null)

  const validate = useCallback((text) => {
    if (!text.trim()) return 'Texte vide.'
    if (text.length > MAX_CHARS) return `Texte trop long (max ${MAX_CHARS} caractères).`
    for (const p of INJECTION_PATTERNS) {
      if (p.test(text)) return 'Entrée non valide.'
    }
    return null
  }, [])

  const correct = useCallback(async (lang = '') => {
    const err = validate(input)
    if (err) { setError(err); return }

    setLoading(true)
    setError(null)
    setCorrected('')
    setDiff(null)
    setShowDiff(false)

    try {
      const data = await api.correct({ text: input, lang })
      setCorrected(data.corrected)
      setDiff(wordDiff(input, data.corrected))
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }, [input, validate])

  const reset = useCallback(() => {
    setCorrectorInput('')
    setCorrected('')
    setDiff(null)
    setShowDiff(false)
    setError(null)
  }, [])

  return {
    input,
    setInput: setCorrectorInput,
    corrected,
    diff,
    showDiff,
    setShowDiff,
    loading,
    error,
    correct,
    reset,
    maxChars: MAX_CHARS,
  }
}
