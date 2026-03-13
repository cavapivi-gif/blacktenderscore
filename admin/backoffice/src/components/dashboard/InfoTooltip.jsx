import { Tooltip } from '../Tooltip'

/**
 * Icône d'aide (?) avec tooltip contextuel — à placer à côté d'un titre de chart.
 * Utilise le système Tooltip existant (fixed position, auto-positionné).
 *
 * @param {string} text  Texte d'explication affiché au survol
 */
export function InfoTooltip({ text }) {
  if (!text) return null
  return (
    <Tooltip content={text} className="normal-case tracking-normal font-normal">
      <span
        role="img"
        aria-label="aide"
        className="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full text-[9px] font-bold leading-none text-muted-foreground/60 border border-muted-foreground/25 hover:text-foreground hover:border-foreground/40 cursor-help select-none transition-colors ml-1.5 shrink-0"
      >
        ?
      </span>
    </Tooltip>
  )
}
