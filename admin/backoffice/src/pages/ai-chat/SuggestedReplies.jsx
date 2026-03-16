import { motion } from 'motion/react'
import { getSuggestions } from './chatUtils'

// ─────────────────────────────────────────────────────────────────────────────
// Suggested replies — questions de relance contextuelles
// ─────────────────────────────────────────────────────────────────────────────

export function SuggestedReplies({ intents, onSend }) {
  const suggestions = getSuggestions(intents)
  return (
    <motion.div
      initial={{ opacity: 0, y: 6 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.25, delay: 0.35, ease: 'easeOut' }}
      className="flex flex-wrap gap-2 mt-2 pl-10"
    >
      {suggestions.map((s, i) => (
        <button
          key={i}
          onClick={() => onSend(s)}
          className="text-xs px-3 py-1.5 rounded-full border border-border/70 text-muted-foreground hover:text-foreground hover:border-foreground/30 hover:bg-accent transition-all"
        >
          {s}
        </button>
      ))}
    </motion.div>
  )
}
