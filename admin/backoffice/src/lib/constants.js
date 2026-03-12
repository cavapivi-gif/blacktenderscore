// ── Chart colors — Bloomberg-grade palette ──────────────────────────────────
// Monochrome base + accent only where it matters.

export const COLORS = {
  // Primary data
  current:    '#10b981',   // emerald-500 — main series
  compare:    '#818cf8',   // indigo-400  — comparison period (dashed)
  peak:       '#ef4444',   // red-500     — reference line peaks
  basket:     '#f59e0b',   // amber-500   — avg basket overlay

  // Chart infrastructure
  grid:       '#f1f5f9',   // slate-100   — subtle grid
  axis:       '#94a3b8',   // slate-400   — axis labels
  tooltip_bg: '#ffffff',
  tooltip_border: '#e2e8f0',

  // Channels / categories (ordered by contrast)
  palette: [
    '#10b981', '#6366f1', '#f59e0b', '#ef4444',
    '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16',
    '#f97316', '#64748b',
  ],

  // Country map
  map_empty:  '#f1f5f9',   // no data
  map_low:    '#bbf7d0',   // emerald-200
  map_mid:    '#4ade80',   // emerald-400
  map_high:   '#16a34a',   // emerald-600
  map_peak:   '#065f46',   // emerald-800

  // Delta badges — ONLY place with color accents
  delta_pos_bg:   '#ecfdf5',  // emerald-50
  delta_pos_text: '#059669',  // emerald-600
  delta_neg_bg:   '#fef2f2',  // red-50
  delta_neg_text: '#dc2626',  // red-600
}

// ── Quick filter presets ─────────────────────────────────────────────────────

export const PRESETS = [
  { label: '7j',   key: '7d',   days: 6,   granularity: 'day'   },
  { label: '30j',  key: '30d',  days: 29,  granularity: 'day'   },
  { label: '90j',  key: '90d',  days: 89,  granularity: 'week'  },
  { label: '1an',  key: '1y',   months: 11, granularity: 'month' },
  { label: 'Tout', key: 'all',  from: '2017-01-01', granularity: 'month' },
]

// ── KPI template definitions ─────────────────────────────────────────────────
// Each KPI knows where its data lives, how to format it, and when to alert.

export const KPI_TEMPLATES = {
  // ── Primary KPIs (with sparklines) ──
  revenue: {
    label: 'CA total',
    field: 'total_revenue',
    format: 'currency',
    sparkField: 'revenue',
  },
  bookings: {
    label: 'Réservations',
    field: 'total_bookings',
    format: 'number',
    sparkField: 'bookings',
  },
  avg_basket: {
    label: 'Panier moyen',
    field: 'avg_basket',
    format: 'currency',
    sparkField: 'avg_basket',
  },
  cancel_rate: {
    label: 'Taux annulation',
    field: 'cancellation_rate',
    format: 'percent',
    invertDelta: true,
    alertThreshold: 10,
  },
  unique_customers: {
    label: 'Clients uniques',
    field: 'unique_customers',
    format: 'number',
  },
  repeat_rate: {
    label: 'Taux de repeat',
    field: 'repeat_rate',
    format: 'percent',
  },

  // ── Secondary KPIs (compact) ──
  revenue_per_day: {
    label: 'CA / jour',
    field: 'revenue_per_day',
    format: 'currency',
  },
  bookings_per_day: {
    label: 'Rés. / jour',
    field: 'bookings_per_day',
    format: 'decimal',
  },
  lead_time: {
    label: 'Lead time moy.',
    field: 'avg_lead_time_days',
    format: 'days',
  },
  avg_quantity: {
    label: 'Qté moyenne',
    field: 'avg_quantity',
    format: 'decimal',
  },
  unique_products: {
    label: 'Produits actifs',
    field: 'unique_products',
    format: 'number',
  },
  unpaid_rate: {
    label: 'Taux impayés',
    field: 'unpaid_rate',
    format: 'percent',
    invertDelta: true,
    alertThreshold: 5,
  },
  peak_weekday: {
    label: 'Jour de pic',
    field: 'peak_weekday',
    format: 'text',
  },
  ytd_revenue: {
    label: 'CA YTD',
    field: 'ytd_revenue',
    format: 'currency',
  },

  // ── Tertiary KPIs ──
  confirmed: {
    label: 'Confirmées',
    field: 'total_confirmed',
    format: 'number',
  },
  paid_bookings: {
    label: 'Rés. payantes',
    field: 'paid_bookings',
    format: 'number',
  },
  refunds: {
    label: 'Remboursements',
    field: 'refunds_total',
    format: 'currency',
  },
  active_months: {
    label: 'Mois actifs',
    field: 'active_months',
    format: 'number',
  },
  top_product: {
    label: 'Top produit',
    field: 'top_product_name',
    format: 'text',
  },
  concentration: {
    label: 'Concentration top 3',
    field: 'top3_concentration',
    format: 'percent',
  },
}

// ── CSV import column mapping ────────────────────────────────────────────────

export const CSV_COLUMN_MAP = {
  // French headers (Regiondo export)
  'N° commande':          'order_increment_id',
  'calendar_sold_id':     'calendar_sold_id',
  'Date réservation':     'created_at',
  'Produit':              '_produit_raw',  // parsed into product_name + price_total + quantity + offer_raw
  'Acheteur':             'buyer_name',
  'Email':                'buyer_email',
  'Date RDV':             'appointment_date',
  'Canal':                'channel',
  'Statut réservation':   'booking_status',
  'Méthode paiement':     'payment_method',
  'Statut paiement':      'payment_status',
  'booking_key':          'booking_key',
  // Country (multiple possible headers)
  'Pays':                 'buyer_country',
  'Country':              'buyer_country',
  'Billing Country':      'buyer_country',
  'Pays acheteur':        'buyer_country',
  'Buyer Country':        'buyer_country',
  // English headers (fallback)
  'Order Number':         'order_increment_id',
  'Booking Date':         'created_at',
  'Product':              '_produit_raw',
  'Buyer':                'buyer_name',
  'Appointment Date':     'appointment_date',
  'Channel':              'channel',
  'Booking Status':       'booking_status',
  'Payment Method':       'payment_method',
  'Payment Status':       'payment_status',
}

// ── Stat include modules (for /bookings/stats?include=...) ───────────────────

export const STAT_MODULES = {
  base:          'periods,kpis,by_product,by_channel,by_weekday',
  heatmap:       'heatmap',
  payments:      'payments',
  booking_hours: 'booking_hours',
  lead_time:     'lead_time',
  repeat:        'repeat_customers',
  product_mix:   'product_mix',
  channel_matrix:'channel_status',
  yoy:           'yoy',
  cumulative:    'cumulative',
  geo:           'geo',
}
