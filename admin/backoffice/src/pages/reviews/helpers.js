import { format } from 'date-fns'
import { fr } from 'date-fns/locale'

// ── Constants ─────────────────────────────────────────────────────────────────

export const FR_WEEKDAYS = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']
export const LEAD_BUCKETS = ['<7j', '7-30j', '30-90j', '90-180j', '>180j']

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Format YYYY-MM-DD -> "3 jan. 2024"
 * @param {string} d
 * @returns {string}
 */
export function fmtDate(d) {
  if (!d || d === '0000-00-00') return '—'
  try { return format(new Date(d + 'T12:00:00'), 'd MMM yyyy', { locale: fr }) }
  catch { return d }
}

/**
 * Compute the comparison period = same duration shifted back 1 day before from.
 * @param {string} from YYYY-MM-DD
 * @param {string} to   YYYY-MM-DD
 * @returns {{ from: string, to: string }}
 */
export function computeComparePeriod(from, to) {
  const fromDate = new Date(from + 'T12:00:00')
  const toDate   = new Date(to   + 'T12:00:00')
  const duration = toDate.getTime() - fromDate.getTime()
  const compareTo   = new Date(fromDate.getTime() - 86400000)
  const compareFrom = new Date(fromDate.getTime() - duration - 86400000)
  return {
    from: compareFrom.toISOString().slice(0, 10),
    to:   compareTo.toISOString().slice(0, 10),
  }
}

/**
 * Build export CSV string from review rows.
 * @param {Array} rows
 */
export function buildExportCsv(rows) {
  const hdr = ['N° commande', 'Produit', 'Client', 'Email', 'Note', 'Résumé', 'Avis', 'Date']
  const csv = [hdr, ...rows.map(r => [
    r.order_number, r.product_name, r.customer_name, r.customer_email,
    r.rating, r.review_title, (r.review_body ?? '').replace(/\n/g, ' '),
    r.review_date,
  ])].map(row => row.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(';')).join('\n')
  Object.assign(document.createElement('a'), {
    href: URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' })),
    download: 'avis.csv',
  }).click()
}
