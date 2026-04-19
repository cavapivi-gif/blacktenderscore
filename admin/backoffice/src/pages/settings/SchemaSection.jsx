/**
 * SchemaSection — Configuration Schema.org GeoCoordinates.
 * Permet de configurer l'injection JSON-LD par post type et taxonomie.
 */
import { useState, useEffect } from 'react'
import { Plus, Trash, MapPin } from 'iconoir-react'
import { api } from '../../lib/api'
import { Notice, Spinner, Btn, SectionTitle, Input } from '../../components/ui'

// ── Types de schema disponibles ───────────────────────────────────────────────
const SCHEMA_TYPES_PT = [
  { value: 'TouristTrip', label: 'TouristTrip (itineraire)' },
  { value: 'TouristAttraction', label: 'TouristAttraction' },
  { value: 'Event', label: 'Event' },
  { value: 'Product', label: 'Product' },
  { value: 'Place', label: 'Place' },
]

const SCHEMA_TYPES_TAX = [
  { value: 'TouristDestination', label: 'TouristDestination' },
  { value: 'Place', label: 'Place' },
  { value: 'DefinedTerm', label: 'DefinedTerm' },
  { value: 'City', label: 'City' },
]

// ── Post types a exclure (natifs WP + internes) ───────────────────────────────
const EXCLUDED_PT = [
  'post', 'page', 'attachment', 'revision', 'nav_menu_item',
  'custom_css', 'customize_changeset', 'oembed_cache', 'user_request',
  'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles',
  'wp_navigation', 'acf-field-group', 'acf-field', 'acf-post-type',
  'acf-taxonomy', 'acf-ui-options-page', 'elementor_library',
  'e-landing-page', 'e-floating-buttons',
]

const EXCLUDED_TAX = [
  'category', 'post_tag', 'nav_menu', 'link_category', 'post_format',
  'wp_theme', 'wp_template_part_area', 'elementor_library_type',
]

// ── Composant principal ───────────────────────────────────────────────────────
export default function SchemaSection() {
  const [loading, setLoading]       = useState(true)
  const [saving, setSaving]         = useState(false)
  const [saved, setSaved]           = useState(false)
  const [error, setError]           = useState(null)

  const [postTypes, setPostTypes]   = useState([])
  const [taxonomies, setTaxonomies] = useState([])
  const [mapFields, setMapFields]   = useState({})
  const [textFields, setTextFields] = useState({})

  const [ptConfigs, setPtConfigs]   = useState([])
  const [taxConfigs, setTaxConfigs] = useState([])

  const [providerName, setProviderName] = useState('')
  const [providerUrl, setProviderUrl]   = useState('')

  // ── Load initial data ────────────────────────────────────────────────
  useEffect(() => {
    let mounted = true

    async function loadData() {
      try {
        const [settings, pts, taxs] = await Promise.all([
          api.schemaSettings(),
          api.schemaPostTypes(),
          api.schemaTaxonomies(),
        ])

        if (!mounted) return

        const ptCfgs = settings.post_types || []
        const taxCfgs = settings.taxonomies || []

        setPtConfigs(ptCfgs)
        setTaxConfigs(taxCfgs)
        setProviderName(settings.provider_name || '')
        setProviderUrl(settings.provider_url || '')
        setPostTypes(pts.filter(p => !EXCLUDED_PT.includes(p.name)))
        setTaxonomies(taxs.filter(t => !EXCLUDED_TAX.includes(t.name)))

        // Pre-load ACF fields for existing configs
        const newMapFields = {}
        const newTextFields = {}

        for (const cfg of ptCfgs) {
          if (!cfg.post_type) continue
          try {
            const [gps, text] = await Promise.all([
              api.schemaMapFields(cfg.post_type, 'post_type'),
              api.schemaTextFields(cfg.post_type, 'post_type'),
            ])
            newMapFields[cfg.post_type] = gps || []
            newTextFields[cfg.post_type] = text || []
          } catch {
            newMapFields[cfg.post_type] = []
            newTextFields[cfg.post_type] = []
          }
        }

        for (const cfg of taxCfgs) {
          if (!cfg.taxonomy) continue
          try {
            const gps = await api.schemaMapFields(cfg.taxonomy, 'taxonomy')
            newMapFields[cfg.taxonomy] = gps || []
          } catch {
            newMapFields[cfg.taxonomy] = []
          }
        }

        if (mounted) {
          setMapFields(newMapFields)
          setTextFields(newTextFields)
        }
      } catch (e) {
        if (mounted) setError(e.message)
      } finally {
        if (mounted) setLoading(false)
      }
    }

    loadData()
    return () => { mounted = false }
  }, [])

  // ── Load fields for a post type or taxonomy ───────────────────────────
  async function loadFields(name, type) {
    if (mapFields[name]) return

    try {
      const [gps, text] = await Promise.all([
        api.schemaMapFields(name, type),
        type === 'post_type' ? api.schemaTextFields(name, type) : Promise.resolve([]),
      ])
      setMapFields(prev => ({ ...prev, [name]: gps || [] }))
      if (type === 'post_type') {
        setTextFields(prev => ({ ...prev, [name]: text || [] }))
      }
    } catch {
      setMapFields(prev => ({ ...prev, [name]: [] }))
      if (type === 'post_type') {
        setTextFields(prev => ({ ...prev, [name]: [] }))
      }
    }
  }

  // ── Handlers post types ──────────────────────────────────────────────
  function addPtConfig() {
    setPtConfigs(prev => [...prev, {
      post_type: '',
      enabled: true,
      schema_type: 'TouristTrip',
      field_title: '',
      field_description: '',
      field_depart: '',
      field_arrivee: '',
    }])
  }

  function updatePtConfig(index, config) {
    setPtConfigs(prev => prev.map((c, i) => i === index ? config : c))
  }

  function removePtConfig(index) {
    setPtConfigs(prev => prev.filter((_, i) => i !== index))
  }

  // ── Handlers taxonomies ──────────────────────────────────────────────
  function addTaxConfig() {
    setTaxConfigs(prev => [...prev, {
      taxonomy: '',
      enabled: true,
      schema_type: 'TouristDestination',
      field_gps: '',
    }])
  }

  function updateTaxConfig(index, config) {
    setTaxConfigs(prev => prev.map((c, i) => i === index ? config : c))
  }

  function removeTaxConfig(index) {
    setTaxConfigs(prev => prev.filter((_, i) => i !== index))
  }

  // ── Save ─────────────────────────────────────────────────────────────
  async function handleSave() {
    setSaving(true)
    setSaved(false)
    setError(null)
    try {
      await api.saveSchemaSettings({
        post_types: ptConfigs.filter(c => c.post_type),
        taxonomies: taxConfigs.filter(c => c.taxonomy),
        provider_name: providerName,
        provider_url: providerUrl,
      })
      setSaved(true)
      setTimeout(() => setSaved(false), 3000)
    } catch (e) {
      setError(e.message)
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
  }

  return (
    <div className="space-y-6">
      {error && <Notice type="error">{error}</Notice>}
      {saved && <Notice type="success">Configuration Schema.org sauvegardee.</Notice>}

      {/* ── Info ──────────────────────────────────────────────────── */}
      <div className="rounded-lg border border-border bg-card p-5">
        <div className="flex items-start gap-3">
          <MapPin className="w-5 h-5 text-primary mt-0.5 shrink-0" />
          <div>
            <p className="text-sm font-medium">Schema.org JSON-LD</p>
            <p className="text-sm text-muted-foreground mt-1">
              Configure l'injection automatique de JSON-LD sur les pages singulières (post types)
              et archives (taxonomies). Mappez les champs ACF pour titre, description et coordonnees GPS.
            </p>
          </div>
        </div>
      </div>

      {/* ── Provider global ───────────────────────────────────────── */}
      <div className="rounded-lg border border-border bg-card p-5 space-y-4">
        <SectionTitle>Prestataire (provider)</SectionTitle>
        <p className="text-sm text-muted-foreground">
          Informations du prestataire incluses dans tous les schemas generes.
        </p>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="text-xs text-muted-foreground mb-1 block">Nom (vide = nom du site)</label>
            <Input
              value={providerName}
              onChange={e => setProviderName(e.target.value)}
              placeholder="Black Tenders"
            />
          </div>
          <div>
            <label className="text-xs text-muted-foreground mb-1 block">URL (vide = URL du site)</label>
            <Input
              value={providerUrl}
              onChange={e => setProviderUrl(e.target.value)}
              placeholder="https://blacktenders.com"
            />
          </div>
        </div>
      </div>

      {/* ── Post types ────────────────────────────────────────────── */}
      <div className="rounded-lg border border-border bg-card p-5 space-y-4">
        <div className="flex items-center justify-between flex-wrap gap-2">
          <div>
            <SectionTitle>Post types</SectionTitle>
            <p className="text-sm text-muted-foreground mt-1">
              Schema injecte sur les pages singulières. Mappez titre, description et GPS depuis vos champs ACF.
            </p>
          </div>
          <Btn size="sm" variant="outline" onClick={addPtConfig}>
            <Plus width={14} height={14} className="mr-1" />
            Ajouter
          </Btn>
        </div>

        {ptConfigs.length === 0 ? (
          <p className="text-sm text-muted-foreground italic py-4 text-center">
            Aucun post type configure. Cliquez sur "Ajouter" pour commencer.
          </p>
        ) : (
          <div className="space-y-3">
            {ptConfigs.map((config, i) => {
              const gpsFields = mapFields[config.post_type] || []
              const descFields = textFields[config.post_type] || []

              return (
                <div key={i} className="space-y-3 p-4 bg-muted/30 rounded-lg border border-border/50">
                  {/* Row 1: Post type + Toggle + Schema type */}
                  <div className="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr_auto] gap-3 items-center">
                    <select
                      value={config.post_type}
                      onChange={e => {
                        const newPt = e.target.value
                        updatePtConfig(i, { ...config, post_type: newPt, field_title: '', field_description: '', field_depart: '', field_arrivee: '' })
                        if (newPt) loadFields(newPt, 'post_type')
                      }}
                      className="px-3 py-2 text-sm border border-input rounded-md bg-background"
                    >
                      <option value="">— Post type —</option>
                      {postTypes.map(p => (
                        <option key={p.name} value={p.name}>{p.label} ({p.name})</option>
                      ))}
                    </select>

                    <label className="flex items-center gap-2 cursor-pointer whitespace-nowrap">
                      <input
                        type="checkbox"
                        checked={config.enabled}
                        onChange={e => updatePtConfig(i, { ...config, enabled: e.target.checked })}
                        className="w-4 h-4 rounded border-input"
                      />
                      <span className="text-sm">Actif</span>
                    </label>

                    <select
                      value={config.schema_type}
                      onChange={e => updatePtConfig(i, { ...config, schema_type: e.target.value })}
                      className="px-3 py-2 text-sm border border-input rounded-md bg-background"
                    >
                      {SCHEMA_TYPES_PT.map(t => (
                        <option key={t.value} value={t.value}>{t.label}</option>
                      ))}
                    </select>

                    <button
                      type="button"
                      onClick={() => removePtConfig(i)}
                      className="p-2 text-destructive hover:bg-destructive/10 rounded"
                      title="Supprimer"
                    >
                      <Trash width={16} height={16} />
                    </button>
                  </div>

                  {/* Row 2: Title + Description fields */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label className="text-xs text-muted-foreground mb-1 block">Titre (champ ACF)</label>
                      <select
                        value={config.field_title || ''}
                        onChange={e => updatePtConfig(i, { ...config, field_title: e.target.value })}
                        className="w-full px-3 py-2 text-sm border border-input rounded-md bg-background"
                        disabled={!descFields.length}
                      >
                        <option value="">— Titre du post —</option>
                        {descFields.map(f => (
                          <option key={f.name} value={f.name}>{f.label} ({f.type})</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="text-xs text-muted-foreground mb-1 block">Description (champ ACF)</label>
                      <select
                        value={config.field_description || ''}
                        onChange={e => updatePtConfig(i, { ...config, field_description: e.target.value })}
                        className="w-full px-3 py-2 text-sm border border-input rounded-md bg-background"
                        disabled={!descFields.length}
                      >
                        <option value="">— Extrait du post —</option>
                        {descFields.map(f => (
                          <option key={f.name} value={f.name}>{f.label} ({f.type})</option>
                        ))}
                      </select>
                    </div>
                  </div>

                  {/* Row 3: GPS fields */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label className="text-xs text-muted-foreground mb-1 block">GPS Depart</label>
                      <select
                        value={config.field_depart || ''}
                        onChange={e => updatePtConfig(i, { ...config, field_depart: e.target.value })}
                        className="w-full px-3 py-2 text-sm border border-input rounded-md bg-background"
                        disabled={!gpsFields.length}
                      >
                        <option value="">— Champ GPS —</option>
                        {gpsFields.map(f => (
                          <option key={f.name} value={f.name}>{f.label}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="text-xs text-muted-foreground mb-1 block">GPS Arrivee (optionnel)</label>
                      <select
                        value={config.field_arrivee || ''}
                        onChange={e => updatePtConfig(i, { ...config, field_arrivee: e.target.value })}
                        className="w-full px-3 py-2 text-sm border border-input rounded-md bg-background"
                        disabled={!gpsFields.length}
                      >
                        <option value="">— Aucun —</option>
                        {gpsFields.map(f => (
                          <option key={f.name} value={f.name}>{f.label}</option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              )
            })}
          </div>
        )}
      </div>

      {/* ── Taxonomies ────────────────────────────────────────────── */}
      <div className="rounded-lg border border-border bg-card p-5 space-y-4">
        <div className="flex items-center justify-between flex-wrap gap-2">
          <div>
            <SectionTitle>Taxonomies</SectionTitle>
            <p className="text-sm text-muted-foreground mt-1">
              Schema injecte sur les pages d'archive taxonomie. Un seul point GPS.
            </p>
          </div>
          <Btn size="sm" variant="outline" onClick={addTaxConfig}>
            <Plus width={14} height={14} className="mr-1" />
            Ajouter
          </Btn>
        </div>

        {taxConfigs.length === 0 ? (
          <p className="text-sm text-muted-foreground italic py-4 text-center">
            Aucune taxonomie configuree. Cliquez sur "Ajouter" pour commencer.
          </p>
        ) : (
          <div className="space-y-3">
            {taxConfigs.map((config, i) => {
              const fields = mapFields[config.taxonomy] || []

              return (
                <div key={i} className="space-y-3 p-4 bg-muted/30 rounded-lg border border-border/50">
                  <div className="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr_1fr_auto] gap-3 items-center">
                    <select
                      value={config.taxonomy}
                      onChange={e => {
                        const newTax = e.target.value
                        updateTaxConfig(i, { ...config, taxonomy: newTax, field_gps: '' })
                        if (newTax) loadFields(newTax, 'taxonomy')
                      }}
                      className="px-3 py-2 text-sm border border-input rounded-md bg-background"
                    >
                      <option value="">— Taxonomie —</option>
                      {taxonomies.map(t => (
                        <option key={t.name} value={t.name}>{t.label} ({t.name})</option>
                      ))}
                    </select>

                    <label className="flex items-center gap-2 cursor-pointer whitespace-nowrap">
                      <input
                        type="checkbox"
                        checked={config.enabled}
                        onChange={e => updateTaxConfig(i, { ...config, enabled: e.target.checked })}
                        className="w-4 h-4 rounded border-input"
                      />
                      <span className="text-sm">Actif</span>
                    </label>

                    <select
                      value={config.schema_type}
                      onChange={e => updateTaxConfig(i, { ...config, schema_type: e.target.value })}
                      className="px-3 py-2 text-sm border border-input rounded-md bg-background"
                    >
                      {SCHEMA_TYPES_TAX.map(t => (
                        <option key={t.value} value={t.value}>{t.label}</option>
                      ))}
                    </select>

                    <select
                      value={config.field_gps || ''}
                      onChange={e => updateTaxConfig(i, { ...config, field_gps: e.target.value })}
                      className="px-3 py-2 text-sm border border-input rounded-md bg-background"
                      disabled={!fields.length}
                    >
                      <option value="">— Champ GPS —</option>
                      {fields.map(f => (
                        <option key={f.name} value={f.name}>{f.label}</option>
                      ))}
                    </select>

                    <button
                      type="button"
                      onClick={() => removeTaxConfig(i)}
                      className="p-2 text-destructive hover:bg-destructive/10 rounded"
                      title="Supprimer"
                    >
                      <Trash width={16} height={16} />
                    </button>
                  </div>
                </div>
              )
            })}
          </div>
        )}
      </div>

      {/* ── Save ──────────────────────────────────────────────────── */}
      <div className="flex items-center gap-4">
        <Btn loading={saving} onClick={handleSave}>Enregistrer</Btn>
      </div>
    </div>
  )
}
