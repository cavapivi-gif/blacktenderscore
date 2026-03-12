import { useState, useRef, useCallback } from 'react'
import { api } from '../lib/api'

const BATCH_SIZE = 500

// ── Mapping colonnes CSV → champs internes ────────────────────────────────────
// Supporte les exports en français et en anglais.
const STATS_COLUMN_MAP = {
  // Français
  'Date de la participation': 'participation_date',
  'Nom du produit':           'product_name',
  'Prénom du client':         'buyer_firstname',
  'Nom du client':            'buyer_lastname',
  'Adresse E-mail du client': 'buyer_email',
  'Prix (net)':               'price_net',
  'Prix (brut)':              'price_gross',
  'Téléphone':                'phone',
  // Anglais (fallbacks)
  'Participation Date':       'participation_date',
  'Product Name':             'product_name',
  'Customer First Name':      'buyer_firstname',
  'Customer Last Name':       'buyer_lastname',
  'Customer Email':           'buyer_email',
  'Net Price':                'price_net',
  'Gross Price':              'price_gross',
  'Phone':                    'phone',
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const FR_MONTHS = {
  janvier:'01', février:'02', fevrier:'02', mars:'03', avril:'04',
  mai:'05', juin:'06', juillet:'07', août:'08', aout:'08',
  septembre:'09', octobre:'10', novembre:'11', décembre:'12', decembre:'12',
}

/** Convertit une date française ou ISO en YYYY-MM-DD. Retourne '' si vide. */
function parseFrenchDate(raw) {
  if (!raw || !raw.trim()) return ''
  const s = raw.trim()
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

/** Parse un prix : "109,09" ou "109.09" → 109.09 ; null si invalide. */
function parsePrice(raw) {
  if (raw === null || raw === undefined || raw === '') return null
  const cleaned = String(raw).trim().replace(/\s/g, '').replace(',', '.')
  const val = parseFloat(cleaned)
  return isNaN(val) ? null : val
}

// ── Parser CSV ────────────────────────────────────────────────────────────────

/** Découpe une ligne CSV en respectant les guillemets (RFC 4180). */
function parseCsvLine(line, sep) {
  const cells = []
  let current = ''
  let inQuotes = false
  for (let i = 0; i < line.length; i++) {
    const ch = line[i]
    if (inQuotes) {
      if (ch === '"') {
        if (line[i + 1] === '"') { current += '"'; i++ }
        else inQuotes = false
      } else {
        current += ch
      }
    } else {
      if (ch === '"') { inQuotes = true }
      else if (ch === sep) { cells.push(current); current = '' }
      else { current += ch }
    }
  }
  cells.push(current)
  return cells
}

/**
 * Parse le texte CSV et retourne un tableau de lignes normalisées.
 * Filtre les lignes sans product_name.
 */
function parseCsvText(text) {
  const lines = text.split(/\r?\n/).filter(l => l.trim())
  if (lines.length < 2) return []

  const firstLine = lines[0]
  let sep = ','
  if (firstLine.split(';').length > firstLine.split(',').length) sep = ';'
  if (firstLine.split('\t').length > firstLine.split(sep).length) sep = '\t'

  const headers  = parseCsvLine(firstLine, sep).map(h => h.trim())
  const fieldMap = headers.map(h => STATS_COLUMN_MAP[h] || null)

  const rows = []
  for (let i = 1; i < lines.length; i++) {
    const cells = parseCsvLine(lines[i], sep)
    if (cells.length < 2) continue

    const row = {}
    cells.forEach((val, idx) => {
      const field = fieldMap[idx]
      if (field) row[field] = val.trim()
    })

    // Filtre : le produit est obligatoire (identifiant minimal)
    if (!row.product_name) continue

    if (row.participation_date) row.participation_date = parseFrenchDate(row.participation_date)
    if (row.price_net   !== undefined) row.price_net   = parsePrice(row.price_net)
    if (row.price_gross !== undefined) row.price_gross = parsePrice(row.price_gross)

    rows.push(row)
  }

  return rows
}

// ── Modales (reprises de CsvImporter) ────────────────────────────────────────

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
            <span>{progress.sent.toLocaleString('fr-FR')} lignes envoyées</span>
            <span>{progress.rows.toLocaleString('fr-FR')} total</span>
          </div>
        </div>
        {(progress.inserted > 0) && (
          <div className="flex gap-3">
            <div className="flex-1 rounded-lg border border-border px-3 py-2 text-center">
              <p className="text-lg font-bold tabular-nums">{progress.inserted.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">insérées</p>
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

function ImportSuccessModal({ result, onClose }) {
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
            {result.inserted.toLocaleString('fr-FR')} participation{result.inserted > 1 ? 's' : ''} importée{result.inserted > 1 ? 's' : ''}
            {result.skipped > 0 && `, ${result.skipped.toLocaleString('fr-FR')} ignorée${result.skipped > 1 ? 's' : ''} (déjà présentes)`}
          </p>
        </div>
        <div className="flex gap-3">
          <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
            <p className="text-xl font-bold tabular-nums">{result.inserted.toLocaleString('fr-FR')}</p>
            <p className="text-[11px] text-muted-foreground">nouvelles</p>
          </div>
          {result.skipped > 0 && (
            <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
              <p className="text-xl font-bold tabular-nums text-muted-foreground">{result.skipped.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">ignorées</p>
            </div>
          )}
        </div>
        {result.errors?.length > 0 && (
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

export default function CsvImporterStats({ onDone }) {
  const [file,      setFile]      = useState(null)
  const [importing, setImporting] = useState(false)
  const [progress,  setProgress]  = useState(null)
  const [error,     setError]     = useState(null)
  const [result,    setResult]    = useState(null)
  const inputRef = useRef(null)

  const handleImport = useCallback(async () => {
    if (!file) return

    setImporting(true)
    setError(null)
    setResult(null)
    setProgress(null)

    try {
      const text = await file.text()
      const rows = parseCsvText(text)

      if (rows.length === 0) {
        setError('Aucune ligne valide trouvée. Vérifiez que le CSV contient "Nom du produit" en en-tête.')
        setImporting(false)
        return
      }

      const totals = { inserted: 0, skipped: 0, errors: [] }
      const totalBatches = Math.ceil(rows.length / BATCH_SIZE)

      for (let i = 0; i < totalBatches; i++) {
        const batch = rows.slice(i * BATCH_SIZE, (i + 1) * BATCH_SIZE)

        setProgress({
          current:  i + 1,
          total:    totalBatches,
          rows:     rows.length,
          sent:     Math.min((i + 1) * BATCH_SIZE, rows.length),
          inserted: totals.inserted,
          errors:   totals.errors,
        })

        const res = await api.importParticipationsCsv(batch)

        totals.inserted += res.inserted ?? 0
        totals.skipped  += res.skipped  ?? 0
        if (res.errors?.length) totals.errors.push(...res.errors)
      }

      setResult(totals)
      setProgress(null)
      setFile(null)
      if (inputRef.current) inputRef.current.value = ''
      if (onDone) onDone()
    } catch (e) {
      setError(e.message || 'Erreur lors de l\'import')
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

        {file && !importing && (
          <p className="text-xs text-muted-foreground">
            {file.name} · {(file.size / 1024).toFixed(0)} Ko
          </p>
        )}

        {error && (
          <div className="rounded-md border border-destructive/30 bg-destructive/5 p-3 text-xs text-destructive">
            {error}
          </div>
        )}
      </div>

      {importing && progress && (
        <ImportProgressModal progress={progress} pct={pct} />
      )}

      {result && (
        <ImportSuccessModal result={result} onClose={() => setResult(null)} />
      )}
    </>
  )
}
