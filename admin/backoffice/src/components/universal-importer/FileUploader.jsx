/**
 * Zone d'upload CSV avec drag-and-drop + preview du fichier sélectionné.
 */
import { useState, useRef, useCallback } from 'react'
import { Btn } from '../ui'

export function FileUploader({ onFileSelected, disabled = false }) {
  const [dragOver, setDragOver] = useState(false)
  const inputRef = useRef(null)

  const handleFile = useCallback((file) => {
    if (!file) return
    if (!file.name.endsWith('.csv') && file.type !== 'text/csv') return
    onFileSelected(file)
  }, [onFileSelected])

  const handleDrop = useCallback((e) => {
    e.preventDefault()
    setDragOver(false)
    if (disabled) return
    const file = e.dataTransfer.files?.[0]
    handleFile(file)
  }, [disabled, handleFile])

  return (
    <div
      className={[
        'relative rounded-lg border-2 border-dashed p-5 text-center transition-colors',
        dragOver ? 'border-primary bg-primary/5' : 'border-border hover:border-muted-foreground/30',
        disabled ? 'opacity-50 pointer-events-none' : 'cursor-pointer',
      ].join(' ')}
      onDragOver={e => { e.preventDefault(); if (!disabled) setDragOver(true) }}
      onDragLeave={() => setDragOver(false)}
      onDrop={handleDrop}
      onClick={() => !disabled && inputRef.current?.click()}
    >
      <input
        ref={inputRef}
        type="file"
        accept=".csv,text/csv"
        onChange={e => { handleFile(e.target.files?.[0]); e.target.value = '' }}
        className="hidden"
        disabled={disabled}
      />
      <div className="space-y-2">
        <div className="flex justify-center">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="text-muted-foreground">
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
        </div>
        <p className="text-sm text-muted-foreground">
          Glissez un fichier CSV ou <span className="text-primary font-medium">parcourir</span>
        </p>
        <p className="text-[11px] text-muted-foreground/70">CSV uniquement (.csv)</p>
      </div>
    </div>
  )
}
