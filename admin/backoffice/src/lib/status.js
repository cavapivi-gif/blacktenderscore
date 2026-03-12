/**
 * Maps booking_status strings → Badge variant + French label.
 */
export const STATUS_MAP = {
  confirmed:  { variant: 'confirmed',  label: 'Confirmé' },
  booked:     { variant: 'confirmed',  label: 'Réservé' },
  approved:   { variant: 'confirmed',  label: 'Approuvé' },
  completed:  { variant: 'confirmed',  label: 'Terminé' },
  sent:       { variant: 'confirmed',  label: 'Envoyé' },
  paid:       { variant: 'confirmed',  label: 'Payé' },
  canceled:   { variant: 'cancelled',  label: 'Annulé' },
  cancelled:  { variant: 'cancelled',  label: 'Annulé' },
  rejected:   { variant: 'cancelled',  label: 'Rejeté' },
  refunded:   { variant: 'cancelled',  label: 'Remboursé' },
  pending:    { variant: 'pending',    label: 'En attente' },
  processing: { variant: 'pending',    label: 'En cours' },
  new:        { variant: 'pending',    label: 'Nouveau' },
}

/**
 * Normalize status strings from various Regiondo formats.
 */
export const STATUS_NORMALIZE = {
  confirmé:   'confirmed',
  confirme:   'confirmed',
  annulé:     'cancelled',
  annule:     'cancelled',
  'en attente': 'pending',
  'en cours': 'processing',
  réservé:    'booked',
  reserve:    'booked',
  approuvé:   'approved',
  approuve:   'approved',
  envoyé:     'sent',
  envoye:     'sent',
  rejeté:     'rejected',
  rejete:     'rejected',
}
