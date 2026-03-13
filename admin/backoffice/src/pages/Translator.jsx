/**
 * Translator — Page traducteur IA autonome.
 * Layout : panneau historique à gauche + zone traduction à droite.
 * Le bouton "Traduire" est désactivé après une traduction réussie
 * jusqu'à ce que le texte source change.
 */
import { useState, useCallback, useEffect, useRef } from 'react'
import { useTranslator, estimateTokens }     from '../hooks/useTranslator'
import { PageHeader }                        from '../components/ui'
import { cn }                                from '../lib/utils'

// ── Constantes ──────────────────────────────────────────────────────────────

const LANGUAGES = [
  { code: 'en', label: 'Anglais'      },
  { code: 'it', label: 'Italien'      },
  { code: 'de', label: 'Allemand'     },
  { code: 'es', label: 'Espagnol'     },
  { code: 'nl', label: 'Néerlandais'  },
  { code: 'ru', label: 'Russe'        },
  { code: 'zh', label: 'Chinois'      },
  { code: 'pt', label: 'Portugais'    },
  { code: 'ar', label: 'Arabe'        },
  { code: 'ja', label: 'Japonais'     },
]

const TONES = [
  { value: 'neutral',      label: 'Neutre'        },
  { value: 'professional', label: 'Professionnel' },
  { value: 'luxury',       label: 'Luxe'          },
  { value: 'tourist',      label: 'Touristique'   },
  { value: 'casual',       label: 'Décontracté'   },
]

// ── Utilitaires ─────────────────────────────────────────────────────────────

function copyText(text) {
  navigator.clipboard.writeText(text).catch(() => {})
}

function exportTxt(results) {
  const content = results.map(r => `[${r.lang.toUpperCase()}]\n${r.result}`).join('\n\n---\n\n')
  const blob = new Blob([content], { type: 'text/plain' })
  const url  = URL.createObjectURL(blob)
  const a    = Object.assign(document.createElement('a'), { href: url, download: 'traductions.txt' })
  a.click()
  URL.revokeObjectURL(url)
}

// ── Sous-composants ─────────────────────────────────────────────────────────

/** Compteur tokens + coût estimé. */
function TokenEstimator({ count }) {
  if (!count) return null
  const cost = ((count / 1000) * 0.00025).toFixed(5)
  return (
    <span className="text-xs text-muted-foreground">≈ {count} tokens · ~${cost}</span>
  )
}

/** Sélecteur multi-langue avec pills. */
function LangSelector({ selected, onChange }) {
  const toggle = useCallback((code) => {
    onChange(prev =>
      prev.includes(code) ? prev.filter(c => c !== code) : [...prev, code]
    )
  }, [onChange])

  return (
    <div className="flex flex-wrap gap-2">
      {LANGUAGES.map(({ code, label }) => {
        const active = selected.includes(code)
        return (
          <button
            key={code}
            type="button"
            onClick={() => toggle(code)}
            className={cn(
              'px-3 py-1 rounded-full text-xs font-medium border transition-colors',
              active
                ? 'bg-primary text-primary-foreground border-primary'
                : 'border-border text-muted-foreground hover:text-foreground hover:border-foreground',
            )}
          >
            {label}
          </button>
        )
      })}
    </div>
  )
}

/** Panneau historique latéral gauche. */
function HistorySidebar({ history, onRestore, onClear }) {
  return (
    <aside className="w-56 shrink-0 border-r flex flex-col bg-muted/10 overflow-hidden">
      <div className="flex items-center justify-between px-4 py-3 border-b bg-background">
        <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          Historique
          {history.length > 0 && (
            <span className="ml-1.5 bg-primary text-primary-foreground rounded-full px-1.5 py-0.5 text-[9px] font-bold">
              {history.length}
            </span>
          )}
        </span>
        {history.length > 0 && (
          <button
            onClick={onClear}
            className="text-[10px] text-muted-foreground hover:text-destructive transition-colors"
          >
            Effacer
          </button>
        )}
      </div>
      <div className="flex-1 overflow-y-auto divide-y">
        {history.length === 0 ? (
          <p className="px-4 py-6 text-xs text-muted-foreground/60 text-center">
            Aucune traduction<br />cette session
          </p>
        ) : (
          history.map((entry, i) => (
            <button
              key={i}
              type="button"
              onClick={() => onRestore(entry)}
              className="w-full text-left px-4 py-3 hover:bg-accent/50 transition-colors group"
            >
              <p className="text-xs text-foreground truncate leading-tight">
                {entry.input.slice(0, 60)}{entry.input.length > 60 ? '…' : ''}
              </p>
              <p className="text-[10px] text-muted-foreground mt-1">
                {entry.translations.map(t => t.lang.toUpperCase()).join(', ')}
                <span className="mx-1">·</span>
                {entry.tone}
              </p>
              <span className="text-[10px] text-primary opacity-0 group-hover:opacity-100 transition-opacity">
                Restaurer →
              </span>
            </button>
          ))
        )}
      </div>
    </aside>
  )
}

// ── Page principale ──────────────────────────────────────────────────────────

export default function Translator() {
  const [selectedLangs, setSelectedLangs] = useState(['en'])
  const [tone, setTone]                   = useState('neutral')
  /** true après une traduction réussie, reset dès que l'input change */
  const [alreadyTranslated, setAlreadyTranslated] = useState(false)
  const inputAtTranslateRef = useRef('')

  const {
    input, setInput, results, loading, error, history,
    tokenEstimate, translate, reset, maxChars,
  } = useTranslator()

  // Quand les résultats arrivent (loading passe à false + results non vide)
  // → verrouiller le bouton si l'input n'a pas changé depuis le lancement
  useEffect(() => {
    if (!loading && results.length > 0 && input === inputAtTranslateRef.current) {
      setAlreadyTranslated(true)
    }
  }, [loading, results, input])

  // Reset le verrou dès que le texte source est modifié
  const handleInputChange = useCallback((e) => {
    setInput(e.target.value)
    setAlreadyTranslated(false)
  }, [setInput])

  const handleTranslate = useCallback(() => {
    if (!selectedLangs.length) return
    inputAtTranslateRef.current = input
    translate({ targetLangs: selectedLangs, tone })
  }, [translate, selectedLangs, tone, input])

  const handleRestore = useCallback((entry) => {
    setInput(entry.input)
    setAlreadyTranslated(false)
  }, [setInput])

  const handleReset = useCallback(() => {
    reset()
    setAlreadyTranslated(false)
  }, [reset])

  const translateDisabled =
    loading || !input.trim() || !selectedLangs.length ||
    input.length > maxChars || alreadyTranslated

  return (
    <div className="flex h-full min-h-0" style={{ minHeight: 'calc(100vh - 32px)' }}>

      {/* ── Historique — panneau gauche ──────────────────────────────────── */}
      <HistorySidebar
        history={history}
        onRestore={handleRestore}
        onClear={handleReset}
      />

      {/* ── Zone principale ──────────────────────────────────────────────── */}
      <div className="flex-1 overflow-y-auto px-6 py-6 space-y-5">
        <PageHeader
          title="Traducteur IA"
          subtitle="Traduction multi-langues pour fiches produits et communications"
        />

        {/* Contrôles : ton + langues */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-xs font-medium text-muted-foreground mb-2">Ton</label>
            <div className="flex flex-wrap gap-1.5">
              {TONES.map(({ value, label }) => (
                <button
                  key={value}
                  type="button"
                  onClick={() => setTone(value)}
                  className={cn(
                    'px-3 py-1 rounded-md text-xs font-medium border transition-colors',
                    tone === value
                      ? 'bg-secondary text-secondary-foreground border-secondary'
                      : 'border-border text-muted-foreground hover:text-foreground',
                  )}
                >
                  {label}
                </button>
              ))}
            </div>
          </div>

          <div>
            <label className="block text-xs font-medium text-muted-foreground mb-2">
              Langues cibles
              {selectedLangs.length > 0 && (
                <span className="ml-1.5 text-primary">
                  ({selectedLangs.length} sélectionnée{selectedLangs.length > 1 ? 's' : ''})
                </span>
              )}
            </label>
            <LangSelector selected={selectedLangs} onChange={setSelectedLangs} />
          </div>
        </div>

        {/* Texte source ↔ résultats */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          {/* Input */}
          <div className="flex flex-col gap-2">
            <div className="flex items-center justify-between">
              <label className="text-xs font-medium text-muted-foreground">Texte source</label>
              <div className="flex items-center gap-3">
                <TokenEstimator count={tokenEstimate} />
                <span className={cn('text-xs', input.length > maxChars ? 'text-destructive' : 'text-muted-foreground')}>
                  {input.length}/{maxChars}
                </span>
              </div>
            </div>
            <textarea
              value={input}
              onChange={handleInputChange}
              placeholder="Entrez le texte à traduire…"
              className="flex-1 min-h-52 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring"
              maxLength={maxChars + 100}
            />
            {error && <p className="text-xs text-destructive">{error}</p>}
          </div>

          {/* Output */}
          <div className="flex flex-col gap-2">
            <div className="flex items-center justify-between">
              <label className="text-xs font-medium text-muted-foreground">Traductions</label>
              {results.length > 0 && (
                <button
                  onClick={() => exportTxt(results)}
                  className="text-xs font-medium text-violet-600 hover:text-violet-700 bg-violet-50 hover:bg-violet-100 border border-violet-200 px-2.5 py-1 rounded-md transition-colors"
                >
                  ↓ Exporter .txt
                </button>
              )}
            </div>
            <div className="flex-1 min-h-52 rounded-lg border border-border overflow-hidden">
              {loading ? (
                <div className="h-full flex items-center justify-center text-sm text-muted-foreground">
                  <span className="animate-pulse">Traduction en cours…</span>
                </div>
              ) : results.length === 0 ? (
                <div className="h-full flex items-center justify-center text-sm text-muted-foreground/50">
                  La traduction apparaîtra ici
                </div>
              ) : (
                <div className="divide-y h-full overflow-y-auto">
                  {results.map(({ lang, result }) => (
                    <div key={lang} className="p-3">
                      <div className="flex items-center justify-between mb-1.5">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                          {lang}
                        </span>
                        <button
                          onClick={() => copyText(result)}
                          className="text-[10px] text-muted-foreground hover:text-foreground"
                        >
                          Copier
                        </button>
                      </div>
                      <p className="text-sm leading-relaxed">{result}</p>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Footer actions */}
        <div className="flex items-center justify-between">
          {(input || results.length > 0) ? (
            <button
              onClick={handleReset}
              className="text-xs text-muted-foreground hover:text-destructive border border-border hover:border-destructive/40 px-3 py-1.5 rounded-md transition-colors"
            >
              Effacer tout
            </button>
          ) : <span />}

          <button
            onClick={handleTranslate}
            disabled={translateDisabled}
            className={cn(
              'px-5 py-2 rounded-lg text-sm font-medium transition-colors',
              alreadyTranslated
                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-default'
                : 'bg-primary text-primary-foreground hover:bg-primary/90',
              (loading || !input.trim() || !selectedLangs.length || input.length > maxChars) && !alreadyTranslated
                ? 'opacity-50 cursor-not-allowed'
                : '',
            )}
          >
            {loading
              ? 'Traduction…'
              : alreadyTranslated
                ? '✓ Traduit — modifiez le texte pour relancer'
                : selectedLangs.length > 1
                  ? `Traduire en ${selectedLangs.length} langues`
                  : 'Traduire'
            }
          </button>
        </div>
      </div>
    </div>
  )
}
