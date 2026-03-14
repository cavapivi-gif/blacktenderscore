import { Btn, Notice, SectionTitle, Divider } from '../../components/ui'
import CsvImporterStats from '../../components/CsvImporterStats'
import { api } from '../../lib/api'

export default function StatsImportSection({
  pImportStatus, setPImportStatus,
  pResetLoading,
  setShowPResetModal,
}) {
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
