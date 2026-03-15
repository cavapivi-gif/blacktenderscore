/**
 * Configurations des types d'import pour l'Universal Importer.
 * Ajouter un nouveau type = ajouter un objet ici, rien d'autre.
 *
 * Chaque champ définit :
 * - key:        nom du champ BDD destination
 * - label:      label affiché dans le mapper
 * - type:       type de validation (string, date, number, email, status, rating)
 * - required:   si le champ est obligatoire pour qu'une ligne soit valide
 * - aliases:    noms CSV courants pour l'auto-match (insensible à la casse + trim)
 * - transform:  nom du transformer à appliquer (depuis import-transforms.js)
 * - multiField: si true, le transform retourne un objet à merger dans la ligne (ex: produitRaw)
 */

export const IMPORT_CONFIGS = {
  // ── Réservations (Solditems Regiondo) ──────────────────────────────────────
  reservations: {
    id: 'reservations',
    label: 'Réservations (Solditems)',
    description: 'Import CSV Regiondo — réservations et paiements',
    apiMethod: 'importReservationsCsv',
    resetMethod: 'resetReservationsDb',
    statusMethod: 'importReservationsStatus',
    batchSize: 200,
    uniqueKey: 'calendar_sold_id',
    allowReset: false,
    itemLabel: 'réservation',
    // Champs composites : plusieurs colonnes CSV → un seul champ BDD
    compositeFields: [
      { target: 'buyer_name', sources: ['_buyer_firstname', '_buyer_lastname'], join: ' ' },
    ],
    fields: [
      { key: 'calendar_sold_id',   label: 'ID Calendrier',      type: 'string',  required: false, aliases: ['calendar_sold_id', 'Calendar Sold ID'] },
      { key: 'order_increment_id', label: 'N° Commande',        type: 'string',  required: false, aliases: ['N° commande', 'Order Number', 'order_number'] },
      { key: 'created_at',         label: 'Date réservation',   type: 'date',    required: false, aliases: ['Date réservation', 'Booking Date', 'created_at'], transform: 'frenchDate' },
      { key: '_produit_raw',       label: 'Produit (brut)',      type: 'string',  required: false, aliases: ['Produit', 'Product'], transform: 'produitRaw', multiField: true },
      { key: 'product_name',      label: 'Nom du produit',      type: 'string',  required: false, aliases: ['Nom du produit', 'Product Name'] },
      { key: 'price_total',       label: 'Prix total',          type: 'number',  required: false, aliases: ['Prix total', 'Prix (net)', 'Prix (brut)', 'Total Price', 'Price', 'Montant', 'Tarif', 'price'], transform: 'parsePrice' },
      { key: 'quantity',           label: 'Quantité',            type: 'number',  required: false, aliases: ['Quantité', 'Quantity', 'Qté', 'qty'] },
      { key: 'offer_raw',         label: 'Offre (texte brut)',   type: 'string',  required: false, aliases: ['Offre', 'Offer', 'offer_raw'] },
      { key: 'buyer_name',         label: 'Acheteur',           type: 'string',  required: false, aliases: ['Acheteur', 'Buyer', 'buyer_name'] },
      { key: '_buyer_firstname',   label: 'Prénom client',      type: 'string',  required: false, aliases: ['Prénom du client', 'Prénom', 'Customer First Name', 'First Name'] },
      { key: '_buyer_lastname',    label: 'Nom client',         type: 'string',  required: false, aliases: ['Nom du client', 'Nom', 'Customer Last Name', 'Last Name'] },
      { key: 'buyer_email',        label: 'Email',              type: 'email',   required: false, aliases: ['Email', 'buyer_email'] },
      { key: 'appointment_date',   label: 'Date RDV',           type: 'date',    required: false, aliases: ['Date RDV', 'Appointment Date'], transform: 'frenchDate' },
      { key: 'channel',            label: 'Canal',              type: 'string',  required: false, aliases: ['Canal', 'Channel'], transform: 'sanitizeChannel' },
      { key: 'booking_status',     label: 'Statut réservation', type: 'status',  required: false, aliases: ['Statut réservation', 'Booking Status'], transform: 'bookingStatus' },
      { key: 'payment_method',     label: 'Méthode paiement',   type: 'string',  required: false, aliases: ['Méthode paiement', 'Payment Method'] },
      { key: 'payment_status',     label: 'Statut paiement',    type: 'status',  required: false, aliases: ['Statut paiement', 'Payment Status'], transform: 'paymentStatus' },
      { key: 'booking_key',        label: 'Clé réservation',    type: 'string',  required: false, aliases: ['booking_key', 'Booking Key'] },
      { key: 'buyer_country',      label: 'Pays',               type: 'string',  required: false, aliases: ['Pays', 'Country', 'Billing Country', 'Pays acheteur', 'Buyer Country'] },
    ],
  },

  // ── Participations (Stats externes) ────────────────────────────────────────
  participations: {
    id: 'participations',
    label: 'Participations (Stats)',
    description: 'Import participations OTA / billetterie externe',
    apiMethod: 'importParticipationsCsv',
    resetMethod: 'resetParticipationsDb',
    statusMethod: 'participationsImportStatus',
    batchSize: 500,
    uniqueKey: 'product_name',
    allowReset: false,
    itemLabel: 'participation',
    fields: [
      { key: 'participation_date', label: 'Date participation', type: 'date',   required: false, aliases: ['Date de la participation', 'Participation Date', 'Date RDV', 'Date évènement'], transform: 'frenchDate' },
      { key: 'booking_date',       label: 'Date réservation',   type: 'date',   required: false, aliases: ['Date réservation', 'Date de réservation', 'Date commande', 'Booking Date', 'Order Date'], transform: 'frenchDate' },
      { key: 'product_name',       label: 'Nom du produit',     type: 'string', required: true,  aliases: ['Nom du produit', 'Product Name'] },
      { key: 'buyer_firstname',    label: 'Prénom client',      type: 'string', required: false, aliases: ['Prénom du client', 'Customer First Name'] },
      { key: 'buyer_lastname',     label: 'Nom client',         type: 'string', required: false, aliases: ['Nom du client', 'Customer Last Name'] },
      { key: 'buyer_email',        label: 'Email client',       type: 'email',  required: false, aliases: ['Adresse E-mail du client', 'Customer Email'] },
      { key: 'price_net',          label: 'Prix net',           type: 'number', required: false, aliases: ['Prix (net)', 'Net Price'], transform: 'parsePrice' },
      { key: 'price_gross',        label: 'Prix brut',          type: 'number', required: false, aliases: ['Prix (brut)', 'Gross Price'], transform: 'parsePrice' },
      { key: 'phone',              label: 'Téléphone',          type: 'string', required: false, aliases: ['Téléphone', 'Phone'] },
    ],
  },

  // ── Avis clients (Reviews Regiondo) ────────────────────────────────────────
  reviews: {
    id: 'reviews',
    label: 'Avis clients',
    description: 'Import CSV Regiondo — avis et évaluations',
    apiMethod: 'importAvisCsv',
    resetMethod: 'resetAvis',
    statusMethod: null,
    batchSize: 500,
    uniqueKey: 'order_number',
    allowReset: true,
    itemLabel: 'avis',
    // Gestion spéciale des doublons Évaluation/évaluation dans le header
    specialHeaderHandling: 'reviews',
    fields: [
      { key: 'order_number',    label: 'N° de commande',    type: 'string',  required: true,  aliases: ['N° de commande'] },
      { key: 'product_name',    label: 'Produit',           type: 'string',  required: false, aliases: ['Produit', 'Product'] },
      { key: 'category',        label: 'Catégorie',         type: 'string',  required: false, aliases: ['Catégorie', 'Categorie', 'Category'] },
      { key: 'guide',           label: 'Guide',             type: 'string',  required: false, aliases: ['Guide'] },
      { key: 'booking_date',    label: 'Date réservation',  type: 'date',    required: false, aliases: ['Date de réservation', 'Date de reservation'], transform: 'frenchDate' },
      { key: 'event_date',      label: "Date de l'évènement", type: 'date',  required: false, aliases: ["Date de l'évènement", "Date de l'evenement"], transform: 'frenchDate' },
      { key: 'review_date',     label: "Date d'évaluation", type: 'date',    required: false, aliases: ["Date d'évaluation", "Date d'evaluation"], transform: 'frenchDate' },
      { key: 'customer_name',   label: 'Nom du client',     type: 'string',  required: false, aliases: ['Nom du client', 'Customer Name'] },
      { key: 'customer_email',  label: 'Email client',      type: 'email',   required: false, aliases: ['Customer email'] },
      { key: 'customer_phone',  label: 'Téléphone client',  type: 'string',  required: false, aliases: ['Customer phone'] },
      { key: 'rating',          label: 'Note (1-5)',         type: 'rating',  required: false, aliases: ['\u00c9valuation'], transform: 'parseRating' },
      { key: 'review_body',     label: 'Texte avis',        type: 'string',  required: false, aliases: ['\u00e9valuation'] },
      { key: 'review_title',    label: "Résumé de l'avis",  type: 'string',  required: false, aliases: ["Résumé de l'évaluation", "Resume de l'evaluation"] },
      { key: 'review_status',   label: 'Statut',            type: 'string',  required: false, aliases: ['Statut', 'Status'] },
      { key: 'employee_name',   label: 'Nom employé',       type: 'string',  required: false, aliases: ['Employee Name'] },
      { key: 'response',        label: 'Réponse',           type: 'string',  required: false, aliases: ['Response'] },
    ],
  },
}

/**
 * Retourne la config pour un type d'import donné.
 * @param {string} type - 'reservations' | 'participations' | 'reviews'
 */
export function getImportConfig(type) {
  const config = IMPORT_CONFIGS[type]
  if (!config) throw new Error(`Type d'import inconnu : "${type}"`)
  return config
}
