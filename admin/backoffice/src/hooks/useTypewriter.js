import { useState, useRef, useEffect } from 'react'

/**
 * useTypewriter — progressive text reveal that smooths bursty SSE chunks.
 * Respects word/line boundaries to avoid cutting inside Markdown syntax.
 * Auto-accelerates when lagging behind the target to stay responsive.
 */
export function useTypewriter(target, active) {
  const [text, setText]   = useState('')
  const rafRef    = useRef(null)
  const targetRef = useRef(target)

  useEffect(() => { targetRef.current = target }, [target])

  useEffect(() => {
    if (!active) {
      if (rafRef.current) { cancelAnimationFrame(rafRef.current); rafRef.current = null }
      setText(target)
      return
    }
    function tick() {
      setText(prev => {
        const t = targetRef.current
        if (prev.length >= t.length) return prev

        const lag = t.length - prev.length

        // Large lag → catch up fast (flush entire backlog)
        if (lag > 400) return t

        // Medium lag → jump to next line break or chunk boundary
        if (lag > 120) {
          const nextNl = t.indexOf('\n', prev.length)
          if (nextNl !== -1 && nextNl - prev.length < 200) return t.slice(0, nextNl + 1)
          return t.slice(0, prev.length + 40)
        }

        // Small lag → advance to next word boundary for smooth reveal
        const step = lag > 40 ? 15 : 4
        let end = Math.min(prev.length + step, t.length)

        // Snap to next space/newline to avoid cutting mid-word
        if (end < t.length) {
          const nextSpace = t.indexOf(' ', end)
          const nextNl    = t.indexOf('\n', end)
          const nearest   = [nextSpace, nextNl].filter(i => i !== -1 && i - end < 12)
          if (nearest.length) end = Math.min(...nearest) + 1
        }

        return t.slice(0, end)
      })
      rafRef.current = requestAnimationFrame(tick)
    }
    rafRef.current = requestAnimationFrame(tick)
    return () => { if (rafRef.current) { cancelAnimationFrame(rafRef.current); rafRef.current = null } }
  }, [active]) // eslint-disable-line react-hooks/exhaustive-deps

  return text
}
