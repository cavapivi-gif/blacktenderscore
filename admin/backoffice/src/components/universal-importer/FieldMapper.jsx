/**
 * Interface de mapping : colonnes CSV (source) → champs BDD (destination).
 * Chaque ligne = un header CSV avec un select dropdown des champs disponibles.
 * Auto-match intelligent par aliases (insensible à la casse + trim).
 */
import { useCallback, useMemo } from 'react'
import { Btn, Badge } from '../ui'

/**
 * Calcule le mapping automatique en comparant les headers CSV aux aliases des champs.
 * @param {string[]} headers - noms de colonnes CSV
 * @param {Array} fields - config fields avec aliases
 * @returns {Record<string, string>} mapping { csvHeader: fieldKey }
 */
export function autoMatch(headers, fields) {
  const mapping = {}
  const usedFields = new Set()

  // Gestion spéciale Évaluation/évaluation (casse du premier caractère)
  const evalUpperField = fields.find(f => f.key === 'rating')
  const evalLowerField = fields.find(f => f.key === 'review_body')

  for (const h of headers) {
    const trimmed = h.trim()
    if (!trimmed) continue

    // Cas spécial : doublons Évaluation/évaluation
    if (evalUpperField && trimmed.charCodeAt(0) === 0xC9 && trimmed.toLowerCase() === '\u00e9valuation' && !usedFields.has('rating')) {
      mapping[h] = 'rating'
      usedFields.add('rating')
      continue
    }
    if (evalLowerField && trimmed.charCodeAt(0) === 0xE9 && trimmed.toLowerCase() === '\u00e9valuation' && !usedFields.has('review_body')) {
      mapping[h] = 'review_body'
      usedFields.add('review_body')
      continue
    }

    // Match standard par alias
    const lower = trimmed.toLowerCase()
    for (const field of fields) {
      if (usedFields.has(field.key)) continue
      const matched = field.aliases.some(a => a.toLowerCase() === lower)
      if (matched) {
        mapping[h] = field.key
        usedFields.add(field.key)
        break
      }
    }
  }

  return mapping
}

export function FieldMapper({ headers, fields, mapping, onMappingChange }) {

  /** Champs déjà utilisés dans le mapping (pour éviter les doublons) */
  const usedFields = useMemo(() => new Set(Object.values(mapping)), [mapping])

  /** Champs requis non encore mappés */
  const missingRequired = useMemo(() => {
    return fields.filter(f => f.required && !usedFields.has(f.key))
  }, [fields, usedFields])

  const handleChange = useCallback((csvHeader, fieldKey) => {
    const next = { ...mapping }
    if (fieldKey === '') {
      delete next[csvHeader]
    } else {
      next[csvHeader] = fieldKey
    }
    onMappingChange(next)
  }, [mapping, onMappingChange])

  const handleAutoMatch = useCallback(() => {
    const auto = autoMatch(headers, fields)
    onMappingChange(auto)
  }, [headers, fields, onMappingChange])

  const handleClear = useCallback(() => {
    onMappingChange({})
  }, [onMappingChange])

  const mappedHeaders = headers.filter(h => mapping[h])
  const unmappedHeaders = headers.filter(h => !mapping[h])

  return (
    <div className="space-y-3">
      {/* Toolbar */}
      <div className="flex items-center justify-between gap-2 flex-wrap">
        <div className="flex items-center gap-2">
          <Btn size="sm" onClick={handleAutoMatch}>
            Auto-mapping
          </Btn>
          <Btn size="sm" variant="ghost" onClick={handleClear}>
            Effacer tout
          </Btn>
        </div>
        <div className="flex items-center gap-2 text-xs text-muted-foreground">
          <Badge variant={mappedHeaders.length > 0 ? 'success' : 'default'}>
            {mappedHeaders.length} / {headers.length} mappés
          </Badge>
          {missingRequired.length > 0 && (
            <Badge variant="error">
              {missingRequired.length} requis manquant{missingRequired.length > 1 ? 's' : ''}
            </Badge>
          )}
        </div>
      </div>

      {/* Lignes de mapping — toutes les colonnes CSV, flat */}
      <div className="rounded-lg border divide-y">
        {headers.map(h => (
          <MappingRow
            key={h}
            csvHeader={h}
            fields={fields}
            selectedField={mapping[h] ?? ''}
            usedFields={usedFields}
            onChange={val => handleChange(h, val)}
            isMapped={!!mapping[h]}
          />
        ))}
      </div>

      {unmappedHeaders.length > 0 && (
        <p className="text-[11px] text-muted-foreground">
          {unmappedHeaders.length} colonne{unmappedHeaders.length > 1 ? 's' : ''} non mappée{unmappedHeaders.length > 1 ? 's' : ''} — à mapper manuellement ou seront ignorées à l'import
        </p>
      )}
    </div>
  )
}

/** Ligne individuelle du mapper : colonne CSV → select champ BDD */
function MappingRow({ csvHeader, fields, selectedField, usedFields, onChange, isMapped }) {
  return (
    <div className={['flex items-center gap-3 px-3 py-2', !isMapped ? 'bg-muted/20' : ''].join(' ')}>
      {/* Source (CSV) */}
      <div className="flex-1 min-w-0">
        <p className={['text-xs font-mono truncate', !isMapped ? 'text-muted-foreground' : ''].join(' ')} title={csvHeader}>
          {csvHeader}
        </p>
      </div>

      {/* Arrow */}
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="text-muted-foreground/50 shrink-0">
        <path d="M5 12h14M12 5l7 7-7 7" strokeLinecap="round" strokeLinejoin="round"/>
      </svg>

      {/* Destination (champ BDD) */}
      <div className="flex-1 min-w-0">
        <select
          value={selectedField}
          onChange={e => onChange(e.target.value)}
          className={[
            'w-full text-xs rounded-md border px-2 py-1.5 bg-background',
            selectedField ? 'text-foreground border-primary/30' : 'text-muted-foreground border-input',
          ].join(' ')}
        >
          <option value="">— ignorer —</option>
          {fields.map(f => (
            <option key={f.key} value={f.key} disabled={usedFields.has(f.key) && selectedField !== f.key}>
              {f.label}{f.required ? ' *' : ''}{usedFields.has(f.key) && selectedField !== f.key ? ' (utilisé)' : ''}
            </option>
          ))}
        </select>
      </div>
    </div>
  )
}
