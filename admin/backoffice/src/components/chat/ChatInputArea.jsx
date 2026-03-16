import { useState, useRef, useCallback } from 'react'
import { SendDiagonal, MediaImage, Xmark, Refresh, Reply } from 'iconoir-react'
import { motion, AnimatePresence } from 'motion/react'
import { ModelPicker } from './ModelPicker'
import { ImagePreviews } from './ImagePreviews'
import { FilePreviewCard, PastedContentCard, DragOverlay } from './FilePreviewCards'
import { useChatFiles } from './useChatFiles'

// ─────────────────────────────────────────────────────────────────────────────
// ChatInputArea — enhanced input with drag/drop, file cards, paste-as-card,
//   reply preview strip, and @mention autocomplete
// ─────────────────────────────────────────────────────────────────────────────

const PASTE_THRESHOLD = 200

// Special AI mention — always first in the list
const CLAUDE_MENTION = { id: '__claude__', display_name: 'Claude', avatar: null }

export function ChatInputArea({
  input, onInputChange, onSubmit, onKeyDown, streaming,
  images, onImagesChange,
  activeProvider, availProviders, onProviderChange,
  error, lastFailed, onRetry, onErrorDismiss,
  // Reply-to
  replyTo, onReplyCancel,
  // Participants for @mentions
  participants,
}) {
  const fileRef    = useRef(null)
  const textareaRef = useRef(null)

  const {
    files, pastedContent, isDragging,
    addFiles, removeFile, addPastedContent, removePasted,
    onDragOver, onDragLeave, onDrop,
    hasUploading,
  } = useChatFiles()

  // @mention state
  const [mention, setMention] = useState({ active: false, query: '', start: 0 })

  // All mentionable names: Claude first, then participants
  const allMentionable = [
    CLAUDE_MENTION,
    ...(participants ?? []).filter(p =>
      String(p.user_id) !== String(window.btBackoffice?.current_user?.id)
    ),
  ]

  const filteredMentions = mention.active
    ? allMentionable.filter(p =>
        p.display_name.toLowerCase().startsWith(mention.query.toLowerCase())
      )
    : []

  // Detect @mention trigger from input value + cursor position
  function checkMention(value, cursorPos) {
    const before = value.slice(0, cursorPos)
    const match  = before.match(/@(\w*)$/)
    if (match) {
      setMention({ active: true, query: match[1], start: cursorPos - match[0].length })
    } else {
      setMention({ active: false, query: '', start: 0 })
    }
  }

  function selectMention(name) {
    const before       = input.slice(0, mention.start)
    const afterMention = input.slice(mention.start + 1 + mention.query.length)
    const newValue     = `${before}@${name} ${afterMention}`
    onInputChange({ target: { value: newValue } })
    setMention({ active: false, query: '', start: 0 })
    // Restore focus after state update
    requestAnimationFrame(() => textareaRef.current?.focus())
  }

  // Expose files/pastedContent to parent via onSubmit wrapper
  const handleSend = useCallback((e) => {
    e?.preventDefault()
    if (hasUploading) return
    setMention({ active: false, query: '', start: 0 })
    onSubmit(e, files, pastedContent)
  }, [onSubmit, files, pastedContent, hasUploading])

  const handleKeyDownInternal = useCallback((e) => {
    // Close mention dropdown on Escape
    if (e.key === 'Escape' && mention.active) {
      e.preventDefault()
      setMention({ active: false, query: '', start: 0 })
      return
    }
    // Select first mention on Tab when dropdown is open
    if (e.key === 'Tab' && mention.active && filteredMentions.length > 0) {
      e.preventDefault()
      selectMention(filteredMentions[0].display_name)
      return
    }
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend() }
    else onKeyDown?.(e)
  }, [handleSend, onKeyDown, mention.active, filteredMentions, selectMention]) // eslint-disable-line react-hooks/exhaustive-deps

  const handleChange = useCallback((e) => {
    onInputChange(e)
    checkMention(e.target.value, e.target.selectionStart ?? e.target.value.length)
  }, [onInputChange]) // eslint-disable-line react-hooks/exhaustive-deps

  const handleFileChange = useCallback((e) => {
    addFiles(e.target.files)
    e.target.value = ''
  }, [addFiles])

  const handlePaste = useCallback((e) => {
    // Image paste → file card
    const fileItems = Array.from(e.clipboardData.items).filter(i => i.kind === 'file')
    if (fileItems.length > 0) {
      e.preventDefault()
      const dt = new DataTransfer()
      fileItems.map(i => i.getAsFile()).filter(Boolean).forEach(f => dt.items.add(f))
      addFiles(dt.files)
      return
    }
    // Long text paste → content card
    const text = e.clipboardData.getData('text')
    if (text?.length > PASTE_THRESHOLD) {
      e.preventDefault()
      const accepted = addPastedContent(text)
      if (!accepted) {
        // fallback: paste normally (limit reached)
        onInputChange({ target: { value: input + text } })
      }
    }
  }, [addFiles, addPastedContent, input, onInputChange])

  // Legacy image handling (base64 — existing system)
  const handleLegacyFile = useCallback((e) => {
    Array.from(e.target.files ?? []).forEach(file => {
      if (!file.type.startsWith('image/')) return
      const reader = new FileReader()
      reader.onload = ev => onImagesChange(prev => [...prev, { data: ev.target.result.split(',')[1], type: file.type }])
      reader.readAsDataURL(file)
    })
    e.target.value = ''
  }, [onImagesChange])

  const hasAttachments = files.length > 0 || pastedContent.length > 0
  const canSend = (input.trim() || images.length > 0 || hasAttachments) && !streaming && !hasUploading

  const replyLabel = replyTo
    ? `${replyTo.role === 'user' ? (replyTo.authorName ?? 'Vous') : (replyTo.authorName ?? 'IA')} : ${replyTo.content?.slice(0, 80) ?? ''}${(replyTo.content?.length ?? 0) > 80 ? '…' : ''}`
    : ''

  return (
    <div className="px-4 py-3 shrink-0 border-t bg-background">
      {/* Error bar */}
      <AnimatePresence>
        {error && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: 'auto' }}
            exit={{ opacity: 0, height: 0 }}
            className="pb-2"
          >
            <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700 max-w-3xl mx-auto">
              <span className="flex-1">{error}</span>
              {lastFailed && (
                <button onClick={onRetry}
                  className="shrink-0 flex items-center gap-1 px-2 py-1 rounded-md bg-red-100 hover:bg-red-200 transition-colors font-medium">
                  <Refresh width={11} height={11} strokeWidth={2} /> Réessayer
                </button>
              )}
              <button onClick={onErrorDismiss}><Xmark width={13} height={13} strokeWidth={2} /></button>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      <form
        onSubmit={handleSend}
        className="max-w-3xl mx-auto"
        onDragOver={onDragOver}
        onDragLeave={onDragLeave}
        onDrop={onDrop}
      >
        {/* @mention dropdown (floats above the form) */}
        <AnimatePresence>
          {mention.active && filteredMentions.length > 0 && (
            <motion.div
              initial={{ opacity: 0, y: 4 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: 4 }}
              className="mb-1 bg-popover border border-border rounded-xl shadow-lg overflow-hidden"
            >
              {filteredMentions.slice(0, 6).map(p => (
                <button
                  key={p.id ?? p.user_id}
                  type="button"
                  onMouseDown={e => { e.preventDefault(); selectMention(p.display_name) }}
                  className="w-full flex items-center gap-2.5 px-3 py-2 text-sm hover:bg-accent transition-colors text-left"
                >
                  {p.avatar
                    ? <img src={p.avatar} alt={p.display_name} className="w-5 h-5 rounded-full shrink-0" />
                    : <span className="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center text-[10px] font-bold text-primary shrink-0">
                        {p.display_name[0].toUpperCase()}
                      </span>
                  }
                  <span className="flex-1 truncate">{p.display_name}</span>
                  {p.id === '__claude__' && (
                    <span className="text-[9px] text-muted-foreground/60 font-medium uppercase tracking-wide">IA</span>
                  )}
                </button>
              ))}
            </motion.div>
          )}
        </AnimatePresence>

        <div className="relative rounded-2xl bg-card shadow-sm focus-within:shadow-[0_0_0_3px_rgba(26,25,23,0.06)] transition-all">
          {isDragging && <DragOverlay />}

          {/* Reply preview strip */}
          <AnimatePresence>
            {replyTo && (
              <motion.div
                initial={{ opacity: 0, height: 0 }}
                animate={{ opacity: 1, height: 'auto' }}
                exit={{ opacity: 0, height: 0 }}
                className="flex items-center gap-2 px-4 pt-3 pb-1 border-b border-border/40"
              >
                <Reply width={11} height={11} strokeWidth={1.5} className="shrink-0 text-muted-foreground/60" />
                <p className="flex-1 text-[11px] text-muted-foreground/70 truncate leading-none">
                  <span className="font-semibold text-muted-foreground">
                    {replyTo.role === 'user' ? (replyTo.authorName ?? 'Vous') : (replyTo.authorName ?? 'IA')}
                  </span>
                  {' — '}
                  {replyTo.content?.slice(0, 80)}{(replyTo.content?.length ?? 0) > 80 ? '…' : ''}
                </p>
                <button type="button" onClick={onReplyCancel}
                  className="shrink-0 text-muted-foreground/50 hover:text-muted-foreground transition-colors">
                  <Xmark width={12} height={12} strokeWidth={2} />
                </button>
              </motion.div>
            )}
          </AnimatePresence>

          {/* Legacy base64 images (existing system) */}
          <ImagePreviews images={images} onRemove={i => onImagesChange(p => p.filter((_, idx) => idx !== i))} />

          {/* New file/pasted cards */}
          {hasAttachments && (
            <div className="flex gap-2 px-4 pt-3 overflow-x-auto pb-1" style={{ scrollbarWidth: 'none' }}>
              {pastedContent.map(c => <PastedContentCard key={c.id} content={c} onRemove={removePasted} />)}
              {files.map(f => <FilePreviewCard key={f.id} file={f} onRemove={removeFile} />)}
            </div>
          )}

          <textarea
            ref={textareaRef}
            rows={1}
            value={input}
            onChange={handleChange}
            onKeyDown={handleKeyDownInternal}
            onPaste={handlePaste}
            placeholder="Posez votre question… (@mention, Entrée pour envoyer)"
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
              {/* Hidden input for legacy base64 images */}
              <input ref={fileRef} type="file" accept="image/*" multiple className="hidden" onChange={handleLegacyFile} />
              <ModelPicker active={activeProvider} available={availProviders} onChange={onProviderChange} />
            </div>
            <button type="submit"
              disabled={!canSend}
              className="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center hover:bg-primary/90 disabled:opacity-30 disabled:pointer-events-none transition-all shadow-sm shrink-0"
            >
              {streaming
                ? <span className="w-3 h-3 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin" />
                : <SendDiagonal width={13} height={13} strokeWidth={2.5} />}
            </button>
          </div>
        </div>

        <p className="text-[10px] text-muted-foreground mt-1.5 px-1">
          Shift+Entrée pour un saut de ligne · <span className="opacity-60">@</span> pour mentionner
          {images.length > 0 && <span className="ml-2 font-medium">{images.length} image{images.length > 1 ? 's' : ''} jointe{images.length > 1 ? 's' : ''}</span>}
          {files.length > 0 && <span className="ml-2 font-medium">{files.length} fichier{files.length > 1 ? 's' : ''}</span>}
          {pastedContent.length > 0 && <span className="ml-2 font-medium">{pastedContent.length} extrait{pastedContent.length > 1 ? 's' : ''} collé{pastedContent.length > 1 ? 's' : ''}</span>}
        </p>
      </form>
    </div>
  )
}
