import { useState } from 'react'

// ── Built-in Google Maps style presets ────────────────────────────────────────
// Full JSON styles from Google Maps Platform / Snazzy Maps.
// activeJson is the currently saved style; user can activate a preset or keep custom.

const BUILT_IN_PRESETS = [
  {
    name: 'Standard',
    description: 'Style Google par défaut',
    value: '[]',
    preview: '#e8eaed',
  },
  {
    name: 'Silver',
    description: 'Tons gris clair, épuré',
    preview: '#c8c8c8',
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
    preview: '#dfd2ae',
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
    preview: '#212121',
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
    preview: '#0f172a',
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
    preview: '#1a0533',
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

/**
 * MapPresets — preset picker + custom JSON editor for Google Maps styles.
 *
 * Props:
 *   presets         {array}    User-saved custom presets (name + value)
 *   activeJson      {string}   Currently active style JSON
 *   onPresetsChange {fn}       Called with updated presets array
 *   onActivate      {fn}       Called with the JSON string to activate
 */
export default function MapPresets({ presets = [], activeJson = '', onPresetsChange, onActivate }) {
  const [customName, setCustomName]   = useState('')
  const [jsonDraft,  setJsonDraft]    = useState(activeJson)
  const [jsonError,  setJsonError]    = useState(null)
  const [jsonOk,     setJsonOk]       = useState(false)

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
    const next = [...presets.filter(p => p.name !== customName.trim()), { name: customName.trim(), value: jsonDraft }]
    onPresetsChange?.(next)
    setCustomName('')
  }

  function deleteCustomPreset(name) {
    onPresetsChange?.(presets.filter(p => p.name !== name))
  }

  const isActive = (val) => activeJson === val

  return (
    <div className="space-y-5">
      {/* Built-in presets */}
      <div>
        <p className="text-xs text-muted-foreground uppercase tracking-wider mb-2">Presets intégrés</p>
        <div className="grid grid-cols-3 gap-2">
          {BUILT_IN_PRESETS.map(p => (
            <button
              key={p.name}
              type="button"
              onClick={() => activatePreset(p.value)}
              className={`flex items-center gap-2.5 rounded-lg border px-3 py-2.5 text-left transition-colors hover:bg-accent ${
                isActive(p.value) ? 'border-primary ring-1 ring-primary bg-primary/5' : 'border-input bg-background'
              }`}
            >
              <span
                className="w-5 h-5 rounded-full border border-black/10 shrink-0"
                style={{ background: p.preview }}
              />
              <span className="min-w-0">
                <span className="block text-xs font-medium leading-tight">{p.name}</span>
                <span className="block text-[11px] text-muted-foreground leading-tight truncate">{p.description}</span>
              </span>
              {isActive(p.value) && (
                <svg className="ml-auto shrink-0 text-primary" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3">
                  <path d="M20 6 9 17l-5-5"/>
                </svg>
              )}
            </button>
          ))}
        </div>
      </div>

      {/* Custom presets */}
      {presets.length > 0 && (
        <div>
          <p className="text-xs text-muted-foreground uppercase tracking-wider mb-2">Mes presets</p>
          <div className="flex flex-wrap gap-2">
            {presets.map(p => (
              <div key={p.name} className={`flex items-center gap-1 rounded-md border pl-3 pr-1 py-1 text-xs transition-colors ${
                isActive(p.value) ? 'border-primary bg-primary/5 text-primary' : 'border-input bg-background'
              }`}>
                <button type="button" onClick={() => activatePreset(p.value)} className="font-medium">
                  {p.name}
                </button>
                <button
                  type="button"
                  onClick={() => deleteCustomPreset(p.name)}
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

      {/* JSON editor */}
      <div className="space-y-2">
        <p className="text-xs text-muted-foreground uppercase tracking-wider">Style personnalisé (JSON)</p>
        <textarea
          value={jsonDraft}
          onChange={e => handleJsonChange(e.target.value)}
          spellCheck={false}
          rows={8}
          placeholder={'[\n  {\n    "featureType": "water",\n    "elementType": "geometry",\n    "stylers": [{ "color": "#193341" }]\n  }\n]'}
          className={`w-full rounded-md border px-3 py-2.5 font-mono text-xs leading-relaxed resize-y bg-transparent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring ${
            jsonError ? 'border-destructive' : jsonOk ? 'border-emerald-400' : 'border-input'
          }`}
        />
        {jsonError && (
          <span className="text-[11px] text-destructive flex items-center gap-1">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            JSON invalide : {jsonError}
          </span>
        )}
        {jsonOk && !jsonError && (
          <span className="text-[11px] text-emerald-600 flex items-center gap-1">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M20 6 9 17l-5-5"/></svg>
            JSON valide
          </span>
        )}
        <div className="flex items-center gap-2 flex-wrap">
          <button
            type="button"
            disabled={!jsonOk}
            onClick={() => onActivate?.(jsonDraft)}
            className="px-3 py-1.5 text-xs font-medium rounded-md border bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none transition-colors"
          >
            Appliquer ce style
          </button>
          <div className="flex items-center gap-1.5">
            <input
              type="text"
              value={customName}
              onChange={e => setCustomName(e.target.value)}
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
          {!jsonDraft && (
            <a href="https://snazzymaps.com" target="_blank" rel="noreferrer"
              className="text-[11px] text-muted-foreground underline hover:text-foreground ml-1">
              Snazzy Maps
            </a>
          )}
        </div>
      </div>
    </div>
  )
}
