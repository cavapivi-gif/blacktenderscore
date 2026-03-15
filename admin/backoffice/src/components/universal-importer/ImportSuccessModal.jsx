/**
 * Modal de succès après import — affiche les stats finales.
 * Réutilise le Dialog existant.
 */
import { Dialog } from '../Dialog'
import { Btn } from '../ui'

export function ImportSuccessModal({ result, onClose, config }) {
  const total = (result.inserted ?? 0) + (result.updated ?? 0)
  const itemLabel = config?.itemLabel ?? 'ligne'

  return (
    <Dialog
      open={true}
      onClose={onClose}
      size="sm"
      footer={<Btn onClick={onClose} className="w-full">Fermer</Btn>}
    >
      <div className="space-y-5 text-center">
        {/* Checkmark animé */}
        <div className="flex justify-center">
          <span className="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 border-2 border-emerald-200">
            <svg
              className="text-emerald-500"
              style={{ animation: 'drawCheck 0.4s ease-out 0.1s both' }}
              width="32" height="32" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"
            >
              <style>{`@keyframes drawCheck{from{stroke-dashoffset:40}to{stroke-dashoffset:0}}`}</style>
              <path d="M20 6 9 17l-5-5" strokeDasharray="40" strokeDashoffset="40"
                style={{ animation: 'drawCheck 0.4s ease-out 0.1s both' }} />
            </svg>
          </span>
        </div>

        <div className="space-y-1">
          <h3 className="text-base font-semibold">Import terminé !</h3>
          <p className="text-sm text-muted-foreground">
            {total.toLocaleString('fr-FR')} {itemLabel}{total > 1 ? 's' : ''} traité{total > 1 ? 'es' : 'e'}
          </p>
        </div>

        {/* Stats */}
        <div className="flex gap-3">
          <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
            <p className="text-xl font-bold tabular-nums">{(result.inserted ?? 0).toLocaleString('fr-FR')}</p>
            <p className="text-[11px] text-muted-foreground">nouvelles</p>
          </div>
          {(result.updated ?? 0) > 0 && (
            <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
              <p className="text-xl font-bold tabular-nums text-muted-foreground">{result.updated.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">mises à jour</p>
            </div>
          )}
          {(result.skipped ?? 0) > 0 && (
            <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
              <p className="text-xl font-bold tabular-nums text-muted-foreground">{result.skipped.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">ignorées</p>
            </div>
          )}
        </div>

        {/* Erreurs (collapsible) */}
        {result.errors?.length > 0 && (
          <details className="text-left rounded-lg border border-destructive/20 bg-destructive/5 px-3 py-2">
            <summary className="text-xs text-destructive cursor-pointer font-medium">
              {result.errors.length} erreur{result.errors.length > 1 ? 's' : ''}
            </summary>
            <ul className="mt-2 space-y-0.5 text-[11px] text-destructive/80 max-h-32 overflow-y-auto">
              {result.errors.slice(0, 20).map((e, i) => <li key={i}>{e}</li>)}
              {result.errors.length > 20 && <li>… et {result.errors.length - 20} de plus</li>}
            </ul>
          </details>
        )}
      </div>
    </Dialog>
  )
}
