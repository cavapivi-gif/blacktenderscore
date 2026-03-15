/**
 * Apercu des N premières lignes du CSV parsé (headers + données brutes).
 */

const MAX_PREVIEW = 5

export function CsvPreview({ headers, rows }) {
  if (!headers?.length) return null

  const previewRows = rows.slice(0, MAX_PREVIEW)

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between">
        <p className="text-xs font-medium text-muted-foreground">
          Aperçu — {rows.length.toLocaleString('fr-FR')} lignes détectées
        </p>
        {rows.length > MAX_PREVIEW && (
          <p className="text-[11px] text-muted-foreground">
            (affichage des {MAX_PREVIEW} premières)
          </p>
        )}
      </div>
      <div className="overflow-x-auto rounded-lg border">
        <table className="w-full text-xs">
          <thead>
            <tr className="border-b bg-muted/50">
              {headers.map((h, i) => (
                <th key={i} className="px-2.5 py-1.5 text-left font-medium text-muted-foreground whitespace-nowrap">
                  {h || <span className="text-muted-foreground/40">(vide)</span>}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {previewRows.map((row, ri) => (
              <tr key={ri} className="border-b last:border-0">
                {headers.map((_, ci) => (
                  <td key={ci} className="px-2.5 py-1.5 max-w-[200px] truncate whitespace-nowrap text-muted-foreground">
                    {row[ci]?.trim() || <span className="text-muted-foreground/30">—</span>}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
