import { Copy, Xmark } from 'iconoir-react'
import { isTextualFile } from './useChatFiles'

// ─────────────────────────────────────────────────────────────────────────────
// File preview cards — adapted to our design tokens
// ─────────────────────────────────────────────────────────────────────────────

function CardShell({ children, onRemove, badge, status }) {
  return (
    <div className="relative group flex-shrink-0 w-[110px] h-[110px] rounded-xl border border-border bg-muted overflow-hidden shadow-sm">
      {children}
      {/* Gradient overlay */}
      <div className="absolute inset-0 bg-gradient-to-t from-foreground/20 to-transparent pointer-events-none" />
      {/* Badge */}
      {badge && (
        <span className="absolute bottom-2 left-2 text-[9px] font-semibold uppercase tracking-wide bg-background/90 text-foreground px-1.5 py-0.5 rounded-md border border-border/60">
          {badge}
        </span>
      )}
      {/* Status icon */}
      {status === 'uploading' && (
        <span className="absolute top-2 left-2 w-3 h-3 border border-muted-foreground border-t-transparent rounded-full animate-spin inline-block" />
      )}
      {/* Remove button */}
      <button
        type="button"
        onClick={() => onRemove()}
        className="absolute top-1.5 right-1.5 w-5 h-5 bg-foreground/80 text-background rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
      >
        <Xmark width={10} height={10} strokeWidth={2.5} />
      </button>
    </div>
  )
}

function TextCardShell({ children, onRemove, badge, status, onCopy }) {
  return (
    <div className="relative group flex-shrink-0 w-[110px] h-[110px] rounded-xl border border-border bg-muted overflow-hidden shadow-sm">
      <div className="p-2 text-[7.5px] text-muted-foreground whitespace-pre-wrap break-words overflow-hidden h-full leading-relaxed">
        {children}
      </div>
      <div className="absolute inset-0 bg-gradient-to-t from-foreground/20 to-transparent pointer-events-none" />
      {badge && (
        <span className="absolute bottom-2 left-2 text-[9px] font-semibold uppercase tracking-wide bg-background/90 text-foreground px-1.5 py-0.5 rounded-md border border-border/60">
          {badge}
        </span>
      )}
      {status === 'uploading' && (
        <span className="absolute top-2 left-2 w-3 h-3 border border-muted-foreground border-t-transparent rounded-full animate-spin inline-block" />
      )}
      <div className="absolute top-1.5 right-1.5 flex gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
        {onCopy && (
          <button type="button" onClick={onCopy}
            className="w-5 h-5 bg-foreground/80 text-background rounded-full flex items-center justify-center">
            <Copy width={9} height={9} strokeWidth={2} />
          </button>
        )}
        <button type="button" onClick={onRemove}
          className="w-5 h-5 bg-foreground/80 text-background rounded-full flex items-center justify-center">
          <Xmark width={10} height={10} strokeWidth={2.5} />
        </button>
      </div>
    </div>
  )
}

export function FilePreviewCard({ file, onRemove }) {
  const isImage    = file.type.startsWith('image/')
  const isTextual  = isTextualFile(file.file)
  const ext        = file.file.name.split('.').pop()?.toUpperCase().slice(0, 8) ?? 'FILE'

  if (isTextual) {
    return (
      <TextCardShell
        badge={ext}
        status={file.uploadStatus}
        onRemove={() => onRemove(file.id)}
        onCopy={file.textContent ? () => navigator.clipboard.writeText(file.textContent) : undefined}
      >
        {file.textContent ?? ''}
      </TextCardShell>
    )
  }

  return (
    <CardShell badge={isImage ? null : ext} status={file.uploadStatus} onRemove={() => onRemove(file.id)}>
      {isImage && file.preview
        ? <img src={file.preview} alt={file.file.name} className="w-full h-full object-cover" />
        : (
          <div className="flex flex-col gap-1 p-2 pt-3">
            <p className="text-[9px] font-medium text-foreground truncate">{file.file.name}</p>
          </div>
        )
      }
    </CardShell>
  )
}

export function PastedContentCard({ content, onRemove }) {
  return (
    <TextCardShell
      badge="COLLÉ"
      onRemove={() => onRemove(content.id)}
      onCopy={() => navigator.clipboard.writeText(content.content)}
    >
      {content.content}
    </TextCardShell>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Drag-over overlay
// ─────────────────────────────────────────────────────────────────────────────

export function DragOverlay() {
  return (
    <div className="absolute inset-0 z-50 bg-primary/5 border-2 border-dashed border-primary/40 rounded-2xl flex items-center justify-center pointer-events-none">
      <p className="text-xs font-medium text-primary/70">Déposer les fichiers ici</p>
    </div>
  )
}
