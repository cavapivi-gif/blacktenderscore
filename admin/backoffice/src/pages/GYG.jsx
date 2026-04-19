import { useState, useEffect, useCallback } from 'react'
import { api } from '../lib/api'
import {
  PageHeader,
  Card,
  Input,
  Select,
  Textarea,
  Btn,
  Badge,
  Table,
  Notice,
  Spinner,
  Toggle,
  StatCard,
  Pagination,
} from '../components/ui'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/Tabs'
import { Sheet, SheetContent, SheetRow } from '../components/Sheet'

// ─── Helpers ─────────────────────────────────────────────────────────────────

const REST_GYG = '/gyg'

/**
 * Appels API vers les endpoints internes bt-regiondo/v1/gyg/*.
 */
const gygApi = {
  testConnection:         ()       => api.get(`${REST_GYG}/test`),
  bookings:               (params) => api.get(`${REST_GYG}/bookings` + toQuery(params)),
  resetBookings:          ()       => api.post(`${REST_GYG}/bookings/reset`, {}),
  stats:                  ()       => api.get(`${REST_GYG}/stats`),
  redeemBooking:          (id)     => api.post(`${REST_GYG}/bookings/${id}/redeem`, {}),
  flushAvailCache:        ()       => api.post(`${REST_GYG}/flush-availability-cache`, {}),
  notifyAvailability:     (body)   => api.post(`${REST_GYG}/notify-availability`, body),
  listDeals:              ()       => api.get(`${REST_GYG}/deals`),
  createDeal:             (body)   => api.post(`${REST_GYG}/deals`, body),
  deleteDeal:             (id)     => apiFetchDelete(`${REST_GYG}/deals/${id}`),
  activateProduct:        (id)     => api.post(`${REST_GYG}/products/${id}/activate`, {}),
  registerSupplier:       (body)   => api.post(`${REST_GYG}/register-supplier`, body),
  logs:                   (params) => api.get(`${REST_GYG}/logs` + toQuery(params)),
}

/** DELETE via fetch direct (api.js n'expose pas de méthode delete) */
async function apiFetchDelete(path) {
  const { rest_url, nonce } = window.btBackoffice || {}
  const res = await fetch(`${rest_url}${path}`, {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
  })
  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || `Erreur ${res.status}`)
  }
  return res.json()
}

function toQuery(params = {}) {
  const q = Object.entries(params)
    .filter(([, v]) => v !== '' && v !== null && v !== undefined)
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join('&')
  return q ? `?${q}` : ''
}

function statusVariant(status) {
  const map = {
    reserved:  'pending',
    confirmed: 'confirmed',
    cancelled: 'cancelled',
    redeemed:  'ok',
  }
  return map[status] ?? 'default'
}

function StatusBadge({ status }) {
  const labels = {
    reserved:  'Réservé',
    confirmed: 'Confirmé',
    cancelled: 'Annulé',
    redeemed:  'Validé',
  }
  return <Badge variant={statusVariant(status)}>{labels[status] ?? status}</Badge>
}

// ─── Onglet Connexion ─────────────────────────────────────────────────────────

function ConnectionTab({ settings, setSettings }) {
  const [saving,      setSaving]      = useState(false)
  const [testing,     setTesting]     = useState(false)
  const [saveResult,  setSaveResult]  = useState(null)
  const [testResult,  setTestResult]  = useState(null)
  const [showReg,     setShowReg]     = useState(false)
  const [regForm,     setRegForm]     = useState({ company_name: '', contact_email: '', phone: '' })
  const [regLoading,  setRegLoading]  = useState(false)
  const [regResult,   setRegResult]   = useState(null)

  const [form, setForm] = useState({
    gyg_supplier_id:       '',
    gyg_mode:              'sandbox',
    gyg_username:          '',
    gyg_password:          '',
    gyg_incoming_username: '',
    gyg_incoming_password: '',
  })

  useEffect(() => {
    if (!settings) return
    setForm(prev => ({
      ...prev,
      gyg_supplier_id: settings.gyg_supplier_id ?? '',
      gyg_mode:        settings.gyg_mode        ?? 'sandbox',
    }))
  }, [settings])

  function handleChange(field, value) {
    setForm(prev => ({ ...prev, [field]: value }))
  }

  async function handleSave() {
    setSaving(true)
    setSaveResult(null)
    try {
      const payload = {
        gyg_supplier_id: form.gyg_supplier_id,
        gyg_mode:        form.gyg_mode,
      }
      if (form.gyg_username)          payload.gyg_username          = form.gyg_username
      if (form.gyg_password)          payload.gyg_password          = form.gyg_password
      if (form.gyg_incoming_username) payload.gyg_incoming_username = form.gyg_incoming_username
      if (form.gyg_incoming_password) payload.gyg_incoming_password = form.gyg_incoming_password

      await api.saveSettings(payload)
      setSaveResult({ ok: true, msg: 'Réglages sauvegardés.' })
      const fresh = await api.settings()
      setSettings(fresh)
    } catch (e) {
      setSaveResult({ ok: false, msg: e.message })
    } finally {
      setSaving(false)
    }
  }

  async function handleTest() {
    setTesting(true)
    setTestResult(null)
    try {
      const res = await gygApi.testConnection()
      setTestResult({ ok: res.ok, mode: res.mode })
    } catch (e) {
      setTestResult({ ok: false, msg: e.message })
    } finally {
      setTesting(false)
    }
  }

  async function handleRegisterSupplier() {
    setRegLoading(true)
    setRegResult(null)
    try {
      const res = await gygApi.registerSupplier({
        company_name:  regForm.company_name,
        contact_email: regForm.contact_email,
        phone:         regForm.phone || undefined,
      })
      setRegResult({ ok: true, msg: 'Supplier enregistré avec succès.', data: res })
    } catch (e) {
      setRegResult({ ok: false, msg: e.message })
    } finally {
      setRegLoading(false)
    }
  }

  return (
    <div className="space-y-6 p-6">
      <Card>
        <div className="p-6 space-y-5">
          <h3 className="text-sm font-medium">Compte GYG Supplier</h3>

          <div className="grid grid-cols-2 gap-4">
            <Input
              label="Supplier ID"
              placeholder="123456"
              value={form.gyg_supplier_id}
              onChange={e => handleChange('gyg_supplier_id', e.target.value)}
            />
            <Select
              label="Mode"
              value={form.gyg_mode}
              onChange={e => handleChange('gyg_mode', e.target.value)}
            >
              <option value="sandbox">Sandbox (test)</option>
              <option value="live">Production (live)</option>
            </Select>
          </div>

          <h3 className="text-sm font-medium pt-2">Auth sortante (notre plugin → GYG)</h3>
          <p className="text-xs text-muted-foreground -mt-3">
            Credentials pour authentifier les appels que nous envoyons vers l'API GYG.
          </p>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <div className="flex items-center gap-2">
                <label className="text-sm font-medium">Username GYG</label>
                {settings?.gyg_username && (
                  <span className="inline-flex items-center gap-1 text-xs text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                    ✓ Configuré
                  </span>
                )}
              </div>
              <Input
                placeholder={settings?.gyg_username ? 'Laisser vide pour conserver' : 'Saisir username'}
                value={form.gyg_username}
                onChange={e => handleChange('gyg_username', e.target.value)}
                autoComplete="off"
              />
            </div>
            <div className="space-y-1.5">
              <div className="flex items-center gap-2">
                <label className="text-sm font-medium">Password GYG</label>
                {settings?.gyg_has_password && (
                  <span className="inline-flex items-center gap-1 text-xs text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                    ✓ Configuré
                  </span>
                )}
              </div>
              <Input
                type="password"
                placeholder={settings?.gyg_has_password ? 'Laisser vide pour conserver' : 'Saisir password'}
                value={form.gyg_password}
                onChange={e => handleChange('gyg_password', e.target.value)}
                autoComplete="new-password"
              />
            </div>
          </div>

          <h3 className="text-sm font-medium pt-2">Auth entrante (GYG → notre plugin)</h3>
          <p className="text-xs text-muted-foreground -mt-3">
            Credentials que GYG doit envoyer pour appeler nos endpoints /wp-json/gyg/v1/*.
          </p>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <div className="flex items-center gap-2">
                <label className="text-sm font-medium">Username entrant</label>
                {settings?.gyg_incoming_username && (
                  <span className="inline-flex items-center gap-1 text-xs text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                    ✓ Configuré
                  </span>
                )}
              </div>
              <Input
                placeholder={settings?.gyg_incoming_username ? 'Laisser vide pour conserver' : 'Saisir username'}
                value={form.gyg_incoming_username}
                onChange={e => handleChange('gyg_incoming_username', e.target.value)}
                autoComplete="off"
              />
            </div>
            <div className="space-y-1.5">
              <div className="flex items-center gap-2">
                <label className="text-sm font-medium">Password entrant</label>
                {settings?.gyg_has_incoming_password && (
                  <span className="inline-flex items-center gap-1 text-xs text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                    ✓ Configuré
                  </span>
                )}
              </div>
              <Input
                type="password"
                placeholder={settings?.gyg_has_incoming_password ? 'Laisser vide pour conserver' : 'Saisir password'}
                value={form.gyg_incoming_password}
                onChange={e => handleChange('gyg_incoming_password', e.target.value)}
                autoComplete="new-password"
              />
            </div>
          </div>

          {saveResult && (
            <Notice type={saveResult.ok ? 'success' : 'error'}>{saveResult.msg}</Notice>
          )}

          <div className="flex items-center gap-3 pt-2">
            <Btn onClick={handleSave} loading={saving}>Sauvegarder</Btn>
            <Btn variant="secondary" onClick={handleTest} loading={testing} disabled={saving}>
              Tester la connexion
            </Btn>
          </div>

          {testResult && (
            <Notice type={testResult.ok ? 'success' : 'error'}>
              {testResult.ok
                ? `Connexion réussie — mode ${testResult.mode}.`
                : `Échec de la connexion${testResult.msg ? ` : ${testResult.msg}` : ''}.`
              }
            </Notice>
          )}
        </div>
      </Card>

      <Card>
        <div className="p-6 space-y-3">
          <h3 className="text-sm font-medium">Endpoints exposés à GYG</h3>
          <p className="text-xs text-muted-foreground">
            Fournir ces URLs dans votre compte GYG Supplier &gt; Integration Settings.
          </p>
          {[
            ['Get Availabilities', 'GET  /wp-json/gyg/v1/get-availabilities/'],
            ['Reserve',            'POST /wp-json/gyg/v1/reserve/'],
            ['Cancel Reservation', 'POST /wp-json/gyg/v1/cancel-reservation/'],
            ['Book',               'POST /wp-json/gyg/v1/book/'],
            ['Cancel Booking',     'POST /wp-json/gyg/v1/cancel-booking/'],
            ['Notify',             'POST /wp-json/gyg/v1/notify/'],
          ].map(([label, path]) => (
            <div key={path} className="flex items-center gap-3 text-xs">
              <span className="text-muted-foreground w-36 shrink-0">{label}</span>
              <code className="bg-muted px-2 py-1 rounded text-[11px] font-mono">{path}</code>
            </div>
          ))}
        </div>
      </Card>

      {/* Section Enregistrement Supplier (P3.3) */}
      <Card>
        <div className="p-6 space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-sm font-medium">Enregistrement Supplier GYG</h3>
              <p className="text-xs text-muted-foreground mt-0.5">
                Usage unique lors de l'onboarding initial. Ne pas répéter si déjà enregistré.
              </p>
            </div>
            <Btn variant="secondary" size="sm" onClick={() => setShowReg(!showReg)}>
              {showReg ? 'Masquer' : 'Afficher'}
            </Btn>
          </div>

          {showReg && (
            <div className="space-y-4 pt-2 border-t">
              <Notice type="warn">
                Cette action n'est à effectuer qu'une seule fois lors de l'onboarding initial sur la plateforme GYG.
              </Notice>

              <div className="grid grid-cols-2 gap-4">
                <Input
                  label="Nom de l'entreprise"
                  placeholder="Ex: StudioJae"
                  value={regForm.company_name}
                  onChange={e => setRegForm(prev => ({ ...prev, company_name: e.target.value }))}
                />
                <Input
                  label="Email de contact"
                  type="email"
                  placeholder="contact@example.com"
                  value={regForm.contact_email}
                  onChange={e => setRegForm(prev => ({ ...prev, contact_email: e.target.value }))}
                />
              </div>
              <Input
                label="Téléphone (optionnel)"
                placeholder="+33 6 00 00 00 00"
                value={regForm.phone}
                onChange={e => setRegForm(prev => ({ ...prev, phone: e.target.value }))}
              />

              {regResult && (
                <Notice type={regResult.ok ? 'success' : 'error'}>{regResult.msg}</Notice>
              )}

              <Btn
                onClick={handleRegisterSupplier}
                loading={regLoading}
                disabled={!regForm.company_name || !regForm.contact_email}
              >
                S'enregistrer comme Supplier GYG
              </Btn>
            </div>
          )}
        </div>
      </Card>
    </div>
  )
}

// ─── Onglet Réservations ──────────────────────────────────────────────────────

function BookingsTab() {
  const [data,         setData]         = useState({ data: [], total: 0 })
  const [loading,      setLoading]      = useState(true)
  const [page,         setPage]         = useState(1)
  const [stats,        setStats]        = useState(null)
  const [error,        setError]        = useState(null)
  const [redeemSheet,  setRedeemSheet]  = useState(null)   // booking sélectionné pour redeem
  const [redeemLoading, setRedeemLoading] = useState(false)
  const [redeemResult, setRedeemResult] = useState(null)

  const PER_PAGE = 50

  const load = useCallback(async (p = 1) => {
    setLoading(true)
    setError(null)
    try {
      const [res, st] = await Promise.all([
        gygApi.bookings({ page: p, per_page: PER_PAGE }),
        gygApi.stats(),
      ])
      setData(res)
      setStats(st)
      setPage(p)
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { load(1) }, [load])

  async function handleRedeem() {
    if (!redeemSheet) return
    setRedeemLoading(true)
    setRedeemResult(null)
    try {
      // Utiliser gyg_booking_id si disponible, sinon l'id local
      const id = redeemSheet.gyg_booking_id || redeemSheet.id
      await gygApi.redeemBooking(id)
      setRedeemResult({ ok: true })
      // Recharger après succès
      await load(page)
      setTimeout(() => setRedeemSheet(null), 1500)
    } catch (e) {
      setRedeemResult({ ok: false, msg: e.message })
    } finally {
      setRedeemLoading(false)
    }
  }

  const BOOKINGS_COLS = [
    { key: 'gyg_booking_id',     label: 'ID Booking',   render: r => r.gyg_booking_id  ?? r.gyg_reservation_id ?? '—' },
    { key: 'product_id',         label: 'Produit'    },
    { key: 'start_datetime',     label: 'Date début', render: r => r.start_datetime?.split('T')[0] ?? r.start_datetime ?? '—' },
    { key: 'status',             label: 'Statut',     render: r => <StatusBadge status={r.status} /> },
    { key: 'customer_name',      label: 'Client',     render: r => r.customer_name || r.customer_email || '—' },
    {
      key: 'actions',
      label: '',
      render: r => r.status === 'confirmed' ? (
        <Btn
          size="sm"
          variant="secondary"
          onClick={e => { e.stopPropagation(); setRedeemSheet(r); setRedeemResult(null) }}
        >
          Valider
        </Btn>
      ) : null,
    },
  ]

  return (
    <div className="space-y-4 p-6">
      {stats && (
        <div className="grid grid-cols-4 divide-x border rounded-lg overflow-hidden">
          <StatCard label="Total"      value={stats.total} />
          <StatCard label="Aujourd'hui" value={stats.today} />
          <StatCard label="Confirmés"  value={stats.by_status?.confirmed ?? 0} />
          <StatCard label="Annulés"    value={stats.by_status?.cancelled ?? 0} />
        </div>
      )}

      {error && <Notice type="error">{error}</Notice>}

      <Card>
        <Table
          columns={BOOKINGS_COLS}
          data={data.data}
          loading={loading}
          empty="Aucune réservation GYG pour l'instant."
        />
        <Pagination
          page={page}
          total={data.total}
          perPage={PER_PAGE}
          onChange={load}
        />
      </Card>

      {/* Sheet de validation (redeem) */}
      <Sheet open={!!redeemSheet} onOpenChange={open => !open && setRedeemSheet(null)}>
        <SheetContent
          title="Valider la réservation"
          description="Confirmez la validation (redeem) de ce booking GYG."
        >
          {redeemSheet && (
            <div className="space-y-4">
              <dl className="space-y-3">
                <SheetRow label="ID Booking">{redeemSheet.gyg_booking_id ?? redeemSheet.gyg_reservation_id ?? '—'}</SheetRow>
                <SheetRow label="Produit">{redeemSheet.product_id ?? '—'}</SheetRow>
                <SheetRow label="Date">
                  {redeemSheet.start_datetime?.split('T')[0] ?? redeemSheet.start_datetime ?? '—'}
                </SheetRow>
                <SheetRow label="Client">
                  {redeemSheet.customer_name || redeemSheet.customer_email || '—'}
                </SheetRow>
                <SheetRow label="Statut">
                  <StatusBadge status={redeemSheet.status} />
                </SheetRow>
              </dl>

              {redeemResult?.ok && (
                <Notice type="success">Réservation validée avec succès.</Notice>
              )}
              {redeemResult?.ok === false && (
                <Notice type="error">{redeemResult.msg}</Notice>
              )}

              {!redeemResult?.ok && (
                <div className="flex gap-3 pt-2">
                  <Btn onClick={handleRedeem} loading={redeemLoading}>
                    Confirmer la validation
                  </Btn>
                  <Btn variant="secondary" onClick={() => setRedeemSheet(null)} disabled={redeemLoading}>
                    Annuler
                  </Btn>
                </div>
              )}
            </div>
          )}
        </SheetContent>
      </Sheet>
    </div>
  )
}

// ─── Onglet Mapping Produits ──────────────────────────────────────────────────

function MappingTab({ settings, setSettings }) {
  const [rows,          setRows]          = useState([])
  const [saving,        setSaving]        = useState(false)
  const [result,        setResult]        = useState(null)
  const [activating,    setActivating]    = useState(null)  // gyg_option_id en cours
  const [activateResult, setActivateResult] = useState({})

  useEffect(() => {
    if (!settings) return
    const map = Array.isArray(settings.gyg_product_map) ? settings.gyg_product_map : []
    setRows(map.length > 0 ? map : [{ notre_product_id: '', gyg_option_id: '', active: true }])
  }, [settings])

  function addRow() {
    setRows(prev => [...prev, { notre_product_id: '', gyg_option_id: '', active: true }])
  }

  function updateRow(idx, field, value) {
    setRows(prev => prev.map((r, i) => i === idx ? { ...r, [field]: value } : r))
  }

  function removeRow(idx) {
    setRows(prev => prev.filter((_, i) => i !== idx))
  }

  async function handleSave() {
    setSaving(true)
    setResult(null)
    try {
      await api.saveSettings({ gyg_product_map: rows })
      setResult({ ok: true, msg: 'Mapping sauvegardé.' })
      const fresh = await api.settings()
      setSettings(fresh)
    } catch (e) {
      setResult({ ok: false, msg: e.message })
    } finally {
      setSaving(false)
    }
  }

  async function handleActivate(gygOptionId) {
    setActivating(gygOptionId)
    setActivateResult(prev => ({ ...prev, [gygOptionId]: null }))
    try {
      await gygApi.activateProduct(gygOptionId)
      setActivateResult(prev => ({ ...prev, [gygOptionId]: { ok: true } }))
      // Recharger settings pour mettre à jour active=true
      const fresh = await api.settings()
      setSettings(fresh)
    } catch (e) {
      setActivateResult(prev => ({ ...prev, [gygOptionId]: { ok: false, msg: e.message } }))
    } finally {
      setActivating(null)
    }
  }

  return (
    <div className="space-y-4 p-6">
      <Card>
        <div className="p-6 space-y-4">
          <p className="text-sm text-muted-foreground">
            Associez vos identifiants produits internes aux Option ID GYG.
            GYG utilise le <strong>gyg_option_id</strong> comme <code>productId</code> dans les appels de disponibilités.
          </p>

          {/* En-têtes */}
          <div className="grid grid-cols-[1fr_1fr_80px_auto_32px] gap-3 text-xs text-muted-foreground uppercase tracking-wider">
            <span>Notre Product ID (Regiondo)</span>
            <span>GYG Option ID</span>
            <span>Actif</span>
            <span />
            <span />
          </div>

          {/* Lignes */}
          {rows.map((row, idx) => (
            <div key={idx} className="grid grid-cols-[1fr_1fr_80px_auto_32px] gap-3 items-center">
              <Input
                placeholder="ex: 12345"
                value={row.notre_product_id}
                onChange={e => updateRow(idx, 'notre_product_id', e.target.value)}
              />
              <Input
                placeholder="ex: 67890"
                type="number"
                value={row.gyg_option_id}
                onChange={e => updateRow(idx, 'gyg_option_id', e.target.value)}
              />
              <div className="flex justify-center items-center gap-2">
                <Toggle
                  checked={!!row.active}
                  onChange={val => updateRow(idx, 'active', val)}
                />
                {!row.active && (
                  <Badge variant="cancelled">Désactivé par GYG</Badge>
                )}
              </div>
              {/* Bouton Réactiver (P3.2) */}
              <div>
                {!row.active && row.gyg_option_id ? (
                  <div className="flex items-center gap-2">
                    <Btn
                      size="sm"
                      variant="secondary"
                      loading={activating === String(row.gyg_option_id)}
                      onClick={() => handleActivate(row.gyg_option_id)}
                    >
                      Réactiver sur GYG
                    </Btn>
                    {activateResult[row.gyg_option_id]?.ok === true && (
                      <Badge variant="ok">Réactivé</Badge>
                    )}
                    {activateResult[row.gyg_option_id]?.ok === false && (
                      <span className="text-xs text-destructive">
                        {activateResult[row.gyg_option_id].msg}
                      </span>
                    )}
                  </div>
                ) : <span />}
              </div>
              <button
                type="button"
                onClick={() => removeRow(idx)}
                className="text-muted-foreground hover:text-destructive transition-colors text-sm font-medium"
                title="Supprimer cette ligne"
              >
                ×
              </button>
            </div>
          ))}

          {result && (
            <Notice type={result.ok ? 'success' : 'error'}>{result.msg}</Notice>
          )}

          <div className="flex items-center gap-3 pt-2">
            <Btn variant="secondary" onClick={addRow} type="button">
              + Ajouter une ligne
            </Btn>
            <Btn onClick={handleSave} loading={saving}>
              Sauvegarder le mapping
            </Btn>
          </div>
        </div>
      </Card>
    </div>
  )
}

// ─── Onglet Deals (P3.1) ─────────────────────────────────────────────────────

function DealsTab() {
  const [deals,      setDeals]      = useState([])
  const [loading,    setLoading]    = useState(true)
  const [error,      setError]      = useState(null)
  const [showForm,   setShowForm]   = useState(false)
  const [dealJson,   setDealJson]   = useState('{}')
  const [creating,   setCreating]   = useState(false)
  const [createResult, setCreateResult] = useState(null)
  const [deleting,   setDeleting]   = useState(null)

  const loadDeals = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await gygApi.listDeals()
      setDeals(Array.isArray(res?.data?.deals) ? res.data.deals : (Array.isArray(res) ? res : []))
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { loadDeals() }, [loadDeals])

  async function handleCreate() {
    setCreating(true)
    setCreateResult(null)
    try {
      const parsed = JSON.parse(dealJson)
      await gygApi.createDeal(parsed)
      setCreateResult({ ok: true, msg: 'Deal créé avec succès.' })
      setDealJson('{}')
      setShowForm(false)
      loadDeals()
    } catch (e) {
      setCreateResult({ ok: false, msg: e.message })
    } finally {
      setCreating(false)
    }
  }

  async function handleDelete(dealId) {
    if (!confirm('Supprimer ce deal ?')) return
    setDeleting(dealId)
    try {
      await gygApi.deleteDeal(dealId)
      loadDeals()
    } catch (e) {
      alert('Erreur : ' + e.message)
    } finally {
      setDeleting(null)
    }
  }

  const DEALS_COLS = [
    { key: 'id',          label: 'ID Deal',      render: r => r.id ?? r.dealId ?? '—' },
    { key: 'name',        label: 'Nom',          render: r => r.name ?? r.title ?? '—' },
    { key: 'discount',    label: 'Remise',       render: r => r.discount ?? r.discountPercent ? `${r.discountPercent ?? r.discount}%` : '—' },
    { key: 'status',      label: 'Statut',       render: r => r.status ? <Badge>{r.status}</Badge> : '—' },
    {
      key: 'actions',
      label: '',
      render: r => {
        const dealId = r.id ?? r.dealId
        return dealId ? (
          <Btn
            size="sm"
            variant="danger"
            loading={deleting === dealId}
            onClick={() => handleDelete(dealId)}
          >
            Supprimer
          </Btn>
        ) : null
      },
    },
  ]

  return (
    <div className="space-y-4 p-6">
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-medium">Deals GYG (promotions)</h3>
        <div className="flex gap-2">
          <Btn variant="secondary" size="sm" onClick={loadDeals} disabled={loading}>
            Actualiser
          </Btn>
          <Btn size="sm" onClick={() => { setShowForm(!showForm); setCreateResult(null) }}>
            + Nouveau deal
          </Btn>
        </div>
      </div>

      {showForm && (
        <Card>
          <div className="p-6 space-y-4">
            <h4 className="text-sm font-medium">Créer un deal</h4>
            <p className="text-xs text-muted-foreground">
              Saisir les données du deal au format JSON selon la spécification GYG Supplier API.
            </p>
            <Textarea
              label="Données du deal (JSON)"
              value={dealJson}
              onChange={e => setDealJson(e.target.value)}
              className="font-mono text-xs"
              style={{ minHeight: 120 }}
            />
            {createResult && (
              <Notice type={createResult.ok ? 'success' : 'error'}>{createResult.msg}</Notice>
            )}
            <div className="flex gap-3">
              <Btn onClick={handleCreate} loading={creating}>Créer le deal</Btn>
              <Btn variant="secondary" onClick={() => setShowForm(false)}>Annuler</Btn>
            </div>
          </div>
        </Card>
      )}

      {error && <Notice type="error">{error}</Notice>}

      <Card>
        <Table
          columns={DEALS_COLS}
          data={deals}
          loading={loading}
          empty="Aucun deal GYG actif."
        />
      </Card>
    </div>
  )
}

// ─── Onglet Logs (P3.5) ──────────────────────────────────────────────────────

function LogsTab() {
  const [data,      setData]      = useState({ data: [], total: 0, stats: null })
  const [loading,   setLoading]   = useState(true)
  const [page,      setPage]      = useState(1)
  const [direction, setDirection] = useState('')
  const [error,     setError]     = useState(null)

  const PER_PAGE = 50

  const load = useCallback(async (p = 1, dir = direction) => {
    setLoading(true)
    setError(null)
    try {
      const res = await gygApi.logs({ page: p, per_page: PER_PAGE, direction: dir })
      setData(res)
      setPage(p)
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }, [direction])

  useEffect(() => { load(1, direction) }, [direction])

  function statusCodeVariant(code) {
    if (!code) return 'default'
    if (code >= 200 && code < 300) return 'ok'
    if (code >= 400 && code < 500) return 'warn'
    if (code >= 500) return 'cancelled'
    return 'default'
  }

  const LOGS_COLS = [
    {
      key: 'direction',
      label: 'Dir.',
      render: r => (
        <Badge variant={r.direction === 'inbound' ? 'pending' : 'active'}>
          {r.direction === 'inbound' ? 'IN' : 'OUT'}
        </Badge>
      ),
    },
    { key: 'endpoint',    label: 'Endpoint',  render: r => <code className="text-[11px] font-mono">{r.endpoint}</code> },
    { key: 'method',      label: 'Méthode',   render: r => <Badge>{r.method}</Badge> },
    {
      key: 'status_code',
      label: 'Status',
      render: r => r.status_code
        ? <Badge variant={statusCodeVariant(r.status_code)}>{r.status_code}</Badge>
        : '—',
    },
    {
      key: 'error',
      label: 'Erreur',
      render: r => r.error
        ? <span className="text-xs text-destructive truncate max-w-xs block">{r.error}</span>
        : '—',
    },
    {
      key: 'created_at',
      label: 'Date',
      render: r => r.created_at ? r.created_at.split('T')[0] + ' ' + (r.created_at.split('T')[1] ?? '').slice(0, 8) : '—',
    },
  ]

  const stats = data.stats

  return (
    <div className="space-y-4 p-6">
      {stats && (
        <div className="grid grid-cols-4 divide-x border rounded-lg overflow-hidden">
          <StatCard label="Total"     value={stats.total} />
          <StatCard label="Entrants"  value={stats.inbound} />
          <StatCard label="Sortants"  value={stats.outbound} />
          <StatCard label="Erreurs"   value={stats.errors} />
        </div>
      )}

      <div className="flex items-center gap-3">
        <Select
          value={direction}
          onChange={e => setDirection(e.target.value)}
          className="w-48"
        >
          <option value="">Tous les logs</option>
          <option value="inbound">Entrants (inbound)</option>
          <option value="outbound">Sortants (outbound)</option>
        </Select>
        <Btn variant="secondary" size="sm" onClick={() => load(1, direction)} disabled={loading}>
          Actualiser
        </Btn>
      </div>

      {error && <Notice type="error">{error}</Notice>}

      <Card>
        <Table
          columns={LOGS_COLS}
          data={data.data}
          loading={loading}
          empty="Aucun log GYG pour l'instant."
        />
        <Pagination
          page={page}
          total={data.total}
          perPage={PER_PAGE}
          onChange={p => load(p, direction)}
        />
      </Card>
    </div>
  )
}

// ─── Page principale GYG ──────────────────────────────────────────────────────

/**
 * Page GetYourGuide — gestion de l'intégration Supplier API GYG.
 *
 * 5 onglets : Connexion | Réservations | Mapping Produits | Deals | Logs
 */
export default function GYG() {
  const [settings, setSettings] = useState(null)
  const [loading,  setLoading]  = useState(true)
  const [error,    setError]    = useState(null)

  useEffect(() => {
    api.settings()
      .then(setSettings)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Spinner size={20} />
      </div>
    )
  }

  return (
    <div className="flex flex-col min-h-full">
      <PageHeader
        title="GetYourGuide"
        subtitle="Intégration Supplier API — réservations, disponibilités, tickets"
        actions={
          <Badge variant={settings?.gyg_mode === 'live' ? 'active' : 'warn'}>
            {settings?.gyg_mode === 'live' ? 'Production' : 'Sandbox'}
          </Badge>
        }
      />

      {error && (
        <div className="p-6">
          <Notice type="error">Impossible de charger les réglages : {error}</Notice>
        </div>
      )}

      <div className="flex-1">
        <Tabs defaultValue="connection">
          <div className="px-6 pt-4 border-b">
            <TabsList>
              <TabsTrigger value="connection">Connexion</TabsTrigger>
              <TabsTrigger value="bookings">Réservations</TabsTrigger>
              <TabsTrigger value="mapping">Mapping Produits</TabsTrigger>
              <TabsTrigger value="deals">Deals</TabsTrigger>
              <TabsTrigger value="logs">Logs</TabsTrigger>
            </TabsList>
          </div>

          <TabsContent value="connection">
            <ConnectionTab settings={settings} setSettings={setSettings} />
          </TabsContent>

          <TabsContent value="bookings">
            <BookingsTab />
          </TabsContent>

          <TabsContent value="mapping">
            <MappingTab settings={settings} setSettings={setSettings} />
          </TabsContent>

          <TabsContent value="deals">
            <DealsTab />
          </TabsContent>

          <TabsContent value="logs">
            <LogsTab />
          </TabsContent>
        </Tabs>
      </div>
    </div>
  )
}
