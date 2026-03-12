import { useState, useRef } from 'react'

/**
 * Minimal CsvImporter stub — file upload component for CSV imports.
 */
export default function CsvImporter({ onImport, loading, label = 'Importer un CSV' }) {
  const [file, setFile] = useState(null)
  const inputRef = useRef(null)

  function handleFileChange(e) {
    const f = e.target.files?.[0] ?? null
    setFile(f)
  }

  function handleSubmit(e) {
    e.preventDefault()
    if (file && onImport) {
      onImport(file)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex items-center gap-3">
      <input
        ref={inputRef}
        type="file"
        accept=".csv,text/csv"
        onChange={handleFileChange}
        className="text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border file:border-input file:bg-background file:text-xs file:font-medium file:text-foreground hover:file:bg-accent file:cursor-pointer file:transition-colors"
      />
      <button
        type="submit"
        disabled={!file || loading}
        className="px-3 py-1.5 text-xs font-medium rounded-md border bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none transition-colors"
      >
        {loading ? 'Import...' : label}
      </button>
    </form>
  )
}
