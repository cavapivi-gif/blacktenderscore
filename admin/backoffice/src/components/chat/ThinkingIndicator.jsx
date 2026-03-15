import { memo } from 'react'
import { motion } from 'motion/react'
import Lottie from 'lottie-react'
import { getProvider } from '../../lib/aiProviders'
import AiProviderIcon from '../AiProviderIcon'

export const ThinkingIndicator = memo(function ThinkingIndicator({ provider }) {
  const cfg = getProvider(provider)
  return (
    <motion.div
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      className="flex gap-3 items-start"
    >
      <div
        className="w-7 h-7 rounded-full flex items-center justify-center shrink-0 shadow-sm"
        style={{ background: cfg.accent + '22', border: `1.5px solid ${cfg.accent}44` }}
      >
        {cfg.lottie ? (
          <Lottie animationData={cfg.lottie} loop className="w-5 h-5" />
        ) : (
          <AiProviderIcon iconKey={cfg.iconKey} variant="Color" size={15} />
        )}
      </div>
      <div className="bg-card border border-border/70 rounded-2xl rounded-tl-sm px-4 py-3.5 shadow-sm">
        <div className="flex items-center gap-1.5">
          {[0,1,2].map(i => (
            <motion.span key={i}
              className="w-1.5 h-1.5 rounded-full"
              style={{ background: cfg.accent }}
              animate={{ opacity: [0.3, 1, 0.3], scale: [0.7, 1, 0.7] }}
              transition={{ duration: 1.2, repeat: Infinity, delay: i * 0.2, ease: 'easeInOut' }}
            />
          ))}
        </div>
      </div>
    </motion.div>
  )
})
