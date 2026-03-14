import { Btn, Notice, Badge } from '../../components/ui'

export default function DiagnosticSection({ diagLoading, diagData, handleDiagnostic }) {
  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">
        Teste chaque endpoint Regiondo et affiche les réponses brutes pour diagnostiquer les problèmes de connexion.
      </p>
      <div>
        <Btn variant="secondary" loading={diagLoading} onClick={handleDiagnostic}>
          {diagData ? 'Relancer le diagnostic' : 'Lancer le diagnostic'}
        </Btn>
      </div>
      {diagData?.error && <Notice type="error">{diagData.error}</Notice>}
      {diagData?.endpoints && (
        <div className="space-y-3 mt-2">
          {diagData.endpoints.map((ep, i) => {
            const ok      = ep.status >= 200 && ep.status < 300 && !ep.error
            const hasData = ep.response?.data && (Array.isArray(ep.response.data) ? ep.response.data.length > 0 : true)
            const hasError = ep.response?.error || ep.response?.error_code || ep.response?.error_message
            return (
              <details key={i} className="rounded-lg border bg-card overflow-hidden">
                <summary className="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-muted/50">
                  <span className={`w-2 h-2 rounded-full shrink-0 ${
                    hasError ? 'bg-orange-400' :
                    ok && hasData ? 'bg-emerald-500' :
                    ok ? 'bg-yellow-400' : 'bg-red-500'
                  }`} />
                  <span className="text-sm font-medium flex-1">{ep.label}</span>
                  <code className="text-[11px] text-muted-foreground">{ep.status}</code>
                  {hasData && <Badge variant="confirmed">{Array.isArray(ep.response.data) ? ep.response.data.length + ' items' : 'OK'}</Badge>}
                  {hasError && <Badge variant="cancelled">Erreur</Badge>}
                  {ok && !hasData && !hasError && <Badge variant="pending">Vide</Badge>}
                  {!ok && <Badge variant="cancelled">{ep.error || 'HTTP ' + ep.status}</Badge>}
                </summary>
                <div className="border-t px-4 py-3">
                  <p className="text-[11px] text-muted-foreground mb-2 break-all">
                    <span className="font-medium">URL :</span> {ep.url}
                  </p>
                  <pre className="text-[11px] bg-muted/50 rounded p-3 overflow-x-auto max-h-80 whitespace-pre-wrap break-all">
                    {JSON.stringify(ep.response ?? ep.raw ?? ep.error, null, 2)}
                  </pre>
                </div>
              </details>
            )
          })}
        </div>
      )}
    </div>
  )
}
