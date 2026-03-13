/**
 * Corrector — Page correcteur IA autonome.
 * Correction orthographique/stylistique avec vue diff mot-à-mot.
 */
import { useState, useCallback } from 'react'
import { useCorrector }          from '../hooks/useCorrector'
import { PageHeader }            from '../components/ui'
import { cn }                    from '../lib/utils'

// ── Constantes ──────────────────────────────────────────────────────────────

const CORR_LANGS = [
  { code: '',   label: 'Détection auto' },
  { code: 'fr', label: 'Français'       },
  { code: 'en', label: 'Anglais'        },
  { code: 'it', label: 'Italien'        },
  { code: 'de', label: 'Allemand'       },
  { code: 'es', label: 'Espagnol'       },
]

// ── Utilitaires ─────────────────────────────────────────────────────────────

function copyText(text) {
  navigator.clipboard.writeText(text).catch(() => {})
}

// ── Sous-composants ─────────────────────────────────────────────────────────

/** Rendu d'un diff mot-à-mot avec couleurs. */
function DiffView({ segments }) {
  if (!segments?.length) return null
  return (
    <div className="text-sm leading-relaxed font-mono whitespace-pre-wrap break-words p-4 rounded-lg border bg-muted/20">
      {segments.map((seg, i) => {
        if (seg.type === 'kept')    return <span key={i}>{seg.text}</span>
        if (seg.type === 'removed') return <span key={i} className="bg-red-100 text-red-700 line-through">{seg.text}</span>
        if (seg.type === 'added')   return <span key={i} className="bg-green-100 text-green-700">{seg.text}</span>
        return null
      })}
    </div>
  )
}

// ── Page principale ──────────────────────────────────────────────────────────

export default function Corrector() {
  const [lang, setLang] = useState('')

  const {
    input, setInput, corrected, diff, showDiff, setShowDiff,
    loading, error, correct, reset, maxChars,
  } = useCorrector()

  const hasChanges = corrected && corrected !== input

  return (
    <div className="px-6 py-6 max-w-5xl mx-auto space-y-5">
      <PageHeader
        title="Correcteur IA"
        subtitle="Correction orthographique et stylistique de vos textes"
      />

      {/* Sélecteur langue */}
      <div>
        <label className="block text-xs font-medium text-muted-foreground mb-2">Langue du texte</label>
        <div className="flex flex-wrap gap-1.5">
          {CORR_LANGS.map(({ code, label }) => (
            <button
              key={code}
              type="button"
              onClick={() => setLang(code)}
              className={cn(
                'px-3 py-1 rounded-md text-xs font-medium border transition-colors',
                lang === code
                  ? 'bg-secondary text-secondary-foreground border-secondary'
                  : 'border-border text-muted-foreground hover:text-foreground',
              )}
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      {/* Zones de texte */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Input */}
        <div className="flex flex-col gap-2">
          <div className="flex items-center justify-between">
            <label className="text-xs font-medium text-muted-foreground">Texte à corriger</label>
            <span className={cn('text-xs', input.length > maxChars ? 'text-destructive' : 'text-muted-foreground')}>
              {input.length}/{maxChars}
            </span>
          </div>
          <textarea
            value={input}
            onChange={e => setInput(e.target.value)}
            placeholder="Entrez le texte à corriger…"
            className="flex-1 min-h-52 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring"
            maxLength={maxChars + 100}
          />
          {error && <p className="text-xs text-destructive">{error}</p>}
        </div>

        {/* Output */}
        <div className="flex flex-col gap-2">
          <div className="flex items-center justify-between">
            <label className="text-xs font-medium text-muted-foreground">Texte corrigé</label>
            <div className="flex items-center gap-3">
              {hasChanges && (
                <button
                  onClick={() => setShowDiff(v => !v)}
                  className={cn(
                    'text-xs px-2.5 py-1 rounded-md border transition-colors',
                    showDiff
                      ? 'bg-amber-50 text-amber-700 border-amber-200'
                      : 'text-muted-foreground border-border hover:text-foreground',
                  )}
                >
                  {showDiff ? 'Masquer diff' : 'Voir modifications'}
                </button>
              )}
              {corrected && (
                <button
                  onClick={() => copyText(corrected)}
                  className="text-xs text-muted-foreground hover:text-foreground"
                >
                  Copier
                </button>
              )}
            </div>
          </div>

          {showDiff && diff ? (
            <DiffView segments={diff} />
          ) : (
            <div className="flex-1 min-h-52 rounded-lg border border-border overflow-hidden">
              {loading ? (
                <div className="h-full flex items-center justify-center text-sm text-muted-foreground">
                  <span className="animate-pulse">Correction en cours…</span>
                </div>
              ) : !corrected ? (
                <div className="h-full flex items-center justify-center text-sm text-muted-foreground/50">
                  Le texte corrigé apparaîtra ici
                </div>
              ) : (
                <div className="p-3 h-full overflow-y-auto">
                  {!hasChanges && (
                    <p className="text-xs text-muted-foreground mb-2 italic">Aucune modification détectée.</p>
                  )}
                  <p className="text-sm leading-relaxed">{corrected}</p>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Footer actions */}
      <div className="flex items-center justify-between">
        {(input || corrected) ? (
          <button
            onClick={reset}
            className="text-xs text-muted-foreground hover:text-destructive border border-border hover:border-destructive/40 px-3 py-1.5 rounded-md transition-colors"
          >
            Effacer tout
          </button>
        ) : <span />}
        <button
          onClick={() => correct(lang)}
          disabled={loading || !input.trim() || input.length > maxChars}
          className={cn(
            'px-5 py-2 rounded-lg text-sm font-medium transition-colors',
            'bg-primary text-primary-foreground hover:bg-primary/90',
            'disabled:opacity-50 disabled:cursor-not-allowed',
          )}
        >
          {loading ? 'Correction…' : 'Corriger'}
        </button>
      </div>
    </div>
  )
}
