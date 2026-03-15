/**
 * Aperçu des données après mapping + transformation.
 * Affiche les 5 premières lignes dans le format final (champs BDD).
 * Validation visuelle : cellules rouges si le type ne correspond pas.
 */
import { useMemo } from 'react'
import { TRANSFORMS } from '../../lib/import-transforms'

const MAX_PREVIEW = 5

/**
 * Valide une valeur selon son type attendu.
 * @returns {'ok' | 'warn' | 'empty'}
 */
function validateCell(value, type) {
  if (value === null || value === undefined || value === '') return 'empty'
  switch (type) {
    case 'date':
      return /^\d{4}-\d{2}-\d{2}$/.test(String(value)) ? 'ok' : 'warn'
    case 'number':
      return typeof value === 'number' || !isNaN(parseFloat(value)) ? 'ok' : 'warn'
    case 'email':
      return String(value).includes('@') ? 'ok' : 'warn'
    case 'rating':
      return (typeof value === 'number' && value >= 1 && value <= 5) ? 'ok' : 'warn'
    default:
      return 'ok'
  }
}

const STATUS_CLS = {
  ok:    '',
  warn:  'bg-amber-50 dark:bg-amber-900/10 text-amber-700 dark:text-amber-400',
  empty: 'text-muted-foreground/30',
}

/**
 * Applique le mapping + transforms sur une ligne CSV brute.
 * @param {string[]} row - cellules brutes
 * @param {string[]} headers - headers CSV
 * @param {Record<string,string>} mapping - { csvHeader: fieldKey }
 * @param {Array} fields - config fields
 * @param {Array} [compositeFields] - fusion multi-colonnes → un champ
 * @returns {Record<string, any>}
 */
export function transformRow(row, headers, mapping, fields, compositeFields) {
  const out = {}
  const fieldMap = Object.fromEntries(fields.map(f => [f.key, f]))

  // Colonnes directement mappées (pas les multiField internes)
  const directlyMapped = new Set(
    Object.values(mapping).filter(k => !k.startsWith('_'))
  )

  headers.forEach((h, i) => {
    const fieldKey = mapping[h]
    if (!fieldKey) return
    const raw = row[i]?.trim() ?? ''
    const fieldDef = fieldMap[fieldKey]
    if (!fieldDef) { out[fieldKey] = raw; return }

    // Appliquer le transform si défini
    if (fieldDef.transform && TRANSFORMS[fieldDef.transform]) {
      const transformed = TRANSFORMS[fieldDef.transform](raw)
      if (fieldDef.multiField && typeof transformed === 'object') {
        // Cas produitRaw : un champ → plusieurs sorties
        // Ne pas écraser les champs déjà mappés directement par l'utilisateur
        Object.entries(transformed).forEach(([k, v]) => {
          if (v !== undefined && v !== null && v !== '' && !directlyMapped.has(k)) {
            out[k] = v
          }
        })
      } else {
        out[fieldKey] = transformed
      }
    } else {
      out[fieldKey] = raw
    }
  })

  // Champs composites : fusionner _buyer_firstname + _buyer_lastname → buyer_name, etc.
  if (compositeFields) {
    for (const { target, sources, join: sep } of compositeFields) {
      // Ne pas écraser un champ déjà mappé directement
      if (directlyMapped.has(target) && out[target]) continue
      const parts = sources.map(s => out[s]).filter(Boolean)
      if (parts.length > 0) out[target] = parts.join(sep ?? ' ')
    }
  }

  // Supprimer les champs internes (commencent par _)
  for (const k of Object.keys(out)) {
    if (k.startsWith('_')) delete out[k]
  }

  return out
}

export function MappedPreview({ headers, rows, mapping, fields, compositeFields }) {
  /** Champs effectivement mappés (dans l'ordre de la config) */
  const mappedFields = useMemo(() => {
    const mappedKeys = new Set(Object.values(mapping))
    // Ajouter les champs générés par multiField transforms
    for (const f of fields) {
      if (f.multiField && mappedKeys.has(f.key)) {
        // produitRaw génère product_name, price_total, quantity, offer_raw
        if (f.transform === 'produitRaw') {
          ;['product_name', 'price_total', 'quantity', 'offer_raw'].forEach(k => mappedKeys.add(k))
          mappedKeys.delete(f.key) // supprimer le champ interne
        }
      }
    }
    // Supprimer les champs internes
    for (const k of [...mappedKeys]) {
      if (k.startsWith('_')) mappedKeys.delete(k)
    }
    return fields
      .filter(f => mappedKeys.has(f.key) && !f.key.startsWith('_'))
      .concat(
        // Ajouter les champs générés dynamiquement qui ne sont pas dans la config
        [...mappedKeys]
          .filter(k => !fields.some(f => f.key === k))
          .map(k => ({ key: k, label: k, type: 'string', required: false }))
      )
  }, [mapping, fields])

  /** Lignes transformées */
  const previewRows = useMemo(() => {
    return rows.slice(0, MAX_PREVIEW).map(row => transformRow(row, headers, mapping, fields, compositeFields))
  }, [rows, headers, mapping, fields])

  if (mappedFields.length === 0) return null

  return (
    <div className="space-y-2">
      <p className="text-xs font-medium text-muted-foreground">
        Aperçu des données transformées
      </p>
      <div className="overflow-x-auto rounded-lg border">
        <table className="w-full text-xs">
          <thead>
            <tr className="border-b bg-muted/50">
              {mappedFields.map(f => (
                <th key={f.key} className="px-2.5 py-1.5 text-left font-medium text-muted-foreground whitespace-nowrap">
                  {f.label}
                  {f.required && <span className="text-destructive ml-0.5">*</span>}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {previewRows.map((row, ri) => (
              <tr key={ri} className="border-b last:border-0">
                {mappedFields.map(f => {
                  const val = row[f.key]
                  const status = validateCell(val, f.type)
                  return (
                    <td key={f.key} className={`px-2.5 py-1.5 max-w-[200px] truncate whitespace-nowrap ${STATUS_CLS[status]}`}>
                      {val !== null && val !== undefined && val !== '' ? String(val) : <span className="text-muted-foreground/30">—</span>}
                    </td>
                  )
                })}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
