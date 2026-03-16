import { useRef, useCallback } from 'react'
import { SendDiagonal, MediaImage, Xmark, Refresh } from 'iconoir-react'
import { motion, AnimatePresence } from 'motion/react'
import { ModelPicker } from './ModelPicker'
import { ImagePreviews } from './ImagePreviews'
import { FilePreviewCard, PastedContentCard, DragOverlay } from './FilePreviewCards'
import { useChatFiles } from './useChatFiles'

// ─────────────────────────────────────────────────────────────────────────────
// ChatInputArea — enhanced input with drag/drop, file cards, paste-as-card
// Styles calés sur nos design tokens (bg-card, border-border, etc.)
// ─────────────────────────────────────────────────────────────────────────────

const PASTE_THRESHOLD = 200

export function ChatInputArea({
  input, onInputChange, onSubmit, onKeyDown, streaming,
  images, onImagesChange,
  activeProvider, availProviders, onProviderChange,
  error, lastFailed, onRetry, onErrorDismiss,
}) {
  const fileRef = useRef(null)
  const {
    files, pastedContent, isDragging,
    addFiles, removeFile, addPastedContent, removePasted,
    onDragOver, onDragLeave, onDrop,
    hasUploading,
  } = useChatFiles()

  // Expose files/pastedContent to parent via onSubmit wrapper
  const handleSend = useCallback((e) => {
    e?.preventDefault()
    if (hasUploading) return
    onSubmit(e, files, pastedContent)
  }, [onSubmit, files, pastedContent, hasUploading])

  const handleKeyDownInternal = useCallback((e) => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend() }
    else onKeyDown?.(e)
  }, [handleSend, onKeyDown])

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
        <div className="relative rounded-2xl bg-card shadow-sm focus-within:shadow-[0_0_0_3px_rgba(26,25,23,0.06)] transition-all">
          {isDragging && <DragOverlay />}

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
            rows={1}
            value={input}
            onChange={onInputChange}
            onKeyDown={handleKeyDownInternal}
            onPaste={handlePaste}
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
          Shift+Entrée pour un saut de ligne
          {images.length > 0 && <span className="ml-2 font-medium">{images.length} image{images.length > 1 ? 's' : ''} jointe{images.length > 1 ? 's' : ''}</span>}
          {files.length > 0 && <span className="ml-2 font-medium">{files.length} fichier{files.length > 1 ? 's' : ''}</span>}
          {pastedContent.length > 0 && <span className="ml-2 font-medium">{pastedContent.length} extrait{pastedContent.length > 1 ? 's' : ''} collé{pastedContent.length > 1 ? 's' : ''}</span>}
        </p>
      </form>
    </div>
  )
}
