import { useState, useEffect } from 'react'
import { api } from '../lib/api'
import { PageHeader, Input, Btn, Notice, Spinner } from '../components/ui'

export default function Settings() {
  const [settings, setSettings] = useState(null)
  const [loading, setLoading]   = useState(true)
  const [saving, setSaving]     = useState(false)
  const [flushing, setFlushing] = useState(false)
  const [saved, setSaved]       = useState(false)
  const [error, setError]       = useState(null)

  useEffect(() => {
    api.settings()
      .then(setSettings)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  function set(key, value) {
    setSettings(prev => ({ ...prev, [key]: value }))
  }

  function setWidgetMap(productId, widgetId) {
    setSettings(prev => ({
      ...prev,
      widget_map: { ...prev.widget_map, [productId]: widgetId },
    }))
  }

  function togglePostType(name) {
    setSettings(prev => ({
      ...prev,
      post_types: prev.post_types.includes(name)
        ? prev.post_types.filter(t => t !== name)
        : [...prev.post_types, name],
    }))
  }

  async function handleSave() {
    setSaving(true)
    setSaved(false)
    setError(null)
    try {
      await api.saveSettings(settings)
      setSaved(true)
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

  if (loading) return (
    <div className="flex items-center justify-center py-20">
      <Spinner size={20} />
    </div>
  )

  return (
    <div>
      <PageHeader
        title="Réglages"
        actions={
          <Btn loading={saving} onClick={handleSave}>
            Enregistrer
          </Btn>
        }
      />

      <div className="max-w-2xl mx-8 mt-8 space-y-8">

        {error  && <Notice type="error">{error}</Notice>}
        {saved  && <Notice type="success">Réglages enregistrés.</Notice>}

        {/* Credentials */}
        <section>
          <SectionTitle>Connexion API Regiondo</SectionTitle>
          <div className="space-y-3 mt-4">
            <Input
              label="Clé publique (Public Key)"
              value={settings.public_key ?? ''}
              onChange={e => set('public_key', e.target.value)}
            />
            <Input
              label="Clé secrète (Secret Key)"
              type="password"
              value={settings.secret_key ?? ''}
              onChange={e => set('secret_key', e.target.value)}
            />
          </div>
        </section>

        <Divider />

        {/* Widget map */}
        <section>
          <SectionTitle>Widget ID par produit</SectionTitle>
          <p className="text-xs text-gray-400 mt-1 mb-4">
            Trouvable dans Regiondo → Shop Config → Website Integration → Booking Widgets.
          </p>

          {(settings.products ?? []).length === 0 && (
            <p className="text-xs text-gray-400">Aucun produit chargé. Vérifiez votre clé API.</p>
          )}

          <div className="space-y-2">
            {(settings.products ?? []).map(p => (
              <div key={p.product_id} className="flex items-center gap-3">
                <span className="w-48 text-sm truncate">{p.name}</span>
                <code className="text-xs text-gray-400 w-16">#{p.product_id}</code>
                <input
                  type="text"
                  value={settings.widget_map?.[p.product_id] ?? ''}
                  onChange={e => setWidgetMap(p.product_id, e.target.value)}
                  placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                  className="flex-1 border border-gray-200 px-3 py-1.5 text-xs font-mono outline-none focus:border-black"
                />
                {settings.widget_map?.[p.product_id]
                  ? <span className="text-xs text-gray-500">OK</span>
                  : <span className="text-xs text-gray-300">—</span>
                }
              </div>
            ))}
          </div>
        </section>

        <Divider />

        {/* Post types */}
        <section>
          <SectionTitle>Types de post actifs</SectionTitle>
          <p className="text-xs text-gray-400 mt-1 mb-4">
            La meta box Regiondo sera disponible sur ces types de contenu.
          </p>
          <div className="space-y-2">
            {(settings.all_post_types ?? []).map(pt => (
              <label key={pt.name} className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.post_types?.includes(pt.name) ?? false}
                  onChange={() => togglePostType(pt.name)}
                  className="w-3.5 h-3.5 accent-black"
                />
                <span className="text-sm">{pt.label}</span>
                <code className="text-xs text-gray-400">{pt.name}</code>
              </label>
            ))}
          </div>
        </section>

        <Divider />

        {/* Cache */}
        <section>
          <SectionTitle>Cache</SectionTitle>
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
              Vider le cache maintenant
            </Btn>
          </div>
          <p className="text-xs text-gray-400 mt-2">3600 = 1h (recommandé)</p>
        </section>

        <div className="pt-4">
          <Btn loading={saving} onClick={handleSave}>
            Enregistrer les réglages
          </Btn>
        </div>

      </div>
    </div>
  )
}

function SectionTitle({ children }) {
  return (
    <h2 className="text-xs uppercase tracking-widest text-gray-400">{children}</h2>
  )
}

function Divider() {
  return <hr className="border-gray-100" />
}
