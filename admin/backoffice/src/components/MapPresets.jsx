import { useState, useRef, useEffect, useCallback } from 'react'

// ── Built-in Google Maps style presets ────────────────────────────────────────
// Colors arrays represent the dominant tones used in the gradient preview swatch.
const BUILT_IN_PRESETS = [
  {
    name: 'Standard',
    description: 'Style Google par défaut',
    colors: ['#e8eaed', '#f5f5f5', '#ffffff'],
    value: '[]',
  },
  {
    name: 'Silver',
    description: 'Tons gris, épuré',
    colors: ['#c8c8c8', '#f5f5f5', '#dadada'],
    value: JSON.stringify([
      { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
      { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] },
      { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f5f5' }] },
      { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
      { featureType: 'road.arterial', elementType: 'labels.text.fill', stylers: [{ color: '#757575' }] },
      { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#dadada' }] },
      { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#c9c9c9' }] },
      { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#9e9e9e' }] },
    ]),
  },
  {
    name: 'Retro',
    description: 'Tons sépia chauds',
    colors: ['#dfd2ae', '#ebe3cd', '#b9d3c2'],
    value: JSON.stringify([
      { elementType: 'geometry', stylers: [{ color: '#ebe3cd' }] },
      { elementType: 'labels.text.fill', stylers: [{ color: '#523735' }] },
      { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f1e6' }] },
      { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#f5f1e6' }] },
      { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#f8c967' }] },
      { featureType: 'road.highway', elementType: 'geometry.stroke', stylers: [{ color: '#e9bc62' }] },
      { featureType: 'water', elementType: 'geometry.fill', stylers: [{ color: '#b9d3c2' }] },
      { featureType: 'poi.park', elementType: 'geometry.fill', stylers: [{ color: '#a5b076' }] },
    ]),
  },
  {
    name: 'Dark',
    description: 'Fond sombre, labels clairs',
    colors: ['#212121', '#383838', '#000000'],
    value: JSON.stringify([
      { elementType: 'geometry', stylers: [{ color: '#212121' }] },
      { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { elementType: 'labels.text.fill', stylers: [{ color: '#757575' }] },
      { elementType: 'labels.text.stroke', stylers: [{ color: '#212121' }] },
      { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#383838' }] },
      { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#616161' }] },
      { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#000000' }] },
      { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#3d3d3d' }] },
    ]),
  },
  {
    name: 'Night',
    description: 'Bleu nuit, style dashboard',
    colors: ['#1d2c4d', '#304a7d', '#0e1626'],
    value: JSON.stringify([
      { elementType: 'geometry', stylers: [{ color: '#1d2c4d' }] },
      { elementType: 'labels.text.fill', stylers: [{ color: '#8ec3b9' }] },
      { elementType: 'labels.text.stroke', stylers: [{ color: '#1a3646' }] },
      { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#304a7d' }] },
      { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#2c6675' }] },
      { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0e1626' }] },
      { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#4e6d70' }] },
      { featureType: 'poi', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
    ]),
  },
  {
    name: 'Aubergine',
    description: 'Violet profond, labels dorés',
    colors: ['#1d0b40', '#3d1c5d', '#17263c'],
    value: JSON.stringify([
      { elementType: 'geometry', stylers: [{ color: '#1d0b40' }] },
      { elementType: 'labels.text.fill', stylers: [{ color: '#f8c967' }] },
      { elementType: 'labels.text.stroke', stylers: [{ color: '#100c23' }] },
      { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#3d1c5d' }] },
      { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#6a2f8c' }] },
      { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#17263c' }] },
      { featureType: 'poi', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
    ]),
  },
]

// ── MapPreview — live Google Maps preview ─────────────────────────────────────
// Loads Maps JS API with the Elementor API key and shows a small interactive map.

function parseStyle(json) {
  if (!json || json === '[]') return []
  try { return JSON.parse(json) } catch { return [] }
}

function MapPreview({ styleJson, apiKey }) {
  const containerRef = useRef(null)
  const mapRef       = useRef(null)
  const loadedRef    = useRef(false)

  const initMap = useCallback(() => {
    if (!containerRef.current || mapRef.current) return
    // Marseille — French coast, relevant for this sailing/activities app
    mapRef.current = new window.google.maps.Map(containerRef.current, {
      center: { lat: 43.2965, lng: 5.3698 },
      zoom: 13,
      disableDefaultUI: true,
      gestureHandling: 'none',
      styles: parseStyle(styleJson),
    })
  }, []) // eslint-disable-line react-hooks/exhaustive-deps

  // Load Maps JS API once
  useEffect(() => {
    if (!apiKey || loadedRef.current) return
    loadedRef.current = true

    if (window.google?.maps?.Map) {
      initMap()
      return
    }

    window.__btMapPreviewCb = initMap
    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&callback=__btMapPreviewCb`
    script.async = true
    document.head.appendChild(script)

    return () => {
      delete window.__btMapPreviewCb
    }
  }, [apiKey, initMap])

  // Apply style changes to existing map instance
  useEffect(() => {
    if (!mapRef.current) return
    mapRef.current.setOptions({ styles: parseStyle(styleJson) })
  }, [styleJson])

  if (!apiKey) {
    return (
      <div className="rounded-lg border border-dashed border-border h-40 flex flex-col items-center justify-center gap-1.5 text-center px-4">
        <svg className="text-muted-foreground/40" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7Z"/>
          <circle cx="12" cy="9" r="2.5"/>
        </svg>
        <p className="text-xs text-muted-foreground">
          Aperçu indisponible — clé API Google Maps manquante.
        </p>
        <p className="text-[11px] text-muted-foreground/70">
          Elementor → Réglages → Intégrations → Google Maps
        </p>
      </div>
    )
  }

  return (
    <div className="rounded-lg overflow-hidden border border-border shadow-sm relative">
      <div ref={containerRef} style={{ height: 200, width: '100%' }} />
      {/* Location badge */}
      <span className="absolute bottom-2 right-2 text-[10px] bg-black/50 text-white/80 rounded px-1.5 py-0.5 backdrop-blur-sm pointer-events-none">
        Marseille · aperçu
      </span>
    </div>
  )
}

// ── PresetSwatch — color gradient strip ───────────────────────────────────────
function PresetSwatch({ colors }) {
  const gradient = `linear-gradient(135deg, ${colors.join(', ')})`
  return (
    <span
      className="w-full h-8 rounded-md border border-black/10 block shrink-0"
      style={{ background: gradient }}
    />
  )
}

// ── MapPresets ─────────────────────────────────────────────────────────────────
/**
 * Preset picker + JSON editor + live preview for Google Maps styles.
 *
 * @param {array}    presets          User-saved custom presets
 * @param {string}   activeJson       Currently active style JSON
 * @param {string}   apiKey           Google Maps JS API key (from Elementor settings)
 * @param {function} onPresetsChange  Called with updated presets array
 * @param {function} onActivate       Called with the JSON string to activate
 */
export default function MapPresets({ presets = [], activeJson = '', apiKey = '', onPresetsChange, onActivate }) {
  const [customName, setCustomName] = useState('')
  const [jsonDraft,  setJsonDraft]  = useState(activeJson)
  const [jsonError,  setJsonError]  = useState(null)
  const [jsonOk,     setJsonOk]     = useState(false)

  // The style shown in the preview: valid jsonDraft if editing, else active
  const previewJson = jsonOk ? jsonDraft : activeJson

  function handleJsonChange(raw) {
    setJsonDraft(raw)
    if (!raw.trim()) { setJsonError(null); setJsonOk(false); return }
    try {
      if (!Array.isArray(JSON.parse(raw))) throw new Error('Doit être un tableau JSON [ ... ]')
      setJsonError(null); setJsonOk(true)
    } catch (e) { setJsonError(e.message); setJsonOk(false) }
  }

  function activatePreset(value) {
    setJsonDraft(value)
    setJsonError(null)
    setJsonOk(value !== '[]' && value !== '')
    onActivate?.(value)
  }

  function saveCustomPreset() {
    if (!customName.trim() || !jsonOk) return
    const next = [
      ...presets.filter(p => p.name !== customName.trim()),
      { name: customName.trim(), value: jsonDraft },
    ]
    onPresetsChange?.(next)
    setCustomName('')
  }

  function deleteCustomPreset(name) {
    onPresetsChange?.(presets.filter(p => p.name !== name))
  }

  const isActive = val => activeJson === val

  return (
    <div className="space-y-6">

      {/* ── Live preview ── */}
      <div className="space-y-2">
        <p className="text-xs text-muted-foreground uppercase tracking-wider font-medium">Aperçu en direct</p>
        <MapPreview styleJson={previewJson} apiKey={apiKey} />
      </div>

      {/* ── Built-in presets ── */}
      <div className="space-y-2">
        <p className="text-xs text-muted-foreground uppercase tracking-wider font-medium">Presets intégrés</p>
        <div className="grid grid-cols-3 gap-2">
          {BUILT_IN_PRESETS.map(p => (
            <button
              key={p.name}
              type="button"
              onClick={() => activatePreset(p.value)}
              className={[
                'flex flex-col gap-2 rounded-lg border p-3 text-left transition-colors hover:bg-accent',
                isActive(p.value)
                  ? 'border-primary ring-1 ring-primary bg-primary/5'
                  : 'border-input bg-background',
              ].join(' ')}
            >
              <PresetSwatch colors={p.colors} />
              <span className="flex items-center justify-between gap-1">
                <span className="min-w-0">
                  <span className="block text-xs font-medium leading-tight">{p.name}</span>
                  <span className="block text-[11px] text-muted-foreground leading-tight truncate">{p.description}</span>
                </span>
                {isActive(p.value) && (
                  <svg className="shrink-0 text-primary" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3">
                    <path d="M20 6 9 17l-5-5"/>
                  </svg>
                )}
              </span>
            </button>
          ))}
        </div>
      </div>

      {/* ── Custom saved presets ── */}
      {presets.length > 0 && (
        <div className="space-y-2">
          <p className="text-xs text-muted-foreground uppercase tracking-wider font-medium">Mes presets</p>
          <div className="flex flex-wrap gap-2">
            {presets.map(p => (
              <div
                key={p.name}
                className={[
                  'flex items-center gap-1 rounded-md border pl-3 pr-1 py-1.5 text-xs transition-colors',
                  isActive(p.value) ? 'border-primary bg-primary/5 text-primary' : 'border-input bg-background',
                ].join(' ')}
              >
                <button type="button" onClick={() => activatePreset(p.value)} className="font-medium">
                  {p.name}
                </button>
                <button
                  type="button"
                  onClick={() => deleteCustomPreset(p.name)}
                  className="ml-1 rounded p-0.5 text-muted-foreground hover:text-destructive transition-colors"
                  title="Supprimer ce preset"
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

      {/* ── Custom JSON editor ── */}
      <div className="space-y-3">
        <div className="flex items-center justify-between">
          <p className="text-xs text-muted-foreground uppercase tracking-wider font-medium">Style personnalisé (JSON)</p>
          <a
            href="https://snazzymaps.com"
            target="_blank"
            rel="noreferrer"
            className="text-[11px] text-muted-foreground hover:text-foreground underline underline-offset-2 transition-colors"
          >
            Snazzy Maps →
          </a>
        </div>

        <div className="relative">
          <textarea
            value={jsonDraft}
            onChange={e => handleJsonChange(e.target.value)}
            spellCheck={false}
            rows={8}
            placeholder={'[\n  {\n    "featureType": "water",\n    "elementType": "geometry",\n    "stylers": [{ "color": "#193341" }]\n  }\n]'}
            className={[
              'w-full rounded-md border px-3 py-2.5 font-mono text-xs leading-relaxed resize-y bg-transparent',
              'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
              jsonError ? 'border-destructive' : jsonOk ? 'border-emerald-400' : 'border-input',
            ].join(' ')}
          />
        </div>

        {/* Validation feedback */}
        {jsonError && (
          <span className="text-[11px] text-destructive flex items-center gap-1">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            JSON invalide : {jsonError}
          </span>
        )}
        {jsonOk && !jsonError && (
          <span className="text-[11px] text-emerald-600 flex items-center gap-1">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M20 6 9 17l-5-5"/></svg>
            JSON valide — aperçu mis à jour
          </span>
        )}

        {/* Actions */}
        <div className="flex items-center gap-2 flex-wrap pt-1">
          <button
            type="button"
            disabled={!jsonOk}
            onClick={() => onActivate?.(jsonDraft)}
            className="px-3 py-1.5 text-xs font-medium rounded-md border bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none transition-colors"
          >
            Appliquer ce style
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
    </div>
  )
}
