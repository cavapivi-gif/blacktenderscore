import { motion } from 'motion/react'
import { getProvider } from '../../lib/aiProviders'
import AiProviderIcon from '../../components/AiProviderIcon'
import { SUGGESTIONS } from './chatUtils'

// ─────────────────────────────────────────────────────────────────────────────
// Empty state / Welcome screen
// ─────────────────────────────────────────────────────────────────────────────

export function WelcomeScreen({ activeProvider, onSuggestion }) {
  const cfg = getProvider(activeProvider)
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0  }}
      transition={{ duration: 0.35, ease: 'easeOut' }}
      className="flex flex-col items-center justify-center h-full gap-8 py-10 px-6"
    >
      <div className="flex flex-col items-center gap-3">
        <motion.div
          animate={{ scale: [1, 1.03, 1] }}
          transition={{ duration: 3, repeat: Infinity, ease: 'easeInOut' }}
          className="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg"
          style={{ background: cfg.accent + '18', border: `1.5px solid ${cfg.accent}33` }}
        >
          <AiProviderIcon iconKey={cfg.iconKey} variant="Color" size={32} />
        </motion.div>
        <div className="text-center">
          <h2 className="text-base font-semibold text-foreground">Conseiller IA — BlackTenders</h2>
          <p className="text-xs text-muted-foreground mt-0.5">Analyse commerciale · Stratégie · Insights</p>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 w-full max-w-2xl">
        {SUGGESTIONS.map((text, i) => (
          <motion.button
            key={i}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0  }}
            transition={{ duration: 0.25, delay: i * 0.05, ease: 'easeOut' }}
            onClick={() => onSuggestion(text)}
            className="text-left text-xs px-4 py-3 rounded-xl border border-border bg-card hover:bg-accent hover:border-foreground/10 transition-all leading-snug text-muted-foreground hover:text-foreground shadow-sm"
          >
            {text}
          </motion.button>
        ))}
      </div>
    </motion.div>
  )
}
