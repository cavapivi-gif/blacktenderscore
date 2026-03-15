import { useState, useRef, useEffect, useCallback } from 'react'
import { api } from '../lib/api'

// ── Helpers ────────────────────────────────────────────────────────────────────

function parseStyle(json) {
  if (!json || json === '[]') return []
  try { return JSON.parse(json) } catch { return [] }
}

// ── MapPreview — live Google Maps preview ──────────────────────────────────────
// Remonte à Marseille par défaut — pertinent pour une app activités nautiques.

function MapPreview({ styleJson, apiKey, height = 320 }) {
  // wrapperRef: React-managed div (no React children inside)
  // mapNodeRef: manually created div given to Google Maps — avoids removeChild conflict
  // Google Maps reparents its container div, which breaks React's removeChild assumptions.
  // By giving Google Maps a non-React node we manage ourselves, React never tries to remove it.
  const wrapperRef = useRef(null)
  const mapNodeRef = useRef(null)
  const mapRef     = useRef(null)
  const loadedRef  = useRef(false)

  const initMap = useCallback(() => {
    if (!mapNodeRef.current || mapRef.current) return
    mapRef.current = new window.google.maps.Map(mapNodeRef.current, {
      center: { lat: 43.2965, lng: 5.3698 },
      zoom: 13,
      disableDefaultUI: true,
      gestureHandling: 'greedy',
      styles: parseStyle(styleJson),
    })
  }, []) // eslint-disable-line react-hooks/exhaustive-deps

  // Create the Google Maps host node outside React's tree; clean it up on unmount.
  useEffect(() => {
    const node = document.createElement('div')
    node.style.width  = '100%'
    node.style.height = '100%'
    mapNodeRef.current = node
    wrapperRef.current?.appendChild(node)
    return () => {
      if (node.parentNode) node.parentNode.removeChild(node)
      mapNodeRef.current = null
      mapRef.current     = null
      loadedRef.current  = false
    }
  }, [])

  useEffect(() => {
    if (!apiKey || loadedRef.current) return
    loadedRef.current = true
    if (window.google?.maps?.Map) { initMap(); return }
    window.__btMapPreviewCb = initMap
    const s = document.createElement('script')
    s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&callback=__btMapPreviewCb`
    s.async = true
    document.head.appendChild(s)
    return () => { delete window.__btMapPreviewCb }
  }, [apiKey, initMap])

  useEffect(() => {
    if (!mapRef.current) return
    mapRef.current.setOptions({ styles: parseStyle(styleJson) })
  }, [styleJson])

  if (!apiKey) {
    return (
      <div
        className="rounded-xl border border-dashed border-border flex flex-col items-center justify-center gap-2 text-center px-4"
        style={{ height }}
      >
        <svg className="text-muted-foreground/30" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7Z"/>
          <circle cx="12" cy="9" r="2.5"/>
        </svg>
        <p className="text-xs text-muted-foreground">Aperçu indisponible — clé API Google Maps manquante.</p>
        <p className="text-[11px] text-muted-foreground/60">Elementor → Réglages → Intégrations → Google Maps</p>
      </div>
    )
  }

  return (
    <div className="rounded-xl overflow-hidden border border-border shadow-sm relative">
      <div ref={wrapperRef} style={{ height, width: '100%' }} />
      <span className="absolute bottom-2 right-2 text-[10px] bg-black/50 text-white/80 rounded px-1.5 py-0.5 backdrop-blur-sm pointer-events-none select-none">
        Marseille · aperçu
      </span>
    </div>
  )
}

// ── SnazzyCard — carte de style Snazzy Maps ────────────────────────────────────

function SnazzyCard({ style, isActive, onSelect }) {
  return (
    <button
      type="button"
      onClick={() => onSelect(style)}
      className={[
        'group flex flex-col rounded-lg border overflow-hidden text-left transition-all hover:shadow-md',
        isActive
          ? 'border-primary ring-2 ring-primary shadow-md'
          : 'border-input bg-background hover:border-primary/50',
      ].join(' ')}
    >
      {/* Preview image from Snazzy Maps CDN */}
      <div className="relative aspect-video w-full overflow-hidden bg-muted/40">
        {style.imageUrl ? (
          <img
            src={style.imageUrl}
            alt={style.name}
            className="w-full h-full object-cover"
            loading="lazy"
          />
        ) : (
          <div className="w-full h-full bg-muted/60" />
        )}
        {isActive && (
          <div className="absolute inset-0 bg-primary/20 flex items-center justify-center">
            <div className="bg-primary text-primary-foreground rounded-full p-1">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3">
                <path d="M20 6 9 17l-5-5"/>
              </svg>
            </div>
          </div>
        )}
      </div>

      {/* Info */}
      <div className="p-2.5 min-w-0 flex flex-col gap-0.5">
        <span className="block text-xs font-medium leading-tight truncate">{style.name}</span>
        {style.views > 0 && (
          <span className="block text-[10px] text-muted-foreground/70">
            {style.views.toLocaleString()} vues
          </span>
        )}
      </div>
    </button>
  )
}

// ── MapPresets — composant principal ──────────────────────────────────────────

/**
 * @param {array}    presets           Presets personnalisés sauvegardés
 * @param {string}   activeJson        Style JSON actif
 * @param {string}   apiKey            Clé Google Maps JS API (Elementor)
 * @param {boolean}  snazzymapsEnabled Clé Snazzy Maps configurée → affiche la bibliothèque
 * @param {function} onPresetsChange   Callback mise à jour presets
 * @param {function} onActivate        Callback activation d'un style JSON
 */
export default function MapPresets({ presets = [], activeJson = '', apiKey = '', snazzymapsEnabled = false, onPresetsChange, onActivate }) {
  // ── Snazzy Maps state ──────────────────────────────────────────────────────
  const [snazzyStyles, setSnazzyStyles]   = useState([])
  const [snazzyLoading, setSnazzyLoading] = useState(false)
  const [snazzyError, setSnazzyError]     = useState(null)
  const [snazzySearch, setSnazzySearch]   = useState('')
  const [snazzyPage, setSnazzyPage]       = useState(1)
  const [snazzyTotal, setSnazzyTotal]     = useState(0)
  const fetchedRef = useRef(false)

  // ── JSON editor state ──────────────────────────────────────────────────────
  const [jsonDraft, setJsonDraft] = useState(activeJson)
  const [jsonError, setJsonError] = useState(null)
  const [jsonOk,    setJsonOk]    = useState(false)
  const [showEditor, setShowEditor] = useState(false)

  // ── Custom preset state ────────────────────────────────────────────────────
  const [customName, setCustomName] = useState('')

  const previewJson = jsonOk ? jsonDraft : activeJson
  const PER_PAGE = 12

  // Auto-fetch Snazzy styles au montage si clé configurée
  useEffect(() => {
    if (snazzymapsEnabled && !fetchedRef.current) {
      fetchedRef.current = true
      fetchSnazzyStyles()
    }
  }, [snazzymapsEnabled]) // eslint-disable-line react-hooks/exhaustive-deps

  async function fetchSnazzyStyles(search = '', page = 1) {
    setSnazzyLoading(true)
    setSnazzyError(null)
    try {
      const data = await api.snazzymapsStyles({
        search: search || undefined,
        per_page: PER_PAGE,
        page,
      })
      if (data.error) { setSnazzyError(data.error); return }
      setSnazzyStyles(data.styles ?? [])
      setSnazzyTotal(data.total ?? data.styles?.length ?? 0)
    } catch (e) {
      setSnazzyError(e.message)
    } finally {
      setSnazzyLoading(false)
    }
  }

  function handleSnazzySearch(e) {
    e.preventDefault()
    setSnazzyPage(1)
    fetchSnazzyStyles(snazzySearch, 1)
  }

  function handleSnazzySelect(style) {
    setJsonDraft(style.json)
    setJsonError(null)
    setJsonOk(true)
    onActivate?.(style.json)
  }

  // ── JSON editor ────────────────────────────────────────────────────────────
  function handleJsonChange(raw) {
    setJsonDraft(raw)
    if (!raw.trim()) { setJsonError(null); setJsonOk(false); return }
    try {
      if (!Array.isArray(JSON.parse(raw))) throw new Error('Doit être un tableau JSON [ … ]')
      setJsonError(null); setJsonOk(true)
    } catch (e) { setJsonError(e.message); setJsonOk(false) }
  }

  function activateJson() {
    if (!jsonOk) return
    onActivate?.(jsonDraft)
  }

  function resetStyle() {
    const empty = '[]'
    setJsonDraft(empty)
    setJsonError(null)
    setJsonOk(false)
    onActivate?.(empty)
  }

  // ── Custom presets ─────────────────────────────────────────────────────────
  function saveCustomPreset() {
    if (!customName.trim() || !jsonOk) return
    const id   = 'custom_' + Date.now()
    const next = [
      ...presets.filter(p => p.name !== customName.trim()),
      { id, name: customName.trim(), json: jsonDraft },
    ]
    onPresetsChange?.(next)
    setCustomName('')
  }

  function deleteCustomPreset(id) {
    onPresetsChange?.(presets.filter(p => p.id !== id))
  }

  const isActive = (json) => activeJson === json

  return (
    <div className="space-y-6">

      {/* ── Aperçu live ──────────────────────────────────────────────────── */}
      <div className="space-y-2">
        <div className="flex items-center justify-between">
          <p className="text-xs text-muted-foreground uppercase tracking-wider font-medium">Aperçu en direct</p>
          {activeJson && activeJson !== '[]' && (
            <button
              type="button"
              onClick={resetStyle}
              className="text-[11px] text-muted-foreground hover:text-destructive transition-colors"
            >
              Réinitialiser (Standard)
            </button>
          )}
        </div>
        <MapPreview styleJson={previewJson} apiKey={apiKey} height={280} />
      </div>

      {/* ── Presets personnalisés ─────────────────────────────────────────── */}
      {presets.length > 0 && (
        <div className="space-y-2">
          <p className="text-xs text-muted-foreground uppercase tracking-wider font-medium">Mes presets</p>
          <div className="flex flex-wrap gap-2">
            {presets.map(p => (
              <div
                key={p.id ?? p.name}
                className={[
                  'flex items-center gap-1 rounded-md border pl-3 pr-1 py-1.5 text-xs transition-colors',
                  isActive(p.json) ? 'border-primary bg-primary/5 text-primary' : 'border-input bg-background',
                ].join(' ')}
              >
                <button type="button" onClick={() => { setJsonDraft(p.json); setJsonOk(true); onActivate?.(p.json) }} className="font-medium">
                  {p.name}
                </button>
                <button
                  type="button"
                  onClick={() => deleteCustomPreset(p.id ?? p.name)}
                  className="ml-1 rounded p-0.5 text-muted-foreground hover:text-destructive transition-colors"
                  title="Supprimer"
                >
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                    <path d="M18 6 6 18M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ── Bibliothèque Snazzy Maps ──────────────────────────────────────── */}
      {snazzymapsEnabled ? (
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <p className="text-xs text-muted-foreground uppercase tracking-wider font-medium">
              Bibliothèque Snazzy Maps
            </p>
            <button
              type="button"
              onClick={() => fetchSnazzyStyles(snazzySearch, snazzyPage)}
              className="text-[11px] text-muted-foreground hover:text-foreground transition-colors"
              disabled={snazzyLoading}
            >
              {snazzyLoading ? 'Chargement…' : 'Actualiser'}
            </button>
          </div>

          {/* Recherche */}
          <form onSubmit={handleSnazzySearch} className="flex gap-2">
            <input
              type="text"
              value={snazzySearch}
              onChange={e => setSnazzySearch(e.target.value)}
              placeholder="Rechercher un style… (ex: dark, minimal, nature)"
              className="flex-1 h-8 rounded-md border border-input bg-background px-3 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            />
            <button
              type="submit"
              disabled={snazzyLoading}
              className="px-3 h-8 text-xs font-medium rounded-md border border-input bg-background hover:bg-accent disabled:opacity-50 transition-colors"
            >
              Rechercher
            </button>
          </form>

          {snazzyError && (
            <p className="text-[11px] text-destructive flex items-center gap-1.5">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
              </svg>
              {snazzyError}
            </p>
          )}

          {snazzyLoading ? (
            <div className="grid grid-cols-3 gap-2">
              {Array.from({ length: 6 }).map((_, i) => (
                <div key={i} className="rounded-lg border border-input bg-muted/30 h-28 animate-pulse" />
              ))}
            </div>
          ) : snazzyStyles.length > 0 ? (
            <div className="grid grid-cols-3 gap-2">
              {snazzyStyles.map(s => (
                <SnazzyCard
                  key={s.id}
                  style={s}
                  isActive={isActive(s.json)}
                  onSelect={handleSnazzySelect}
                />
              ))}
            </div>
          ) : !snazzyError && (
            <p className="text-xs text-muted-foreground text-center py-6">Aucun style trouvé.</p>
          )}
        </div>
      ) : (
        <div className="rounded-lg border border-dashed border-border p-4 text-center space-y-1">
          <p className="text-xs font-medium">Bibliothèque Snazzy Maps</p>
          <p className="text-[11px] text-muted-foreground">
            Configurez votre clé API Snazzy Maps ci-dessus pour accéder à des centaines de styles.
          </p>
        </div>
      )}

      {/* ── JSON personnalisé ─────────────────────────────────────────────── */}
      <div className="space-y-2">
        <button
          type="button"
          onClick={() => setShowEditor(v => !v)}
          className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors w-full"
        >
          <svg
            width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"
            className={`transition-transform ${showEditor ? 'rotate-90' : ''}`}
          >
            <path d="M9 18l6-6-6-6"/>
          </svg>
          <span className="uppercase tracking-wider font-medium">Style personnalisé (JSON)</span>
          <a
            href="https://snazzymaps.com"
            target="_blank"
            rel="noreferrer"
            className="ml-auto text-[11px] hover:text-foreground underline underline-offset-2"
            onClick={e => e.stopPropagation()}
          >
            Snazzy Maps →
          </a>
        </button>

        {showEditor && (
          <div className="space-y-2 pt-1">
            <textarea
              value={jsonDraft}
              onChange={e => handleJsonChange(e.target.value)}
              spellCheck={false}
              rows={7}
              placeholder={'[\n  {\n    "featureType": "water",\n    "elementType": "geometry",\n    "stylers": [{ "color": "#193341" }]\n  }\n]'}
              className={[
                'w-full rounded-md border px-3 py-2.5 font-mono text-xs leading-relaxed resize-y bg-transparent',
                'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                jsonError ? 'border-destructive' : jsonOk ? 'border-emerald-400' : 'border-input',
              ].join(' ')}
            />
            {jsonError && (
              <span className="text-[11px] text-destructive flex items-center gap-1">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                {jsonError}
              </span>
            )}
            {jsonOk && !jsonError && (
              <span className="text-[11px] text-emerald-600 flex items-center gap-1">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M20 6 9 17l-5-5"/></svg>
                JSON valide — aperçu mis à jour
              </span>
            )}
            <div className="flex items-center gap-2 flex-wrap pt-0.5">
              <button
                type="button"
                disabled={!jsonOk}
                onClick={activateJson}
                className="px-3 py-1.5 text-xs font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none transition-colors"
              >
                Appliquer
              </button>
              <div className="flex items-center gap-1.5 ml-auto">
                <input
                  type="text"
                  value={customName}
                  onChange={e => setCustomName(e.target.value)}
                  onKeyDown={e => e.key === 'Enter' && saveCustomPreset()}
                  placeholder="Nom du preset…"
                  className="h-7 rounded-md border border-input bg-background px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring w-36"
                />
                <button
                  type="button"
                  disabled={!customName.trim() || !jsonOk}
                  onClick={saveCustomPreset}
                  className="px-2.5 py-1 text-xs font-medium rounded-md border border-input bg-background hover:bg-accent disabled:opacity-50 disabled:pointer-events-none transition-colors"
                >
                  Sauvegarder
                </button>
              </div>
            </div>
          </div>
        )}
      </div>

    </div>
  )
}
