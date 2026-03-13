import { useState, useRef, useCallback } from 'react'
import { api } from '../lib/api'

const BATCH_SIZE = 500

// ── Mapping colonnes CSV Regiondo "Avis" → champs internes ───────────────────
// Colonnes Regiondo (après trim des espaces) :
//   "Évaluation"              → rating  (numérique 1-5, E majuscule + accent)
//   "évaluation"              → review_body (corps texte, e minuscule + accent)
//   "Date d'évaluation"       → review_date (avec espace final dans certains exports)
//   "Résumé de l'évaluation"  → review_title (idem)
const REVIEWS_COLUMN_MAP = {
  'Produit':                   'product_name',
  'Catégorie':                 'category',
  'Categorie':                 'category',
  'Guide':                     'guide',
  "N° de commande":            'order_number',
  'Date de réservation':       'booking_date',
  'Date de reservation':       'booking_date',
  "Date de l'évènement":       'event_date',
  "Date de l'evenement":       'event_date',
  "Date d'évaluation":         'review_date',
  "Date d'evaluation":         'review_date',
  'Nom du client':             'customer_name',
  'Customer email':            'customer_email',
  'Customer phone':            'customer_phone',
  // Note: "Évaluation" (É majuscule) = note numérique
  '\u00c9valuation':           'rating',        // Évaluation
  // Note: "évaluation" (é minuscule) = texte de l'avis
  '\u00e9valuation':           'review_body',   // évaluation
  "Résumé de l'évaluation":   'review_title',
  "Resume de l'evaluation":    'review_title',
  'Statut':                    'review_status',
  'Employee Name':             'employee_name',
  'Response':                  'response',
}

// ── Helpers date ──────────────────────────────────────────────────────────────
const FR_MONTHS = {
  janvier:'01', février:'02', fevrier:'02', mars:'03', avril:'04',
  mai:'05', juin:'06', juillet:'07', août:'08', aout:'08',
  septembre:'09', octobre:'10', novembre:'11', décembre:'12', decembre:'12',
}
function parseFrenchDate(raw) {
  if (!raw) return raw
  if (/^\d{4}-\d{2}-\d{2}/.test(raw)) return raw.slice(0, 10)
  const m = raw.match(/(\d{1,2})\s+(\S+)\s+(\d{4})/i)
  if (m) {
    const month = FR_MONTHS[m[2].toLowerCase()]
    if (month) return `${m[3]}-${month}-${m[1].padStart(2, '0')}`
  }
  const d = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/)
  if (d) return `${d[3]}-${d[2].padStart(2, '0')}-${d[1].padStart(2, '0')}`
  return raw
}

// ── Parseur CSV RFC-4180 complet (gère les sauts de ligne dans les champs quotés) ──
/**
 * Parse le texte CSV caractère par caractère.
 * Les sauts de ligne DANS les champs entre guillemets sont correctement ignorés.
 * Retourne un tableau de tableaux de chaînes (lignes × cellules).
 */
function parseFullCsv(text, sep) {
  const rows = []
  let cells = []
  let current = ''
  let inQuotes = false

  for (let i = 0; i <= text.length; i++) {
    const ch = i < text.length ? text[i] : null

    if (ch === null) {
      // Fin du texte — flush la dernière cellule
      cells.push(current)
      if (cells.some(c => c !== '')) rows.push(cells)
      break
    }

    if (inQuotes) {
      if (ch === '"') {
        if (text[i + 1] === '"') { current += '"'; i++ } // guillemet échappé
        else inQuotes = false
      } else {
        current += ch // \n et \r inclus dans le champ
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

function parseReviewsCsv(text) {
  // Détection du séparateur sur la première ligne
  const firstLineEnd = text.indexOf('\n')
  const firstLine = firstLineEnd >= 0 ? text.slice(0, firstLineEnd).replace(/\r$/, '') : text
  let sep = ','
  if (firstLine.split(';').length > firstLine.split(',').length) sep = ';'
  if (firstLine.split('\t').length > firstLine.split(sep).length) sep = '\t'

  const allRows = parseFullCsv(text, sep)
  if (allRows.length < 2) return []

  // En-têtes : trim + gestion doublons Évaluation/évaluation
  const rawHeaders = allRows[0].map(h => h.trim())
  const seenEval = { upper: false, lower: false }
  const fieldMap = rawHeaders.map(h => {
    const hLower = h.toLowerCase()
    // Évaluation (É majuscule, U+00C9) = note numérique
    if (h.charCodeAt(0) === 0xc9 && hLower === '\u00e9valuation') {
      if (!seenEval.upper) { seenEval.upper = true; return 'rating' }
      return null
    }
    // évaluation (é minuscule, U+00E9) = texte de l'avis
    if (h.charCodeAt(0) === 0xe9 && hLower === '\u00e9valuation') {
      if (!seenEval.lower) { seenEval.lower = true; return 'review_body' }
      return null
    }
    return REVIEWS_COLUMN_MAP[h] || null
  })

  const rows = []
  for (let i = 1; i < allRows.length; i++) {
    const cells = allRows[i]
    if (cells.length < 2) continue

    const row = {}
    cells.forEach((val, idx) => {
      const field = fieldMap[idx]
      if (field) row[field] = val.trim()
    })

    if (!row.order_number) continue

    // Normalisation des dates
    if (row.booking_date) row.booking_date = parseFrenchDate(row.booking_date)
    if (row.event_date)   row.event_date   = parseFrenchDate(row.event_date)
    if (row.review_date)  row.review_date  = parseFrenchDate(row.review_date)

    // Rating : convertir en entier
    if (row.rating !== undefined) row.rating = parseInt(row.rating, 10) || null

    rows.push(row)
  }

  return rows
}

// ── ImportProgressModal ───────────────────────────────────────────────────────
function ImportProgressModal({ progress, pct }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" />
      <div className="relative bg-card rounded-xl border shadow-2xl w-full max-w-sm mx-4 p-7 space-y-5">
        <div className="flex items-center gap-3">
          <span className="relative flex h-8 w-8 shrink-0">
            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-30" />
            <span className="relative inline-flex rounded-full h-8 w-8 bg-primary/10 border border-primary/20 items-center justify-center">
              <svg className="animate-spin text-primary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
              </svg>
            </span>
          </span>
          <div>
            <p className="text-sm font-semibold">Import en cours…</p>
            <p className="text-xs text-muted-foreground">Batch {progress.current} / {progress.total}</p>
          </div>
          <span className="ml-auto text-2xl font-bold tabular-nums text-primary">{pct}%</span>
        </div>

        <div className="space-y-1.5">
          <div className="h-2.5 rounded-full bg-muted overflow-hidden">
            <div className="h-full rounded-full bg-primary transition-all duration-500 ease-out" style={{ width: `${pct}%` }} />
          </div>
          <div className="flex justify-between text-[11px] text-muted-foreground tabular-nums">
            <span>{progress.sent.toLocaleString('fr-FR')} avis envoyés</span>
            <span>{progress.rows.toLocaleString('fr-FR')} total</span>
          </div>
        </div>

        {(progress.inserted > 0 || progress.updated > 0) && (
          <div className="flex gap-3">
            <div className="flex-1 rounded-lg border border-border px-3 py-2 text-center">
              <p className="text-lg font-bold tabular-nums">{progress.inserted.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">insérés</p>
            </div>
            <div className="flex-1 rounded-lg border border-border px-3 py-2 text-center">
              <p className="text-lg font-bold tabular-nums text-muted-foreground">{progress.updated.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">mis à jour</p>
            </div>
            {progress.errors?.length > 0 && (
              <div className="flex-1 rounded-lg border border-destructive/30 px-3 py-2 text-center">
                <p className="text-lg font-bold tabular-nums text-destructive">{progress.errors.length}</p>
                <p className="text-[11px] text-destructive/70">erreurs</p>
              </div>
            )}
          </div>
        )}

        <p className="text-[11px] text-muted-foreground text-center">Ne fermez pas cette fenêtre</p>
      </div>
    </div>
  )
}

// ── ImportSuccessModal ────────────────────────────────────────────────────────
function ImportSuccessModal({ result, onClose }) {
  const total = result.inserted + result.updated
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={onClose} />
      <div className="relative bg-card rounded-xl border shadow-2xl w-full max-w-sm mx-4 p-7 space-y-5 text-center">
        <div className="flex justify-center">
          <span className="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 border-2 border-emerald-200">
            <svg className="text-emerald-500" style={{ animation: 'drawCheck 0.4s ease-out 0.1s both' }}
              width="32" height="32" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <style>{`@keyframes drawCheck{from{stroke-dashoffset:40}to{stroke-dashoffset:0}}`}</style>
              <path d="M20 6 9 17l-5-5" strokeDasharray="40" strokeDashoffset="40"
                style={{ animation: 'drawCheck 0.4s ease-out 0.1s both' }} />
            </svg>
          </span>
        </div>

        <div className="space-y-1">
          <h3 className="text-base font-semibold">Import terminé !</h3>
          <p className="text-sm text-muted-foreground">
            {total.toLocaleString('fr-FR')} avis traité{total > 1 ? 's' : ''}
          </p>
        </div>

        <div className="flex gap-3">
          <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
            <p className="text-xl font-bold tabular-nums">{result.inserted.toLocaleString('fr-FR')}</p>
            <p className="text-[11px] text-muted-foreground">nouveaux</p>
          </div>
          <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
            <p className="text-xl font-bold tabular-nums text-muted-foreground">{result.updated.toLocaleString('fr-FR')}</p>
            <p className="text-[11px] text-muted-foreground">mis à jour</p>
          </div>
          {result.skipped > 0 && (
            <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
              <p className="text-xl font-bold tabular-nums text-muted-foreground">{result.skipped.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">ignorés</p>
            </div>
          )}
        </div>

        {result.errors.length > 0 && (
          <details className="text-left rounded-lg border border-destructive/20 bg-destructive/5 px-3 py-2">
            <summary className="text-xs text-destructive cursor-pointer font-medium">
              {result.errors.length} erreur{result.errors.length > 1 ? 's' : ''}
            </summary>
            <ul className="mt-2 space-y-0.5 text-[11px] text-destructive/80 max-h-32 overflow-y-auto">
              {result.errors.slice(0, 20).map((e, i) => <li key={i}>{e}</li>)}
              {result.errors.length > 20 && <li>… et {result.errors.length - 20} de plus</li>}
            </ul>
          </details>
        )}

        <button type="button" onClick={onClose}
          className="w-full px-4 py-2 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors">
          Fermer
        </button>
      </div>
    </div>
  )
}

// ── Composant principal ───────────────────────────────────────────────────────
export default function ReviewsImporter({ onDone }) {
  const [file, setFile]               = useState(null)
  const [importing, setImporting]     = useState(false)
  const [progress, setProgress]       = useState(null)
  const [error, setError]             = useState(null)
  const [result, setResult]           = useState(null)
  const [resetFirst, setResetFirst]   = useState(false)
  const inputRef = useRef(null)

  const handleImport = useCallback(async () => {
    if (!file) return

    setImporting(true)
    setError(null)
    setResult(null)
    setProgress(null)

    try {
      // Vider la table avant import si demandé (corrige les données corrompues)
      if (resetFirst) {
        await api.resetAvis()
      }

      const text = await file.text()
      const rows = parseReviewsCsv(text)

      if (rows.length === 0) {
        setError('Aucun avis valide trouvé. Vérifiez que le CSV contient "N° de commande" en en-tête.')
        setImporting(false)
        return
      }

      const totals = { inserted: 0, updated: 0, skipped: 0, errors: [] }
      const totalBatches = Math.ceil(rows.length / BATCH_SIZE)

      for (let i = 0; i < totalBatches; i++) {
        const batch = rows.slice(i * BATCH_SIZE, (i + 1) * BATCH_SIZE)

        setProgress({
          current: i + 1,
          total: totalBatches,
          rows: rows.length,
          sent: Math.min((i + 1) * BATCH_SIZE, rows.length),
          inserted: totals.inserted,
          updated:  totals.updated,
          errors:   totals.errors,
        })

        const res = await api.importAvisCsv(batch)

        totals.inserted += res.inserted ?? 0
        totals.updated  += res.updated  ?? 0
        totals.skipped  += res.skipped  ?? 0
        if (res.errors?.length) totals.errors.push(...res.errors)
      }

      setResult(totals)
      setProgress(null)
      setFile(null)
      if (inputRef.current) inputRef.current.value = ''
      if (onDone) onDone()
    } catch (e) {
      setError(e.message || "Erreur lors de l'import")
    } finally {
      setImporting(false)
    }
  }, [file, onDone])

  const pct = progress ? Math.round((progress.sent / progress.rows) * 100) : 0

  return (
    <>
      <div className="space-y-3">
        <div className="flex items-center gap-3">
          <input
            ref={inputRef}
            type="file"
            accept=".csv,text/csv"
            onChange={e => { setFile(e.target.files?.[0] ?? null); setResult(null); setError(null) }}
            disabled={importing}
            className="text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border file:border-input file:bg-background file:text-xs file:font-medium file:text-foreground hover:file:bg-accent file:cursor-pointer file:transition-colors"
          />
          <button
            type="button"
            onClick={handleImport}
            disabled={!file || importing}
            className="px-3 py-1.5 text-xs font-medium rounded-md border bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none transition-colors"
          >
            Importer
          </button>
        </div>

        <label className="flex items-center gap-2 cursor-pointer select-none w-fit">
          <input
            type="checkbox"
            checked={resetFirst}
            onChange={e => setResetFirst(e.target.checked)}
            disabled={importing}
            className="rounded border-input"
          />
          <span className="text-xs text-muted-foreground">Vider la table avant d'importer</span>
        </label>

        {resetFirst && (
          <div className="rounded-md border border-amber-300/50 bg-amber-50/50 dark:bg-amber-900/10 dark:border-amber-500/30 p-3 text-xs text-amber-700 dark:text-amber-400">
            ⚠️ Toutes les données existantes seront supprimées avant l'import. Utile pour corriger des données corrompues.
          </div>
        )}

        {file && !importing && (
          <p className="text-xs text-muted-foreground">{file.name} · {(file.size / 1024).toFixed(0)} Ko</p>
        )}

        {error && (
          <div className="rounded-md border border-destructive/30 bg-destructive/5 p-3 text-xs text-destructive">
            {error}
          </div>
        )}
      </div>

      {importing && progress && <ImportProgressModal progress={progress} pct={pct} />}
      {result && <ImportSuccessModal result={result} onClose={() => setResult(null)} />}
    </>
  )
}
