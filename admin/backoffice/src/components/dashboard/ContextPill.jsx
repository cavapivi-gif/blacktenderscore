import { cn } from '../../lib/utils'

/** Pill cliquable dans le header (produits, clients, DB, statut API) */
export function ContextPill({ label, value, onClick, variant }) {
  const variantCls =
    variant === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-400' :
    variant === 'error'   ? 'border-red-200 bg-red-50 text-red-600 dark:bg-red-950/30 dark:border-red-800 dark:text-red-400' :
    onClick               ? 'border-border bg-background hover:bg-accent text-foreground cursor-pointer' :
                            'border-transparent bg-transparent text-muted-foreground cursor-default'
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn('flex items-center gap-1.5 px-2.5 py-1 rounded-md border text-xs transition-colors', variantCls)}
    >
      <span className="font-semibold tabular-nums">{value}</span>
      {label && <span className="opacity-75">{label}</span>}
    </button>
  )
}
