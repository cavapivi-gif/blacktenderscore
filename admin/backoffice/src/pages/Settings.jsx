import { useState, useEffect, useRef, useCallback } from 'react' // useRef/useCallback still used by sync/diag polling
import { useParams, NavLink } from 'react-router-dom'
import { RefreshDouble } from 'iconoir-react'
import { api } from '../lib/api'
import { PageHeader, Input, Btn, Notice, Spinner, SectionTitle, Divider, Badge, DangerModal } from '../components/ui'
import { RolePermissions } from '../components/settings/RolePermissions'

import { SECTIONS, fmtYMD, daysAgo } from './settings/shared'
import ApiSection from './settings/ApiSection'
import SyncSection from './settings/SyncSection'
import CssSection from './settings/CssSection'
import MapSection from './settings/MapSection'
import CacheSection from './settings/CacheSection'
import ManualSyncSection from './settings/ManualSyncSection'
import DiagnosticSection from './settings/DiagnosticSection'
import BookingsSyncSection from './settings/BookingsSyncSection'
import ReservationsImportSection from './settings/ReservationsImportSection'
import StatsImportSection from './settings/StatsImportSection'
import InstallationSection from './settings/InstallationSection'

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
  const [showBResetModal,  setShowBResetModal]  = useState(false)
  const savedTimerRef = useRef(null)

  // Cleanup saved timer on unmount
  useEffect(() => () => clearTimeout(savedTimerRef.current), [])

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
      clearTimeout(savedTimerRef.current)
      savedTimerRef.current = setTimeout(() => setSaved(false), 3000)
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
    setShowBResetModal(false)
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
    } catch {
      // Silently handled — user sees loading state reset
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

      case 'api':
        return (
          <ApiSection
            settings={settings}
            set={set}
            testing={testing}
            handleTestApi={handleTestApi}
            testResult={testResult}
            gTesting={gTesting}
            handleTestGoogle={handleTestGoogle}
            gTestResult={gTestResult}
            setGTestResult={setGTestResult}
          />
        )

      case 'sync':
        return (
          <SyncSection
            settings={settings}
            set={set}
            syncPostType={syncPostType}
          />
        )

      case 'css':
        return (
          <CssSection
            settings={settings}
            set={set}
          />
        )

      case 'map':
        return (
          <MapSection
            settings={settings}
            set={set}
          />
        )

      case 'cache':
        return (
          <CacheSection
            settings={settings}
            set={set}
            flushing={flushing}
            handleFlush={handleFlush}
          />
        )

      case 'manual-sync':
        return (
          <ManualSyncSection
            syncing={syncing}
            handleSync={handleSync}
            syncResult={syncResult}
          />
        )

      case 'diagnostic':
        return (
          <DiagnosticSection
            diagLoading={diagLoading}
            diagData={diagData}
            handleDiagnostic={handleDiagnostic}
          />
        )

      case 'bookings-sync':
        return <BookingsSyncSection />

      case 'reservations-import':
        return (
          <ReservationsImportSection
            rSyncStatus={rSyncStatus}
            setRSyncStatus={setRSyncStatus}
            rSyncLoading={rSyncLoading}
            rSyncProgress={rSyncProgress}
            rSyncLog={rSyncLog}
            rResetLoading={rResetLoading}
            handleFullImport={handleFullImport}
            handleIncrImport={handleIncrImport}
            setShowResetModal={setShowResetModal}
          />
        )

      case 'stats-import':
        return (
          <StatsImportSection
            pImportStatus={pImportStatus}
            setPImportStatus={setPImportStatus}
            pResetLoading={pResetLoading}
            setShowPResetModal={setShowPResetModal}
          />
        )

      case 'installation':
        return <InstallationSection />

      case 'permissions':
        return <RolePermissions />

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
        open={showBResetModal}
        title="Vider la table bt_bookings"
        onClose={() => setShowBResetModal(false)}
        onConfirm={handleResetDb}
        confirmLabel="Supprimer définitivement"
        loading={bResetLoading}
      >
        Toutes les réservations brutes (bookings) seront supprimées définitivement. Cette action est irréversible.
      </DangerModal>

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
