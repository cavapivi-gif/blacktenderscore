import { useState, useRef, useEffect, useCallback } from 'react'
import { motion, AnimatePresence } from 'motion/react'
import { ShareAndroid } from 'iconoir-react'

import { streamChat, api } from '../lib/api'
import { today, daysAgo } from '../lib/utils'
import { ChatSharePanel } from '../components/chat/ChatSharePanel'
import { syncChat, deleteChat } from '../lib/chatApi'
import { useSearchParams } from 'react-router-dom'
import { useConversations } from '../hooks/useConversations'
import { RainbowButton } from '../components/ui/rainbow-button'
import { ChatInputArea } from './ai-chat/ChatInputArea'

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

// ─────────────────────────────────────────────────────────────────────────────
// Main component
// ─────────────────────────────────────────────────────────────────────────────

export default function AIChat() {
  const {
    conversations, grouped, activeId, activeConv, dbLoading,
    setActiveId, create, updateMessages, updateProvider, remove, rename, clearAll, loadRemoteMessages, refreshMessages,
  } = useConversations()
  const [searchParams, setSearchParams] = useSearchParams()

  const [sidebarWidth,  setSidebarWidth]  = useState(280)
  const isResizing = useRef(false)

  const startResize = useCallback((e) => {
    isResizing.current = true
    const startX = e.clientX
    const startW = sidebarWidth
    function onMove(ev) {
      if (!isResizing.current) return
      const w = Math.min(520, Math.max(200, startW + ev.clientX - startX))
      setSidebarWidth(w)
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

  const [input,         setInput]         = useState('')
  const [images,        setImages]        = useState([])
  const [streaming,     setStreaming]     = useState(false)
  const [streamText,    setStreamText]    = useState('')
  const [error,         setError]         = useState(null)
  const lastFailedRef = useRef(null) // { content, images } for retry
  const [filterParams,  setFilterParams]  = useState({ from: daysAgo(365), to: today() })
  const [activeProvider,setActiveProvider]= useState('anthropic')
  const [availProviders,setAvailProviders]= useState({ anthropic: false, openai: false, gemini: false })
  const [showThinking,  setShowThinking]  = useState(false)

  // Share panel (nouveau système granulaire) + ancien modal message
  const [shareModal,  setShareModal]  = useState({ open: false, mode: 'conv', msgContent: '' })
  const [sharePanelOpen, setSharePanelOpen] = useState(false)

  // Shared chat token from URL (e.g. ?bt_chat=TOKEN)
  const [sharedLoading, setSharedLoading] = useState(false)

  const { toasts, push: toast } = useToast()
  const scrollRef   = useRef(null)
  const sendingRef  = useRef(false)
  const abortRef    = useRef(null)

  const messages = activeConv?.messages ?? []

  // Load AI status
  useEffect(() => {
    api.aiStatus().then(res => {
      setAvailProviders(res.providers ?? {})
      setActiveProvider(res.active ?? 'anthropic')
    }).catch(() => setAvailProviders({ anthropic: true }))
  }, [])

  // Sync provider with active conversation
  useEffect(() => {
    if (activeConv?.provider) setActiveProvider(activeConv.provider)
  }, [activeId])

  // Sync URL ↔ activeId : ?chat=<id> quand une conv est ouverte, propre sinon
  useEffect(() => {
    setSearchParams(prev => {
      const next = new URLSearchParams(prev)
      if (activeId) next.set('chat', activeId)
      else next.delete('chat')
      // Conserver éventuels autres params (share, etc.) sauf s'ils entrent en conflit
      next.delete('share')
      return next
    }, { replace: true })
  }, [activeId])

  // Charge les messages + participants à la sélection :
  // - conversation remote (partagée, non locale)
  // - conversation owner synced en DB mais participants pas encore chargés
  // Attend la fin du chargement initial DB pour éviter un double fetch.
  useEffect(() => {
    if (!activeConv || dbLoading) return
    if (activeConv.remote || (activeConv.db_id && !activeConv.participants?.length)) {
      loadRemoteMessages(activeConv.id)
    }
  }, [activeId, dbLoading])

  // Polling temps réel — démarre dès qu'une conversation DB est active.
  // Condition : partagée (non-owner) OU collaborateurs connus OU participants pas encore chargés.
  // S'arrête automatiquement si on découvre que c'est une conversation solo.
  useEffect(() => {
    if (!activeConv?.db_id) return
    const isShared      = activeConv.permission !== 'owner'
    const hasCollabs    = (activeConv.participants?.length ?? 0) > 1
    const notYetLoaded  = !activeConv.participants?.length   // participants pas encore récupérés
    if (!isShared && !hasCollabs && !notYetLoaded) return
    const id = activeConv.id
    const interval = setInterval(() => refreshMessages(id), 3000)
    return () => clearInterval(interval)
  }, [activeId, activeConv?.db_id, activeConv?.participants?.length, refreshMessages])

  // Restauration depuis URL au montage : ?share=<uuid>, ?chat=<id>, ou legacy ?bt_chat=<token>
  useEffect(() => {
    const shareUuid  = searchParams.get('share')
    const chatId     = searchParams.get('chat')
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

    // Restauration conversation directe via ?chat=<id>
    if (chatId) {
      setActiveId(chatId)
      return
    }

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
  }, []) // run once on mount

  // Cleanup : annule le stream en cours si le composant est démonté
  useEffect(() => () => abortRef.current?.abort(), [])

  // Auto-scroll — après paint DOM (rAF évite le flash en haut lors du changement de conv)
  useEffect(() => {
    const el = scrollRef.current
    if (!el) return
    requestAnimationFrame(() => { el.scrollTop = el.scrollHeight })
  }, [messages, streamText, showThinking, activeId])

  // ── Send message ────────────────────────────────────────────────────────────
  const send = useCallback(async (content, attachedImages = []) => {
    if (!content.trim() || streaming || sendingRef.current) return
    sendingRef.current = true
    // Annule un éventuel stream précédent encore en cours
    abortRef.current?.abort()
    const ac = new AbortController()
    abortRef.current = ac

    let convId = activeId
    if (!convId) convId = create(activeProvider, filterParams)

    const userMsg = { role: 'user', content, images: attachedImages, id: `m_${Date.now()}`, user_id: window.btBackoffice?.current_user?.id }
    const updatedMsgs = [...messages, userMsg]

    const isFirst  = messages.length === 0
    const autoTitle = isFirst ? content.slice(0, 50) + (content.length > 50 ? '…' : '') : null
    updateMessages(convId, updatedMsgs, autoTitle)

    setInput(''); setImages([])
    setStreaming(true); setShowThinking(true); setStreamText(''); setError(null)

    // ── Auto-detect data intent + fetch stats ────────────────────────────────
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

    // Injecte le contexte données dans le dernier message user (invisible en UI)
    const history = updatedMsgs.slice(-20).map((m, idx, arr) => {
      const base = { role: m.role, content: m.content }
      if (idx === arr.length - 1 && m.images?.length) base.images = m.images
      if (idx === arr.length - 1 && m.role === 'user' && contextBlock) {
        base.content = `${contextBlock}\n\n---\n${m.content}`
      }
      return base
    })

    let full = ''
    try {
      const reader  = await streamChat(history, statsRange.from, statsRange.to, { provider: activeProvider, signal: ac.signal })
      const decoder = new TextDecoder()
      let buf = ''

      while (true) {
        const { done, value } = await reader.read()
        if (done) break
        buf += decoder.decode(value, { stream: true })
        const lines = buf.split('\n')
        buf = lines.pop() ?? ''
        for (const line of lines) {
          if (!line.startsWith('data: ')) continue
          const raw = line.slice(6).trim()
          if (raw === '[DONE]') break
          try {
            const json = JSON.parse(raw)
            if (json.error) throw new Error(json.error)
            if (json.text) {
              setShowThinking(false)
              full += json.text
              setStreamText(full)
            }
          } catch (pe) {
            if (!pe.message?.startsWith('JSON')) throw pe
          }
        }
      }

      if (full) {
        const finalMsgs = [...updatedMsgs, {
          role: 'assistant', content: full, provider: activeProvider,
          id: `m_${Date.now()}`,
          statsData: statsData ? { data: statsData, intents, range: statsRange } : null,
        }]
        updateMessages(convId, finalMsgs)
        // Auto-sync vers DB si conversation partagée (db_id présent)
        const conv = conversations.find(c => c.id === convId)
        if (conv?.db_id) syncChat({ ...conv, messages: finalMsgs }).catch(() => {})
      }
      lastFailedRef.current = null
    } catch (e) {
      if (full) {
        const finalMsgs = [...updatedMsgs, {
          role: 'assistant', content: full, provider: activeProvider,
          id: `m_${Date.now()}`,
          statsData: statsData ? { data: statsData, intents, range: statsRange } : null,
        }]
        updateMessages(convId, finalMsgs)
        const conv = conversations.find(c => c.id === convId)
        if (conv?.db_id) syncChat({ ...conv, messages: finalMsgs }).catch(() => {})
      }
      setError(e.name === 'AbortError' ? null : (e.message || "Erreur de connexion à l'IA."))
      if (e.name !== 'AbortError') lastFailedRef.current = { content, images: attachedImages }
      setShowThinking(false)
    } finally {
      setStreaming(false); setShowThinking(false); setStreamText('')
      sendingRef.current = false
      abortRef.current = null
    }
  }, [messages, streaming, filterParams, activeProvider, activeId, create, updateMessages, conversations])

  // ChatInputArea calls onSubmit(e, files, pastedContent) — files/pastedContent ignorés pour l'instant (images base64 gérées en interne)
  function handleSubmit(e)  { e?.preventDefault(); send(input, images) }

  function handleProviderChange(key) {
    setActiveProvider(key)
    if (activeId) updateProvider(activeId, key)
  }

  function handleNewConv() { setActiveId(null); setInput(''); setImages([]); setError(null) }

  const hasMessages = messages.length > 0 || streaming

  return (
    <div className="flex" style={{ height: 'calc(100vh - 56px)' }}>

      {/* ── Conversation sidebar (redimensionnable) ────────────────────────── */}
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

      {/* Poignée de redimensionnement */}
      <div
        onMouseDown={startResize}
        className="w-1 shrink-0 hover:bg-border/60 active:bg-border transition-colors cursor-col-resize"
      />

      {/* ── Main chat ────────────────────────────────────────────────────────── */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">

        {/* Toolbar */}
        <div className="flex items-center gap-3 px-5 py-2.5 border-b bg-background shrink-0">
          {/* Chat title */}
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
            {/* Avatars des participants de la conversation active */}
            {activeConv && (activeConv.participants?.length ?? 0) > 0 && (
              <div className="flex -space-x-1.5 items-center">
                {(activeConv.participants ?? []).slice(0, 5).map(p => (
                  <img
                    key={p.user_id}
                    src={p.avatar}
                    alt={p.display_name}
                    title={`${p.display_name}${p.permission === 'owner' ? ' (propriétaire)' : p.permission === 'write' ? ' (écriture)' : ' (lecture)'}`}
                    className="w-6 h-6 rounded-full border-2 border-background shadow-sm"
                    style={{ outline: `2px solid ${p.color ?? '#e5e5e5'}` }}
                  />
                ))}
                {(activeConv.participants?.length ?? 0) > 5 && (
                  <span className="w-6 h-6 rounded-full bg-muted border-2 border-background text-[9px] font-medium text-muted-foreground flex items-center justify-center">
                    +{activeConv.participants.length - 5}
                  </span>
                )}
              </div>
            )}

            {/* Nouveau chat */}
            <RainbowButton
              onClick={handleNewConv}
              title="Nouvelle conversation"
              className="text-xs"
              style={{ height: '30px' }}
            >
              + Nouveau
            </RainbowButton>

            {/* Share button */}
            {activeConv && messages.length > 0 && (
              <RainbowButton
                onClick={() => setSharePanelOpen(true)}
                title="Partager cette conversation"
                className="text-xs"
                style={{ height: '30px' }}
              >
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
                <WelcomeScreen
                  key="welcome"
                  activeProvider={activeProvider}
                  onSuggestion={text => send(text, [])}
                />
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
                            isLast={idx === lastAsstIdx && !streaming}
                            onSend={idx === lastAsstIdx && !streaming ? send : undefined}
                            onCopy={() => toast('Copié dans le presse-papier')}
                            onShare={content => setShareModal({ open: true, mode: 'msg', msgContent: content })}
                          />
                    ))
                  })()}

                  <AnimatePresence>
                    {showThinking && <ThinkingIndicator key="think" provider={activeProvider} />}
                  </AnimatePresence>

                  <AnimatePresence>
                    {streaming && !showThinking && streamText && (
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

        {/* Input + error bar (délégués à ChatInputArea) */}
        <ChatInputArea
          input={input}
          onInputChange={e => setInput(e.target.value)}
          onSubmit={handleSubmit}
          streaming={streaming}
          images={images}
          onImagesChange={setImages}
          activeProvider={activeProvider}
          availProviders={availProviders}
          onProviderChange={handleProviderChange}
          error={error}
          lastFailed={lastFailedRef.current}
          onRetry={() => { const f = lastFailedRef.current; lastFailedRef.current = null; setError(null); send(f.content, f.images) }}
          onErrorDismiss={() => { setError(null); lastFailedRef.current = null }}
        />
      </div>

      {/* Share panel (conversation) */}
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

      {/* Toast stack */}
      <ToastStack toasts={toasts} />
    </div>
  )
}
