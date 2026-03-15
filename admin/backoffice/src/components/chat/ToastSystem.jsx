import { useState, useCallback } from 'react'
import { motion, AnimatePresence } from 'motion/react'

export function useToast() {
  const [toasts, setToasts] = useState([])
  const push = useCallback((msg, type = 'success') => {
    const id = Date.now()
    setToasts(p => [...p.slice(-2), { id, msg, type }])
    setTimeout(() => setToasts(p => p.filter(t => t.id !== id)), 2800)
  }, [])
  return { toasts, push }
}

export function ToastStack({ toasts }) {
  return (
    <div className="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none">
      <AnimatePresence>
        {toasts.map(t => (
          <motion.div key={t.id}
            initial={{ opacity: 0, y: 12, scale: 0.95 }}
            animate={{ opacity: 1, y: 0,  scale: 1    }}
            exit={{    opacity: 0, y: 8,  scale: 0.95 }}
            transition={{ duration: 0.2, ease: 'easeOut' }}
            className={`px-4 py-2.5 rounded-xl text-xs font-medium shadow-lg border backdrop-blur-sm ${
              t.type === 'error'   ? 'bg-red-50    border-red-200    text-red-700'
            : t.type === 'success' ? 'bg-white      border-border     text-foreground'
            :                        'bg-white      border-border     text-muted-foreground'
            }`}
          >
            {t.msg}
          </motion.div>
        ))}
      </AnimatePresence>
    </div>
  )
}
