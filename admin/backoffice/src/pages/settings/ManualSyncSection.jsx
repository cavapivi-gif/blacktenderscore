import { RefreshDouble } from 'iconoir-react'
import { Btn, Notice } from '../../components/ui'

export default function ManualSyncSection({ syncing, handleSync, syncResult }) {
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
}
