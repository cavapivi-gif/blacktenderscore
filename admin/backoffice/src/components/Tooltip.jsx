import { useState, useRef, useCallback, useEffect } from 'react'
import { cn } from '../lib/utils'

/* ── TooltipProvider ─────────────────────────────────────────────────────── */

export function TooltipProvider({ children }) {
  return children
}

/* ── Tooltip ─────────────────────────────────────────────────────────────── */

export function Tooltip({ content, className, children }) {
  const [visible, setVisible] = useState(false)
  const [position, setPosition] = useState({ top: 0, left: 0 })
  const triggerRef = useRef(null)
  const tooltipRef = useRef(null)

  const updatePosition = useCallback(() => {
    const trigger = triggerRef.current
    const tip = tooltipRef.current
    if (!trigger || !tip) return

    const triggerRect = trigger.getBoundingClientRect()
    const tipRect = tip.getBoundingClientRect()

    let left = triggerRect.left + triggerRect.width / 2 - tipRect.width / 2
    const top = triggerRect.top - tipRect.height - 8

    // Keep tooltip within viewport horizontally
    const padding = 8
    if (left < padding) left = padding
    if (left + tipRect.width > window.innerWidth - padding) {
      left = window.innerWidth - padding - tipRect.width
    }

    setPosition({ top, left })
  }, [])

  useEffect(() => {
    if (visible) updatePosition()
  }, [visible, updatePosition])

  const show = useCallback(() => setVisible(true), [])
  const hide = useCallback(() => setVisible(false), [])

  if (!content) return children

  return (
    <>
      <div
        ref={triggerRef}
        onMouseEnter={show}
        onMouseLeave={hide}
        onFocus={show}
        onBlur={hide}
        className="inline-flex"
      >
        {children}
      </div>

      <div
        ref={tooltipRef}
        role="tooltip"
        style={{
          position: 'fixed',
          top: `${position.top}px`,
          left: `${position.left}px`,
          pointerEvents: 'none',
        }}
        className={cn(
          'z-50 max-w-[250px] break-words rounded-md bg-foreground px-2.5 py-1.5 text-xs text-background shadow-lg transition-opacity duration-150',
          visible ? 'opacity-100' : 'opacity-0 invisible',
          className,
        )}
      >
        {content}
        {/* Arrow / caret */}
        <span
          className="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-foreground"
          aria-hidden="true"
        />
      </div>
    </>
  )
}
