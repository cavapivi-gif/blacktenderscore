import { useState } from 'react'
import { RefreshDouble } from 'iconoir-react'
import { Btn, Notice, SectionTitle, Divider } from '../../components/ui'
import { api } from '../../lib/api'
import CsvImporter from '../../components/CsvImporter'

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

export default function ReservationsImportSection({
  rSyncStatus, setRSyncStatus,
  rSyncLoading, rSyncProgress, rSyncLog,
  rResetLoading,
  handleFullImport, handleIncrImport,
  setShowResetModal,
}) {
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
