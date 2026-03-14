import { useState, useCallback } from 'react'

export function useToast() {
  const [toasts, setToasts] = useState([])
  const push = useCallback((msg, type = 'success') => {
    const id = Date.now()
    setToasts(p => [...p.slice(-2), { id, msg, type }])
    setTimeout(() => setToasts(p => p.filter(t => t.id !== id)), 2800)
  }, [])
  return { toasts, push }
}
