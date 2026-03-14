import { useState } from 'react'
import { motion } from 'motion/react'
import Lottie from 'lottie-react'
import { Copy, Check, ShareAndroid } from 'iconoir-react'
import { getProvider } from '../../lib/aiProviders'
import AiProviderIcon from '../../components/AiProviderIcon'
import { Markdown } from './Markdown'
import { StatsWidget } from './StatsWidget'
import { SuggestedReplies } from './SuggestedReplies'
import { useTypewriter } from './useTypewriter'

// ─────────────────────────────────────────────────────────────────────────────
// Message components
// ─────────────────────────────────────────────────────────────────────────────

export function CopyBtn({ text, onCopy }) {
  const [done, setDone] = useState(false)
  function copy() {
    navigator.clipboard.writeText(text).then(() => {
      setDone(true); setTimeout(() => setDone(false), 2000)
      onCopy?.()
    })
  }
  return (
    <button onClick={copy} title="Copier"
      className="p-1 rounded hover:bg-muted/80 text-muted-foreground hover:text-foreground transition-colors opacity-0 group-hover:opacity-100"
    >
      {done
        ? <Check width={12} height={12} strokeWidth={2} className="text-emerald-500" />
        : <Copy  width={12} height={12} strokeWidth={1.5} />}
    </button>
  )
}

export function UserMsg({ msg, participants, currentUserId }) {
  // Normalise les IDs en string pour éviter number/string mismatch (PHP ARRAY_A vs JSON)
  const myId    = currentUserId != null ? String(currentUserId) : null
  const msgUid  = msg.user_id   != null ? String(msg.user_id)   : null

  // Message de l'utilisateur courant si pas de user_id (message local) ou user_id == moi
  const isMe        = !msgUid || msgUid === myId
  const participant = participants?.find(p => String(p.user_id) === (isMe ? myId : msgUid))
  const bubbleColor = participant?.color ?? '#dbd2c0'

  return (
    <motion.div
      initial={{ opacity: 0, y: 10, scale: 0.98 }}
      animate={{ opacity: 1, y: 0,  scale: 1    }}
      transition={{ duration: 0.18, ease: 'easeOut' }}
      className="flex justify-end gap-3"
    >
      <div className="max-w-[76%] space-y-1.5">
        {/* Nom + avatar si c'est un autre participant */}
        {!isMe && participant && (
          <div className="flex items-center gap-1.5 justify-end mb-0.5">
            <span className="text-[10px] text-muted-foreground font-medium">{participant.display_name}</span>
            <img src={participant.avatar} alt={participant.display_name}
              className="w-4 h-4 rounded-full border border-background shrink-0" />
          </div>
        )}
        {msg.images?.length > 0 && (
          <div className="flex gap-1.5 justify-end flex-wrap">
            {msg.images.map((img, i) => (
              <img key={i} src={`data:${img.type};base64,${img.data}`}
                className="h-20 w-20 object-cover rounded-xl border border-white/20 shadow-sm" alt="" />
            ))}
          </div>
        )}
        <div
          className="rounded-2xl rounded-tr-sm px-4 py-3 text-sm leading-relaxed text-foreground"
          style={{ backgroundColor: bubbleColor }}
        >
          {msg.content}
        </div>
      </div>
    </motion.div>
  )
}

export function AssistantMsg({ msg, streaming = false, onCopy, onShare, isLast = false, onSend }) {
  const cfg         = getProvider(msg.provider || 'anthropic')
  const displayText = useTypewriter(msg.content, streaming)
  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0  }}
      transition={{ duration: 0.22, ease: 'easeOut' }}
      className="flex gap-3 group"
    >
      {/* Avatar */}
      <div
        className="w-7 h-7 rounded-full flex items-center justify-center shrink-0 mt-0.5 shadow-sm"
        style={{ background: cfg.accent + '22', border: `1.5px solid ${cfg.accent}44` }}
      >
        <AiProviderIcon iconKey={cfg.iconKey} variant="Color" size={15} />
      </div>

      {/* Bubble */}
      <div className="flex-1 min-w-0">
        <div className="bg-card border border-border/70 rounded-2xl rounded-tl-sm px-4 py-3.5 shadow-sm">
          {streaming
            ? (
              <div className="text-base leading-relaxed whitespace-pre-wrap">
                {displayText}
                <motion.span
                  animate={{ opacity: [1, 0, 1] }}
                  transition={{ duration: 0.6, repeat: Infinity }}
                  className="inline-block w-[2px] h-[1em] bg-foreground/50 ml-0.5 rounded-sm align-text-bottom"
                />
              </div>
            )
            : <Markdown content={msg.content} />}
        </div>

        {/* Actions + KPIs */}
        {!streaming && msg.content && (
          <>
            <div className="flex items-center gap-1 mt-1.5 pl-1">
              <CopyBtn text={msg.content} onCopy={onCopy} />
              {onShare && (
                <button
                  onClick={() => onShare(msg.content)}
                  title="Partager cette réponse"
                  className="p-1 rounded hover:bg-muted/80 text-muted-foreground hover:text-foreground transition-colors opacity-0 group-hover:opacity-100"
                >
                  <ShareAndroid width={12} height={12} strokeWidth={1.5} />
                </button>
              )}
              <span className="text-[10px] text-muted-foreground/50 self-center ml-1">{cfg.label}</span>
            </div>
            {msg.statsData && (
              <StatsWidget
                data={msg.statsData.data}
                intents={msg.statsData.intents}
                range={msg.statsData.range}
              />
            )}
            {isLast && onSend && (
              <SuggestedReplies
                intents={msg.statsData?.intents ?? []}
                onSend={onSend}
              />
            )}
          </>
        )}
      </div>
    </motion.div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Thinking indicator
// ─────────────────────────────────────────────────────────────────────────────

export function ThinkingIndicator({ provider }) {
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
}
