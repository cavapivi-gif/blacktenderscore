import { useState, useEffect, useRef } from 'react'
import { useLocation } from 'react-router-dom'
import { api } from '../lib/api'
import { PageHeader, Input, Textarea, Btn, Notice, Spinner, SectionTitle, Divider, Toggle } from '../components/ui'

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
          <Textarea
            value={settings.booking_custom_css ?? ''}
            onChange={e => set('booking_custom_css', e.target.value)}
            placeholder={`.regiondo-widget .regiondo-button-addtocart {\n  border-radius: 40px;\n  background: #222;\n}`}
            className="font-mono text-xs min-h-[120px]"
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
