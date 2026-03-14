import { useState, useRef, useEffect, useCallback } from 'react'
import { motion, AnimatePresence } from 'motion/react'
import {
  SendDiagonal, MediaImage, Xmark, ShareAndroid,
} from 'iconoir-react'

import { streamChat, api } from '../lib/api'
import { today, daysAgo } from '../lib/utils'
import { ChatSharePanel } from '../components/chat/ChatSharePanel'
import { syncChat } from '../lib/chatApi'
import { useSearchParams } from 'react-router-dom'
import { useConversations } from '../hooks/useConversations'

import {
  useToast,
  ToastStack,
  ConvSidebar,
  WelcomeScreen,
  UserMsg,
  AssistantMsg,
  ThinkingIndicator,
  ModelPicker,
  ImagePreviews,
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
  const [searchParams] = useSearchParams()

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
  const inputRef    = useRef(null)
  const fileRef     = useRef(null)
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
    const interval = setInterval(() => refreshMessages(activeConv.id), 3000)
    return () => clearInterval(interval)
  }, [activeId, activeConv?.db_id, activeConv?.participants?.length])

  // Load shared conversation from URL param (?share=uuid or legacy ?bt_chat=token)
  useEffect(() => {
    const shareUuid = searchParams.get('share')
    const legacyToken = searchParams.get('bt_chat')

    if (shareUuid) {
      // New system: load by UUID from DB
      import('../lib/chatApi').then(({ getChat }) => {
        setSharedLoading(true)
        getChat(shareUuid)
          .then(data => {
            setActiveId(shareUuid)
            toast('Conversation partagée chargée')
          })
          .catch(() => toast('Lien de partage introuvable ou accès refusé', 'error'))
          .finally(() => setSharedLoading(false))
      })
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

  // Auto-scroll — messages nouveaux + changement de conversation
  useEffect(() => {
    const el = scrollRef.current
    if (el) el.scrollTop = el.scrollHeight
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
      setError(e.message || "Erreur de connexion à l'IA.")
      setShowThinking(false)
    } finally {
      setStreaming(false); setShowThinking(false); setStreamText('')
      sendingRef.current = false
      abortRef.current = null
    }
  }, [messages, streaming, filterParams, activeProvider, activeId, create, updateMessages, conversations])

  function handleSubmit(e)   { e.preventDefault(); send(input, images) }
  function handleKeyDown(e)  { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input, images) } }

  function handleFileChange(e) {
    Array.from(e.target.files ?? []).forEach(file => {
      if (!file.type.startsWith('image/')) return
      const reader = new FileReader()
      reader.onload = ev => setImages(prev => [...prev, { data: ev.target.result.split(',')[1], type: file.type }])
      reader.readAsDataURL(file)
    })
    e.target.value = ''
  }

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
          onDelete={id => { remove(id); toast('Conversation supprimée') }}
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

            {/* Share button */}
            {activeConv && messages.length > 0 && (
              <button
                onClick={() => setSharePanelOpen(true)}
                title="Partager cette conversation"
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-foreground text-background hover:bg-foreground/85 transition-colors shrink-0"
              >
                <ShareAndroid width={12} height={12} strokeWidth={2} />
                <span className="hidden sm:inline">Partager</span>
              </button>
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
            <AnimatePresence mode="wait">
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

        {/* Error */}
        <AnimatePresence>
          {error && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: 'auto' }}
              exit={{    opacity: 0, height: 0     }}
              className="px-6 pb-1"
            >
              <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">
                <span className="flex-1">{error}</span>
                <button onClick={() => setError(null)}><Xmark width={13} height={13} strokeWidth={2} /></button>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Input */}
        <div className="px-4 py-3 shrink-0 border-t bg-background">
          <form onSubmit={handleSubmit} className="max-w-3xl mx-auto">
            <div className="rounded-2xl bg-card shadow-sm focus-within:shadow-[0_0_0_3px_rgba(26,25,23,0.06)] transition-all">
              <ImagePreviews images={images} onRemove={i => setImages(p => p.filter((_, idx) => idx !== i))} />
              <textarea
                ref={inputRef} rows={1} value={input}
                onChange={e => setInput(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder="Posez votre question… (Entrée pour envoyer)"
                disabled={streaming}
                className="w-full resize-none bg-transparent px-4 py-3 text-sm focus-visible:outline-none placeholder:text-muted-foreground disabled:opacity-50 leading-relaxed"
                style={{ fieldSizing: 'content', minHeight: '44px', maxHeight: '180px' }}
              />
              <div className="flex items-center justify-between px-3 pb-3 gap-2">
                <div className="flex items-center gap-2">
                  <button type="button" onClick={() => fileRef.current?.click()}
                    title="Joindre une image"
                    className="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/60 transition-colors shrink-0">
                    <MediaImage width={15} height={15} strokeWidth={1.5} />
                  </button>
                  <input ref={fileRef} type="file" accept="image/*" multiple className="hidden" onChange={handleFileChange} />
                  <ModelPicker active={activeProvider} available={availProviders} onChange={handleProviderChange} />
                </div>
                <button type="submit"
                  disabled={(!input.trim() && !images.length) || streaming}
                  className="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center hover:bg-primary/90 disabled:opacity-30 disabled:pointer-events-none transition-all shadow-sm shrink-0"
                >
                  {streaming
                    ? <span className="w-3 h-3 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin" />
                    : <SendDiagonal width={13} height={13} strokeWidth={2.5} />}
                </button>
              </div>
            </div>
            <p className="text-[10px] text-muted-foreground mt-1.5 px-1">
              Shift+Entrée pour un saut de ligne
              {images.length > 0 && <span className="ml-2 font-medium">{images.length} image{images.length > 1 ? 's' : ''} jointe{images.length > 1 ? 's' : ''}</span>}
            </p>
          </form>
        </div>
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
