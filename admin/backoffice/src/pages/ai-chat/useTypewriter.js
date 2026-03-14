import { useState, useRef, useEffect } from 'react'

// ─────────────────────────────────────────────────────────────────────────────
// useTypewriter — révèle le texte progressivement, lisse les gros chunks SSE
// ─────────────────────────────────────────────────────────────────────────────

export function useTypewriter(target, active) {
  const [text, setText]   = useState('')
  const rafRef    = useRef(null)
  const targetRef = useRef(target)

  // Garde la cible à jour et relance la boucle si elle s'était arrêtée
  useEffect(() => {
    targetRef.current = target
    if (active && !rafRef.current) {
      rafRef.current = requestAnimationFrame(tick)
    }
  }, [target])

  function tick() {
    let caughtUp = false
    setText(prev => {
      const t = targetRef.current
      if (prev.length >= t.length) { caughtUp = true; return prev }
      const lag  = t.length - prev.length
      const step = lag > 300 ? 12 : lag > 80 ? 5 : 2
      return t.slice(0, prev.length + step)
    })
    if (caughtUp) {
      rafRef.current = null
    } else {
      rafRef.current = requestAnimationFrame(tick)
    }
  }

  useEffect(() => {
    if (!active) {
      // Streaming terminé → affichage immédiat du texte final
      if (rafRef.current) { cancelAnimationFrame(rafRef.current); rafRef.current = null }
      setText(target)
      return
    }
    rafRef.current = requestAnimationFrame(tick)
    return () => { if (rafRef.current) { cancelAnimationFrame(rafRef.current); rafRef.current = null } }
  }, [active]) // eslint-disable-line react-hooks/exhaustive-deps

  return text
}
