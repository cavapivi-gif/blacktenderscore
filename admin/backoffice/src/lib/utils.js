/**
 * Classnames utility — joins truthy class strings.
 * @param  {...(string|false|null|undefined)} classes
 * @returns {string}
 */
export function cn(...classes) {
  return classes.filter(Boolean).join(' ')
}

// ── Date formatters ─────────────────────────────────────────────────────────

export function today() {
  return new Date().toISOString().slice(0, 10)
}

export function daysAgo(n) {
  const d = new Date()
  d.setDate(d.getDate() - n)
  return d.toISOString().slice(0, 10)
}

export function monthsAgo(n) {
  const d = new Date()
  d.setMonth(d.getMonth() - n)
  d.setDate(1)
  return d.toISOString().slice(0, 10)
}

export function addDays(dateStr, n) {
  const d = new Date(dateStr + 'T12:00:00')
  d.setDate(d.getDate() + n)
  return d.toISOString().slice(0, 10)
}

export function prevPeriod(from, to) {
  const days = Math.round((new Date(to + 'T12:00:00') - new Date(from + 'T12:00:00')) / 86400000) + 1
  return { cmpFrom: addDays(from, -days), cmpTo: addDays(from, -1) }
}

/**
 * Retourne la plage et la comparaison pour une année calendrier.
 * - Année passée  → from: YYYY-01-01, to: YYYY-12-31
 * - Année courante → from: YYYY-01-01, to: aujourd'hui (en cours)
 * - Comparaison   → toujours l'année civile précédente complète (N-1-01-01 → N-1-12-31)
 *
 * @param {number|string} year
 * @returns {{ from, to, cmpFrom, cmpTo }}
 */
export function calendarYear(year) {
  const y       = Number(year)
  const curYear = new Date().getFullYear()
  return {
    from:    `${y}-01-01`,
    to:      y < curYear ? `${y}-12-31` : today(),
    cmpFrom: `${y - 1}-01-01`,
    cmpTo:   `${y - 1}-12-31`,
  }
}

/**
 * Liste des années disponibles, de l'année courante jusqu'à firstYear (inclus).
 * @param {number} [firstYear=2017]
 * @returns {number[]}
 */
export function availableYears(firstYear = 2017) {
  const cur = new Date().getFullYear()
  const years = []
  for (let y = cur; y >= firstYear; y--) years.push(y)
  return years
}

export function fmtShort(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr + 'T12:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

// ── Number formatters ───────────────────────────────────────────────────────

export function fmtCurrency(v, decimals = 0) {
  if (v == null) return '—'
  return Number(v).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: decimals })
}

export function fmtNum(v) {
  if (v == null) return '—'
  return Number(v).toLocaleString('fr-FR')
}

export function fmtPercent(v, decimals = 1) {
  if (v == null) return '—'
  return Number(v).toFixed(decimals) + '%'
}

export function fmtDecimal(v, decimals = 1) {
  if (v == null) return '—'
  return Number(v).toLocaleString('fr-FR', { maximumFractionDigits: decimals })
}

/**
 * Calculate delta percentage between current and previous values.
 * @returns {number|null}
 */
export function delta(curr, prev) {
  if (prev == null || prev === 0) return null
  return Math.round(((curr - prev) / Math.abs(prev)) * 100)
}
