/**
 * Transformations de normalisation pour l'Universal Importer.
 * Chaque fonction prend une valeur brute (string) et retourne la valeur nettoyée.
 * Extraites des 3 anciens importers (CsvImporter, CsvImporterStats, ReviewsImporter).
 */

// ── Mois français → numéro ──────────────────────────────────────────────────
const FR_MONTHS = {
  janvier:'01', février:'02', fevrier:'02', mars:'03', avril:'04',
  mai:'05', juin:'06', juillet:'07', août:'08', aout:'08',
  septembre:'09', octobre:'10', novembre:'11', décembre:'12', decembre:'12',
}

/**
 * Convertit une date française ou ISO en YYYY-MM-DD.
 * Gère : "01 juin 2026 18:00", "01/06/2026", "2026-06-01T..."
 */
export function frenchDate(raw) {
  if (!raw) return raw
  const s = String(raw).trim()
  if (!s) return ''
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.slice(0, 10)
  const m = s.match(/(\d{1,2})\s+(\S+)\s+(\d{4})/i)
  if (m) {
    const month = FR_MONTHS[m[2].toLowerCase()]
    if (month) return `${m[3]}-${month}-${m[1].padStart(2, '0')}`
  }
  const d = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/)
  if (d) return `${d[3]}-${d[2].padStart(2, '0')}-${d[1].padStart(2, '0')}`
  return s
}

/**
 * Parse un prix français : "109,09" ou "1 234,56" → 109.09 / 1234.56
 */
export function parsePrice(raw) {
  if (raw === null || raw === undefined || raw === '') return null
  const cleaned = String(raw).trim().replace(/\s/g, '').replace(',', '.')
  const val = parseFloat(cleaned)
  return isNaN(val) ? null : val
}

// ── Booking status : français Regiondo → anglais canonique ──────────────────
const BOOKING_STATUS_MAP = {
  'confirmé':                         'confirmed',
  'confirmé (bon enregistré)':        'confirmed',
  'confirmé (bon cadeau)':            'confirmed',
  'annulé':                           'cancelled',
  'annulé (commercial)':              'cancelled',
  'annulé (regiondo)':                'cancelled',
  'annulé (paiement non effectué)':   'cancelled',
  'refusé':                           'rejected',
  'échu':                             'expired',
  'en attente':                       'pending',
  'remboursé':                        'refunded',
}

export function bookingStatus(raw) {
  if (!raw) return raw
  const key = raw.trim().toLowerCase()
  return BOOKING_STATUS_MAP[key] ?? raw
}

/**
 * Normalise le statut de paiement : "Payé (carte)", "Non payé" → paid, unpaid, refunded, pending
 */
export function paymentStatus(raw) {
  if (!raw) return raw
  const lower = raw.trim().toLowerCase()
  if (lower.startsWith('payé') || lower.startsWith('paid') || lower === 'completed' || lower === 'succeeded') return 'paid'
  if (lower.includes('non payé') || lower.includes('impayé') || lower === 'unpaid') return 'unpaid'
  if (lower.startsWith('remboursé') || lower.startsWith('refunded')) return 'refunded'
  if (lower.startsWith('en attente') || lower.startsWith('pending') || lower.startsWith('processing')) return 'pending'
  return raw
}

/**
 * Nettoie le canal : supprime les tags HTML et les codes de réf ajoutés par Regiondo.
 * "GetYourGuide Deutschland GmbH </br>GYGRFQWKLZWK" → "GetYourGuide Deutschland GmbH"
 */
export function sanitizeChannel(raw) {
  if (!raw) return raw
  return raw.replace(/<\/?\s*br\s*\/?>.*/si, '').replace(/<[^>]+>/g, '').trim()
}

/**
 * Parse le champ "Produit" Regiondo qui contient nom + quantité + prix dans une seule cellule.
 * Retourne { product_name, price_total, quantity, offer_raw }.
 * Cas spécial : 1 colonne source → plusieurs champs destination.
 */
export function produitRaw(raw) {
  if (!raw || typeof raw !== 'string') return {}
  const trimmed = raw.trim()
  const result = { offer_raw: trimmed }

  const priceMatch = trimmed.match(/Montant\s+total\s*:\s*([\d\s.,]+)\s*€/i)
  if (priceMatch) {
    let priceStr = priceMatch[1].replace(/\s/g, '').replace(',', '.')
    const parsed = parseFloat(priceStr)
    if (!isNaN(parsed)) result.price_total = parsed
  }

  const qtyMatch = trimmed.match(/(\d+)\s*[×x]/i)
  if (qtyMatch) result.quantity = parseInt(qtyMatch[1], 10)

  const nameMatch = trimmed.match(/^(.+?)\s+\d+\s*[×x]\s/i)
  if (nameMatch) {
    result.product_name = nameMatch[1].replace(/\s+/g, ' ').trim()
  } else {
    const fallback = trimmed.split(/Montant\s+total/i)[0]
    if (fallback) result.product_name = fallback.replace(/[,\s]+$/, '').replace(/\s+/g, ' ').trim()
  }

  return result
}

/**
 * Parse un rating en entier (1-5). Retourne null si invalide.
 */
export function parseRating(raw) {
  if (raw === undefined || raw === null || raw === '') return null
  const n = parseInt(String(raw), 10)
  return (isNaN(n) || n < 1 || n > 5) ? null : n
}

/**
 * Registre de tous les transformers, référençable par nom depuis les configs.
 */
export const TRANSFORMS = {
  frenchDate,
  parsePrice,
  bookingStatus,
  paymentStatus,
  sanitizeChannel,
  produitRaw,
  parseRating,
}
