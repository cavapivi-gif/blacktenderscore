import { useState, useEffect, useRef, useCallback } from 'react' // useRef/useCallback still used by sync/diag polling
import { useParams, NavLink } from 'react-router-dom'
import { RefreshDouble } from 'iconoir-react'
import { api } from '../lib/api'
import { PROVIDER_LIST } from '../lib/aiProviders'
import AiProviderIcon from '../components/AiProviderIcon'
import { PageHeader, Input, Btn, Notice, Spinner, SectionTitle, Divider, Badge, DangerModal } from '../components/ui'
import CsvImporter from '../components/CsvImporter'
import CsvImporterStats from '../components/CsvImporterStats'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/Tabs'
import CodeEditor from '@uiw/react-textarea-code-editor'
import '@uiw/react-textarea-code-editor/dist.css'
import MapPresets from '../components/MapPresets'
import { RolePermissions } from '../components/settings/RolePermissions'

// ── Sections metadata ─────────────────────────────────────────────────────────
const SECTIONS = {
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
              Configuration initiale guidée en 7 étapes
            </p>
          </div>
        </div>

        {/* Détail des étapes */}
        <div className="px-6 py-5 space-y-3">
          {[
            { n: 1, label: 'Prérequis système',   sub: 'Vérifie PHP ≥ 8.0, OpenSSL et crée les tables bt_reservations, bt_reviews, bt_participations' },
            { n: 2, label: 'Chiffrement RGPD',    sub: 'Génère et configure la clé AES-256 pour les données personnelles' },
            { n: 3, label: 'API Regiondo',         sub: 'Saisie et test des clés Public / Secret Regiondo'                },
            { n: 4, label: 'Récapitulatif',        sub: 'Bilan de la configuration et lancement du backoffice'            },
            { n: 5, label: 'Import solditems',     sub: 'Importe les réservations enrichies via CSV → Paramètres › Import solditems' },
            { n: 6, label: 'Import Stats',         sub: 'Importe les participations (OTA, billetterie…) via CSV → Paramètres › Import Stats' },
            { n: 7, label: 'Import avis',          sub: 'Importe les avis clients Regiondo via CSV → Paramètres › Avis clients' },
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
  // Google test de connexion
  const [gTesting,    setGTesting]    = useState(false)
  const [gTestResult, setGTestResult] = useState(null)
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
  // Participations import (stats externes) state
  const [pImportStatus,    setPImportStatus]    = useState(null)
  const [pResetLoading,    setPResetLoading]    = useState(false)
  const [showPResetModal,  setShowPResetModal]  = useState(false)

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

  // Charge le statut import participations (stats externes)
  useEffect(() => {
    if (section !== 'stats-import') return
    api.participationsImportStatus().then(setPImportStatus).catch(() => {})
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
      // Rafraîchit les settings depuis le serveur : affiche les clés masquées
      // et confirme visuellement que tout a bien été sauvegardé
      const fresh = await api.settings()
      setSettings(fresh)
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

  async function handleTestGoogle() {
    setGTesting(true); setGTestResult(null)
    try {
      const res = await api.googleTest()
      setGTestResult(res)
    } catch (e) { setGTestResult({ success: false, error: e.message }) }
    finally { setGTesting(false) }
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

  async function handleResetParticipationsDb() {
    setShowPResetModal(false)
    setPResetLoading(true)
    try {
      await api.resetParticipationsDb()
      const status = await api.participationsImportStatus().catch(() => null)
      if (status) setPImportStatus(status)
    } catch (e) {
      console.error('Reset participations error:', e.message)
    } finally {
      setPResetLoading(false)
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
          <Tabs defaultValue="regiondo">
            <TabsList className="rounded-b-none border border-b-0 border-border bg-muted w-full justify-start gap-0 p-0 h-auto flex-wrap">
              {[
                { val: 'regiondo', label: 'Regiondo',       dot: !!(settings.public_key) },
                { val: 'google',   label: 'Google',         dot: !!(settings.google_credentials_json) },
                { val: 'ia',       label: 'IA',             dot: !!(settings.anthropic_api_key || settings.openai_api_key || settings.gemini_api_key) },
                { val: 'snazzy',   label: 'Snazzy Maps',    dot: !!(settings.snazzymaps_api_key) },
              ].map(({ val, label, dot }) => (
                <TabsTrigger key={val} value={val} className="rounded-none first:rounded-tl-md px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:bg-white data-[state=active]:text-foreground">
                  {label}
                  {dot && <span className="ml-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />}
                </TabsTrigger>
              ))}
            </TabsList>

            {/* ── Regiondo ── */}
            <TabsContent value="regiondo" className="mt-0 border border-t-0 rounded-b-md p-4">
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
            </TabsContent>

            {/* ── Google (GA4 + Search Console) ── */}
            <TabsContent value="google" className="mt-0 border border-t-0 rounded-b-md p-4">
              <div className="space-y-5">

                {/* ── Guide pas-à-pas ── */}
                <div className="rounded-lg border bg-muted/30 divide-y divide-border text-xs">
                  <div className="px-4 py-2.5 font-semibold text-foreground text-sm flex items-center gap-2">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
                    Guide de connexion Google
                  </div>
                  {[
                    {
                      n: 1,
                      title: 'Créer un projet Google Cloud',
                      body: <>Rendez-vous sur <a href="https://console.cloud.google.com" target="_blank" rel="noreferrer" className="underline font-mono hover:text-foreground">console.cloud.google.com</a> → Nouveau projet.</>,
                    },
                    {
                      n: 2,
                      title: 'Activer les APIs',
                      body: <>Dans <em>APIs & Services → Bibliothèque</em>, activez :<br/>
                        · <strong>Google Analytics Data API</strong> (pour GA4)<br/>
                        · <strong>Google Search Console API</strong></>,
                    },
                    {
                      n: 3,
                      title: 'Créer un Service Account',
                      body: <>IAM & Admin → Comptes de service → Créer. Puis : Clés → Ajouter une clé → JSON. Téléchargez le fichier.</>,
                    },
                    {
                      n: 4,
                      title: 'Autoriser dans GA4',
                      body: <><a href="https://analytics.google.com" target="_blank" rel="noreferrer" className="underline hover:text-foreground">analytics.google.com</a> → Administration → Propriété → Gestion des accès → <strong>+</strong> → ajoutez l'email du service account avec le rôle <strong>Lecteur</strong>.</>,
                    },
                    {
                      n: 5,
                      title: 'Autoriser dans Search Console',
                      body: <><a href="https://search.google.com/search-console" target="_blank" rel="noreferrer" className="underline hover:text-foreground">Search Console</a> → Paramètres → Utilisateurs et autorisations → Ajouter l'email du service account.</>,
                    },
                    {
                      n: 6,
                      title: 'Coller le JSON + renseigner les IDs ci-dessous',
                      body: 'Collez le contenu du fichier JSON téléchargé, puis renseignez votre Property ID GA4 et votre Measurement ID.',
                    },
                  ].map(({ n, title, body }) => (
                    <div key={n} className="flex gap-3 px-4 py-3">
                      <div className="w-5 h-5 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">{n}</div>
                      <div className="text-muted-foreground leading-relaxed">
                        <span className="font-medium text-foreground">{title}</span>
                        <br/>{body}
                      </div>
                    </div>
                  ))}
                </div>

                {/* ── Service Account JSON ── */}
                <div className="space-y-1.5">
                  <label className="text-sm font-medium block">Service Account JSON</label>
                  {/* Le backend ne renvoie jamais la clé privée — affiche seulement l'email */}
                  {settings.google_credentials_json?.configured ? (
                    <div className="space-y-2">
                      <div className="flex items-center gap-2 px-3 py-2 rounded-md border bg-emerald-50 border-emerald-200">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="text-emerald-600 shrink-0"><path d="M20 6 9 17l-5-5"/></svg>
                        <span className="text-xs text-emerald-700 font-mono">{settings.google_credentials_json.client_email}</span>
                      </div>
                      <button
                        type="button"
                        onClick={() => { set('google_credentials_json', ''); setGTestResult(null) }}
                        className="text-[11px] text-destructive hover:underline"
                      >
                        Révoquer et remplacer
                      </button>
                    </div>
                  ) : (
                    <div className="space-y-1.5">
                      <textarea
                        rows={6}
                        spellCheck={false}
                        value={typeof settings.google_credentials_json === 'string' ? settings.google_credentials_json : ''}
                        onChange={e => { set('google_credentials_json', e.target.value); setGTestResult(null) }}
                        placeholder={'{\n  "type": "service_account",\n  "project_id": "...",\n  "client_email": "...",\n  "private_key": "-----BEGIN PRIVATE KEY-----\\n..."\n}'}
                        className="w-full rounded-md border border-input bg-transparent px-3 py-2.5 font-mono text-xs leading-relaxed resize-y focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                      />
                      {(() => {
                        const v = typeof settings.google_credentials_json === 'string' ? settings.google_credentials_json : ''
                        if (!v) return null
                        try {
                          const c = JSON.parse(v)
                          return c?.type === 'service_account'
                            ? <p className="text-[11px] text-emerald-600 flex items-center gap-1"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M20 6 9 17l-5-5"/></svg>{c.client_email} — JSON valide</p>
                            : <p className="text-[11px] text-destructive">JSON invalide — doit être de type "service_account"</p>
                        } catch { return <p className="text-[11px] text-destructive">JSON malformé</p> }
                      })()}
                      <p className="text-[11px] text-muted-foreground">La clé privée est chiffrée en transit (HTTPS) et jamais renvoyée au navigateur après enregistrement.</p>
                    </div>
                  )}
                </div>

                {/* ── GA4 IDs ── */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {/* Property ID — numérique, pour l'API Data */}
                  <div className="space-y-1.5">
                    <label className="text-sm font-medium block">Property ID <span className="text-muted-foreground font-normal">(Data API)</span></label>
                    {(() => {
                      const pid = settings.ga4_property_id ?? ''
                      const isMeasurementId = /^G-/i.test(pid)
                      const isInvalid = pid && !/^\d+$/.test(pid)
                      return (
                        <>
                          <input
                            type="text"
                            value={pid}
                            onChange={e => { set('ga4_property_id', e.target.value.replace(/[^0-9]/g, '')); setGTestResult(null) }}
                            placeholder="412345678"
                            className={`flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono ${isInvalid ? 'border-destructive focus-visible:ring-destructive' : 'border-input'}`}
                          />
                          {isMeasurementId ? (
                            <p className="text-[11px] text-destructive flex items-start gap-1">
                              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                              <span><strong>G-... est un Measurement ID</strong>, pas un Property ID. Déplacez-le dans le champ à droite. Le Property ID est un <strong>nombre</strong>, trouvez-le dans GA4 → Admin → Propriété → <em>Informations sur la propriété</em>.</span>
                            </p>
                          ) : (
                            <p className="text-[11px] text-muted-foreground">
                              GA4 → Admin → Propriété → <em>Informations sur la propriété</em> → <strong>ID de la propriété</strong> (nombre, ex: 412345678)
                            </p>
                          )}
                        </>
                      )
                    })()}
                  </div>

                  {/* Measurement ID — G-XXXXXXXX, pour le tracking gtag.js */}
                  <div className="space-y-1.5">
                    <label className="text-sm font-medium block">Measurement ID <span className="text-muted-foreground font-normal">(tracking front)</span></label>
                    {(() => {
                      const mid = settings.ga4_measurement_id ?? ''
                      const isNumericOnly = mid && /^\d+$/.test(mid)
                      return (
                        <>
                          <input
                            type="text"
                            value={mid}
                            onChange={e => set('ga4_measurement_id', e.target.value.toUpperCase())}
                            placeholder="G-XXXXXXXXXX"
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono"
                          />
                          {isNumericOnly ? (
                            <p className="text-[11px] text-amber-600 flex items-start gap-1">
                              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                              Ressemble à un Property ID — le Measurement ID commence par <strong>G-</strong>
                            </p>
                          ) : (
                            <p className="text-[11px] text-muted-foreground">
                              GA4 → Admin → Flux de données → votre flux → <em>Measurement ID</em> (commence par <strong>G-</strong>)
                            </p>
                          )}
                        </>
                      )
                    })()}
                  </div>
                </div>

                {/* ── Search Console URL ── */}
                <div className="space-y-1.5">
                  <label className="text-sm font-medium block">Search Console — URL du site <span className="font-normal text-muted-foreground">(production)</span></label>
                  <input
                    type="url"
                    value={settings.search_console_site_url ?? ''}
                    onChange={e => { set('search_console_site_url', e.target.value); setGTestResult(null) }}
                    placeholder="https://studiojae.fr/"
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono"
                  />
                  {/* Avertissement si l'URL ressemble à un site dev/local */}
                  {(() => {
                    const url = settings.search_console_site_url ?? ''
                    const isDevLike = url && /dev\.|localhost|127\.0\.0|staging\.|preprod\./.test(url)
                    return isDevLike ? (
                      <p className="text-[11px] text-amber-600 flex items-center gap-1">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        URL de développement détectée — Search Console indexe votre site de <strong>production</strong>, entrez son URL ici (ex : <code className="bg-muted px-0.5 rounded">https://studiojae.fr/</code>).
                      </p>
                    ) : (
                      <p className="text-[11px] text-muted-foreground">
                        URL de votre site <strong>de production</strong> exactement telle que déclarée dans Search Console.
                        Retrouvez-la dans <a href="https://search.google.com/search-console" target="_blank" rel="noreferrer" className="underline hover:text-foreground">Search Console</a> → menu déroulant en haut à gauche.
                      </p>
                    )
                  })()}
                </div>

                {/* ── Cache GA4 + GSC ── */}
                <div className="space-y-3 p-3 rounded-md border border-border bg-muted/30">
                  <p className="text-xs font-medium text-muted-foreground">Cache Analytics</p>
                  <div className="grid grid-cols-2 gap-3">
                    <div className="space-y-1.5">
                      <label className="text-sm font-medium block">Cache GA4 (heures)</label>
                      <input
                        type="number"
                        min="1"
                        max="168"
                        value={settings.ga4_cache_hours ?? 6}
                        onChange={e => set('ga4_cache_hours', Number(e.target.value))}
                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                      />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-sm font-medium block">Cache GSC (heures)</label>
                      <input
                        type="number"
                        min="1"
                        max="168"
                        value={settings.gsc_cache_hours ?? 12}
                        onChange={e => set('gsc_cache_hours', Number(e.target.value))}
                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                      />
                    </div>
                  </div>
                  <div className="flex gap-2 pt-1">
                    <Btn
                      variant="secondary"
                      size="sm"
                      onClick={async () => { try { await api.flushGa4Cache() } catch(e) { alert(e.message) } }}
                    >
                      Vider cache GA4
                    </Btn>
                    <Btn
                      variant="secondary"
                      size="sm"
                      onClick={async () => { try { await api.flushGscCache() } catch(e) { alert(e.message) } }}
                    >
                      Vider cache GSC
                    </Btn>
                  </div>
                </div>

                {/* ── Bouton test ── */}
                <div className="flex flex-col gap-3 pt-1">
                  <Btn
                    variant="secondary"
                    size="sm"
                    loading={gTesting}
                    onClick={handleTestGoogle}
                    disabled={!settings.google_credentials_json?.configured}
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 12l2 2 4-4"/><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                    Tester la connexion Google
                  </Btn>
                  {!settings.google_credentials_json?.configured && (
                    <p className="text-[11px] text-muted-foreground">Enregistrez d'abord le Service Account JSON pour pouvoir tester.</p>
                  )}

                  {/* Résultats du test */}
                  {gTestResult && !gTestResult.success && (
                    <Notice type="error">{gTestResult.error}</Notice>
                  )}
                  {gTestResult?.success && (
                    <div className="space-y-3">
                      <p className="text-[11px] text-muted-foreground">
                        Service account : <span className="font-mono text-foreground">{gTestResult.client_email}</span>
                      </p>

                      {/* ── GA4 : propriété configurée ── */}
                      {gTestResult.ga4?.configured && (
                        <div className={`flex items-start gap-2 px-3 py-2 rounded-md border text-xs ${
                          gTestResult.ga4.ok ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'
                        }`}>
                          {gTestResult.ga4.ok
                            ? <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M20 6 9 17l-5-5"/></svg>
                            : <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M18 6 6 18M6 6l12 12"/></svg>}
                          <span>
                            <strong>GA4 Property {settings.ga4_property_id} :</strong>{' '}
                            {gTestResult.ga4.ok ? <>Connecté — {gTestResult.ga4.display_name}</> : gTestResult.ga4.error}
                          </span>
                        </div>
                      )}

                      {/* ── GA4 : toutes les propriétés accessibles ── */}
                      {gTestResult.ga4?.accessible_properties?.length > 0 && (
                        <div className="rounded-md border bg-card text-xs overflow-hidden">
                          <div className="px-3 py-2 bg-muted/50 font-medium text-foreground border-b flex items-center gap-1.5">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
                            Propriétés GA4 accessibles à ce service account
                          </div>
                          {gTestResult.ga4.accessible_properties.map(p => (
                            <div key={p.property_id} className="flex items-center justify-between px-3 py-2 border-b last:border-0 hover:bg-muted/30">
                              <div>
                                <span className="font-medium">{p.display_name}</span>
                                <span className="text-muted-foreground ml-2 font-mono">{p.property_id}</span>
                                <span className="text-muted-foreground ml-1">— {p.account_name}</span>
                              </div>
                              <button
                                type="button"
                                onClick={() => { set('ga4_property_id', p.property_id); setGTestResult(null) }}
                                className="ml-3 shrink-0 px-2 py-0.5 rounded border border-primary text-primary text-[11px] hover:bg-primary hover:text-primary-foreground transition-colors"
                              >
                                Utiliser
                              </button>
                            </div>
                          ))}
                        </div>
                      )}
                      {gTestResult.ga4?.accessible_properties?.length === 0 && (
                        <Notice type="warn">
                          Aucune propriété GA4 accessible — ajoutez l'email <code className="font-mono">{gTestResult.client_email}</code> dans GA4 → Administration → Gestion des accès (rôle Lecteur).
                        </Notice>
                      )}

                      {/* ── Search Console : site configuré ── */}
                      {gTestResult.search_console?.configured && (
                        <>
                          <div className={`flex items-start gap-2 px-3 py-2 rounded-md border text-xs ${
                            gTestResult.search_console.ok ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'
                          }`}>
                            {gTestResult.search_console.ok
                              ? <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M20 6 9 17l-5-5"/></svg>
                              : <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M18 6 6 18M6 6l12 12"/></svg>}
                            <span>
                              <strong>Search Console :</strong>{' '}
                              {gTestResult.search_console.ok
                                ? <>Connecté — {gTestResult.search_console.permission_level}</>
                                : gTestResult.search_console.error}
                              {!gTestResult.search_console.ok && gTestResult.search_console.error?.includes('permission') && !gTestResult.search_console.correct_url && (
                                <span className="block mt-1.5 leading-relaxed">
                                  Pour corriger : <a href="https://search.google.com/search-console" target="_blank" rel="noreferrer" className="underline">Search Console</a> → Paramètres → Utilisateurs → Ajouter <code className="bg-black/10 px-0.5 rounded font-mono">{gTestResult.client_email}</code> avec le rôle <strong>Propriétaire restreint</strong>.
                                </span>
                              )}
                            </span>
                          </div>
                          {/* ── Correction automatique : propriété Domain détectée ── */}
                          {gTestResult.search_console.correct_url && (
                            <div className="flex items-start gap-2 px-3 py-2.5 rounded-md border border-amber-300 bg-amber-50 text-xs text-amber-900">
                              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5 text-amber-600"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                              <span className="flex-1">
                                <strong>Propriété de type Domain détectée.</strong> L'URL configurée ne fonctionne pas avec l'API, mais la variante{' '}
                                <code className="bg-black/10 px-0.5 rounded font-mono">{gTestResult.search_console.correct_url}</code>{' '}
                                est accessible.
                                <button
                                  type="button"
                                  onClick={() => {
                                    set('search_console_site_url', gTestResult.search_console.correct_url)
                                    setGTestResult(prev => ({
                                      ...prev,
                                      search_console: { ...prev.search_console, correct_url: null }
                                    }))
                                  }}
                                  className="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded border border-amber-600 text-amber-800 font-medium hover:bg-amber-100 transition-colors"
                                >
                                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                  Mettre à jour
                                </button>
                              </span>
                            </div>
                          )}
                        </>
                      )}

                      {/* ── Search Console : tous les sites accessibles ── */}
                      {gTestResult.search_console?.accessible_sites?.length > 0 && (
                        <div className="rounded-md border bg-card text-xs overflow-hidden">
                          <div className="px-3 py-2 bg-muted/50 font-medium text-foreground border-b flex items-center gap-1.5">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                            Sites Search Console accessibles à ce service account
                          </div>
                          {gTestResult.search_console.accessible_sites.map(s => (
                            <div key={s.url} className="flex items-center justify-between px-3 py-2 border-b last:border-0 hover:bg-muted/30">
                              <div>
                                <span className="font-mono">{s.url}</span>
                                <span className="text-muted-foreground ml-2">{s.permission_level}</span>
                              </div>
                              <button
                                type="button"
                                onClick={() => { set('search_console_site_url', s.url); setGTestResult(null) }}
                                className="ml-3 shrink-0 px-2 py-0.5 rounded border border-primary text-primary text-[11px] hover:bg-primary hover:text-primary-foreground transition-colors"
                              >
                                Utiliser
                              </button>
                            </div>
                          ))}
                        </div>
                      )}
                      {gTestResult.search_console?.accessible_sites?.length === 0 && (
                        <Notice type="warn">
                          Aucun site Search Console accessible — ajoutez l'email <code className="font-mono">{gTestResult.client_email}</code> dans Search Console → Paramètres → Utilisateurs.
                        </Notice>
                      )}

                      {!gTestResult.ga4?.configured && !gTestResult.search_console?.configured && (
                        <Notice type="warn">Credentials valides mais aucun Property ID ni URL configuré — utilisez les boutons ci-dessus pour sélectionner votre propriété/site.</Notice>
                      )}
                    </div>
                  )}
                </div>

              </div>
            </TabsContent>

            {/* ── IA (multi-provider) ── */}
            <TabsContent value="ia" className="mt-0 border border-t-0 rounded-b-md p-4">
              <div className="space-y-4">

                {/* Sélecteur visuel de provider */}
                <div>
                  <p className="text-sm font-medium mb-2">Provider actif</p>
                  <div className="grid grid-cols-3 gap-2">
                    {PROVIDER_LIST.map(p => {
                      const active = (settings.ai_provider ?? 'anthropic') === p.key
                      return (
                        <button
                          key={p.key}
                          type="button"
                          onClick={() => set('ai_provider', p.key)}
                          className={[
                            'flex flex-col items-center gap-1.5 p-3 rounded-lg border transition-all text-center',
                            active
                              ? 'border-foreground/30 bg-foreground/5 shadow-sm'
                              : 'border-border hover:border-foreground/20 hover:bg-muted/40 opacity-70',
                          ].join(' ')}
                        >
                          <AiProviderIcon iconKey={p.iconKey} variant="Color" size={22} />
                          <span className="text-[11px] font-medium leading-tight">{p.sublabel}</span>
                          <span className="text-[10px] text-muted-foreground leading-tight">{p.label}</span>
                          {active && (
                            <span className="text-[10px] text-emerald-600 font-medium mt-0.5">Actif</span>
                          )}
                        </button>
                      )
                    })}
                  </div>
                </div>

                {/* Clés API — une par provider */}
                <div className="space-y-2.5">
                  {PROVIDER_LIST.map(p => {
                    const active = (settings.ai_provider ?? 'anthropic') === p.key
                    const fieldKey = p.key + '_api_key'
                    const hasKey   = !!(settings[fieldKey])
                    return (
                      <div
                        key={p.key}
                        className={[
                          'flex items-center gap-3 p-3 rounded-lg border transition-colors',
                          active ? 'border-foreground/20 bg-card' : 'border-border opacity-60',
                        ].join(' ')}
                      >
                        <AiProviderIcon iconKey={p.iconKey} variant="Color" size={18} />
                        <div className="flex-1 min-w-0">
                          <p className="text-xs font-medium mb-1">{p.sublabel}</p>
                          <Input
                            type="password"
                            value={settings[fieldKey] ?? ''}
                            onChange={e => set(fieldKey, e.target.value)}
                            placeholder={p.placeholder}
                          />
                        </div>
                        {hasKey && (
                          <span className="text-[10px] text-emerald-600 font-medium shrink-0">✓</span>
                        )}
                      </div>
                    )
                  })}
                </div>

                {/* Note Meta / Groq */}
                <p className="text-[11px] text-muted-foreground">
                  Meta Llama utilise l'API <strong>Groq</strong> (accès gratuit sur console.groq.com).
                </p>
              </div>
            </TabsContent>

            {/* ── Snazzy Maps ── */}
            <TabsContent value="snazzy" className="mt-0 border border-t-0 rounded-b-md p-4">
              <div className="space-y-4">
                <p className="text-sm text-muted-foreground">
                  Permet d'accéder à la bibliothèque de styles depuis l'éditeur de carte.
                </p>
                <Input
                  label="Clé API Snazzy Maps"
                  value={settings.snazzymaps_api_key ?? ''}
                  onChange={e => set('snazzymaps_api_key', e.target.value)}
                  placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                />
                <p className="text-[11px] text-muted-foreground">
                  Obtenez votre clé sur <a href="https://snazzymaps.com/account/api" target="_blank" rel="noreferrer" className="underline hover:text-foreground">snazzymaps.com/account/api</a>.
                </p>
              </div>
            </TabsContent>
          </Tabs>
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
                <TabsTrigger value="css" className="rounded-none rounded-tl-md px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:border-b data-[state=active]:border-b-white data-[state=active]:bg-white data-[state=active]:text-foreground">
                  CSS
                  {settings.booking_custom_css?.trim() && (
                    <span className="ml-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />
                  )}
                </TabsTrigger>
                <TabsTrigger value="js" className="rounded-none rounded-tr-md px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:border-b data-[state=active]:border-b-white data-[state=active]:bg-white data-[state=active]:text-foreground">
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
            </p>
            {!settings.snazzymaps_api_key && (
              <Notice type="warn">
                Clé Snazzy Maps non configurée — rendez-vous dans{' '}
                <NavLink to="/settings/api" className="underline font-medium">Connexion API → Snazzy Maps</NavLink>{' '}
                pour l'ajouter.
              </Notice>
            )}
            <MapPresets
              presets={settings.map_presets ?? []}
              activeJson={settings.map_style_json ?? ''}
              apiKey={settings.maps_api_key ?? ''}
              snazzymapsEnabled={!!(settings.snazzymaps_api_key)}
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
              <Btn variant="danger" loading={rResetLoading} onClick={() => setShowResetModal(true)}
                disabled={rSyncLoading || rResetLoading}
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

      // ── Import participations (stats externes) ────────────────────────────
      case 'stats-import': {
        return (
          <div className="space-y-5">
            {/* Stats DB */}
            <div className="rounded-lg border bg-card p-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-0.5">Dans la DB</p>
                <p className="text-xl font-semibold tabular-nums">
                  {pImportStatus ? pImportStatus.total_in_db.toLocaleString('fr-FR') : '—'}
                </p>
              </div>
              <div>
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-0.5">Première date</p>
                <p className="text-sm font-medium">{pImportStatus?.date_min ?? '—'}</p>
              </div>
              <div>
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-0.5">Dernière date</p>
                <p className="text-sm font-medium">{pImportStatus?.date_max ?? '—'}</p>
              </div>
              <div>
                <p className="text-[11px] text-muted-foreground uppercase tracking-wider mb-0.5">Dernier import</p>
                <p className="text-xs text-muted-foreground">
                  {pImportStatus?.last_import
                    ? new Date(pImportStatus.last_import).toLocaleString('fr-FR')
                    : 'Jamais'}
                </p>
              </div>
            </div>

            <p className="text-sm text-muted-foreground">
              Importe les <strong>participations</strong> depuis un CSV externe (OTA, billetterie…).
              Colonnes attendues : <code>Date de la participation</code>, <code>Nom du produit</code>,
              prénom/nom/email client, prix net/brut, téléphone.
            </p>

            <Notice type="warn">
              <strong>Déduplication :</strong> chaque ligne est identifiée par la combinaison
              date + produit + email + prix brut. Deux achats identiques sur ces quatre champs
              seront comptés comme un seul — si vos exports manquent de dates, réimportez
              après avoir corrigé la source.
            </Notice>

            <Divider />
            <SectionTitle>Import CSV</SectionTitle>
            <CsvImporterStats
              onDone={() => api.participationsImportStatus().then(setPImportStatus).catch(() => {})}
            />

            <Divider />
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium">Vider la table</p>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Supprime toutes les participations importées. Réimportez ensuite depuis le CSV.
                </p>
              </div>
              <Btn variant="danger" loading={pResetLoading} onClick={() => setShowPResetModal(true)}>
                Vider la DB
              </Btn>
            </div>
          </div>
        )
      }

      case 'installation': {
        return <InstallationSection />
      }

      case 'permissions': {
        return <RolePermissions />
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

      <DangerModal
        open={showResetModal}
        title="Vider la table bt_reservations"
        onClose={() => setShowResetModal(false)}
        onConfirm={handleResetReservationsDb}
        confirmLabel="Supprimer définitivement"
        loading={rResetLoading}
      >
        Toutes les réservations importées ({rSyncStatus?.total_in_db?.toLocaleString('fr-FR') ?? '?'} lignes)
        seront supprimées définitivement. Cette action est irréversible.
      </DangerModal>

      <DangerModal
        open={showPResetModal}
        title="Vider la table bt_participations"
        onClose={() => setShowPResetModal(false)}
        onConfirm={handleResetParticipationsDb}
        confirmLabel="Supprimer définitivement"
        loading={pResetLoading}
      >
        Toutes les participations importées ({pImportStatus?.total_in_db?.toLocaleString('fr-FR') ?? '?'} lignes)
        seront supprimées définitivement. Cette action est irréversible.
      </DangerModal>
    </div>
  )
}
