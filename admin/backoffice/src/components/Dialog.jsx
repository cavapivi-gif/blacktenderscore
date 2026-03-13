import { useEffect } from 'react'
import { motion, AnimatePresence } from 'motion/react'
import { Xmark } from 'iconoir-react'

const SIZE_CLS = {
  sm: 'max-w-sm',
  md: 'max-w-xl',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
}

/**
 * Modal Dialog centré — composant réutilisable.
 * @param {boolean}        open
 * @param {function}       onClose
 * @param {string}         [title]       — titre affiché dans le header
 * @param {string}         [description] — sous-titre / description courte
 * @param {ReactNode}      [footer]      — contenu du footer (actions)
 * @param {'sm'|'md'|'lg'|'xl'} [size='md']
 * @param {ReactNode}      children      — contenu principal
 * @param {string}         [className]   — classes supplémentaires sur le panel
 */
export function Dialog({ open, onClose, title, description, footer, size = 'md', children, className = '' }) {
  // Fermeture via Escape
  useEffect(() => {
    if (!open) return
    const onKey = e => { if (e.key === 'Escape') onClose?.() }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, onClose])

  return (
    <AnimatePresence>
      {open && (
        <div className="fixed inset-0 z-50 flex items-start justify-center p-4 pt-16 overflow-y-auto">
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.15 }}
            className="fixed inset-0 bg-black/40 backdrop-blur-sm"
            onClick={onClose}
          />

          {/* Panel */}
          <motion.div
            initial={{ opacity: 0, scale: 0.96, y: 8 }}
            animate={{ opacity: 1, scale: 1,    y: 0 }}
            exit={{    opacity: 0, scale: 0.96, y: 8 }}
            transition={{ duration: 0.18, ease: [0.22, 1, 0.36, 1] }}
            className={[
              'relative bg-card border shadow-2xl rounded-2xl w-full',
              SIZE_CLS[size] ?? SIZE_CLS.md,
              className,
            ].join(' ')}
            onClick={e => e.stopPropagation()}
          >
            {/* Header */}
            {(title || onClose) && (
              <div className="flex items-start justify-between gap-3 px-5 pt-5">
                <div className="flex-1 min-w-0">
                  {title && (
                    <h2 className="text-sm font-semibold text-foreground leading-tight">{title}</h2>
                  )}
                  {description && (
                    <p className="text-xs text-muted-foreground mt-0.5 leading-relaxed">{description}</p>
                  )}
                </div>
                {onClose && (
                  <button
                    onClick={onClose}
                    className="p-1.5 rounded-lg hover:bg-muted/70 text-muted-foreground hover:text-foreground transition-colors shrink-0 -mr-1 -mt-1"
                  >
                    <Xmark width={14} height={14} strokeWidth={2} />
                  </button>
                )}
              </div>
            )}

            {/* Body */}
            <div className={title ? 'px-5 py-4' : 'p-5'}>
              {children}
            </div>

            {/* Footer */}
            {footer && (
              <div className="px-5 pb-5 -mt-1 flex justify-end gap-2 flex-wrap">
                {footer}
              </div>
            )}
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  )
}
