/**
 * Parseur CSV universel — extrait des 3 anciens composants et unifié.
 * Supporte RFC 4180 (guillemets, newlines dans les champs) + détection auto du séparateur.
 */

/**
 * Detecte le separateur CSV sur la première ligne.
 * @param {string} firstLine
 * @returns {string} ',' | ';' | '\t'
 */
export function detectSeparator(firstLine) {
  let sep = ','
  if (firstLine.split(';').length > firstLine.split(',').length) sep = ';'
  if (firstLine.split('\t').length > firstLine.split(sep).length) sep = '\t'
  return sep
}

/**
 * Parseur RFC 4180 complet — gère les newlines dans les champs entre guillemets.
 * Retourne un tableau de tableaux de chaînes (lignes × cellules).
 * @param {string} text   - texte CSV brut
 * @param {string} sep    - séparateur détecté
 * @returns {string[][]}
 */
export function parseFullCsv(text, sep) {
  const rows = []
  let cells = []
  let current = ''
  let inQuotes = false

  for (let i = 0; i <= text.length; i++) {
    const ch = i < text.length ? text[i] : null

    if (ch === null) {
      cells.push(current)
      if (cells.some(c => c !== '')) rows.push(cells)
      break
    }

    if (inQuotes) {
      if (ch === '"') {
        if (text[i + 1] === '"') { current += '"'; i++ }
        else inQuotes = false
      } else {
        current += ch
      }
    } else {
      if (ch === '"') {
        inQuotes = true
      } else if (ch === sep) {
        cells.push(current)
        current = ''
      } else if (ch === '\r') {
        // ignoré (partie de \r\n)
      } else if (ch === '\n') {
        cells.push(current)
        current = ''
        if (cells.some(c => c !== '')) rows.push(cells)
        cells = []
      } else {
        current += ch
      }
    }
  }

  return rows
}

/**
 * Parse un fichier CSV complet et retourne headers + lignes de données brutes.
 * @param {string} text - contenu du fichier CSV
 * @returns {{ headers: string[], rows: string[][], separator: string }}
 */
export function parseCsvRaw(text) {
  const firstLineEnd = text.indexOf('\n')
  const firstLine = firstLineEnd >= 0
    ? text.slice(0, firstLineEnd).replace(/\r$/, '')
    : text

  const sep = detectSeparator(firstLine)
  const allRows = parseFullCsv(text, sep)

  if (allRows.length < 1) return { headers: [], rows: [], separator: sep }

  const headers = allRows[0].map(h => h.trim())
  const rows = allRows.slice(1).filter(r => r.length >= 2 || r.some(c => c.trim()))

  return { headers, rows, separator: sep }
}
