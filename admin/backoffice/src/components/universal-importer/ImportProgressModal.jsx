/**
 * Modal de progression pendant l'import batch.
 * Réutilise le Dialog existant pour le style cohérent.
 */
import { Dialog } from '../Dialog'
import { Spinner } from '../ui'

export function ImportProgressModal({ progress, pct, config }) {
  const itemLabel = config?.itemLabel ?? 'ligne'

  return (
    <Dialog open={true} size="sm" title="Import en cours…" description={`Batch ${progress.current} / ${progress.total}`}>
      <div className="space-y-5">
        {/* Barre de progression */}
        <div className="space-y-1.5">
          <div className="flex items-center gap-3 mb-2">
            <Spinner size={16} />
            <span className="text-2xl font-bold tabular-nums text-primary ml-auto">{pct}%</span>
          </div>
          <div className="h-2.5 rounded-full bg-muted overflow-hidden">
            <div
              className="h-full rounded-full bg-primary transition-all duration-500 ease-out"
              style={{ width: `${pct}%` }}
            />
          </div>
          <div className="flex justify-between text-[11px] text-muted-foreground tabular-nums">
            <span>{progress.sent.toLocaleString('fr-FR')} {itemLabel}s envoyées</span>
            <span>{progress.rows.toLocaleString('fr-FR')} total</span>
          </div>
        </div>

        {/* Compteurs live */}
        {(progress.inserted > 0 || progress.updated > 0) && (
          <div className="flex gap-3">
            <div className="flex-1 rounded-lg border border-border px-3 py-2 text-center">
              <p className="text-lg font-bold tabular-nums">{progress.inserted.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">insérées</p>
            </div>
            {progress.updated > 0 && (
              <div className="flex-1 rounded-lg border border-border px-3 py-2 text-center">
                <p className="text-lg font-bold tabular-nums text-muted-foreground">{progress.updated.toLocaleString('fr-FR')}</p>
                <p className="text-[11px] text-muted-foreground">mises à jour</p>
              </div>
            )}
            {progress.errors?.length > 0 && (
              <div className="flex-1 rounded-lg border border-destructive/30 px-3 py-2 text-center">
                <p className="text-lg font-bold tabular-nums text-destructive">{progress.errors.length}</p>
                <p className="text-[11px] text-destructive/70">erreurs</p>
              </div>
            )}
          </div>
        )}

        <p className="text-[11px] text-muted-foreground text-center">Ne fermez pas cette fenêtre</p>
      </div>
    </Dialog>
  )
}
