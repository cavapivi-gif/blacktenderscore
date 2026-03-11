import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { api } from '../lib/api'
import { PageHeader, StatCard, Table, Btn, Notice, Spinner, Badge } from '../components/ui'
import { RefreshDouble } from 'iconoir-react'

const STATUS_MAP = {
  confirmed: { variant: 'confirmed', label: 'Confirmé' },
  cancelled: { variant: 'cancelled', label: 'Annulé' },
  pending:   { variant: 'pending',   label: 'En attente' },
  booked:    { variant: 'confirmed', label: 'Réservé' },
  sent:      { variant: 'pending',   label: 'Envoyé' },
  approved:  { variant: 'confirmed', label: 'Approuvé' },
  reserved:  { variant: 'pending',   label: 'Réservé' },
  canceled:  { variant: 'cancelled', label: 'Annulé' },
  rejected:  { variant: 'cancelled', label: 'Rejeté' },
  processing:{ variant: 'pending',   label: 'En cours' },
}

export default function Dashboard() {
  const navigate = useNavigate()
  const [data, setData]       = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState(null)
  const [syncing, setSyncing] = useState(false)
  const [flushing, setFlushing] = useState(false)
  const [syncResult, setSyncResult] = useState(null)
  const [diagLoading, setDiagLoading] = useState(false)
  const [diagData, setDiagData] = useState(null)

  useEffect(() => {
    api.dashboard()
      .then(setData)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  async function handleSync() {
    setSyncing(true)
    setSyncResult(null)
    try {
      const res = await api.sync()
      setSyncResult(res)
      const fresh = await api.dashboard()
      setData(fresh)
    } catch (e) {
      setError(e.message)
    } finally {
      setSyncing(false)
    }
  }

  async function handleFlush() {
    setFlushing(true)
    try {
      await api.flushCache()
      const fresh = await api.dashboard()
      setData(fresh)
    } finally {
      setFlushing(false)
    }
  }

  async function handleDiagnostic() {
    setDiagLoading(true)
    setDiagData(null)
    try {
      const res = await api.diagnostic()
      setDiagData(res)
    } catch (e) {
      setDiagData({ error: e.message })
    } finally {
      setDiagLoading(false)
    }
  }

  const bookingCols = [
    { key: 'booking_ref',   label: 'Référence' },
    { key: 'product_name',  label: 'Produit' },
    { key: 'booking_date',  label: 'Date', render: r => r.booking_date?.slice(0, 10) ?? '—' },
    { key: 'customer_name', label: 'Client' },
    {
      key: 'total_price',
      label: 'Montant',
      render: r => r.total_price ? `${r.total_price} ${r.currency_code ?? 'EUR'}` : '—',
    },
    {
      key: 'status',
      label: 'Statut',
      render: r => {
        const s = STATUS_MAP[r.status] ?? { variant: 'default', label: r.status ?? '—' }
        return <Badge variant={s.variant}>{s.label}</Badge>
      },
    },
  ]

  return (
    <div>
      <PageHeader
        title="Tableau de bord"
        subtitle="Vue d'ensemble de votre activité Regiondo"
        actions={
          <>
            <Btn variant="secondary" size="sm" loading={flushing} onClick={handleFlush}>
              Vider le cache
            </Btn>
            <Btn size="sm" loading={syncing} onClick={handleSync}>
              <RefreshDouble width={13} height={13} />
              Synchroniser
            </Btn>
          </>
        }
      />

      {error && (
        <div className="px-6 pt-5">
          <Notice type="error">{error}</Notice>
        </div>
      )}

      {syncResult && (
        <div className="px-6 pt-5">
          <Notice type="success">
            Sync terminée — {syncResult.created} créés, {syncResult.updated} mis à jour, {syncResult.errors} erreurs
          </Notice>
        </div>
      )}

      {loading ? (
        <div className="flex items-center justify-center py-20">
          <Spinner size={20} />
        </div>
      ) : (
        <>
          {/* Stats */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-px bg-border mx-6 mt-5 border rounded-lg overflow-hidden">
            <StatCard
              label="Produits"
              value={data?.products_count ?? 0}
              sub={<button onClick={() => navigate('/products')} className="text-xs underline">Voir tout</button>}
            />
            <StatCard
              label="Réservations ce mois"
              value={data?.bookings_month ?? 0}
              accent
              sub={<button onClick={() => navigate('/bookings')} className="text-xs underline">Voir tout</button>}
            />
            <StatCard
              label="CA du mois"
              value={
                data?.revenue_month != null
                  ? `${Number(data.revenue_month).toLocaleString('fr-FR')} €`
                  : '—'
              }
              sub="Revenus confirmés"
            />
            <StatCard
              label="Clients CRM"
              value={data?.customers_total ?? 0}
              sub={<button onClick={() => navigate('/customers')} className="text-xs underline">Voir tout</button>}
            />
          </div>

          {/* API status */}
          {data?.api_status && (
            <div className="mx-6 mt-4">
              <Notice type={data.api_status === 'ok' ? 'info' : 'warn'}>
                {data.api_status === 'ok'
                  ? `API Regiondo connectée — ${data.products_count} produits synchronisés`
                  : `Problème de connexion API : ${data.api_error ?? 'vérifiez vos clés dans Réglages'}`}
              </Notice>
            </div>
          )}

          {/* Recent bookings */}
          <div className="mx-6 mt-6">
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs text-muted-foreground uppercase tracking-wider">Réservations récentes</span>
              <button onClick={() => navigate('/bookings')} className="text-xs text-muted-foreground underline hover:text-foreground">
                Tout voir
              </button>
            </div>
            <div className="rounded-lg border overflow-hidden">
              <Table
                columns={bookingCols}
                data={data?.recent_bookings ?? []}
                empty="Aucune réservation trouvée."
              />
            </div>
          </div>

          {/* Diagnostic API */}
          <div className="mx-6 mt-6 mb-10">
            <div className="flex items-center gap-3 mb-3">
              <span className="text-xs text-muted-foreground uppercase tracking-wider">Diagnostic API</span>
              <Btn variant="secondary" size="sm" loading={diagLoading} onClick={handleDiagnostic}>
                {diagData ? 'Relancer le diagnostic' : 'Lancer le diagnostic'}
              </Btn>
            </div>

            {diagData?.error && (
              <Notice type="error">{diagData.error}</Notice>
            )}

            {diagData?.endpoints && (
              <div className="space-y-3">
                {diagData.endpoints.map((ep, i) => {
                  const ok = ep.status >= 200 && ep.status < 300 && !ep.error
                  const hasData = ep.response?.data && (Array.isArray(ep.response.data) ? ep.response.data.length > 0 : true)
                  const hasError = ep.response?.error || ep.response?.error_code || ep.response?.error_message
                  return (
                    <details key={i} className="rounded-lg border bg-card overflow-hidden">
                      <summary className="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-muted/50">
                        <span className={`w-2 h-2 rounded-full shrink-0 ${
                          hasError ? 'bg-orange-400' :
                          ok && hasData ? 'bg-emerald-500' :
                          ok ? 'bg-yellow-400' :
                          'bg-red-500'
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
                          <span className="font-medium">URL:</span> {ep.url}
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
        </>
      )}
    </div>
  )
}
