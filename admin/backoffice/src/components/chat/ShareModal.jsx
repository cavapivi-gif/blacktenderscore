import { useState } from 'react'
import { Copy, Check, Link } from 'iconoir-react'
import { api } from '../../lib/api'
import { Dialog } from '../Dialog'

// ─────────────────────────────────────────────────────────────────────────────
// Share Modal — conversation entière ou réponse individuelle
// ─────────────────────────────────────────────────────────────────────────────

export function ShareModal({ mode, conv, msgContent, onClose, onToast }) {
  const [loading,   setLoading]   = useState(false)
  const [shareUrl,  setShareUrl]  = useState('')
  const [copied,    setCopied]    = useState(false)

  async function generate() {
    setLoading(true)
    try {
      const payload = mode === 'msg'
        ? {
            title:    `Réponse IA — ${conv?.title ?? 'Conversation'}`,
            provider: conv?.provider ?? 'anthropic',
            messages: [{ role: 'assistant', content: msgContent, provider: conv?.provider }],
          }
        : {
            title:    conv.title,
            provider: conv.provider,
            messages: conv.messages,
          }
      const res = await api.shareChat(payload)
      setShareUrl(res.url)
    } catch (e) {
      onToast?.(e.message || 'Erreur lors de la génération du lien', 'error')
      onClose()
    } finally {
      setLoading(false)
    }
  }

  function copyUrl() {
    navigator.clipboard.writeText(shareUrl).then(() => {
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
      onToast?.('Lien copié !')
    })
  }

  return (
    <Dialog
      open
      onClose={onClose}
      title={mode === 'msg' ? 'Partager cette réponse' : 'Partager la conversation'}
      description="Génère un lien d'accès pour un administrateur connecté."
      size="md"
    >
      {!shareUrl ? (
        <div className="space-y-4">
          {/* Preview */}
          <div className="bg-muted/40 rounded-xl p-3 max-h-40 overflow-y-auto">
            {mode === 'msg' ? (
              <p className="text-xs text-muted-foreground leading-relaxed line-clamp-6">{msgContent}</p>
            ) : (
              <div className="space-y-1.5">
                {(conv?.messages ?? []).slice(0, 4).map((m, i) => (
                  <div key={i} className={`text-[11px] leading-relaxed ${m.role === 'user' ? 'text-foreground/70' : 'text-muted-foreground'}`}>
                    <span className="font-medium uppercase tracking-wide text-[9px] opacity-50">{m.role === 'user' ? 'Vous' : 'IA'}</span>
                    <p className="truncate">{m.content?.slice(0, 120)}{(m.content?.length ?? 0) > 120 ? '…' : ''}</p>
                  </div>
                ))}
                {(conv?.messages?.length ?? 0) > 4 && (
                  <p className="text-[10px] text-muted-foreground/50">+ {conv.messages.length - 4} autres messages</p>
                )}
              </div>
            )}
          </div>

          <button
            onClick={generate}
            disabled={loading}
            className="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors"
          >
            {loading ? (
              <span className="w-4 h-4 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin" />
            ) : (
              <Link width={14} height={14} strokeWidth={2} />
            )}
            {loading ? 'Génération…' : 'Générer le lien'}
          </button>
        </div>
      ) : (
        <div className="space-y-4">
          <div className="flex items-center gap-2 bg-muted/40 rounded-xl px-3 py-2.5 border border-border">
            <Link width={13} height={13} strokeWidth={1.5} className="text-muted-foreground shrink-0" />
            <span className="flex-1 text-xs font-mono text-foreground truncate">{shareUrl}</span>
          </div>
          <button
            onClick={copyUrl}
            className={`w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all ${
              copied
                ? 'bg-emerald-50 border border-emerald-200 text-emerald-700'
                : 'bg-primary text-primary-foreground hover:bg-primary/90'
            }`}
          >
            {copied
              ? <><Check width={14} height={14} strokeWidth={2.5} /> Copié !</>
              : <><Copy  width={14} height={14} strokeWidth={1.5} /> Copier le lien</>
            }
          </button>
          <p className="text-[11px] text-muted-foreground/60 text-center leading-relaxed">
            Ce lien est accessible aux administrateurs connectés. Il expire après 30 jours.
          </p>
        </div>
      )}
    </Dialog>
  )
}
