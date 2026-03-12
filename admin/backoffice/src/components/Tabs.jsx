import { createContext, useContext, useState, useCallback } from 'react'
import { cn } from '../lib/utils'

/* ── Context ─────────────────────────────────────────────────────────────── */

const TabsContext = createContext(null)

function useTabsContext() {
  const ctx = useContext(TabsContext)
  if (!ctx) throw new Error('Tabs compound components must be used inside <Tabs>')
  return ctx
}

/* ── Tabs (root) ─────────────────────────────────────────────────────────── */

export function Tabs({
  defaultValue,
  value: controlledValue,
  onValueChange,
  className,
  children,
  ...props
}) {
  const [uncontrolled, setUncontrolled] = useState(defaultValue ?? '')

  const isControlled = controlledValue !== undefined
  const activeValue = isControlled ? controlledValue : uncontrolled

  const setActiveValue = useCallback(
    (v) => {
      if (!isControlled) setUncontrolled(v)
      onValueChange?.(v)
    },
    [isControlled, onValueChange],
  )

  return (
    <TabsContext.Provider value={{ activeValue, setActiveValue }}>
      <div className={cn('w-full', className)} {...props}>
        {children}
      </div>
    </TabsContext.Provider>
  )
}

/* ── TabsList ────────────────────────────────────────────────────────────── */

export function TabsList({ className, children, ...props }) {
  return (
    <div
      role="tablist"
      className={cn(
        'inline-flex items-center gap-1 rounded-lg bg-muted p-1',
        className,
      )}
      {...props}
    >
      {children}
    </div>
  )
}

/* ── TabsTrigger ─────────────────────────────────────────────────────────── */

export function TabsTrigger({ value, className, children, ...props }) {
  const { activeValue, setActiveValue } = useTabsContext()
  const isActive = activeValue === value

  return (
    <button
      role="tab"
      type="button"
      aria-selected={isActive}
      data-state={isActive ? 'active' : 'inactive'}
      className={cn(
        'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
        isActive
          ? 'bg-primary text-primary-foreground shadow-sm'
          : 'text-muted-foreground hover:text-foreground',
        className,
      )}
      onClick={() => setActiveValue(value)}
      {...props}
    >
      {children}
    </button>
  )
}

/* ── TabsContent ─────────────────────────────────────────────────────────── */

export function TabsContent({ value, className, children, ...props }) {
  const { activeValue } = useTabsContext()

  if (activeValue !== value) return null

  return (
    <div
      role="tabpanel"
      data-state={activeValue === value ? 'active' : 'inactive'}
      className={cn('mt-2 focus-visible:outline-none', className)}
      {...props}
    >
      {children}
    </div>
  )
}
