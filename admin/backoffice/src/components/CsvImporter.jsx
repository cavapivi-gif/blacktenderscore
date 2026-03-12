import { useState, useRef, useCallback } from 'react'
import { api } from '../lib/api'
import { CSV_COLUMN_MAP } from '../lib/constants'

const BATCH_SIZE = 500

// ── Normalize booking_status: French Regiondo values → English canonical ────
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
function normalizeBookingStatus(raw) {
  if (!raw) return raw
  const key = raw.trim().toLowerCase()
  return BOOKING_STATUS_MAP[key] ?? raw
}

// ── Normalize payment_status: extract canonical state ────────────────────────
function normalizePaymentStatus(raw) {
  if (!raw) return raw
  const lower = raw.trim().toLowerCase()
  if (lower.startsWith('payé') || lower.startsWith('paid') || lower === 'completed' || lower === 'succeeded') return 'paid'
  if (lower.includes('non payé') || lower.includes('impayé') || lower === 'unpaid') return 'unpaid'
  if (lower.startsWith('remboursé') || lower.startsWith('refunded')) return 'refunded'
  if (lower.startsWith('en attente') || lower.startsWith('pending') || lower.startsWith('processing')) return 'pending'
  return raw
}

// ── Sanitize channel: strip HTML </br> and booking ref codes ─────────────────
// "GetYourGuide Deutschland GmbH </br>GYGRFQWKLZWK" → "GetYourGuide Deutschland GmbH"
function sanitizeChannel(raw) {
  if (!raw) return raw
  return raw.replace(/<\/?\s*br\s*\/?>.*/si, '').replace(/<[^>]+>/g, '').trim()
}

// ── Parse French appointment date to ISO YYYY-MM-DD ──────────────────────────
// Handles: "01 juin 2026 18:00", "1 juin 2026", "01/06/2026", ISO dates
const FR_MONTHS = {
  janvier:'01', février:'02', fevrier:'02', mars:'03', avril:'04',
  mai:'05', juin:'06', juillet:'07', août:'08', aout:'08',
  septembre:'09', octobre:'10', novembre:'11', décembre:'12', decembre:'12',
}
function parseFrenchDate(raw) {
  if (!raw) return raw
  // Already ISO YYYY-MM-DD
  if (/^\d{4}-\d{2}-\d{2}/.test(raw)) return raw.slice(0, 10)
  // "01 juin 2026 18:00" or "1 juin 2026"
  const m = raw.match(/(\d{1,2})\s+(\S+)\s+(\d{4})/i)
  if (m) {
    const month = FR_MONTHS[m[2].toLowerCase()]
    if (month) return `${m[3]}-${month}-${m[1].padStart(2, '0')}`
  }
  // "01/06/2026"
  const d = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/)
  if (d) return `${d[3]}-${d[2].padStart(2, '0')}-${d[1].padStart(2, '0')}`
  return raw
}

// ── Parse the "Produit" / "_produit_raw" column ─────────────────────────────
// Example input:
//   "Essential Estérel Calanques 1.5-Hour 1 × Essential Calanques Estérel 2026 ,
//    Price per person Essential 2026 Montant total: 55,00 €"
//
// Extracts: product_name, price_total, quantity, offer_raw

function parseProduitRaw(raw) {
  if (!raw || typeof raw !== 'string') return {}
  const trimmed = raw.trim()
  const result = { offer_raw: trimmed }

  // Extract price: "Montant total: 55,00 €" or "Montant total : 123 456,78 €"
  const priceMatch = trimmed.match(/Montant\s+total\s*:\s*([\d\s.,]+)\s*€/i)
  if (priceMatch) {
    // French decimal: "1 234,56" → 1234.56
    let priceStr = priceMatch[1].replace(/\s/g, '').replace(',', '.')
    const parsed = parseFloat(priceStr)
    if (!isNaN(parsed)) result.price_total = parsed
  }

  // Extract quantity: "1 ×" or "2 x" or "3×"
  const qtyMatch = trimmed.match(/(\d+)\s*[×x]/i)
  if (qtyMatch) {
    result.quantity = parseInt(qtyMatch[1], 10)
  }

  // Extract product name: text before the first "N ×" pattern
  const nameMatch = trimmed.match(/^(.+?)\s+\d+\s*[×x]\s/i)
  if (nameMatch) {
    result.product_name = nameMatch[1].replace(/\s+/g, ' ').trim()
  } else {
    // Fallback: text before "Montant total"
    const fallback = trimmed.split(/Montant\s+total/i)[0]
    if (fallback) {
      result.product_name = fallback.replace(/[,\s]+$/, '').replace(/\s+/g, ' ').trim()
    }
  }

  return result
}

// ── Parse CSV text into array of objects ────────────────────────────────────

function parseCsvText(text) {
  const lines = text.split(/\r?\n/).filter(l => l.trim())
  if (lines.length < 2) return []

  // Detect separator: ; or , or \t
  const firstLine = lines[0]
  let sep = ','
  if (firstLine.split(';').length > firstLine.split(',').length) sep = ';'
  if (firstLine.split('\t').length > firstLine.split(sep).length) sep = '\t'

  // Parse header row
  const headers = parseCsvLine(firstLine, sep).map(h => h.trim())

  // Map headers to field names
  const fieldMap = headers.map(h => CSV_COLUMN_MAP[h] || null)

  // Parse data rows
  const rows = []
  for (let i = 1; i < lines.length; i++) {
    const cells = parseCsvLine(lines[i], sep)
    if (cells.length < 2) continue // skip empty lines

    const row = {}
    cells.forEach((val, idx) => {
      const field = fieldMap[idx]
      if (field) row[field] = val.trim()
    })

    // Skip rows without calendar_sold_id
    if (!row.calendar_sold_id) continue

    // Parse _produit_raw → extract product_name, price_total, quantity, offer_raw
    if (row._produit_raw) {
      const parsed = parseProduitRaw(row._produit_raw)
      if (parsed.product_name && !row.product_name) row.product_name = parsed.product_name
      if (parsed.price_total != null && row.price_total == null) row.price_total = parsed.price_total
      if (parsed.quantity != null && !row.quantity) row.quantity = parsed.quantity
      if (parsed.offer_raw) row.offer_raw = parsed.offer_raw
      delete row._produit_raw
    }

    // Normalize/sanitize fields from Regiondo CSV exports
    if (row.appointment_date) row.appointment_date = parseFrenchDate(row.appointment_date)
    if (row.booking_status)   row.booking_status   = normalizeBookingStatus(row.booking_status)
    if (row.payment_status)   row.payment_status   = normalizePaymentStatus(row.payment_status)
    if (row.channel)          row.channel          = sanitizeChannel(row.channel)

    rows.push(row)
  }

  return rows
}

// RFC 4180 CSV line parser (handles quoted fields with embedded separators/newlines)
function parseCsvLine(line, sep) {
  const cells = []
  let current = ''
  let inQuotes = false

  for (let i = 0; i < line.length; i++) {
    const ch = line[i]
    if (inQuotes) {
      if (ch === '"') {
        if (line[i + 1] === '"') {
          current += '"'
          i++ // skip escaped quote
        } else {
          inQuotes = false
        }
      } else {
        current += ch
      }
    } else {
      if (ch === '"') {
        inQuotes = true
      } else if (ch === sep) {
        cells.push(current)
        current = ''
      } else {
        current += ch
      }
    }
  }
  cells.push(current)
  return cells
}

// ── ImportProgressModal ──────────────────────────────────────────────────────
function ImportProgressModal({ progress, pct }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" />
      <div className="relative bg-card rounded-xl border shadow-2xl w-full max-w-sm mx-4 p-7 space-y-5">

        {/* Titre + spinner */}
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
            <p className="text-xs text-muted-foreground">
              Batch {progress.current} / {progress.total}
            </p>
          </div>
          <span className="ml-auto text-2xl font-bold tabular-nums text-primary">
            {pct}%
          </span>
        </div>

        {/* Barre de progression */}
        <div className="space-y-1.5">
          <div className="h-2.5 rounded-full bg-muted overflow-hidden">
            <div
              className="h-full rounded-full bg-primary transition-all duration-500 ease-out"
              style={{ width: `${pct}%` }}
            />
          </div>
          <div className="flex justify-between text-[11px] text-muted-foreground tabular-nums">
            <span>{progress.sent.toLocaleString('fr-FR')} lignes envoyées</span>
            <span>{progress.rows.toLocaleString('fr-FR')} total</span>
          </div>
        </div>

        {/* Compteurs live */}
        {(progress.inserted > 0 || progress.updated > 0) && (
          <div className="flex gap-3">
            <div className="flex-1 rounded-lg border border-border px-3 py-2 text-center">
              <p className="text-lg font-bold tabular-nums">{progress.inserted.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">insérées</p>
            </div>
            <div className="flex-1 rounded-lg border border-border px-3 py-2 text-center">
              <p className="text-lg font-bold tabular-nums text-muted-foreground">{progress.updated.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">mises à jour</p>
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

        {/* Checkmark animé */}
        <div className="flex justify-center">
          <span className="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 border-2 border-emerald-200">
            <svg
              className="text-emerald-500"
              style={{ animation: 'drawCheck 0.4s ease-out 0.1s both' }}
              width="32" height="32" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"
            >
              <style>{`@keyframes drawCheck{from{stroke-dashoffset:40}to{stroke-dashoffset:0}}`}</style>
              <path d="M20 6 9 17l-5-5" strokeDasharray="40" strokeDashoffset="40"
                style={{ animation: 'drawCheck 0.4s ease-out 0.1s both' }} />
            </svg>
          </span>
        </div>

        <div className="space-y-1">
          <h3 className="text-base font-semibold">Import terminé !</h3>
          <p className="text-sm text-muted-foreground">
            {total.toLocaleString('fr-FR')} réservation{total > 1 ? 's' : ''} traitée{total > 1 ? 's' : ''}
          </p>
        </div>

        {/* Stats */}
        <div className="flex gap-3">
          <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
            <p className="text-xl font-bold tabular-nums">{result.inserted.toLocaleString('fr-FR')}</p>
            <p className="text-[11px] text-muted-foreground">nouvelles</p>
          </div>
          <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
            <p className="text-xl font-bold tabular-nums text-muted-foreground">{result.updated.toLocaleString('fr-FR')}</p>
            <p className="text-[11px] text-muted-foreground">mises à jour</p>
          </div>
          {result.skipped > 0 && (
            <div className="flex-1 rounded-lg border border-border px-3 py-2.5 text-center">
              <p className="text-xl font-bold tabular-nums text-muted-foreground">{result.skipped.toLocaleString('fr-FR')}</p>
              <p className="text-[11px] text-muted-foreground">ignorées</p>
            </div>
          )}
        </div>

        {/* Erreurs (collapsible) */}
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

        <button
          type="button"
          onClick={onClose}
          className="w-full px-4 py-2 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors"
        >
          Fermer
        </button>
      </div>
    </div>
  )
}

// ── Component ───────────────────────────────────────────────────────────────

export default function CsvImporter({ onDone }) {
  const [file, setFile] = useState(null)
  const [importing, setImporting] = useState(false)
  const [progress, setProgress] = useState(null)
  const [error, setError] = useState(null)
  const [result, setResult] = useState(null)
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
        setError('Aucune ligne valide trouvée. Vérifiez que le CSV contient un en-tête avec "calendar_sold_id".')
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

        const res = await api.importReservationsCsv(batch)

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
