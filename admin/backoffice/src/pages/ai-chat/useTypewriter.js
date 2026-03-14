import { useState, useRef, useEffect } from 'react'

// ─────────────────────────────────────────────────────────────────────────────
// useTypewriter — révèle le texte progressivement, lisse les gros chunks SSE
// ─────────────────────────────────────────────────────────────────────────────

export function useTypewriter(target, active) {
  const [text, setText]   = useState('')
  const rafRef    = useRef(null)
  const targetRef = useRef(target)

  // Garde la cible à jour sans re-déclencher la boucle
  useEffect(() => { targetRef.current = target }, [target])

  useEffect(() => {
    if (!active) {
      // Streaming terminé → affichage immédiat du texte final
      if (rafRef.current) { cancelAnimationFrame(rafRef.current); rafRef.current = null }
      setText(target)
      return
    }
    function tick() {
      setText(prev => {
        const t   = targetRef.current
        if (prev.length >= t.length) return prev
        // Accélère si on a du retard pour ne jamais être trop en arrière
        const lag  = t.length - prev.length
        const step = lag > 300 ? 12 : lag > 80 ? 5 : 2
        return t.slice(0, prev.length + step)
      })
      rafRef.current = requestAnimationFrame(tick)
    }
    rafRef.current = requestAnimationFrame(tick)
    return () => { if (rafRef.current) { cancelAnimationFrame(rafRef.current); rafRef.current = null } }
  }, [active]) // eslint-disable-line react-hooks/exhaustive-deps

  return text
}
