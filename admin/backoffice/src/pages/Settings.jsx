import { useState, useEffect, useRef, useCallback } from 'react'
import { useLocation } from 'react-router-dom'
import { api } from '../lib/api'
import { PageHeader, Input, Textarea, Btn, Notice, Spinner, SectionTitle, Divider, Toggle } from '../components/ui'

/* ── CSS Sanitizer — strips XSS vectors ────────────────────────── */
const CSS_DANGEROUS = [
  /expression\s*\(/gi,
  /javascript\s*:/gi,
  /-moz-binding\s*:/gi,
  /behavior\s*:/gi,
  /url\s*\(\s*["']?\s*data\s*:\s*text\/html/gi,
  /<\/?script/gi,
  /<\/?style/gi,
  /@import\s+url/gi,
]

function sanitizeCss(raw) {
  let css = raw
  for (const pattern of CSS_DANGEROUS) {
    css = css.replace(pattern, '/* blocked */')
  }
  return css
}

function getCssWarnings(raw) {
  const warnings = []
  if (/expression\s*\(/i.test(raw)) warnings.push('expression() bloqué (vecteur XSS IE)')
  if (/javascript\s*:/i.test(raw)) warnings.push('javascript: bloqué')
  if (/-moz-binding/i.test(raw)) warnings.push('-moz-binding bloqué')
  if (/behavior\s*:/i.test(raw)) warnings.push('behavior: bloqué')
  if (/<script/i.test(raw)) warnings.push('Balise <script> bloquée')
  if (/@import\s+url/i.test(raw)) warnings.push('@import url() bloqué')
  return warnings
}

/* ── CSS Tokenizer + Highlighter (single-pass, no regex overlap) ── */
const CSS_COLORS = {
  comment:  '#6a737d',
  string:   '#032f62',
  selector: '#22863a',
  property: '#6f42c1',
  value:    '#222',
  number:   '#005cc5',
  hex:      '#e36209',
  brace:    '#586069',
  colon:    '#999',
  semi:     '#999',
  atrule:   '#d73a49',
}

function esc(s) {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
}

function span(color, text) {
  return `<span style="color:${color}">${text}</span>`
}

function highlightCss(code) {
  if (!code) return ''
  let out = ''
  let i = 0
  const len = code.length
  // Track whether we're inside { } to distinguish selectors from properties
  let inBlock = 0

  while (i < len) {
    // ── Comment /* ... */
    if (code[i] === '/' && code[i + 1] === '*') {
      const end = code.indexOf('*/', i + 2)
      const slice = end === -1 ? code.slice(i) : code.slice(i, end + 2)
      out += span(CSS_COLORS.comment, esc(slice))
      i += slice.length
      continue
    }

    // ── String "..." or '...'
    if (code[i] === '"' || code[i] === "'") {
      const q = code[i]
      let j = i + 1
      while (j < len && code[j] !== q) { if (code[j] === '\\') j++; j++ }
      const slice = code.slice(i, j + 1)
      out += span(CSS_COLORS.string, esc(slice))
      i = j + 1
      continue
    }

    // ── Braces
    if (code[i] === '{') { inBlock++; out += span(CSS_COLORS.brace, '{'); i++; continue }
    if (code[i] === '}') { inBlock = Math.max(0, inBlock - 1); out += span(CSS_COLORS.brace, '}'); i++; continue }

    // ── Semicolon
    if (code[i] === ';') { out += span(CSS_COLORS.semi, ';'); i++; continue }

    // ── Inside a block: property: value;
    if (inBlock > 0) {
      // Whitespace / newlines — pass through
      if (/\s/.test(code[i])) { out += esc(code[i]); i++; continue }

      // Property name (word-chars and hyphens before a colon)
      const propMatch = code.slice(i).match(/^([a-zA-Z-]+)(\s*)(:\s*)/)
      if (propMatch) {
        out += span(CSS_COLORS.property, esc(propMatch[1]))
        out += esc(propMatch[2])
        out += span(CSS_COLORS.colon, esc(propMatch[3]))
        i += propMatch[0].length

        // Now consume the value until ; or } or end
        let val = ''
        while (i < len && code[i] !== ';' && code[i] !== '}') {
          // Nested comment in value
          if (code[i] === '/' && code[i + 1] === '*') {
            // Flush accumulated value
            if (val) { out += colorizeValue(val); val = '' }
            const cEnd = code.indexOf('*/', i + 2)
            const cSlice = cEnd === -1 ? code.slice(i) : code.slice(i, cEnd + 2)
            out += span(CSS_COLORS.comment, esc(cSlice))
            i += cSlice.length
            continue
          }
          // String in value
          if (code[i] === '"' || code[i] === "'") {
            if (val) { out += colorizeValue(val); val = '' }
            const q = code[i]
            let j = i + 1
            while (j < len && code[j] !== q) { if (code[j] === '\\') j++; j++ }
            out += span(CSS_COLORS.string, esc(code.slice(i, j + 1)))
            i = j + 1
            continue
          }
          val += code[i]
          i++
        }
        if (val) out += colorizeValue(val)
        continue
      }

      // Fallback: just output the char
      out += esc(code[i]); i++; continue
    }

    // ── Outside block: selector or at-rule
    // @-rule
    if (code[i] === '@') {
      let j = i
      while (j < len && code[j] !== '{' && code[j] !== ';' && code[j] !== '\n') j++
      out += span(CSS_COLORS.atrule, esc(code.slice(i, j)))
      i = j
      continue
    }

    // Selector: everything until {
    if (/[a-zA-Z.#:\[\]_*>,~+\-]/.test(code[i])) {
      let j = i
      while (j < len && code[j] !== '{' && code[j] !== '}') j++
      out += span(CSS_COLORS.selector, esc(code.slice(i, j)))
      i = j
      continue
    }

    // Anything else (whitespace, newlines outside blocks)
    out += esc(code[i]); i++
  }

  return out
}

// Colorize a CSS value fragment — highlight #hex and numbers
function colorizeValue(val) {
  return esc(val)
    .replace(/(#[0-9a-fA-F]{3,8})\b/g, `<span style="color:${CSS_COLORS.hex}">$1</span>`)
    .replace(/\b(\d+\.?\d*)(px|em|rem|%|vh|vw|vmin|vmax|s|ms|deg|fr|ch|ex)?\b/g,
      `<span style="color:${CSS_COLORS.number}">$1$2</span>`)
}

/* ── CssEditor Component ───────────────────────────────────────── */
function CssEditor({ value, onChange, placeholder }) {
  const textareaRef = useRef(null)
  const preRef = useRef(null)
  const warnings = getCssWarnings(value || '')

  const syncScroll = useCallback(() => {
    if (preRef.current && textareaRef.current) {
      preRef.current.scrollTop = textareaRef.current.scrollTop
      preRef.current.scrollLeft = textareaRef.current.scrollLeft
    }
  }, [])

  const handleChange = useCallback((e) => {
    onChange(sanitizeCss(e.target.value))
  }, [onChange])

  // Auto-resize
  useEffect(() => {
    const ta = textareaRef.current
    if (ta) {
      ta.style.height = 'auto'
      ta.style.height = Math.max(120, ta.scrollHeight) + 'px'
    }
  }, [value])

  return (
    <div className="flex flex-col gap-2">
      <div className="relative rounded-md border border-input overflow-hidden font-mono text-xs leading-relaxed">
        {/* Highlighted layer (behind) */}
        <pre
          ref={preRef}
          className="absolute inset-0 m-0 p-3 overflow-hidden pointer-events-none whitespace-pre-wrap break-words"
          aria-hidden="true"
          dangerouslySetInnerHTML={{ __html: highlightCss(value || '') + '\n' }}
          style={{ background: 'transparent', fontFamily: 'inherit', fontSize: 'inherit', lineHeight: 'inherit' }}
        />
        {/* Textarea (on top, transparent text) */}
        <textarea
          ref={textareaRef}
          value={value || ''}
          onChange={handleChange}
          onScroll={syncScroll}
          placeholder={placeholder}
          spellCheck={false}
          className="relative w-full p-3 bg-transparent resize-none outline-none"
          style={{
            color: 'transparent',
            caretColor: 'var(--foreground, #000)',
            fontFamily: 'inherit',
            fontSize: 'inherit',
            lineHeight: 'inherit',
            minHeight: '120px',
            WebkitTextFillColor: 'transparent',
          }}
        />
      </div>
      {warnings.length > 0 && (
        <div className="flex flex-col gap-0.5">
          {warnings.map((w, i) => (
            <span key={i} className="text-[11px] text-destructive flex items-center gap-1">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
              {w}
            </span>
          ))}
        </div>
      )}
    </div>
  )
}

const INTERVALS = [
  { value: 0,    label: 'Désactivée (manuel uniquement)' },
  { value: 30,   label: 'Toutes les 30 minutes' },
  { value: 60,   label: 'Toutes les heures' },
  { value: 360,  label: 'Toutes les 6 heures' },
  { value: 1440, label: 'Une fois par jour' },
]

export default function Settings() {
  const [settings, setSettings] = useState(null)
  const [loading, setLoading]   = useState(true)
  const [saving, setSaving]     = useState(false)
  const [flushing, setFlushing] = useState(false)
  const [testing, setTesting]   = useState(false)
  const [saved, setSaved]       = useState(false)
  const [error, setError]       = useState(null)
  const [testResult, setTestResult] = useState(null)
  const location                = useLocation()
  const didScroll               = useRef(false)

  useEffect(() => {
    api.settings()
      .then(setSettings)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    if (!loading && location.hash && !didScroll.current) {
      didScroll.current = true
      const el = document.getElementById(location.hash.slice(1))
      if (el) el.scrollIntoView({ behavior: 'smooth' })
    }
  }, [loading, location.hash])

  function set(key, value) {
    setSettings(prev => ({ ...prev, [key]: value }))
  }

  function setWidgetId(productId, value) {
    setSettings(prev => {
      const map = { ...prev.widget_map }
      const existing = map[productId]
      // Keep custom_css if it was per-product (backward compat), but we don't use it anymore
      if (typeof existing === 'object') {
        map[productId] = { ...existing, widget_id: value }
      } else {
        map[productId] = { widget_id: value, custom_css: '' }
      }
      return { ...prev, widget_map: map }
    })
  }

  function getWidgetId(productId) {
    const v = settings?.widget_map?.[productId]
    if (typeof v === 'string') return v
    return v?.widget_id ?? ''
  }

  async function handleSave() {
    setSaving(true)
    setSaved(false)
    setError(null)
    try {
      await api.saveSettings(settings)
      setSaved(true)
      setTimeout(() => setSaved(false), 3000)
    } catch (e) {
      setError(e.message)
    } finally {
      setSaving(false)
    }
  }

  async function handleFlush() {
    setFlushing(true)
    try {
      await api.flushCache()
    } finally {
      setFlushing(false)
    }
  }

  async function handleTestApi() {
    setTesting(true)
    setTestResult(null)
    try {
      const res = await api.testConnection()
      setTestResult(res)
    } catch (e) {
      setTestResult({ success: false, message: e.message })
    } finally {
      setTesting(false)
    }
  }

  if (loading) return (
    <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
  )

  if (!settings) return (
    <div className="px-6 py-8">
      <Notice type="error">{error ?? 'Impossible de charger les réglages.'}</Notice>
    </div>
  )

  const syncPostType = settings.post_types?.[0] ?? 'excursion'

  return (
    <div>
      <PageHeader
        title="Réglages"
        subtitle="Configuration de la connexion Regiondo et des widgets"
        actions={<Btn loading={saving} onClick={handleSave}>Enregistrer</Btn>}
      />

      <div className="max-w-2xl mx-6 mt-6 pb-16 space-y-8">

        {error && <Notice type="error">{error}</Notice>}
        {saved && <Notice type="success">Réglages enregistrés.</Notice>}

        {/* ── Credentials ───────────────────────────────────────── */}
        <section id="api">
          <SectionTitle>Connexion API Regiondo</SectionTitle>
          <p className="text-sm text-muted-foreground mt-1 mb-4">
            Retrouvez vos clés dans Regiondo → Paramètres → API.
          </p>
          <div className="space-y-3">
            <Input
              label="Clé publique (Public Key)"
              value={settings.public_key ?? ''}
              onChange={e => set('public_key', e.target.value)}
              placeholder="Votre clé publique Regiondo"
            />
            <Input
              label="Clé secrète (Secret Key)"
              type="password"
              value={settings.secret_key ?? ''}
              onChange={e => set('secret_key', e.target.value)}
              placeholder="Votre clé secrète Regiondo"
            />
            <div className="flex items-center gap-3">
              <Btn variant="secondary" size="sm" loading={testing} onClick={handleTestApi}>
                Tester la connexion
              </Btn>
              {testResult && (
                <span className={`text-xs ${testResult.success ? 'text-emerald-600' : 'text-destructive'}`}>
                  {testResult.message}
                </span>
              )}
            </div>
          </div>
        </section>

        <Divider />

        {/* ── Auto-sync ───────────────────────────────────────── */}
        <section id="sync">
          <SectionTitle>Synchronisation automatique des produits</SectionTitle>
          <p className="text-sm text-muted-foreground mt-1 mb-4">
            Importe automatiquement les produits Regiondo vers des posts WordPress.
          </p>
          <div className="space-y-3">
            <label className="flex flex-col gap-1.5">
              <span className="text-sm font-medium">Fréquence</span>
              <select
                value={settings.sync_interval ?? 0}
                onChange={e => set('sync_interval', Number(e.target.value))}
                className="flex h-9 w-64 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                {INTERVALS.map(o => (
                  <option key={o.value} value={o.value}>{o.label}</option>
                ))}
              </select>
            </label>

            <label className="flex flex-col gap-1.5">
              <span className="text-sm font-medium">Type de post cible</span>
              <select
                value={syncPostType}
                onChange={e => set('post_types', [e.target.value])}
                className="flex h-9 w-64 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                {(settings.all_post_types ?? []).map(pt => (
                  <option key={pt.name} value={pt.name}>{pt.label} ({pt.name})</option>
                ))}
              </select>
            </label>

            {settings.sync_next_run && (
              <p className="text-xs text-muted-foreground">
                Prochaine sync : {new Date(settings.sync_next_run * 1000).toLocaleString('fr-FR')}
              </p>
            )}
          </div>
        </section>

        <Divider />

        {/* ── Widget map ───────────────────────────────────────── */}
        <section id="widgets">
          <SectionTitle>Widgets Regiondo — ID par produit</SectionTitle>
          <p className="text-sm text-muted-foreground mt-1 mb-4">
            Associez chaque produit à son Widget ID Regiondo.
            <br />
            <span className="text-xs">Regiondo → Shop Config → Website Integration → Booking Widgets</span>
          </p>

          {(settings.products ?? []).length === 0 && (
            <Notice type="warn">Aucun produit. Vérifiez la clé API puis enregistrez.</Notice>
          )}

          <div className="space-y-3">
            {(settings.products ?? []).map(p => (
              <div key={p.product_id} className="flex items-center gap-3 rounded-lg border bg-card px-4 py-3">
                {p.thumbnail_url && (
                  <img src={p.thumbnail_url} alt="" className="w-8 h-8 rounded object-cover border shrink-0" />
                )}
                <div className="shrink-0 w-40">
                  <div className="text-sm font-medium truncate">{p.name}</div>
                  <code className="text-[11px] text-muted-foreground">#{p.product_id}</code>
                </div>
                <div className="flex-1">
                  <Input
                    value={getWidgetId(p.product_id)}
                    onChange={e => setWidgetId(p.product_id, e.target.value)}
                    placeholder="Widget ID (UUID)"
                    className="font-mono text-xs"
                  />
                </div>
              </div>
            ))}
          </div>
        </section>

        <Divider />

        {/* ── Global Custom CSS for booking widgets ──────────── */}
        <section id="widget-css">
          <SectionTitle>Custom CSS — Widgets de réservation</SectionTitle>
          <p className="text-sm text-muted-foreground mt-1 mb-4">
            Ce CSS sera injecté dans chaque <code className="bg-muted px-1 py-0.5 rounded text-[11px]">{'<booking-widget>'}</code> sur le front.
            Il s'applique à tous les widgets de réservation Regiondo.
          </p>
          <CssEditor
            value={settings.booking_custom_css ?? ''}
            onChange={v => set('booking_custom_css', v)}
            placeholder={`.regiondo-widget .regiondo-button-addtocart {\n  border-radius: 40px;\n  background: #222;\n}`}
          />
        </section>

        <Divider />

        {/* ── Cache ─────────────────────────────────────────────── */}
        <section id="cache">
          <SectionTitle>Cache API</SectionTitle>
          <div className="flex items-end gap-3 mt-4">
            <div className="w-36">
              <Input
                label="Durée (secondes)"
                type="number"
                min={60}
                value={settings.cache_ttl ?? 3600}
                onChange={e => set('cache_ttl', Number(e.target.value))}
              />
            </div>
            <Btn variant="secondary" loading={flushing} onClick={handleFlush}>
              Vider maintenant
            </Btn>
          </div>
          <p className="text-xs text-muted-foreground mt-2">3600 = 1 heure (recommandé)</p>
        </section>

        <div className="pt-4">
          <Btn loading={saving} onClick={handleSave}>Enregistrer les réglages</Btn>
        </div>

      </div>
    </div>
  )
}
