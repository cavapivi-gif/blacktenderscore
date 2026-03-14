import { Notice } from '../../components/ui'

export default function BookingsSyncSection() {
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
