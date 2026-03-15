import { useState, useRef, useEffect, useCallback } from 'react'
import { motion, AnimatePresence } from 'motion/react'
import { ShareAndroid } from 'iconoir-react'

import { api } from '../lib/api'
import { today, daysAgo } from '../lib/utils'
import { ChatSharePanel } from '../components/chat/ChatSharePanel'
import { syncChat, deleteChat } from '../lib/chatApi'
import { useSearchParams } from 'react-router-dom'
import { useConversations } from '../hooks/useConversations'
import { useAiChat } from '../hooks/useAiChat'
import { usePusherSync } from '../hooks/usePusherSync'
import { RainbowButton } from '../components/ui/rainbow-button'
import { ChatInputArea } from '../components/chat/ChatInputArea'

import {
  useToast,
  ToastStack,
  ConvSidebar,
  WelcomeScreen,
  UserMsg,
  AssistantMsg,
  ThinkingIndicator,
  ShareModal,
  parseMessageDate,
  detectDataIntent,
  buildDataContext,
} from './ai-chat'

export default function AIChat() {
  const {
    conversations, grouped, activeId, activeConv, dbLoading,
    setActiveId, create, updateMessages, updateProvider, remove, rename, clearAll, loadRemoteMessages, refreshMessages,
  } = useConversations()
  const [searchParams, setSearchParams] = useSearchParams()

  // ── Sidebar resize ──────────────────────────────────────────────────────────
  const [sidebarWidth, setSidebarWidth] = useState(280)
  const isResizing = useRef(false)

  const startResize = useCallback((e) => {
    isResizing.current = true
    const startX = e.clientX
    const startW = sidebarWidth
    function onMove(ev) {
      if (!isResizing.current) return
      setSidebarWidth(w => Math.min(520, Math.max(200, startW + ev.clientX - startX)))
    }
    function onUp() {
      isResizing.current = false
      document.removeEventListener('mousemove', onMove)
      document.removeEventListener('mouseup', onUp)
      document.body.style.cursor = ''
      document.body.style.userSelect = ''
    }
    document.body.style.cursor = 'col-resize'
    document.body.style.userSelect = 'none'
    document.addEventListener('mousemove', onMove)
    document.addEventListener('mouseup', onUp)
  }, [sidebarWidth])

  // ── UI state ─────────────────────────────────────────────────────────────────
  const [input,          setInput]          = useState('')
  const [images,         setImages]         = useState([])
  const [error,          setError]          = useState(null)
  const [filterParams,   setFilterParams]   = useState({ from: daysAgo(365), to: today() })
  const [activeProvider, setActiveProvider] = useState('anthropic')
  const [availProviders, setAvailProviders] = useState({ anthropic: false, openai: false, gemini: false })
  const [shareModal,     setShareModal]     = useState({ open: false, mode: 'conv', msgContent: '' })
  const [sharePanelOpen, setSharePanelOpen] = useState(false)
  const [sharedLoading,  setSharedLoading]  = useState(false)

  const lastFailedRef = useRef(null)
  const statsDataRef  = useRef(null)
  const sendingRef    = useRef(false)
  const convIdRef     = useRef(null) // capture convId au moment du stream

  const { toasts, push: toast } = useToast()
  const scrollRef = useRef(null)
  const messages  = activeConv?.messages ?? []

  // ── Vercel AI SDK stream (remplace le reader loop SSE manuel) ────────────────
  const { stream, stop, isLoading, streamText, showThinking } = useAiChat({
    // Pas de useCallback nécessaire : useAiChat stocke ces callbacks en refs,
    // ce qui garantit que la version fraîche (avec conversations à jour) est toujours appelée.
    onFinish: (content, provider) => {
      sendingRef.current = false
      const convId = convIdRef.current
      if (!convId || !content) return
      const conv     = conversations.find(c => c.id === convId)
      const prevMsgs = conv?.messages ?? []
      const finalMsg = {
        role: 'assistant', content, provider,
        id: `m_${Date.now()}`,
        statsData: statsDataRef.current,
      }
      const finalMsgs = [...prevMsgs, finalMsg]
      updateMessages(convId, finalMsgs)
      if (conv?.db_id) syncChat({ ...conv, messages: finalMsgs }).catch(() => {})
      lastFailedRef.current = null
      statsDataRef.current  = null
    },

    onError: (err) => {
      sendingRef.current = false
      if (err.name !== 'AbortError') {
        setError(err.message || "Erreur de connexion à l'IA.")
      }
    },
  })

  // ── Load AI provider status ──────────────────────────────────────────────────
  useEffect(() => {
    api.aiStatus().then(res => {
      setAvailProviders(res.providers ?? {})
      setActiveProvider(res.active ?? 'anthropic')
    }).catch(() => setAvailProviders({ anthropic: true }))
  }, [])

  useEffect(() => {
    if (activeConv?.provider) setActiveProvider(activeConv.provider)
  }, [activeId])

  // ── Sync URL ↔ activeId ──────────────────────────────────────────────────────
  useEffect(() => {
    setSearchParams(prev => {
      const next = new URLSearchParams(prev)
      if (activeId) next.set('chat', activeId)
      else next.delete('chat')
      next.delete('share')
      return next
    }, { replace: true })
  }, [activeId])

  // ── Charge messages remote à la sélection ────────────────────────────────────
  useEffect(() => {
    if (!activeConv || dbLoading) return
    if (activeConv.remote || (activeConv.db_id && !activeConv.participants?.length)) {
      loadRemoteMessages(activeConv.id)
    }
  }, [activeId, dbLoading])

  // ── Collaboration temps réel ───────────────────────────────────────────────
  // Pusher si configuré (window.btBackoffice.pusher_key présent), sinon polling 3s fallback.
  const pusherEnabled = !!(window.btBackoffice?.pusher_key) && !!(activeConv?.db_id)
  const needsSync     = !!(activeConv?.db_id) && (
    activeConv.permission !== 'owner' ||
    (activeConv.participants?.length ?? 0) > 1 ||
    !activeConv.participants?.length
  )

  usePusherSync(
    activeConv?.id ?? null,
    useCallback(() => { if (activeConv?.id) refreshMessages(activeConv.id) }, [activeConv?.id, refreshMessages]),
    pusherEnabled && needsSync,
  )

  // Fallback polling quand Pusher n'est pas configuré
  useEffect(() => {
    if (!needsSync || pusherEnabled) return
    const id = activeConv.id
    const interval = setInterval(() => refreshMessages(id), 3000)
    return () => clearInterval(interval)
  }, [activeId, activeConv?.db_id, activeConv?.participants?.length, pusherEnabled, needsSync, refreshMessages])

  // ── Restauration depuis URL ──────────────────────────────────────────────────
  useEffect(() => {
    const shareUuid   = searchParams.get('share')
    const chatId      = searchParams.get('chat')
    const legacyToken = searchParams.get('bt_chat')

    if (shareUuid) {
      import('../lib/chatApi').then(({ getChat }) => {
        setSharedLoading(true)
        getChat(shareUuid)
          .then(() => { setActiveId(shareUuid); toast('Conversation partagée chargée') })
          .catch(() => toast('Lien de partage introuvable ou accès refusé', 'error'))
          .finally(() => setSharedLoading(false))
      }).catch(() => toast('Erreur de chargement', 'error'))
      return
    }
    if (chatId) { setActiveId(chatId); return }
    if (legacyToken) {
      setSharedLoading(true)
      api.getSharedChat(legacyToken)
        .then(data => {
          const id = create(data.provider ?? 'anthropic', null)
          updateMessages(id, data.messages ?? [], data.title ?? 'Conversation partagée')
          toast('Conversation partagée chargée')
        })
        .catch(() => toast('Lien de partage introuvable ou expiré', 'error'))
        .finally(() => setSharedLoading(false))
    }
  }, [])

  // ── Auto-scroll ──────────────────────────────────────────────────────────────
  useEffect(() => {
    const el = scrollRef.current
    if (!el) return
    requestAnimationFrame(() => { el.scrollTop = el.scrollHeight })
  }, [messages, streamText, showThinking, activeId])

  // ── Send ─────────────────────────────────────────────────────────────────────
  const send = useCallback(async (content, attachedImages = []) => {
    if (!content.trim() || isLoading || sendingRef.current) return
    sendingRef.current = true
    setError(null)

    let convId = activeId
    if (!convId) convId = create(activeProvider, filterParams)
    convIdRef.current = convId

    const userMsg = {
      role: 'user', content, images: attachedImages,
      id: `m_${Date.now()}`,
      user_id: window.btBackoffice?.current_user?.id,
    }
    const updatedMsgs = [...messages, userMsg]
    const autoTitle   = messages.length === 0 ? content.slice(0, 50) + (content.length > 50 ? '…' : '') : null
    updateMessages(convId, updatedMsgs, autoTitle)
    setInput(''); setImages([])

    // ── Intent detection + stats fetch ─────────────────────────────────────
    const intents     = detectDataIntent(content)
    const parsedRange = parseMessageDate(content)
    const statsRange  = parsedRange ?? filterParams
    let statsData     = null
    let contextBlock  = ''

    if (intents.length > 0) {
      try {
        const res = await api.bookingsStats({ from: statsRange.from, to: statsRange.to, granularity: 'month' })
        statsData    = res
        contextBlock = buildDataContext(res, statsRange.from, statsRange.to)
      } catch {}
    }

    statsDataRef.current = statsData ? { data: statsData, intents, range: statsRange } : null

    // Contenu envoyé à l'IA : contexte données + message user
    const aiContent = contextBlock ? `${contextBlock}\n\n---\n${content}` : content

    // stream() → useAiChat → useChat → PHP admin-ajax → Anthropic/OpenAI/etc.
    lastFailedRef.current = { content, images: attachedImages }
    await stream(updatedMsgs, aiContent, activeProvider, statsRange)
  }, [messages, isLoading, filterParams, activeProvider, activeId, create, updateMessages, stream])

  function handleSubmit(e)  { e?.preventDefault(); send(input, images) }
  function handleProviderChange(key) {
    setActiveProvider(key)
    if (activeId) updateProvider(activeId, key)
  }
  function handleNewConv() { setActiveId(null); setInput(''); setImages([]); setError(null) }

  const hasMessages = messages.length > 0 || isLoading

  return (
    <div className="flex" style={{ height: 'calc(100vh - 56px)' }}>

      {/* ── Sidebar (redimensionnable) ──────────────────────────────────────── */}
      <div style={{ width: sidebarWidth, minWidth: sidebarWidth }} className="border-r bg-background overflow-hidden flex flex-col shrink-0">
        <ConvSidebar
          grouped={grouped}
          activeId={activeId}
          onSelect={id => { setActiveId(id); setError(null) }}
          onNew={handleNewConv}
          onDelete={id => {
            const conv = conversations.find(c => c.id === id)
            if (conv?.db_id) deleteChat(conv.db_id).catch(() => {})
            remove(id)
            toast('Conversation supprimée')
          }}
          onRename={rename}
          onClear={() => { clearAll(); toast('Historique effacé') }}
        />
      </div>

      {/* Poignée resize */}
      <div onMouseDown={startResize} className="w-1 shrink-0 hover:bg-border/60 active:bg-border transition-colors cursor-col-resize" />

      {/* ── Main chat ─────────────────────────────────────────────────────────── */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">

        {/* Toolbar */}
        <div className="flex items-center gap-3 px-5 py-2.5 border-b bg-background shrink-0">
          {activeConv?.title && (
            <span className="text-xs font-medium text-foreground/70 truncate max-w-[180px] shrink-0" title={activeConv.title}>
              {activeConv.title}
            </span>
          )}
          {activeConv?.title && <span className="text-border shrink-0">·</span>}

          <span className="text-xs text-muted-foreground shrink-0">Période :</span>
          <input type="date" value={filterParams.from}
            onChange={e => setFilterParams(p => ({ ...p, from: e.target.value }))}
            className="h-7 rounded-md border border-input bg-transparent px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" />
          <span className="text-xs text-muted-foreground">→</span>
          <input type="date" value={filterParams.to}
            onChange={e => setFilterParams(p => ({ ...p, to: e.target.value }))}
            className="h-7 rounded-md border border-input bg-transparent px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" />

          <div className="ml-auto flex items-center gap-2 shrink-0">
            {activeConv && (activeConv.participants?.length ?? 0) > 0 && (
              <div className="flex -space-x-1.5 items-center">
                {(activeConv.participants ?? []).slice(0, 5).map(p => (
                  <img key={p.user_id} src={p.avatar} alt={p.display_name}
                    title={`${p.display_name}${p.permission === 'owner' ? ' (propriétaire)' : p.permission === 'write' ? ' (écriture)' : ' (lecture)'}`}
                    className="w-6 h-6 rounded-full border-2 border-background shadow-sm"
                    style={{ outline: `2px solid ${p.color ?? '#e5e5e5'}` }} />
                ))}
                {(activeConv.participants?.length ?? 0) > 5 && (
                  <span className="w-6 h-6 rounded-full bg-muted border-2 border-background text-[9px] font-medium text-muted-foreground flex items-center justify-center">
                    +{activeConv.participants.length - 5}
                  </span>
                )}
              </div>
            )}

            {activeConv && (
              <RainbowButton onClick={handleNewConv} title="Nouvelle conversation" className="text-xs" style={{ height: '30px' }}>
                + Nouveau
              </RainbowButton>
            )}

            {activeConv && messages.length > 0 && (
              <RainbowButton onClick={() => setSharePanelOpen(true)} title="Partager" className="text-xs" style={{ height: '30px' }}>
                <ShareAndroid width={12} height={12} strokeWidth={2} />
                <span className="hidden sm:inline">Partager</span>
              </RainbowButton>
            )}
          </div>
        </div>

        {/* Messages */}
        <div ref={scrollRef} className="flex-1 overflow-y-auto px-6 py-6" style={{ backgroundColor: '#F7F5F1' }}>
          {sharedLoading ? (
            <div className="flex items-center justify-center h-full gap-3 text-sm text-muted-foreground">
              <span className="w-4 h-4 border-2 border-muted border-t-foreground/50 rounded-full animate-spin" />
              Chargement de la conversation…
            </div>
          ) : (
            <AnimatePresence>
              {!hasMessages ? (
                <WelcomeScreen key="welcome" activeProvider={activeProvider} onSuggestion={text => send(text, [])} />
              ) : (
                <motion.div key="chat" className="space-y-6 max-w-3xl mx-auto">
                  {(() => {
                    const lastAsstIdx = messages.map(m => m.role).lastIndexOf('assistant')
                    return messages.map((msg, idx) => (
                      msg.role === 'user'
                        ? <UserMsg key={msg.id} msg={msg} participants={activeConv?.participants} currentUserId={window.btBackoffice?.current_user?.id} />
                        : <AssistantMsg
                            key={msg.id}
                            msg={msg}
                            isLast={idx === lastAsstIdx && !isLoading}
                            onSend={idx === lastAsstIdx && !isLoading ? send : undefined}
                            onCopy={() => toast('Copié dans le presse-papier')}
                            onShare={content => setShareModal({ open: true, mode: 'msg', msgContent: content })}
                          />
                    ))
                  })()}

                  <AnimatePresence>
                    {showThinking && <ThinkingIndicator key="think" provider={activeProvider} />}
                  </AnimatePresence>

                  <AnimatePresence>
                    {isLoading && !showThinking && streamText && (
                      <AssistantMsg
                        key="stream"
                        msg={{ content: streamText, provider: activeProvider, id: 'stream' }}
                        streaming
                      />
                    )}
                  </AnimatePresence>
                </motion.div>
              )}
            </AnimatePresence>
          )}
        </div>

        {/* Input */}
        <ChatInputArea
          input={input}
          onInputChange={e => setInput(e.target.value)}
          onSubmit={handleSubmit}
          streaming={isLoading}
          images={images}
          onImagesChange={setImages}
          activeProvider={activeProvider}
          availProviders={availProviders}
          onProviderChange={handleProviderChange}
          error={error}
          lastFailed={lastFailedRef.current}
          onRetry={() => {
            const f = lastFailedRef.current
            lastFailedRef.current = null
            setError(null)
            send(f.content, f.images)
          }}
          onErrorDismiss={() => { setError(null); lastFailedRef.current = null }}
        />
      </div>

      {/* Share panel */}
      <ChatSharePanel
        open={sharePanelOpen}
        onClose={() => { setSharePanelOpen(false); if (activeConv?.db_id) loadRemoteMessages(activeConv.id) }}
        conv={activeConv}
      />

      {/* Share modal (single message) */}
      {shareModal.open && shareModal.mode === 'msg' && (
        <ShareModal
          mode={shareModal.mode}
          conv={activeConv}
          msgContent={shareModal.msgContent}
          onClose={() => setShareModal(p => ({ ...p, open: false }))}
          onToast={toast}
        />
      )}

      <ToastStack toasts={toasts} />
    </div>
  )
}
