import { useState } from 'react'
import CodeEditor from '@uiw/react-textarea-code-editor'

// ── Sections metadata ─────────────────────────────────────────────────────────
export const SECTIONS = {
  api:         { title: 'Connexion API',      subtitle: 'Regiondo · Google Analytics · Search Console · IA (Anthropic / OpenAI / Gemini) · Snazzy Maps', hasSave: true  },
  sync:        { title: 'Synchronisation',    subtitle: 'Import automatique des produits Regiondo vers WordPress', hasSave: true  },
  css:         { title: 'Custom CSS & JS',     subtitle: 'CSS et JS injectés dans chaque widget de réservation',    hasSave: true  },
  map:         { title: 'Map Style',          subtitle: 'Style JSON Google Maps pour les widgets carte',           hasSave: true  },
  cache:       { title: 'Cache API',          subtitle: 'Durée de mise en cache des réponses Regiondo',           hasSave: true  },
  'manual-sync':        { title: 'Sync produits',        subtitle: 'Synchronisation immédiate des produits Regiondo → WordPress',                   hasSave: false },
  'bookings-sync':      { title: 'Sync réservations',   subtitle: 'Importe toutes les réservations Regiondo dans la base de données',               hasSave: false },
  'reservations-import':{ title: 'Import solditems',    subtitle: 'Importe les articles vendus enrichis (solditems) — CA réel, remboursements…',    hasSave: false },
  'stats-import':       { title: 'Import Stats',        subtitle: 'Importe les participations depuis un CSV externe (OTA, billetterie…)',            hasSave: false },
  diagnostic:           { title: 'Diagnostic API',      subtitle: 'Teste chaque endpoint et affiche les réponses brutes',                            hasSave: false },
  installation:         { title: 'Installation',        subtitle: 'Relancer le wizard de configuration initiale du plugin',                          hasSave: false },
  permissions:          { title: 'Permissions',         subtitle: 'Droits d\'accès par rôle WordPress',                                              hasSave: false },
  markdown:             { title: 'Markdown Editor',     subtitle: 'Post types et style visuel par défaut pour le widget Elementor Markdown Renderer', hasSave: false },
  schema:               { title: 'Schema SEO',          subtitle: 'Injection automatique Schema.org GeoCoordinates (JSON-LD) par post type et taxonomie', hasSave: false },
}

// ── CSS Sanitizer — strips XSS vectors ───────────────────────────────────────
const CSS_DANGEROUS = [
  /expression\s*\(/gi, /javascript\s*:/gi, /-moz-binding\s*:/gi,
  /behavior\s*:/gi, /url\s*\(\s*["']?\s*data\s*:\s*text\/html/gi,
  /<\/?script/gi, /<\/?style/gi, /@import\s+url/gi,
]
export function sanitizeCss(raw) {
  let css = raw
  for (const p of CSS_DANGEROUS) css = css.replace(p, '/* blocked */')
  return css
}
export function getCssWarnings(raw) {
  const w = []
  if (/expression\s*\(/i.test(raw)) w.push('expression() bloqué (vecteur XSS IE)')
  if (/javascript\s*:/i.test(raw)) w.push('javascript: bloqué')
  if (/-moz-binding/i.test(raw)) w.push('-moz-binding bloqué')
  if (/behavior\s*:/i.test(raw)) w.push('behavior: bloqué')
  if (/<script/i.test(raw)) w.push('Balise <script> bloquée')
  if (/@import\s+url/i.test(raw)) w.push('@import url() bloqué')
  return w
}

// ── Style commun des éditeurs de code ────────────────────────────────────────
export const EDITOR_STYLE = {
  fontFamily: '"JetBrains Mono", "Fira Code", "Cascadia Code", ui-monospace, monospace',
  fontSize: 12,
  lineHeight: 1.6,
  minHeight: 200,
  borderRadius: '0 0 0.375rem 0.375rem', // coins bas seulement (le haut = tabs)
  border: '1px solid var(--border, #e5e7eb)',
  borderTop: 'none',
  background: '#fff',
}

// ── Warnings CSS XSS ─────────────────────────────────────────────────────────
export function CssWarnings({ warnings }) {
  if (!warnings.length) return null
  return warnings.map((w, i) => (
    <span key={i} className="text-[11px] text-destructive flex items-center gap-1">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
      </svg>
      {w}
    </span>
  ))
}

// ── CssEditor ─────────────────────────────────────────────────────────────────
export function CssEditor({ value, onChange, placeholder }) {
  return (
    <>
      <CodeEditor
        value={value || ''}
        language="css"
        placeholder={placeholder}
        onChange={e => onChange(sanitizeCss(e.target.value))}
        padding={12}
        style={EDITOR_STYLE}
        data-color-mode="light"
      />
      <CssWarnings warnings={getCssWarnings(value || '')} />
    </>
  )
}

// ── JsEditor ─────────────────────────────────────────────────────────────────
export function JsEditor({ value, onChange }) {
  return (
    <CodeEditor
      value={value || ''}
      language="js"
      placeholder={"// Cibler le widget :\n// document.querySelector('booking-widget').addEventListener('load', function() {\n//   console.log('widget ready')\n// })"}
      onChange={e => onChange(e.target.value)}
      padding={12}
      style={EDITOR_STYLE}
      data-color-mode="light"
    />
  )
}

// ── MapStyleEditor ────────────────────────────────────────────────────────────
export function MapStyleEditor({ value, onChange }) {
  const [error, setError] = useState(null)
  const [ok,    setOk]    = useState(false)

  function handleChange(e) {
    const raw = e.target.value
    onChange(raw)
    if (!raw.trim()) { setError(null); setOk(false); return }
    try {
      if (!Array.isArray(JSON.parse(raw))) throw new Error('Doit être un tableau JSON [ ... ]')
      setError(null); setOk(true)
    } catch (err) { setError(err.message); setOk(false) }
  }

  return (
    <div className="flex flex-col gap-2">
      <textarea
        value={value} onChange={handleChange} spellCheck={false} rows={10}
        placeholder={'[\n  {\n    "featureType": "water",\n    "elementType": "geometry",\n    "stylers": [{ "color": "#193341" }]\n  }\n]'}
        className={`w-full rounded-md border px-3 py-2.5 font-mono text-xs leading-relaxed resize-y bg-transparent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring ${error ? 'border-destructive' : ok ? 'border-emerald-400' : 'border-input'}`}
      />
      {error && (
        <span className="text-[11px] text-destructive flex items-center gap-1">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
          JSON invalide : {error}
        </span>
      )}
      {ok && (
        <span className="text-[11px] text-emerald-600 flex items-center gap-1">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M20 6 9 17l-5-5"/></svg>
          JSON valide — style sera injecté sur les pages avec widget carte.
        </span>
      )}
      {!value && (
        <p className="text-[11px] text-muted-foreground">
          Ressources : <a href="https://snazzymaps.com" target="_blank" rel="noreferrer" className="underline hover:text-foreground">Snazzy Maps</a>
          {' · '}
          <a href="https://mapstyle.withgoogle.com" target="_blank" rel="noreferrer" className="underline hover:text-foreground">Google Map Styler</a>
        </p>
      )}
    </div>
  )
}

export const INTERVALS = [
  { value: 0,    label: 'Désactivée (manuel uniquement)' },
  { value: 30,   label: 'Toutes les 30 minutes' },
  { value: 60,   label: 'Toutes les heures' },
  { value: 360,  label: 'Toutes les 6 heures' },
  { value: 1440, label: 'Une fois par jour' },
]

// ── Utility helpers ──────────────────────────────────────────────────────────
export function fmtYMD(d) {
  return [d.getFullYear(), String(d.getMonth()+1).padStart(2,'0'), String(d.getDate()).padStart(2,'0')].join('-')
}
export function daysAgo(n) { const d = new Date(); d.setDate(d.getDate() - n); return d }
