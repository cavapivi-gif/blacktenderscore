import { Input, Btn, Notice } from '../../components/ui'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../../components/Tabs'
import { PROVIDER_LIST } from '../../lib/aiProviders'
import AiProviderIcon from '../../components/AiProviderIcon'
import { api } from '../../lib/api'

export default function ApiSection({ settings, set, testing, handleTestApi, testResult, gTesting, handleTestGoogle, gTestResult, setGTestResult }) {
  return (
    <Tabs defaultValue="regiondo">
      <TabsList className="rounded-b-none border border-b-0 border-border bg-muted w-full justify-start gap-0 p-0 h-auto flex-wrap">
        {[
          { val: 'regiondo', label: 'Regiondo',       dot: !!(settings.public_key) },
          { val: 'google',   label: 'Google',         dot: !!(settings.google_credentials_json) },
          { val: 'ia',       label: 'IA',             dot: !!(settings.anthropic_api_key || settings.openai_api_key || settings.gemini_api_key) },
          { val: 'snazzy',   label: 'Snazzy Maps',    dot: !!(settings.snazzymaps_api_key) },
        ].map(({ val, label, dot }) => (
          <TabsTrigger key={val} value={val} className="rounded-none first:rounded-tl-md px-4 py-2 text-xs data-[state=active]:shadow-none data-[state=active]:bg-white data-[state=active]:text-foreground">
            {label}
            {dot && <span className="ml-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />}
          </TabsTrigger>
        ))}
      </TabsList>

      {/* ── Regiondo ── */}
      <TabsContent value="regiondo" className="mt-0 border border-t-0 rounded-b-md p-4">
        <div className="space-y-4">
          <p className="text-sm text-muted-foreground">
            Retrouvez vos clés dans Regiondo → Paramètres → API.
          </p>
          <Input
            label="Clé publique (Public Key)"
            value={settings.public_key ?? ''}
            onChange={e => set('public_key', e.target.value)}
            placeholder="Votre clé publique Regiondo"
          />
          <Input
            label="Clé secrète (Secret Key)"
            type="password"
            value={settings.secret_key ?? ''}
            onChange={e => set('secret_key', e.target.value)}
            placeholder="Votre clé secrète Regiondo"
          />
          <div className="flex items-center gap-3 pt-1">
            <Btn variant="secondary" size="sm" loading={testing} onClick={handleTestApi}>
              Tester la connexion
            </Btn>
            {testResult && (
              <span className={`text-xs ${testResult.success ? 'text-emerald-600' : 'text-destructive'}`}>
                {testResult.message}
              </span>
            )}
          </div>
        </div>
      </TabsContent>

      {/* ── Google (GA4 + Search Console) ── */}
      <TabsContent value="google" className="mt-0 border border-t-0 rounded-b-md p-4">
        <div className="space-y-5">

          {/* ── Guide pas-à-pas ── */}
          <div className="rounded-lg border bg-muted/30 divide-y divide-border text-xs">
            <div className="px-4 py-2.5 font-semibold text-foreground text-sm flex items-center gap-2">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
              Guide de connexion Google
            </div>
            {[
              {
                n: 1,
                title: 'Créer un projet Google Cloud',
                body: <>Rendez-vous sur <a href="https://console.cloud.google.com" target="_blank" rel="noreferrer" className="underline font-mono hover:text-foreground">console.cloud.google.com</a> → Nouveau projet.</>,
              },
              {
                n: 2,
                title: 'Activer les APIs',
                body: <>Dans <em>APIs & Services → Bibliothèque</em>, activez :<br/>
                  · <strong>Google Analytics Data API</strong> (pour GA4)<br/>
                  · <strong>Google Search Console API</strong></>,
              },
              {
                n: 3,
                title: 'Créer un Service Account',
                body: <>IAM & Admin → Comptes de service → Créer. Puis : Clés → Ajouter une clé → JSON. Téléchargez le fichier.</>,
              },
              {
                n: 4,
                title: 'Autoriser dans GA4',
                body: <><a href="https://analytics.google.com" target="_blank" rel="noreferrer" className="underline hover:text-foreground">analytics.google.com</a> → Administration → Propriété → Gestion des accès → <strong>+</strong> → ajoutez l'email du service account avec le rôle <strong>Lecteur</strong>.</>,
              },
              {
                n: 5,
                title: 'Autoriser dans Search Console',
                body: <><a href="https://search.google.com/search-console" target="_blank" rel="noreferrer" className="underline hover:text-foreground">Search Console</a> → Paramètres → Utilisateurs et autorisations → Ajouter l'email du service account.</>,
              },
              {
                n: 6,
                title: 'Coller le JSON + renseigner les IDs ci-dessous',
                body: 'Collez le contenu du fichier JSON téléchargé, puis renseignez votre Property ID GA4 et votre Measurement ID.',
              },
            ].map(({ n, title, body }) => (
              <div key={n} className="flex gap-3 px-4 py-3">
                <div className="w-5 h-5 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">{n}</div>
                <div className="text-muted-foreground leading-relaxed">
                  <span className="font-medium text-foreground">{title}</span>
                  <br/>{body}
                </div>
              </div>
            ))}
          </div>

          {/* ── Service Account JSON ── */}
          <div className="space-y-1.5">
            <label className="text-sm font-medium block">Service Account JSON</label>
            {/* Le backend ne renvoie jamais la clé privée — affiche seulement l'email */}
            {settings.google_credentials_json?.configured ? (
              <div className="space-y-2">
                <div className="flex items-center gap-2 px-3 py-2 rounded-md border bg-emerald-50 border-emerald-200">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="text-emerald-600 shrink-0"><path d="M20 6 9 17l-5-5"/></svg>
                  <span className="text-xs text-emerald-700 font-mono">{settings.google_credentials_json.client_email}</span>
                </div>
                <button
                  type="button"
                  onClick={() => { set('google_credentials_json', ''); setGTestResult(null) }}
                  className="text-[11px] text-destructive hover:underline"
                >
                  Révoquer et remplacer
                </button>
              </div>
            ) : (
              <div className="space-y-1.5">
                <textarea
                  rows={6}
                  spellCheck={false}
                  value={typeof settings.google_credentials_json === 'string' ? settings.google_credentials_json : ''}
                  onChange={e => { set('google_credentials_json', e.target.value); setGTestResult(null) }}
                  placeholder={'{\n  "type": "service_account",\n  "project_id": "...",\n  "client_email": "...",\n  "private_key": "-----BEGIN PRIVATE KEY-----\\n..."\n}'}
                  className="w-full rounded-md border border-input bg-transparent px-3 py-2.5 font-mono text-xs leading-relaxed resize-y focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                />
                {(() => {
                  const v = typeof settings.google_credentials_json === 'string' ? settings.google_credentials_json : ''
                  if (!v) return null
                  try {
                    const c = JSON.parse(v)
                    return c?.type === 'service_account'
                      ? <p className="text-[11px] text-emerald-600 flex items-center gap-1"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M20 6 9 17l-5-5"/></svg>{c.client_email} — JSON valide</p>
                      : <p className="text-[11px] text-destructive">JSON invalide — doit être de type "service_account"</p>
                  } catch { return <p className="text-[11px] text-destructive">JSON malformé</p> }
                })()}
                <p className="text-[11px] text-muted-foreground">La clé privée est chiffrée en transit (HTTPS) et jamais renvoyée au navigateur après enregistrement.</p>
              </div>
            )}
          </div>

          {/* ── GA4 IDs ── */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {/* Property ID — numérique, pour l'API Data */}
            <div className="space-y-1.5">
              <label className="text-sm font-medium block">Property ID <span className="text-muted-foreground font-normal">(Data API)</span></label>
              {(() => {
                const pid = settings.ga4_property_id ?? ''
                const isMeasurementId = /^G-/i.test(pid)
                const isInvalid = pid && !/^\d+$/.test(pid)
                return (
                  <>
                    <input
                      type="text"
                      value={pid}
                      onChange={e => { set('ga4_property_id', e.target.value.replace(/[^0-9]/g, '')); setGTestResult(null) }}
                      placeholder="412345678"
                      className={`flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono ${isInvalid ? 'border-destructive focus-visible:ring-destructive' : 'border-input'}`}
                    />
                    {isMeasurementId ? (
                      <p className="text-[11px] text-destructive flex items-start gap-1">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        <span><strong>G-... est un Measurement ID</strong>, pas un Property ID. Déplacez-le dans le champ à droite. Le Property ID est un <strong>nombre</strong>, trouvez-le dans GA4 → Admin → Propriété → <em>Informations sur la propriété</em>.</span>
                      </p>
                    ) : (
                      <p className="text-[11px] text-muted-foreground">
                        GA4 → Admin → Propriété → <em>Informations sur la propriété</em> → <strong>ID de la propriété</strong> (nombre, ex: 412345678)
                      </p>
                    )}
                  </>
                )
              })()}
            </div>

            {/* Measurement ID — G-XXXXXXXX, pour le tracking gtag.js */}
            <div className="space-y-1.5">
              <label className="text-sm font-medium block">Measurement ID <span className="text-muted-foreground font-normal">(tracking front)</span></label>
              {(() => {
                const mid = settings.ga4_measurement_id ?? ''
                const isNumericOnly = mid && /^\d+$/.test(mid)
                return (
                  <>
                    <input
                      type="text"
                      value={mid}
                      onChange={e => set('ga4_measurement_id', e.target.value.toUpperCase())}
                      placeholder="G-XXXXXXXXXX"
                      className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono"
                    />
                    {isNumericOnly ? (
                      <p className="text-[11px] text-amber-600 flex items-start gap-1">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        Ressemble à un Property ID — le Measurement ID commence par <strong>G-</strong>
                      </p>
                    ) : (
                      <p className="text-[11px] text-muted-foreground">
                        GA4 → Admin → Flux de données → votre flux → <em>Measurement ID</em> (commence par <strong>G-</strong>)
                      </p>
                    )}
                  </>
                )
              })()}
            </div>
          </div>

          {/* ── Search Console URL ── */}
          <div className="space-y-1.5">
            <label className="text-sm font-medium block">Search Console — URL du site <span className="font-normal text-muted-foreground">(production)</span></label>
            <input
              type="url"
              value={settings.search_console_site_url ?? ''}
              onChange={e => { set('search_console_site_url', e.target.value); setGTestResult(null) }}
              placeholder="https://studiojae.fr/"
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono"
            />
            {/* Avertissement si l'URL ressemble à un site dev/local */}
            {(() => {
              const url = settings.search_console_site_url ?? ''
              const isDevLike = url && /dev\.|localhost|127\.0\.0|staging\.|preprod\./.test(url)
              return isDevLike ? (
                <p className="text-[11px] text-amber-600 flex items-center gap-1">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                  URL de développement détectée — Search Console indexe votre site de <strong>production</strong>, entrez son URL ici (ex : <code className="bg-muted px-0.5 rounded">https://studiojae.fr/</code>).
                </p>
              ) : (
                <p className="text-[11px] text-muted-foreground">
                  URL de votre site <strong>de production</strong> exactement telle que déclarée dans Search Console.
                  Retrouvez-la dans <a href="https://search.google.com/search-console" target="_blank" rel="noreferrer" className="underline hover:text-foreground">Search Console</a> → menu déroulant en haut à gauche.
                </p>
              )
            })()}
          </div>

          {/* ── Cache GA4 + GSC ── */}
          <div className="space-y-3 p-3 rounded-md border border-border bg-muted/30">
            <p className="text-xs font-medium text-muted-foreground">Cache Analytics</p>
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <label className="text-sm font-medium block">Cache GA4 (heures)</label>
                <input
                  type="number"
                  min="1"
                  max="168"
                  value={settings.ga4_cache_hours ?? 6}
                  onChange={e => set('ga4_cache_hours', Number(e.target.value))}
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium block">Cache GSC (heures)</label>
                <input
                  type="number"
                  min="1"
                  max="168"
                  value={settings.gsc_cache_hours ?? 12}
                  onChange={e => set('gsc_cache_hours', Number(e.target.value))}
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                />
              </div>
            </div>
            <div className="flex gap-2 pt-1">
              <Btn
                variant="secondary"
                size="sm"
                onClick={async () => { try { await api.flushGa4Cache() } catch(e) { alert(e.message) } }}
              >
                Vider cache GA4
              </Btn>
              <Btn
                variant="secondary"
                size="sm"
                onClick={async () => { try { await api.flushGscCache() } catch(e) { alert(e.message) } }}
              >
                Vider cache GSC
              </Btn>
            </div>
          </div>

          {/* ── Bouton test ── */}
          <div className="flex flex-col gap-3 pt-1">
            <Btn
              variant="secondary"
              size="sm"
              loading={gTesting}
              onClick={handleTestGoogle}
              disabled={!settings.google_credentials_json?.configured}
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 12l2 2 4-4"/><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
              Tester la connexion Google
            </Btn>
            {!settings.google_credentials_json?.configured && (
              <p className="text-[11px] text-muted-foreground">Enregistrez d'abord le Service Account JSON pour pouvoir tester.</p>
            )}

            {/* Résultats du test */}
            {gTestResult && !gTestResult.success && (
              <Notice type="error">{gTestResult.error}</Notice>
            )}
            {gTestResult?.success && (
              <div className="space-y-3">
                <p className="text-[11px] text-muted-foreground">
                  Service account : <span className="font-mono text-foreground">{gTestResult.client_email}</span>
                </p>

                {/* ── GA4 : propriété configurée ── */}
                {gTestResult.ga4?.configured && (
                  <div className={`flex items-start gap-2 px-3 py-2 rounded-md border text-xs ${
                    gTestResult.ga4.ok ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'
                  }`}>
                    {gTestResult.ga4.ok
                      ? <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M20 6 9 17l-5-5"/></svg>
                      : <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M18 6 6 18M6 6l12 12"/></svg>}
                    <span>
                      <strong>GA4 Property {settings.ga4_property_id} :</strong>{' '}
                      {gTestResult.ga4.ok ? <>Connecté — {gTestResult.ga4.display_name}</> : gTestResult.ga4.error}
                    </span>
                  </div>
                )}

                {/* ── GA4 : toutes les propriétés accessibles ── */}
                {gTestResult.ga4?.accessible_properties?.length > 0 && (
                  <div className="rounded-md border bg-card text-xs overflow-hidden">
                    <div className="px-3 py-2 bg-muted/50 font-medium text-foreground border-b flex items-center gap-1.5">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
                      Propriétés GA4 accessibles à ce service account
                    </div>
                    {gTestResult.ga4.accessible_properties.map(p => (
                      <div key={p.property_id} className="flex items-center justify-between px-3 py-2 border-b last:border-0 hover:bg-muted/30">
                        <div>
                          <span className="font-medium">{p.display_name}</span>
                          <span className="text-muted-foreground ml-2 font-mono">{p.property_id}</span>
                          <span className="text-muted-foreground ml-1">— {p.account_name}</span>
                        </div>
                        <button
                          type="button"
                          onClick={() => { set('ga4_property_id', p.property_id); setGTestResult(null) }}
                          className="ml-3 shrink-0 px-2 py-0.5 rounded border border-primary text-primary text-[11px] hover:bg-primary hover:text-primary-foreground transition-colors"
                        >
                          Utiliser
                        </button>
                      </div>
                    ))}
                  </div>
                )}
                {gTestResult.ga4?.accessible_properties?.length === 0 && (
                  <Notice type="warn">
                    Aucune propriété GA4 accessible — ajoutez l'email <code className="font-mono">{gTestResult.client_email}</code> dans GA4 → Administration → Gestion des accès (rôle Lecteur).
                  </Notice>
                )}

                {/* ── Search Console : site configuré ── */}
                {gTestResult.search_console?.configured && (
                  <>
                    <div className={`flex items-start gap-2 px-3 py-2 rounded-md border text-xs ${
                      gTestResult.search_console.ok ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'
                    }`}>
                      {gTestResult.search_console.ok
                        ? <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M20 6 9 17l-5-5"/></svg>
                        : <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5"><path d="M18 6 6 18M6 6l12 12"/></svg>}
                      <span>
                        <strong>Search Console :</strong>{' '}
                        {gTestResult.search_console.ok
                          ? <>Connecté — {gTestResult.search_console.permission_level}</>
                          : gTestResult.search_console.error}
                        {!gTestResult.search_console.ok && gTestResult.search_console.error?.includes('permission') && !gTestResult.search_console.correct_url && (
                          <span className="block mt-1.5 leading-relaxed">
                            Pour corriger : <a href="https://search.google.com/search-console" target="_blank" rel="noreferrer" className="underline">Search Console</a> → Paramètres → Utilisateurs → Ajouter <code className="bg-black/10 px-0.5 rounded font-mono">{gTestResult.client_email}</code> avec le rôle <strong>Propriétaire restreint</strong>.
                          </span>
                        )}
                      </span>
                    </div>
                    {/* ── Correction automatique : propriété Domain détectée ── */}
                    {gTestResult.search_console.correct_url && (
                      <div className="flex items-start gap-2 px-3 py-2.5 rounded-md border border-amber-300 bg-amber-50 text-xs text-amber-900">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="shrink-0 mt-0.5 text-amber-600"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <span className="flex-1">
                          <strong>Propriété de type Domain détectée.</strong> L'URL configurée ne fonctionne pas avec l'API, mais la variante{' '}
                          <code className="bg-black/10 px-0.5 rounded font-mono">{gTestResult.search_console.correct_url}</code>{' '}
                          est accessible.
                          <button
                            type="button"
                            onClick={() => {
                              set('search_console_site_url', gTestResult.search_console.correct_url)
                              setGTestResult(prev => ({
                                ...prev,
                                search_console: { ...prev.search_console, correct_url: null }
                              }))
                            }}
                            className="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded border border-amber-600 text-amber-800 font-medium hover:bg-amber-100 transition-colors"
                          >
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            Mettre à jour
                          </button>
                        </span>
                      </div>
                    )}
                  </>
                )}

                {/* ── Search Console : tous les sites accessibles ── */}
                {gTestResult.search_console?.accessible_sites?.length > 0 && (
                  <div className="rounded-md border bg-card text-xs overflow-hidden">
                    <div className="px-3 py-2 bg-muted/50 font-medium text-foreground border-b flex items-center gap-1.5">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                      Sites Search Console accessibles à ce service account
                    </div>
                    {gTestResult.search_console.accessible_sites.map(s => (
                      <div key={s.url} className="flex items-center justify-between px-3 py-2 border-b last:border-0 hover:bg-muted/30">
                        <div>
                          <span className="font-mono">{s.url}</span>
                          <span className="text-muted-foreground ml-2">{s.permission_level}</span>
                        </div>
                        <button
                          type="button"
                          onClick={() => { set('search_console_site_url', s.url); setGTestResult(null) }}
                          className="ml-3 shrink-0 px-2 py-0.5 rounded border border-primary text-primary text-[11px] hover:bg-primary hover:text-primary-foreground transition-colors"
                        >
                          Utiliser
                        </button>
                      </div>
                    ))}
                  </div>
                )}
                {gTestResult.search_console?.accessible_sites?.length === 0 && (
                  <Notice type="warn">
                    Aucun site Search Console accessible — ajoutez l'email <code className="font-mono">{gTestResult.client_email}</code> dans Search Console → Paramètres → Utilisateurs.
                  </Notice>
                )}

                {!gTestResult.ga4?.configured && !gTestResult.search_console?.configured && (
                  <Notice type="warn">Credentials valides mais aucun Property ID ni URL configuré — utilisez les boutons ci-dessus pour sélectionner votre propriété/site.</Notice>
                )}
              </div>
            )}
          </div>

        </div>
      </TabsContent>

      {/* ── IA (multi-provider) ── */}
      <TabsContent value="ia" className="mt-0 border border-t-0 rounded-b-md p-4">
        <div className="space-y-4">

          {/* Sélecteur visuel de provider */}
          <div>
            <p className="text-sm font-medium mb-2">Provider actif</p>
            <div className="grid grid-cols-3 gap-2">
              {PROVIDER_LIST.map(p => {
                const active = (settings.ai_provider ?? 'anthropic') === p.key
                return (
                  <button
                    key={p.key}
                    type="button"
                    onClick={() => set('ai_provider', p.key)}
                    className={[
                      'flex flex-col items-center gap-1.5 p-3 rounded-lg border transition-all text-center',
                      active
                        ? 'border-foreground/30 bg-foreground/5 shadow-sm'
                        : 'border-border hover:border-foreground/20 hover:bg-muted/40 opacity-70',
                    ].join(' ')}
                  >
                    <AiProviderIcon iconKey={p.iconKey} variant="Color" size={22} />
                    <span className="text-[11px] font-medium leading-tight">{p.sublabel}</span>
                    <span className="text-[10px] text-muted-foreground leading-tight">{p.label}</span>
                    {active && (
                      <span className="text-[10px] text-emerald-600 font-medium mt-0.5">Actif</span>
                    )}
                  </button>
                )
              })}
            </div>
          </div>

          {/* Clés API — une par provider */}
          <div className="space-y-2.5">
            {PROVIDER_LIST.map(p => {
              const active = (settings.ai_provider ?? 'anthropic') === p.key
              const fieldKey = p.key + '_api_key'
              const hasKey   = !!(settings[fieldKey])
              return (
                <div
                  key={p.key}
                  className={[
                    'flex items-center gap-3 p-3 rounded-lg border transition-colors',
                    active ? 'border-foreground/20 bg-card' : 'border-border opacity-60',
                  ].join(' ')}
                >
                  <AiProviderIcon iconKey={p.iconKey} variant="Color" size={18} />
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-medium mb-1">{p.sublabel}</p>
                    <Input
                      type="password"
                      value={settings[fieldKey] ?? ''}
                      onChange={e => set(fieldKey, e.target.value)}
                      placeholder={p.placeholder}
                    />
                  </div>
                  {hasKey && (
                    <span className="text-[10px] text-emerald-600 font-medium shrink-0">✓</span>
                  )}
                </div>
              )
            })}
          </div>

          {/* Note Meta / Groq */}
          <p className="text-[11px] text-muted-foreground">
            Meta Llama utilise l'API <strong>Groq</strong> (accès gratuit sur console.groq.com).
          </p>
        </div>
      </TabsContent>

      {/* ── Snazzy Maps ── */}
      <TabsContent value="snazzy" className="mt-0 border border-t-0 rounded-b-md p-4">
        <div className="space-y-4">
          <p className="text-sm text-muted-foreground">
            Permet d'accéder à la bibliothèque de styles depuis l'éditeur de carte.
          </p>
          <Input
            label="Clé API Snazzy Maps"
            value={settings.snazzymaps_api_key ?? ''}
            onChange={e => set('snazzymaps_api_key', e.target.value)}
            placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
          />
          <p className="text-[11px] text-muted-foreground">
            Obtenez votre clé sur <a href="https://snazzymaps.com/account/api" target="_blank" rel="noreferrer" className="underline hover:text-foreground">snazzymaps.com/account/api</a>.
          </p>
        </div>
      </TabsContent>
    </Tabs>
  )
}
