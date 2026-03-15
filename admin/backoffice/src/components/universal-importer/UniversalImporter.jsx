/**
 * Universal Importer — composant unifié pour tous les types d'import CSV.
 * Remplace CsvImporter, CsvImporterStats, ReviewsImporter.
 *
 * Usage :
 *   <UniversalImporter type="reservations" onDone={() => {}} />
 *   <UniversalImporter type="participations" onDone={() => {}} />
 *   <UniversalImporter type="reviews" onDone={() => {}} />
 */
import { useState, useCallback, useMemo } from 'react'
import { api } from '../../lib/api'
import { getImportConfig } from '../../lib/import-configs'
import { parseCsvRaw } from '../../lib/csv-parser'
import { TRANSFORMS } from '../../lib/import-transforms'
import { Btn, Notice } from '../ui'
import { FileUploader } from './FileUploader'
import { CsvPreview } from './CsvPreview'
import { FieldMapper, autoMatch } from './FieldMapper'
import { MappedPreview, transformRow } from './MappedPreview'
import { MappingProfileSelector } from './MappingProfileSelector'
import { ImportProgressModal } from './ImportProgressModal'
import { ImportSuccessModal } from './ImportSuccessModal'

/**
 * @param {Object} props
 * @param {'reservations'|'participations'|'reviews'} props.type - type d'import
 * @param {function} [props.onDone] - callback après import réussi
 */
export default function UniversalImporter({ type, onDone }) {
  const config = useMemo(() => getImportConfig(type), [type])

  // ── État du flux ─────────────────────────────────────────────────────────
  const [step, setStep]             = useState('upload')  // upload | mapping | ready | importing
  const [file, setFile]             = useState(null)
  const [csvData, setCsvData]       = useState(null)      // { headers, rows, separator }
  const [mapping, setMapping]       = useState({})
  const [resetFirst, setResetFirst] = useState(false)
  const [importing, setImporting]   = useState(false)
  const [progress, setProgress]     = useState(null)
  const [result, setResult]         = useState(null)
  const [error, setError]           = useState(null)

  // ── Étape 1 : Upload & Parse ─────────────────────────────────────────────
  const handleFileSelected = useCallback(async (selectedFile) => {
    setFile(selectedFile)
    setError(null)
    setResult(null)

    try {
      const text = await selectedFile.text()
      const parsed = parseCsvRaw(text)

      if (parsed.headers.length === 0 || parsed.rows.length === 0) {
        setError('Aucune donnée trouvée dans le fichier. Vérifiez le format CSV.')
        return
      }

      setCsvData(parsed)

      // Auto-match immédiat
      const auto = autoMatch(parsed.headers, config.fields)
      setMapping(auto)
      setStep('mapping')
    } catch (e) {
      setError(`Erreur de lecture : ${e.message}`)
    }
  }, [config.fields])

  // ── Validation du mapping ────────────────────────────────────────────────
  const missingRequired = useMemo(() => {
    if (!config) return []
    const mappedKeys = new Set(Object.values(mapping))
    return config.fields.filter(f => f.required && !mappedKeys.has(f.key))
  }, [config, mapping])

  const canImport = step === 'mapping' && missingRequired.length === 0 && csvData?.rows.length > 0

  // ── Étape 3 : Import batch ───────────────────────────────────────────────
  const handleImport = useCallback(async () => {
    if (!csvData || !canImport) return

    setImporting(true)
    setError(null)
    setResult(null)
    setStep('importing')

    try {
      // Reset table si demandé
      if (resetFirst && config.resetMethod && api[config.resetMethod]) {
        await api[config.resetMethod]()
      }

      // Transformer toutes les lignes
      const rows = csvData.rows
        .map(row => transformRow(row, csvData.headers, mapping, config.fields, config.compositeFields))
        .filter(row => {
          // Filtrer les lignes sans clé unique
          const uniqueField = config.fields.find(f => f.required)
          if (!uniqueField) return true
          return row[uniqueField.key] && String(row[uniqueField.key]).trim() !== ''
        })

      if (rows.length === 0) {
        setError(`Aucune ligne valide après transformation. Vérifiez que le champ requis est bien mappé.`)
        setStep('mapping')
        setImporting(false)
        return
      }

      const batchSize = config.batchSize
      const totalBatches = Math.ceil(rows.length / batchSize)
      const totals = { inserted: 0, updated: 0, skipped: 0, errors: [] }

      const apiMethod = api[config.apiMethod]
      if (!apiMethod) throw new Error(`Méthode API "${config.apiMethod}" introuvable`)

      for (let i = 0; i < totalBatches; i++) {
        const batch = rows.slice(i * batchSize, (i + 1) * batchSize)

        setProgress({
          current:  i + 1,
          total:    totalBatches,
          rows:     rows.length,
          sent:     Math.min((i + 1) * batchSize, rows.length),
          inserted: totals.inserted,
          updated:  totals.updated,
          errors:   totals.errors,
        })

        const res = await apiMethod(batch)

        totals.inserted += res.inserted ?? 0
        totals.updated  += res.updated  ?? 0
        totals.skipped  += res.skipped  ?? 0
        if (res.errors?.length) totals.errors.push(...res.errors)
      }

      setResult(totals)
      setProgress(null)
      // Reset pour permettre un nouvel import
      setFile(null)
      setCsvData(null)
      setMapping({})
      setStep('upload')
      if (onDone) onDone()
    } catch (e) {
      setError(e.message || "Erreur lors de l'import")
      setStep('mapping')
    } finally {
      setImporting(false)
    }
  }, [csvData, canImport, config, mapping, resetFirst, onDone])

  // ── Reset complet ────────────────────────────────────────────────────────
  const handleReset = useCallback(() => {
    setFile(null)
    setCsvData(null)
    setMapping({})
    setStep('upload')
    setError(null)
    setResult(null)
    setProgress(null)
    setResetFirst(false)
  }, [])

  // ── Pourcentage de progression ───────────────────────────────────────────
  const pct = progress ? Math.round((progress.sent / progress.rows) * 100) : 0

  return (
    <div className="space-y-4">
      {/* Étape 1 : Upload */}
      {step === 'upload' && !result && (
        <FileUploader onFileSelected={handleFileSelected} disabled={importing} />
      )}

      {/* Info fichier */}
      {file && step !== 'upload' && (
        <div className="flex items-center justify-between rounded-lg border bg-muted/30 px-3 py-2">
          <div className="text-xs">
            <span className="font-medium">{file.name}</span>
            <span className="text-muted-foreground ml-2">{(file.size / 1024).toFixed(0)} Ko</span>
            {csvData && (
              <span className="text-muted-foreground ml-2">
                · {csvData.rows.length.toLocaleString('fr-FR')} lignes · séparateur « {csvData.separator === '\t' ? 'TAB' : csvData.separator} »
              </span>
            )}
          </div>
          {!importing && (
            <Btn size="sm" variant="ghost" onClick={handleReset}>Changer de fichier</Btn>
          )}
        </div>
      )}

      {/* Étape 1b : Aperçu CSV brut */}
      {csvData && step === 'mapping' && (
        <CsvPreview headers={csvData.headers} rows={csvData.rows} />
      )}

      {/* Profils de mapping sauvegardés */}
      {csvData && step === 'mapping' && (
        <MappingProfileSelector
          importType={config.id}
          mapping={mapping}
          onLoadProfile={setMapping}
        />
      )}

      {/* Étape 2 : Field Mapping */}
      {csvData && step === 'mapping' && (
        <FieldMapper
          headers={csvData.headers}
          fields={config.fields}
          mapping={mapping}
          onMappingChange={setMapping}
        />
      )}

      {/* Étape 2b : Aperçu données transformées */}
      {csvData && step === 'mapping' && Object.keys(mapping).length > 0 && (
        <MappedPreview
          headers={csvData.headers}
          rows={csvData.rows}
          mapping={mapping}
          fields={config.fields}
          compositeFields={config.compositeFields}
        />
      )}

      {/* Option reset (si le type le permet) */}
      {config.allowReset && step === 'mapping' && (
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
      )}

      {resetFirst && (
        <Notice type="warn">
          Toutes les données existantes seront supprimées avant l'import. Utile pour corriger des données corrompues.
        </Notice>
      )}

      {/* Bouton Import */}
      {step === 'mapping' && (
        <div className="flex items-center gap-3">
          <Btn
            onClick={handleImport}
            disabled={!canImport || importing}
            loading={importing}
          >
            Importer {csvData ? `${csvData.rows.length.toLocaleString('fr-FR')} lignes` : ''}
          </Btn>
          {missingRequired.length > 0 && (
            <p className="text-xs text-destructive">
              Champ{missingRequired.length > 1 ? 's' : ''} requis non mappé{missingRequired.length > 1 ? 's' : ''} :{' '}
              {missingRequired.map(f => f.label).join(', ')}
            </p>
          )}
        </div>
      )}

      {/* Erreur */}
      {error && (
        <Notice type="error">{error}</Notice>
      )}

      {/* Modal Progress */}
      {importing && progress && (
        <ImportProgressModal progress={progress} pct={pct} config={config} />
      )}

      {/* Modal Succès */}
      {result && (
        <ImportSuccessModal result={result} onClose={() => setResult(null)} config={config} />
      )}
    </div>
  )
}
