import { useState, useEffect, useRef, useCallback } from 'react' // useRef/useCallback still used by sync/diag polling
import { useParams } from 'react-router-dom'
import { RefreshDouble } from 'iconoir-react'
import { api } from '../lib/api'
import { PageHeader, Input, Btn, Notice, Spinner, SectionTitle, Divider, Badge, DangerModal } from '../components/ui'
import CsvImporter from '../components/CsvImporter'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/Tabs'
import CodeEditor from '@uiw/react-textarea-code-editor'
import '@uiw/react-textarea-code-editor/dist.css'
import MapPresets from '../components/MapPresets'

// ── Sections metadata ─────────────────────────────────────────────────────────
const SECTIONS = {
  api:         { title: 'Connexion API',      subtitle: 'Clés d\'accès à l\'API Regiondo',                        hasSave: true  },
  sync:        { title: 'Synchronisation',    subtitle: 'Import automatique des produits Regiondo vers WordPress', hasSave: true  },
  widgets:     { title: 'Widgets',            subtitle: 'Associez chaque produit à son Widget ID Regiondo',        hasSave: true  },
  css:         { title: 'Custom CSS & JS',     subtitle: 'CSS et JS injectés dans chaque widget de réservation',    hasSave: true  },
  map:         { title: 'Map Style',          subtitle: 'Style JSON Google Maps pour les widgets carte',           hasSave: true  },
  cache:       { title: 'Cache API',          subtitle: 'Durée de mise en cache des réponses Regiondo',           hasSave: true  },
  'manual-sync':        { title: 'Sync produits',        subtitle: 'Synchronisation immédiate des produits Regiondo → WordPress',                   hasSave: false },
  'bookings-sync':      { title: 'Sync réservations',   subtitle: 'Importe toutes les réservations Regiondo dans la base de données',               hasSave: false },
  'reservations-import':{ title: 'Import solditems',    subtitle: 'Importe les articles vendus enrichis (solditems) — CA réel, remboursements…',    hasSave: false },
  diagnostic:           { title: 'Diagnostic API',      subtitle: 'Teste chaque endpoint et affiche les réponses brutes',                            hasSave: false },
  installation:         { title: 'Installation',        subtitle: 'Relancer le wizard de configuration initiale du plugin',                          hasSave: false },
}

// ── CSS Sanitizer — strips XSS vectors ───────────────────────────────────────
const CSS_DANGEROUS = [
  /expression\s*\(/gi, /javascript\s*:/gi, /-moz-binding\s*:/gi,
  /behavior\s*:/gi, /url\s*\(\s*["']?\s*data\s*:\s*text\/html/gi,
  /<\/?script/gi, /<\/?style/gi, /@import\s+url/gi,
]
function sanitizeCss(raw) {
  let css = raw
  for (const p of CSS_DANGEROUS) css = css.replace(p, '/* blocked */')
  return css
}
function getCssWarnings(raw) {
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
const EDITOR_STYLE = {
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
function CssWarnings({ warnings }) {
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
function CssEditor({ value, onChange, placeholder }) {
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
function JsEditor({ value, onChange }) {
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
function MapStyleEditor({ value, onChange }) {
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

const INTERVALS = [
  { value: 0,    label: 'Désactivée (manuel uniquement)' },
  { value: 30,   label: 'Toutes les 30 minutes' },
  { value: 60,   label: 'Toutes les heures' },
  { value: 360,  label: 'Toutes les 6 heures' },
  { value: 1440, label: 'Une fois par jour' },
]

// ── Re-parse prices button ──────────────────────────────────────────────────────

function ReparsePricesButton() {
  const [loading, setLoading] = useState(false)
  const [progress, setProgress] = useState(null) // { updated, remaining }
  const [result, setResult] = useState(null)
  const [error, setError] = useState(null)

  async function handleReparse() {
    setLoading(true)
    setError(null)
    setResult(null)
    setProgress(null)
    try {
      let total = 0
      let remaining = 1
      // Loop: 200 rows per batch to avoid Cloudflare 525 timeout
      while (remaining > 0) {
        const res = await api.reparsePrices()
        total += res.updated ?? 0
        remaining = res.remaining ?? 0
        setProgress({ updated: total, remaining })
      }
      setResult({ updated: total })
      setProgress(null)
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="space-y-2">
      <button
        type="button"
        onClick={handleReparse}
        disabled={loading}
        className="px-3 py-1.5 text-xs font-medium rounded-md border bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none transition-colors"
      >
        {loading ? 'Re-parsing en cours...' : 'Lancer le re-parse'}
      </button>
      {progress && (
        <p className="text-xs text-muted-foreground">
          {progress.updated.toLocaleString('fr-FR')} corrigés, {progress.remaining.toLocaleString('fr-FR')} restants...
        </p>
      )}
      {result && (
        <Notice type="success">
          {result.updated > 0
            ? `${result.updated.toLocaleString('fr-FR')} enregistrements corrigés.`
            : 'Aucun enregistrement à corriger — tous les prix sont déjà renseignés.'}
        </Notice>
      )}
      {error && <Notice type="error">{error}</Notice>}
    </div>
  )
}

// ── Main component ─────────────────────────────────────────────────────────────
// ── Section Installation ───────────────────────────────────────────────────────

function InstallationSection() {
  const [loading, setLoading] = useState(false)
  const [error,   setError]   = useState(null)

  async function handleLaunch() {
    setLoading(true)
    setError(null)
    try {
      await api.onboardingReset()
      window.location.reload()
    } catch (e) {
      setError(e.message)
      setLoading(false)
    }
  }

  return (
    <div className="space-y-4">
      <div className="rounded-lg border bg-card overflow-hidden">
        {/* En-tête illustré */}
        <div className="px-6 py-8 bg-primary/5 border-b flex items-center gap-5">
          <div className="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-primary">
              <path d="M12 2L2 7l10 5 10-5-10-5z"/>
              <path d="M2 17l10 5 10-5"/>
              <path d="M2 12l10 5 10-5"/>
            </svg>
          </div>
          <div>
            <h3 className="font-semibold text-foreground">Wizard d'installation</h3>
            <p className="text-sm text-muted-foreground mt-0.5">
              Configuration initiale guidée en 5 étapes
            </p>
          </div>
        </div>

        {/* Détail des étapes */}
        <div className="px-6 py-5 space-y-3">
          {[
            { n: 1, label: 'Prérequis système',   sub: 'Vérifie PHP ≥ 8.0, OpenSSL et crée la table bt_reservations'    },
            { n: 2, label: 'Chiffrement RGPD',    sub: 'Génère et configure la clé AES-256 pour les données personnelles' },
            { n: 3, label: 'API Regiondo',         sub: 'Saisie et test des clés Public / Secret Regiondo'                },
            { n: 4, label: 'Récapitulatif',        sub: 'Bilan de la configuration et lancement du backoffice'            },
          ].map(({ n, label, sub }) => (
            <div key={n} className="flex items-start gap-3">
              <div className="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[11px] font-semibold shrink-0 mt-0.5">
                {n}
              </div>
              <div>
                <div className="text-sm font-medium">{label}</div>
                <div className="text-xs text-muted-foreground">{sub}</div>
              </div>
            </div>
          ))}
        </div>

        {/* Action */}
        <div className="px-6 py-4 border-t bg-muted/20 flex items-center gap-3 flex-wrap">
          <Btn onClick={handleLaunch} loading={loading}>
            Lancer l'installation
          </Btn>
          <span className="text-xs text-muted-foreground">
            La page va se recharger pour afficher le wizard.
          </span>
        </div>
      </div>

      {error && <Notice type="error">{error}</Notice>}

      <Notice type="warn">
        Relancer le wizard ne supprime pas vos données ni vos réglages — il réinitialise uniquement
        l'écran d'accueil du backoffice.
      </Notice>
    </div>
  )
}

export default function Settings() {
  const { section = 'api' } = useParams()
  const meta = SECTIONS[section] ?? SECTIONS.api

  const [settings,    setSettings]    = useState(null)
  const [loading,     setLoading]     = useState(true)
  const [saving,      setSaving]      = useState(false)
  const [flushing,    setFlushing]    = useState(false)
  const [testing,     setTesting]     = useState(false)
  const [saved,       setSaved]       = useState(false)
  const [error,       setError]       = useState(null)
  const [testResult,  setTestResult]  = useState(null)
  const [syncing,        setSyncing]        = useState(false)
  const [syncResult,     setSyncResult]     = useState(null)
  const [diagLoading,    setDiagLoading]    = useState(false)
  const [diagData,       setDiagData]       = useState(null)
  // Booking sync state
  const [bSyncStatus,    setBSyncStatus]    = useState(null)
  const [bSyncLoading,   setBSyncLoading]   = useState(false)
  const [bSyncProgress,  setBSyncProgress]  = useState(null)  // { current, total, year, done }
  const [bSyncLog,       setBSyncLog]       = useState([])
  const [bResetLoading,  setBResetLoading]  = useState(false)
  // Reservation import (solditems) state
  const [rSyncStatus,    setRSyncStatus]    = useState(null)
  const [rSyncLoading,   setRSyncLoading]   = useState(false)
  const [rSyncProgress,  setRSyncProgress]  = useState(null)
  const [rSyncLog,       setRSyncLog]       = useState([])
  const [rResetLoading,  setRResetLoading]  = useState(false)
  const [showResetModal, setShowResetModal] = useState(false)

  useEffect(() => {
    api.settings()
      .then(setSettings)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  // Charge le statut DB quand on arrive sur la section bookings-sync
  useEffect(() => {
    if (section !== 'bookings-sync') return
    api.syncBookingsStatus().then(setBSyncStatus).catch(() => {})
  }, [section])

  // Charge le statut import solditems
  useEffect(() => {
    if (section !== 'reservations-import') return
    api.importReservationsStatus().then(setRSyncStatus).catch(() => {})
  }, [section])

  function set(key, value) {
    setSettings(prev => ({ ...prev, [key]: value }))
  }

  function setWidgetId(productId, value) {
    setSettings(prev => {
      const map      = { ...prev.widget_map }
      const existing = map[productId]
      map[productId] = typeof existing === 'object'
        ? { ...existing, widget_id: value }
        : { widget_id: value, custom_css: '' }
      return { ...prev, widget_map: map }
    })
  }

  function getWidgetId(productId) {
    const v = settings?.widget_map?.[productId]
    if (typeof v === 'string') return v
    return v?.widget_id ?? ''
  }

  async function handleSave() {
    setSaving(true); setSaved(false); setError(null)
    try {
      await api.saveSettings(settings)
      setSaved(true)
      setTimeout(() => setSaved(false), 3000)
    } catch (e) { setError(e.message) }
    finally { setSaving(false) }
  }

  async function handleFlush() {
    setFlushing(true)
    try { await api.flushCache() } finally { setFlushing(false) }
  }

  async function handleTestApi() {
    setTesting(true); setTestResult(null)
    try {
      const res = await api.testConnection()
      setTestResult(res)
    } catch (e) { setTestResult({ success: false, message: e.message }) }
    finally { setTesting(false) }
  }

  async function handleSync() {
    setSyncing(true); setSyncResult(null)
    try {
      const res = await api.sync()
      setSyncResult(res)
    } catch (e) { setSyncResult({ error: e.message }) }
    finally { setSyncing(false) }
  }

  // ── Booking sync handlers ──────────────────────────────────────────────────
  async function handleFullSync() {
    setBSyncLoading(true)
    setBSyncLog([])
    const currentYear = new Date().getFullYear()
    const startYear   = 2019
    const years       = Array.from({ length: currentYear - startYear + 1 }, (_, i) => startYear + i)

    setBSyncProgress({ current: 0, total: years.length, year: null, done: false })

    let totalSynced = 0
    const log = []

    for (let i = 0; i < years.length; i++) {
      const year = years[i]
      setBSyncProgress({ current: i + 1, total: years.length, year, done: false })
      try {
        const res = await api.syncBookings({ year })
        const msg = `${year} — ${res.fetched} récupérées, ${res.synced} en DB${res.errors?.length ? ` (${res.errors.length} erreurs)` : ''}`
        log.push({ ok: !res.errors?.length, msg })
        totalSynced += res.synced ?? 0
      } catch (e) {
        log.push({ ok: false, msg: `${year} — Erreur: ${e.message}` })
      }
      setBSyncLog([...log])
    }

    setBSyncProgress({ current: years.length, total: years.length, year: null, done: true, total_synced: totalSynced })
    const status = await api.syncBookingsStatus().catch(() => null)
    if (status) setBSyncStatus(status)
    setBSyncLoading(false)
  }

  async function handleIncrSync() {
    setBSyncLoading(true)
    setBSyncLog([])
    try {
      const res = await api.syncBookings({ from: fmtYMD(daysAgo(30)), to: fmtYMD(new Date()) })
      setBSyncLog([{ ok: true, msg: `30 derniers jours — ${res.fetched} récupérées, ${res.synced} en DB` }])
      const status = await api.syncBookingsStatus().catch(() => null)
      if (status) setBSyncStatus(status)
    } catch (e) {
      setBSyncLog([{ ok: false, msg: `Erreur: ${e.message}` }])
    } finally {
      setBSyncLoading(false)
    }
  }

  async function handleResetDb() {
    if (!window.confirm('Vider toute la table bt_bookings ? Cette action est irréversible.')) return
    setBResetLoading(true)
    try {
      await api.resetBookingsDb()
      setBSyncStatus(null)
      setBSyncLog([{ ok: true, msg: 'Table vidée. Lancez une sync complète pour réimporter.' }])
      const status = await api.syncBookingsStatus().catch(() => null)
      if (status) setBSyncStatus(status)
    } catch (e) {
      setBSyncLog([{ ok: false, msg: `Erreur: ${e.message}` }])
    } finally {
      setBResetLoading(false)
    }
  }

  function fmtYMD(d) {
    return [d.getFullYear(), String(d.getMonth()+1).padStart(2,'0'), String(d.getDate()).padStart(2,'0')].join('-')
  }
  function daysAgo(n) { const d = new Date(); d.setDate(d.getDate() - n); return d }

  // ── Reservation import handlers (solditems) ────────────────────────────────
  async function handleFullImport() {
    setRSyncLoading(true)
    setRSyncLog([])
    const currentYear = new Date().getFullYear()
    const startYear   = 2019
    const years       = Array.from({ length: currentYear - startYear + 1 }, (_, i) => startYear + i)

    setRSyncProgress({ current: 0, total: years.length, year: null, done: false })

    let totalImported = 0
    const log = []

    for (let i = 0; i < years.length; i++) {
      const year = years[i]
      setRSyncProgress({ current: i + 1, total: years.length, year, done: false })
      try {
        const res = await api.importReservations({ year })
        const inserted = res.inserted ?? 0
        const updated  = res.updated  ?? 0
        const skipped  = res.skipped  ?? 0
        const errCount = res.errors?.length ?? 0
        const msg = `${year} — ${res.fetched} récupérés, ${inserted} insérés, ${updated} maj${errCount ? `, ${errCount} erreurs` : ''}`
        log.push({ ok: !errCount, msg })
        totalImported += inserted + updated
      } catch (e) {
        log.push({ ok: false, msg: `${year} — Erreur: ${e.message}` })
      }
      setRSyncLog([...log])
    }

    setRSyncProgress({ current: years.length, total: years.length, year: null, done: true, total_imported: totalImported })
    const status = await api.importReservationsStatus().catch(() => null)
    if (status) setRSyncStatus(status)
    setRSyncLoading(false)
  }

  async function handleIncrImport() {
    setRSyncLoading(true)
    setRSyncLog([])
    try {
      const res = await api.importReservations({ from: fmtYMD(daysAgo(30)), to: fmtYMD(new Date()) })
      const msg = `30 derniers jours — ${res.fetched} récupérés, ${res.inserted ?? 0} insérés, ${res.updated ?? 0} mis à jour`
      setRSyncLog([{ ok: !(res.errors?.length), msg }])
      const status = await api.importReservationsStatus().catch(() => null)
      if (status) setRSyncStatus(status)
    } catch (e) {
      setRSyncLog([{ ok: false, msg: `Erreur: ${e.message}` }])
    } finally {
      setRSyncLoading(false)
    }
  }

  async function handleResetReservationsDb() {
    setShowResetModal(false)
    setRResetLoading(true)
    try {
      await api.resetReservationsDb()
      setRSyncStatus(null)
      setRSyncLog([{ ok: true, msg: 'Table vidée. Lancez un import complet pour réimporter.' }])
      const status = await api.importReservationsStatus().catch(() => null)
      if (status) setRSyncStatus(status)
    } catch (e) {
      setRSyncLog([{ ok: false, msg: `Erreur: ${e.message}` }])
    } finally {
      setRResetLoading(false)
    }
  }

  async function handleDiagnostic() {
    setDiagLoading(true); setDiagData(null)
    try {
      const res = await api.diagnostic()
      setDiagData(res)
    } catch (e) { setDiagData({ error: e.message }) }
    finally { setDiagLoading(false) }
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

  // ── Sections ────────────────────────────────────────────────────────────────
  function renderSection() {
    switch (section) {

      // ── Connexion API ──────────────────────────────────────────────────────
      case 'api':
        return (
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Retrouvez vos clés dans Regiondo → Paramètres → API.
            </p>
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
            <div className="flex items-center gap-3 pt-1">
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
        )

      // ── Synchronisation auto ───────────────────────────────────────────────
      case 'sync':
        return (
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Importe automatiquement les produits Regiondo vers des posts WordPress.
            </p>
            <label className="flex flex-col gap-1.5">
              <span className="text-sm font-medium">Fréquence</span>
              <select
                value={settings.sync_interval ?? 0}
                onChange={e => set('sync_interval', Number(e.target.value))}
                className="flex h-9 w-64 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                {INTERVALS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
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
        )

      // ── Widgets ───────────────────────────────────────────────────────────
      case 'widgets':
        return (
          <div className="space-y-3">
            <p className="text-sm text-muted-foreground">
              Associez chaque produit à son Widget ID Regiondo.{' '}
              <span className="text-xs">Regiondo → Shop Config → Website Integration → Booking Widgets</span>
            </p>
            {(settings.products ?? []).length === 0 && (
              <Notice type="warn">Aucun produit. Vérifiez la clé API puis enregistrez.</Notice>
            )}
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
        )

      // ── Custom CSS & JS ───────────────────────────────────────────────────
      case 'css':
        return (
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Code injecté dans chaque{' '}
              <code className="bg-muted px-1 py-0.5 rounded text-[11px]">{'<booking-widget>'}</code>{' '}
              Regiondo sur le front. S'applique à tous les widgets de réservation.
            </p>
            <Tabs defaultValue="css">
              <TabsList className="rounded-b-none border border-b-0 border-border bg-muted w-full justify-start gap-0 p-0 h-auto">
                <TabsTrigger value="css" className="rounded-none rounded-tl-md px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:border-b data-[state=active]:border-b-white data-[state=active]:bg-white">
                  CSS
                  {settings.booking_custom_css?.trim() && (
                    <span className="ml-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />
                  )}
                </TabsTrigger>
                <TabsTrigger value="js" className="rounded-none rounded-tr-md px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:border-b data-[state=active]:border-b-white data-[state=active]:bg-white">
                  JavaScript
                  {settings.booking_custom_js?.trim() && (
                    <span className="ml-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />
                  )}
                </TabsTrigger>
              </TabsList>
              <TabsContent value="css" className="mt-0 flex flex-col gap-2">
                <CssEditor
                  value={settings.booking_custom_css ?? ''}
                  onChange={v => set('booking_custom_css', v)}
                  placeholder={`.regiondo-widget .regiondo-button-addtocart {\n  border-radius: 40px;\n  background: #222;\n}`}
                />
              </TabsContent>
              <TabsContent value="js" className="mt-0">
                <JsEditor
                  value={settings.booking_custom_js ?? ''}
                  onChange={v => set('booking_custom_js', v)}
                />
              </TabsContent>
            </Tabs>
          </div>
        )

      // ── Map Style ─────────────────────────────────────────────────────────
      case 'map':
        return (
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Choisissez un preset ou créez le vôtre. Le style actif est injecté sur toutes les pages
              contenant un widget{' '}
              <span className="text-xs bg-muted rounded px-1 mx-0.5 font-mono">bt-itinerary</span>
              ou{' '}
              <span className="text-xs bg-muted rounded px-1 mx-0.5 font-mono">google_maps</span>{' '}
              avec "Appliquer le style de carte" activé.
              Chaque widget peut aussi surcharger avec son propre preset via l'onglet Avancé d'Elementor.
            </p>
            <MapPresets
              presets={settings.map_presets ?? []}
              activeJson={settings.map_style_json ?? ''}
              onPresetsChange={v => set('map_presets', v)}
              onActivate={v => set('map_style_json', v)}
            />
          </div>
        )

      // ── Cache ─────────────────────────────────────────────────────────────
      case 'cache':
        return (
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Les réponses Regiondo sont mises en cache pour éviter les appels répétés.
            </p>
            <div className="flex items-end gap-3">
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
            <p className="text-xs text-muted-foreground">3600 = 1 heure (recommandé)</p>
          </div>
        )

      // ── Sync manuelle ─────────────────────────────────────────────────────
      case 'manual-sync':
        return (
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Lance une synchronisation immédiate des produits Regiondo vers WordPress,
              sans attendre le cron automatique.
            </p>
            <div className="flex items-center gap-3">
              <Btn loading={syncing} onClick={handleSync}>
                <RefreshDouble width={14} height={14} />
                Synchroniser maintenant
              </Btn>
            </div>
            {syncResult && !syncResult.error && (
              <Notice type="success">
                Sync terminée — {syncResult.created ?? 0} créés, {syncResult.updated ?? 0} mis à jour, {syncResult.errors ?? 0} erreurs
              </Notice>
            )}
            {syncResult?.error && (
              <Notice type="error">{syncResult.error}</Notice>
            )}
          </div>
        )

      // ── Diagnostic ────────────────────────────────────────────────────────
      case 'diagnostic':
        return (
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Teste chaque endpoint Regiondo et affiche les réponses brutes pour diagnostiquer les problèmes de connexion.
            </p>
            <div>
              <Btn variant="secondary" loading={diagLoading} onClick={handleDiagnostic}>
                {diagData ? 'Relancer le diagnostic' : 'Lancer le diagnostic'}
              </Btn>
            </div>
            {diagData?.error && <Notice type="error">{diagData.error}</Notice>}
            {diagData?.endpoints && (
              <div className="space-y-3 mt-2">
                {diagData.endpoints.map((ep, i) => {
                  const ok      = ep.status >= 200 && ep.status < 300 && !ep.error
                  const hasData = ep.response?.data && (Array.isArray(ep.response.data) ? ep.response.data.length > 0 : true)
                  const hasError = ep.response?.error || ep.response?.error_code || ep.response?.error_message
                  return (
                    <details key={i} className="rounded-lg border bg-card overflow-hidden">
                      <summary className="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-muted/50">
                        <span className={`w-2 h-2 rounded-full shrink-0 ${
                          hasError ? 'bg-orange-400' :
                          ok && hasData ? 'bg-emerald-500' :
                          ok ? 'bg-yellow-400' : 'bg-red-500'
                        }`} />
                        <span className="text-sm font-medium flex-1">{ep.label}</span>
                        <code className="text-[11px] text-muted-foreground">{ep.status}</code>
                        {hasData && <Badge variant="confirmed">{Array.isArray(ep.response.data) ? ep.response.data.length + ' items' : 'OK'}</Badge>}
                        {hasError && <Badge variant="cancelled">Erreur</Badge>}
                        {ok && !hasData && !hasError && <Badge variant="pending">Vide</Badge>}
                        {!ok && <Badge variant="cancelled">{ep.error || 'HTTP ' + ep.status}</Badge>}
                      </summary>
                      <div className="border-t px-4 py-3">
                        <p className="text-[11px] text-muted-foreground mb-2 break-all">
                          <span className="font-medium">URL :</span> {ep.url}
                        </p>
                        <pre className="text-[11px] bg-muted/50 rounded p-3 overflow-x-auto max-h-80 whitespace-pre-wrap break-all">
                          {JSON.stringify(ep.response ?? ep.raw ?? ep.error, null, 2)}
                        </pre>
                      </div>
                    </details>
                  )
                })}
              </div>
            )}
          </div>
        )

      // ── Sync réservations (deprecated — partner/bookings API returns 401) ──
      case 'bookings-sync': {
        return (
          <div className="space-y-5">
            <Notice type="warn">
              Cette section est désactivée. L'API Regiondo <code>/partner/bookings</code> retourne 401
              pour les comptes de type « supplier ». Utilisez <strong>Import solditems</strong> à la place —
              c'est la source de données principale pour le dashboard.
            </Notice>
            <p className="text-sm text-muted-foreground">
              Les données de réservation (CA, canaux, heatmap…) proviennent de la table <code>bt_reservations</code>,
              alimentée par l'import CSV ou l'import API solditems.
            </p>
          </div>
        )
      }

      // ── Import solditems (réservations enrichies) ─────────────────────────
      case 'reservations-import': {
        const rPct = rSyncProgress
          ? Math.round((rSyncProgress.current / rSyncProgress.total) * 100)
          : 0

        return (
          <div className="space-y-5">
            {/* Stats DB */}
            <div className="rounded-lg border bg-card p-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-0.5">Dans la DB</p>
                <p className="text-xl font-semibold tabular-nums">
                  {rSyncStatus ? rSyncStatus.total_in_db.toLocaleString('fr-FR') : '—'}
                </p>
              </div>
              <div>
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-0.5">Première date</p>
                <p className="text-sm font-medium">{rSyncStatus?.date_min ?? '—'}</p>
              </div>
              <div>
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-0.5">Dernière date</p>
                <p className="text-sm font-medium">{rSyncStatus?.date_max ?? '—'}</p>
              </div>
              <div>
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-0.5">Dernier import</p>
                <p className="text-xs text-muted-foreground">
                  {rSyncStatus?.last_import
                    ? new Date(rSyncStatus.last_import).toLocaleString('fr-FR')
                    : 'Jamais'}
                </p>
              </div>
            </div>

            <p className="text-sm text-muted-foreground">
              Importe les <strong>articles vendus</strong> (solditems) depuis Regiondo : montants réels,
              remboursements (prix négatifs), canal de vente, statut paiement.
              Les données enrichissent les stats CA du dashboard.
            </p>

            {/* Actions */}
            <div className="flex flex-wrap gap-3">
              <Btn loading={rSyncLoading} onClick={handleFullImport} disabled={rSyncLoading}>
                <RefreshDouble width={14} height={14} />
                Import complet (2019 → aujourd'hui)
              </Btn>
              <Btn variant="secondary" loading={rSyncLoading} onClick={handleIncrImport} disabled={rSyncLoading}>
                <RefreshDouble width={14} height={14} />
                Import incrémental (30 j.)
              </Btn>
              <Btn variant="ghost" loading={rResetLoading} onClick={() => setShowResetModal(true)}
                disabled={rSyncLoading || rResetLoading}
                className="text-destructive hover:text-destructive"
              >
                Vider la DB
              </Btn>
            </div>

            {/* Barre de progression */}
            {rSyncProgress && !rSyncProgress.done && (
              <div className="space-y-1.5">
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                  <span>
                    {rSyncProgress.year ? `Import ${rSyncProgress.year}…` : 'Démarrage…'}
                  </span>
                  <span>{rSyncProgress.current}/{rSyncProgress.total} années</span>
                </div>
                <div className="h-2 rounded-full bg-muted overflow-hidden">
                  <div
                    className="h-full bg-primary transition-all duration-300 rounded-full"
                    style={{ width: `${rPct}%` }}
                  />
                </div>
              </div>
            )}

            {rSyncProgress?.done && (
              <Notice type="success">
                Import terminé — {rSyncProgress.total_imported?.toLocaleString('fr-FR') ?? '?'} articles en DB.
              </Notice>
            )}

            {/* Log */}
            {rSyncLog.length > 0 && (
              <div className="rounded-lg border bg-muted/30 divide-y divide-border max-h-64 overflow-y-auto text-xs">
                {rSyncLog.map((entry, i) => (
                  <div key={i} className={`flex items-center gap-2 px-3 py-2 ${entry.ok ? '' : 'text-destructive'}`}>
                    <span className={`w-1.5 h-1.5 rounded-full shrink-0 ${entry.ok ? 'bg-emerald-500' : 'bg-destructive'}`} />
                    {entry.msg}
                  </div>
                ))}
              </div>
            )}

            <Divider />
            <SectionTitle>Import CSV</SectionTitle>
            <p className="text-sm text-muted-foreground -mt-2">
              Alternative à l'API — importez directement depuis un export Regiondo CSV.
              Aucun appel API, aucun risque de timeout 525.
            </p>
            <CsvImporter
              onDone={() => api.importReservationsStatus().then(setRSyncStatus).catch(() => {})}
            />

            <Divider />
            <SectionTitle>Corriger les prix manquants</SectionTitle>
            <p className="text-sm text-muted-foreground -mt-2">
              Re-parse les enregistrements existants dont le prix est manquant (NULL)
              à partir du champ offer_raw. Utile après un import CSV qui n'avait pas parsé le prix.
            </p>
            <ReparsePricesButton />
          </div>
        )
      }

      case 'installation': {
        return <InstallationSection />
      }

      default:
        return <Notice type="warn">Section inconnue.</Notice>
    }
  }

  return (
    <div>
      <PageHeader
        title={meta.title}
        subtitle={meta.subtitle}
        actions={
          meta.hasSave ? (
            <Btn loading={saving} onClick={handleSave}>Enregistrer</Btn>
          ) : null
        }
      />

      <div className="max-w-2xl mx-6 mt-6 pb-16 space-y-4">
        {error  && <Notice type="error">{error}</Notice>}
        {saved  && <Notice type="success">Réglages enregistrés.</Notice>}
        {renderSection()}
      </div>

      {showResetModal && (
        <DangerModal
          title="Vider la table wp_bt_reservations"
          description={`Toutes les réservations importées (${
            rSyncStatus?.total_in_db?.toLocaleString('fr-FR') ?? '?'
          } lignes) seront supprimées définitivement. Cette action est irréversible.`}
          confirmWord="SUPPRIMER"
          loading={rResetLoading}
          onConfirm={handleResetReservationsDb}
          onCancel={() => setShowResetModal(false)}
        />
      )}
    </div>
  )
}
