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

// ── Component ───────────────────────────────────────────────────────────────

export default function CsvImporter({ onDone }) {
  const [file, setFile] = useState(null)
  const [importing, setImporting] = useState(false)
  const [progress, setProgress] = useState(null) // { current, total, inserted, updated, errors }
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
          ...totals,
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
          {importing ? 'Import en cours...' : 'Importer'}
        </button>
      </div>

      {/* Progress bar */}
      {progress && (
        <div className="space-y-1">
          <div className="flex items-center justify-between text-xs text-muted-foreground">
            <span>Batch {progress.current}/{progress.total}</span>
            <span>{progress.sent.toLocaleString('fr-FR')} / {progress.rows.toLocaleString('fr-FR')} lignes</span>
          </div>
          <div className="h-1.5 rounded-full bg-muted overflow-hidden">
            <div
              className="h-full rounded-full bg-primary transition-all duration-300"
              style={{ width: `${pct}%` }}
            />
          </div>
        </div>
      )}

      {/* Result summary */}
      {result && (
        <div className="rounded-md border bg-muted/30 p-3 text-xs space-y-1">
          <p className="font-medium text-foreground">Import terminé</p>
          <div className="flex gap-4 text-muted-foreground">
            <span>Insérés : <strong className="text-foreground">{result.inserted.toLocaleString('fr-FR')}</strong></span>
            <span>Mis à jour : <strong className="text-foreground">{result.updated.toLocaleString('fr-FR')}</strong></span>
            {result.skipped > 0 && <span>Ignorés : {result.skipped.toLocaleString('fr-FR')}</span>}
          </div>
          {result.errors.length > 0 && (
            <details className="mt-1">
              <summary className="text-destructive cursor-pointer">{result.errors.length} erreur(s)</summary>
              <ul className="mt-1 space-y-0.5 text-destructive/80">
                {result.errors.slice(0, 20).map((e, i) => <li key={i}>{e}</li>)}
                {result.errors.length > 20 && <li>... et {result.errors.length - 20} de plus</li>}
              </ul>
            </details>
          )}
        </div>
      )}

      {/* Error */}
      {error && (
        <div className="rounded-md border border-destructive/30 bg-destructive/5 p-3 text-xs text-destructive">
          {error}
        </div>
      )}
    </div>
  )
}
