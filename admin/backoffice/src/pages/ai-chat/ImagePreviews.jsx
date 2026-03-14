import { Xmark } from 'iconoir-react'

// ─────────────────────────────────────────────────────────────────────────────
// Image previews
// ─────────────────────────────────────────────────────────────────────────────

export function ImagePreviews({ images, onRemove }) {
  if (!images.length) return null
  return (
    <div className="flex gap-2 px-4 pt-3 flex-wrap">
      {images.map((img, i) => (
        <div key={i} className="relative group/img">
          <img src={`data:${img.type};base64,${img.data}`}
            className="h-14 w-14 object-cover rounded-lg border border-input shadow-sm" alt="" />
          <button type="button" onClick={() => onRemove(i)}
            className="absolute -top-1.5 -right-1.5 w-5 h-5 bg-foreground text-background rounded-full flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-opacity shadow">
            <Xmark width={10} height={10} strokeWidth={2.5} />
          </button>
        </div>
      ))}
    </div>
  )
}
