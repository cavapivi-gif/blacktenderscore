import { useState, useCallback, useEffect, useRef } from 'react'

export function useToast() {
  const [toasts, setToasts] = useState([])
  const timersRef = useRef(new Set())

  // Cleanup all pending timers on unmount
  useEffect(() => () => timersRef.current.forEach(clearTimeout), [])

  const push = useCallback((msg, type = 'success') => {
    const id = Date.now()
    setToasts(p => [...p.slice(-2), { id, msg, type }])
    const timer = setTimeout(() => {
      setToasts(p => p.filter(t => t.id !== id))
      timersRef.current.delete(timer)
    }, 2800)
    timersRef.current.add(timer)
  }, [])

  return { toasts, push }
}
