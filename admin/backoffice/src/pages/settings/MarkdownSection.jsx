/**
 * MarkdownSection — Onglet settings pour le plugin Elementor Markdown Renderer (EMW).
 * Consomme les endpoints REST emw/v1 enregistrés par le plugin EMW.
 * Namespace séparé → appels REST directs (pas via api.js bt-regiondo/v1).
 */
import { useState, useEffect, useCallback } from 'react'
import { Notice, Spinner, Btn, SectionTitle } from '../../components/ui'

// ── Helper REST vers emw/v1 ───────────────────────────────────────────
const { nonce } = window.btBackoffice || {}
const EMW_BASE = (() => {
  // Extrait la racine WP REST depuis l'URL BT (ex: https://…/wp-json/bt-regiondo/v1/)
  const { rest_url = '' } = window.btBackoffice || {}
  const match = rest_url.match(/^(https?:\/\/.+?\/wp-json\/)/)
  return match ? match[1] + 'emw/v1' : '/wp-json/emw/v1'
})()

async function emwFetch(path, options = {}) {
  const res = await fetch(`${EMW_BASE}${path}`, {
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
      ...(options.headers || {}),
    },
    ...options,
  })
  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || `Erreur HTTP ${res.status}`)
  }
  return res.json()
}

// ── Constantes couleurs ───────────────────────────────────────────────
const COLOR_KEYS = [
  'base_color','link_color',
  'h1_color','h2_color','h3_color','h4_color','h5_color','h6_color',
  'bq_border_color','bq_bg','hr_color',
  'code_bg','code_color','pre_bg','pre_color',
  'table_header_bg','table_stripe_bg','table_border_color',
]
const SLIDER_KEYS = [
  'max_width','p_margin_bottom','li_spacing','bq_border_width',
  'hr_height','img_border_radius','img_max_width',
  'h1_size','h2_size','h3_size','h4_size','h5_size','h6_size',
]

// ── Composants de champs ──────────────────────────────────────────────
function FieldColor({ label, value = '', onChange }) {
  return (
    <div className="flex flex-col gap-1">
      <label className="text-xs text-muted-foreground">{label}</label>
      <div className="flex items-center gap-2">
        <input
          type="color"
          value={value || '#000000'}
          onChange={e => onChange(e.target.value)}
          className="w-8 h-8 rounded cursor-pointer border border-input p-0.5"
        />
        <input
          type="text"
          value={value}
          onChange={e => onChange(e.target.value)}
          maxLength={7}
          placeholder="#000000"
          className="w-24 px-2 py-1 text-xs font-mono border border-input rounded-md bg-transparent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        />
      </div>
    </div>
  )
}

function FieldSlider({ label, value = {}, onChange, units = ['px', 'em', 'rem', '%', 'ch'] }) {
  const size = value?.size ?? 0
  const unit = value?.unit ?? units[0]
  return (
    <div className="flex flex-col gap-1">
      <label className="text-xs text-muted-foreground">{label}</label>
      <div className="flex items-center gap-1.5">
        <input
          type="number"
          value={size}
          step="0.1"
          onChange={e => onChange({ size: parseFloat(e.target.value) || 0, unit })}
          className="w-20 px-2 py-1 text-xs border border-input rounded-md bg-transparent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        />
        <select
          value={unit}
          onChange={e => onChange({ size, unit: e.target.value })}
          className="px-1.5 py-1 text-xs border border-input rounded-md bg-background focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        >
          {units.map(u => <option key={u} value={u}>{u}</option>)}
        </select>
      </div>
    </div>
  )
}

function FieldNumber({ label, value = 0, onChange, step = 0.05, min = 0, max = 10 }) {
  return (
    <div className="flex flex-col gap-1">
      <label className="text-xs text-muted-foreground">{label}</label>
      <input
        type="number"
        value={value}
        step={step}
        min={min}
        max={max}
        onChange={e => onChange(parseFloat(e.target.value) || 0)}
        className="w-24 px-2 py-1 text-xs border border-input rounded-md bg-transparent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
      />
    </div>
  )
}

// ── Panneau config visuelle pour un post type ─────────────────────────
function VisualConfig({ ptSlug, config, defaults, onChange }) {
  const v = (key) => config[key] ?? defaults[key]
  const set = (key) => (val) => onChange({ ...config, [key]: val })

  return (
    <div className="space-y-5">
      {/* Global */}
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">Global</p>
        <div className="grid grid-cols-2 gap-x-4 gap-y-3">
          <FieldSlider label="Largeur max" value={v('max_width')} onChange={set('max_width')} units={['px','%','ch']} />
          <FieldColor label="Couleur texte" value={v('base_color')} onChange={set('base_color')} />
          <FieldColor label="Couleur liens" value={v('link_color')} onChange={set('link_color')} />
          <FieldNumber label="Interligne ¶" value={v('p_line_height')} onChange={set('p_line_height')} min={1} max={3} />
          <FieldSlider label="Espace ¶ → ¶" value={v('p_margin_bottom')} onChange={set('p_margin_bottom')} />
          <FieldSlider label="Espace items liste" value={v('li_spacing')} onChange={set('li_spacing')} />
        </div>
      </div>

      {/* Headings */}
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">Titres H1 – H6</p>
        <div className="grid grid-cols-2 gap-x-4 gap-y-3">
          {[1,2,3,4,5,6].map(h => (
            <div key={h} className="flex items-end gap-3 p-2 rounded-md bg-muted/30">
              <FieldSlider label={`H${h} taille`} value={v(`h${h}_size`)} onChange={set(`h${h}_size`)} units={['em','px','rem']} />
              <FieldColor label={`H${h} couleur`} value={v(`h${h}_color`)} onChange={set(`h${h}_color`)} />
            </div>
          ))}
        </div>
      </div>

      {/* Citation */}
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">Citation (blockquote)</p>
        <div className="grid grid-cols-2 gap-x-4 gap-y-3">
          <FieldColor label="Couleur bordure" value={v('bq_border_color')} onChange={set('bq_border_color')} />
          <FieldSlider label="Épaisseur bordure" value={v('bq_border_width')} onChange={set('bq_border_width')} units={['px']} />
          <FieldColor label="Fond" value={v('bq_bg')} onChange={set('bq_bg')} />
        </div>
      </div>

      {/* Séparateur */}
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">Séparateur (hr)</p>
        <div className="grid grid-cols-2 gap-x-4 gap-y-3">
          <FieldColor label="Couleur" value={v('hr_color')} onChange={set('hr_color')} />
          <FieldSlider label="Épaisseur" value={v('hr_height')} onChange={set('hr_height')} units={['px']} />
        </div>
      </div>

      {/* Code */}
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">Code</p>
        <div className="grid grid-cols-2 gap-x-4 gap-y-3">
          <FieldColor label="Fond inline" value={v('code_bg')} onChange={set('code_bg')} />
          <FieldColor label="Texte inline" value={v('code_color')} onChange={set('code_color')} />
          <FieldColor label="Fond bloc" value={v('pre_bg')} onChange={set('pre_bg')} />
          <FieldColor label="Texte bloc" value={v('pre_color')} onChange={set('pre_color')} />
        </div>
      </div>

      {/* Images */}
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">Images</p>
        <div className="grid grid-cols-2 gap-x-4 gap-y-3">
          <FieldSlider label="Largeur max" value={v('img_max_width')} onChange={set('img_max_width')} units={['px','%']} />
          <FieldSlider label="Border radius" value={v('img_border_radius')} onChange={set('img_border_radius')} units={['px']} />
        </div>
      </div>

      {/* Tables */}
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">Tableaux</p>
        <div className="grid grid-cols-2 gap-x-4 gap-y-3">
          <FieldColor label="Couleur bordures" value={v('table_border_color')} onChange={set('table_border_color')} />
          <FieldColor label="Fond en-tête" value={v('table_header_bg')} onChange={set('table_header_bg')} />
          <FieldColor label="Fond lignes paires" value={v('table_stripe_bg')} onChange={set('table_stripe_bg')} />
        </div>
      </div>
    </div>
  )
}

// ── Composant principal ───────────────────────────────────────────────
export default function MarkdownSection() {
  const [postTypes,    setPostTypes]    = useState([])   // [{slug, label, icon}]
  const [settings,     setSettings]     = useState(null) // {enabled_post_types, visual_config}
  const [defaults,     setDefaults]     = useState({})
  const [loading,      setLoading]      = useState(true)
  const [saving,       setSaving]       = useState(false)
  const [saved,        setSaved]        = useState(false)
  const [error,        setError]        = useState(null)
  const [openPt,       setOpenPt]       = useState(null) // Post type dont l'accordion est ouvert

  useEffect(() => {
    Promise.all([
      emwFetch('/settings'),
      emwFetch('/post-types'),
    ])
      .then(([settingsData, ptData]) => {
        setSettings(settingsData.settings)
        setDefaults(settingsData.defaults)
        setPostTypes(ptData)
      })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  const togglePt = useCallback((slug) => {
    setSettings(prev => {
      const current = prev.enabled_post_types || []
      const enabled  = current.includes(slug)
        ? current.filter(s => s !== slug)
        : [...current, slug]
      return { ...prev, enabled_post_types: enabled }
    })
  }, [])

  const updateVisual = useCallback((slug, config) => {
    setSettings(prev => ({
      ...prev,
      visual_config: { ...(prev.visual_config || {}), [slug]: config },
    }))
  }, [])

  async function handleSave() {
    setSaving(true); setSaved(false); setError(null)
    try {
      await emwFetch('/settings', {
        method: 'POST',
        body: JSON.stringify(settings),
      })
      setSaved(true)
      setTimeout(() => setSaved(false), 3000)
    } catch (e) {
      setError(e.message)
    } finally {
      setSaving(false)
    }
  }

  if (loading) return (
    <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
  )
  if (!settings) return (
    <Notice type="error">{error ?? 'Plugin Elementor Markdown Renderer introuvable ou non activé.'}</Notice>
  )

  const enabledPts = settings.enabled_post_types || []

  return (
    <div className="space-y-6">
      {error  && <Notice type="error">{error}</Notice>}
      {saved  && <Notice type="success">Paramètres sauvegardés.</Notice>}

      {/* ── Post types activés ──────────────────────────────────── */}
      <div className="rounded-lg border border-border bg-card p-5 space-y-4">
        <div>
          <SectionTitle>Post types activés</SectionTitle>
          <p className="text-sm text-muted-foreground mt-1">
            L'éditeur Markdown remplace l'éditeur classique sur les fiches des post types sélectionnés.
          </p>
        </div>

        <div className="grid grid-cols-2 gap-2">
          {postTypes.map(({ slug, label, icon }) => {
            const active = enabledPts.includes(slug)
            return (
              <label
                key={slug}
                className={[
                  'flex items-center gap-3 px-3 py-2.5 rounded-lg border cursor-pointer transition-colors select-none',
                  active
                    ? 'border-primary/50 bg-primary/5 text-foreground'
                    : 'border-border hover:border-border/70 text-muted-foreground',
                ].join(' ')}
              >
                <input
                  type="checkbox"
                  checked={active}
                  onChange={() => togglePt(slug)}
                  className="sr-only"
                />
                <span className={`dashicons ${icon} text-base shrink-0`} />
                <div className="min-w-0">
                  <span className="text-sm font-medium block truncate">{label}</span>
                  <span className="text-[10px] text-muted-foreground font-mono">{slug}</span>
                </div>
                <span className={[
                  'ml-auto text-[10px] px-1.5 py-0.5 rounded font-medium shrink-0',
                  active ? 'bg-primary/15 text-primary' : 'bg-muted text-muted-foreground',
                ].join(' ')}>
                  {active ? 'Actif' : 'Inactif'}
                </span>
              </label>
            )
          })}
        </div>
      </div>

      {/* ── Config visuelle par post type ──────────────────────── */}
      {enabledPts.length > 0 && (
        <div className="rounded-lg border border-border bg-card p-5 space-y-3">
          <div>
            <SectionTitle>Style visuel par post type</SectionTitle>
            <p className="text-sm text-muted-foreground mt-1">
              Valeurs appliquées par défaut. Le widget Elementor peut les surcharger par page.
            </p>
          </div>

          {enabledPts.map(slug => {
            const pt = postTypes.find(p => p.slug === slug)
            if (!pt) return null
            const isOpen = openPt === slug
            const config = settings.visual_config?.[slug] || {}

            return (
              <div key={slug} className="border border-border rounded-lg overflow-hidden">
                <button
                  type="button"
                  onClick={() => setOpenPt(isOpen ? null : slug)}
                  className="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-accent transition-colors"
                >
                  <span className={`dashicons ${pt.icon} text-base text-muted-foreground`} />
                  <span className="text-sm font-medium flex-1">{pt.label}</span>
                  <span className="text-[10px] font-mono text-muted-foreground mr-2">{slug}</span>
                  <svg
                    width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"
                    className={`transition-transform duration-200 text-muted-foreground ${isOpen ? 'rotate-180' : ''}`}
                  >
                    <path d="m6 9 6 6 6-6" />
                  </svg>
                </button>

                {isOpen && (
                  <div className="border-t border-border px-4 py-4">
                    <VisualConfig
                      ptSlug={slug}
                      config={config}
                      defaults={defaults}
                      onChange={(cfg) => updateVisual(slug, cfg)}
                    />
                  </div>
                )}
              </div>
            )
          })}
        </div>
      )}

      {enabledPts.length === 0 && (
        <Notice type="warn">
          Activez au moins un post type ci-dessus pour configurer son style visuel.
        </Notice>
      )}

      {/* ── Save ────────────────────────────────────────────────── */}
      <div className="flex items-center gap-4">
        <Btn loading={saving} onClick={handleSave}>Enregistrer</Btn>
      </div>
    </div>
  )
}
