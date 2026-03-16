import { useState, useEffect, useCallback, useMemo } from 'react'
import { Search, Download, Trash, NavArrowDown } from 'iconoir-react'
import { format, formatDistanceToNowStrict } from 'date-fns'
import { fr } from 'date-fns/locale'
import { api } from '../lib/api'
import { avatarColor } from '../lib/colors'
import { PageHeader, Table, Pagination, Notice, Spinner, Badge, Btn } from '../components/ui'

/** Normalise une date MySQL (espace) ou ISO (T) en objet Date valide. */
const parseDate = d => {
  if (!d || d === '0000-00-00') return null
  const iso = d.includes('T') ? d : d.replace(' ', 'T')
  const date = new Date(iso)
  return isNaN(date.getTime()) ? null : date
}

/** Formatage de date relatif. */
const fmtDate = d => {
  const date = parseDate(d)
  if (!date) return '—'
  const days = Math.floor((Date.now() - date.getTime()) / 86400000)
  if (days < 0)  return format(date, 'd MMM yy', { locale: fr })
  if (days === 0) return "auj."
  if (days <= 60) return formatDistanceToNowStrict(date, { locale: fr, addSuffix: true })
  return format(date, 'd MMM yy', { locale: fr })
}

/** Formatage date longue pour la vue détaillée. */
const fmtDateLong = d => {
  const date = parseDate(d)
  if (!date) return '—'
  return format(date, "d MMMM yyyy 'à' HH:mm", { locale: fr })
}

/** Formate une date courte DD/MM/YYYY → lisible. */
const fmtDateShort = d => {
  if (!d) return '—'
  // Déjà au format DD/MM/YYYY depuis le formulaire
  return d
}

/** Extrait un domaine lisible depuis une URL de referrer. */
const extractDomain = url => {
  if (!url) return null
  try { return new URL(url).hostname.replace(/^www\./, '') } catch { return url }
}

/** Nom complet depuis client_firstname + client_name. */
const fullName = r => [r.client_firstname, r.client_name].filter(Boolean).join(' ') || '—'

/** Labels français pour duration_type. */
const DURATION_LABELS = {
  half:    'Demi-journée',
  full:    'Journée complète',
  multi:   'Multi-jours',
  custom:  'Sur mesure',
}
const fmtDuration = v => DURATION_LABELS[v] || v || '—'

/** Plage de dates lisible. */
const fmtDateRange = (start, end) => {
  if (!start) return '—'
  if (!end || end === start) return fmtDateShort(start)
  return `${fmtDateShort(start)} → ${fmtDateShort(end)}`
}

/** Export CSV des soumissions. */
function exportCsv(rows) {
  const hdr = ['Prénom', 'Nom', 'Email', 'Téléphone', 'Type', 'Excursion', 'Bateau', 'Formule', 'Dates', 'Email envoyé', 'Canal', 'Date']
  const csv = [hdr, ...rows.map(r => [
    r.client_firstname ?? '', r.client_name ?? '', r.client_email ?? '', r.client_phone ?? '',
    r.form_type ?? '', r.excursion_name ?? '', r.boat_name ?? '',
    fmtDuration(r.duration_type), fmtDateRange(r.date_start, r.date_end),
    r.email_sent ? 'Envoyé' : 'Échec',
    r.utm_source || extractDomain(r.referrer) || '',
    r.created_at ?? '',
  ])].map(r => r.join(';')).join('\n')
  Object.assign(document.createElement('a'), {
    href: URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' })),
    download: 'formulaires.csv',
  }).click()
}

const EMAIL_FILTERS = [
  { label: 'Tous',    value: '' },
  { label: 'Envoyés', value: '1' },
  { label: 'Échecs',  value: '0' },
]

/**
 * Page Formulaires — liste paginée des soumissions de formulaires
 * avec barre de stats, recherche, filtres et vue détaillée inline.
 */
export default function Forms() {
  const [data, setData]             = useState([])
  const [total, setTotal]           = useState(0)
  const [loading, setLoading]       = useState(true)
  const [error, setError]           = useState(null)
  const [page, setPage]             = useState(1)
  const [q, setQ]                   = useState('')
  const [search, setSearch]         = useState('')
  const [emailFilter, setEmailFilter] = useState('')
  const [expandedId, setExpandedId] = useState(null)
  const [stats, setStats]           = useState(null)
  const [deleting, setDeleting]     = useState(null)
  const perPage = 50

  /* ── Chargement des soumissions ─────────────────────────────────────────── */
  const load = useCallback(() => {
    setLoading(true)
    api.forms({
      page,
      per_page: perPage,
      search: search || undefined,
      email_sent: emailFilter || undefined,
    })
      .then(r => { setData(r.data ?? []); setTotal(r.total ?? 0) })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [page, search, emailFilter])

  useEffect(() => { load() }, [load])
  useEffect(() => { api.formStats().then(setStats).catch(() => {}) }, [])

  /* Debounce de la recherche */
  useEffect(() => {
    const t = setTimeout(() => { setSearch(q); setPage(1) }, 400)
    return () => clearTimeout(t)
  }, [q])

  /* ── Suppression d'une soumission ───────────────────────────────────────── */
  const handleDelete = useCallback((id) => {
    if (!window.confirm('Supprimer définitivement cette soumission ?')) return
    setDeleting(id)
    api.deleteForm(id)
      .then(() => {
        setExpandedId(null)
        load()
        api.formStats().then(setStats).catch(() => {})
      })
      .catch(e => setError(e.message))
      .finally(() => setDeleting(null))
  }, [load])

  /* ── Colonnes du tableau ────────────────────────────────────────────────── */
  const columns = useMemo(() => [
    {
      key: 'client', label: 'Client',
      render: r => {
        const name = fullName(r)
        return (
          <div className="flex items-center gap-2.5">
            <div
              className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
              style={avatarColor(name)}
            >
              {(name[0] ?? '?').toUpperCase()}
            </div>
            <div className="min-w-0">
              <div className="font-medium text-sm leading-tight truncate">{name}</div>
              <div className="text-xs text-muted-foreground truncate">{r.client_email || '—'}</div>
            </div>
          </div>
        )
      },
    },
    {
      key: 'form_type', label: 'Type',
      render: r => r.form_type ? <Badge variant="default">{r.form_type === 'quote' ? 'Devis' : r.form_type}</Badge> : <span className="text-xs text-muted-foreground">—</span>,
    },
    {
      key: 'excursion_name', label: 'Excursion',
      render: r => <span className="text-sm truncate max-w-[180px] block">{r.excursion_name || '—'}</span>,
    },
    {
      key: 'boat_name', label: 'Bateau',
      render: r => <span className="text-sm">{r.boat_name || '—'}</span>,
    },
    {
      key: 'duration_type', label: 'Formule',
      render: r => <span className="text-xs text-muted-foreground">{fmtDuration(r.duration_type)}</span>,
    },
    {
      key: 'email_sent', label: 'Email',
      render: r => (
        <Badge variant={r.email_sent ? 'ok' : 'error'}>
          {r.email_sent ? 'Envoyé' : 'Échec'}
        </Badge>
      ),
    },
    {
      key: 'created_at', label: 'Date',
      render: r => <span className="text-xs text-muted-foreground">{fmtDate(r.created_at)}</span>,
    },
  ], [])

  /* ── Stats bar ──────────────────────────────────────────────────────────── */
  const statsCards = stats ? [
    { label: 'Total soumissions', value: stats.total ?? 0 },
    { label: 'Envoyés',           value: stats.sent ?? 0 },
    { label: 'Échecs',            value: stats.failed ?? 0 },
    { label: "Aujourd'hui",       value: stats.today ?? 0 },
  ] : null

  /* ── Rendu ──────────────────────────────────────────────────────────────── */
  return (
    <div>
      <PageHeader
        title="Demandes de devis"
        subtitle="Soumissions via le widget BT — Tarifs"
        actions={
          <div className="flex items-center gap-2 flex-wrap">
            <div className="relative">
              <Search className="absolute left-2.5 top-2.5 w-4 h-4 text-muted-foreground pointer-events-none" />
              <input
                className="h-9 pl-8 pr-3 text-sm rounded-md border border-input bg-transparent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring w-44"
                placeholder="Nom ou email…"
                value={q}
                onChange={e => setQ(e.target.value)}
              />
            </div>
            <select
              className="h-9 px-3 text-sm rounded-md border border-input bg-background cursor-pointer focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              value={emailFilter}
              onChange={e => { setEmailFilter(e.target.value); setPage(1) }}
            >
              {EMAIL_FILTERS.map(f => <option key={f.value} value={f.value}>{f.label}</option>)}
            </select>
            <Btn variant="secondary" size="sm" onClick={() => exportCsv(data)} disabled={!data.length}>
              <Download className="w-4 h-4" /> Export
            </Btn>
            <span className="text-xs text-muted-foreground">{total} soumission{total !== 1 ? 's' : ''}</span>
          </div>
        }
      />

      {/* Stats bar */}
      {statsCards && (
        <div className="mx-6 mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
          {statsCards.map(s => (
            <div key={s.label} className="rounded-lg border bg-card p-4">
              <p className="text-xs text-muted-foreground">{s.label}</p>
              <p className="text-2xl font-bold tabular-nums mt-1">{s.value}</p>
            </div>
          ))}
        </div>
      )}

      {error && <div className="px-6 pt-5"><Notice type="error">{error}</Notice></div>}

      <div className="mx-6 mt-5 rounded-lg border overflow-hidden">
        {loading
          ? <div className="flex items-center justify-center py-20"><Spinner size={20} /></div>
          : <>
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b bg-muted/50">
                    {columns.map(col => (
                      <th key={col.key} className="text-left px-4 py-3 text-xs font-medium text-muted-foreground">
                        {col.label}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {data.length === 0 && (
                    <tr>
                      <td colSpan={columns.length} className="text-center py-12 text-sm text-muted-foreground">
                        Aucune soumission trouvée.
                      </td>
                    </tr>
                  )}
                  {data.map(row => (
                    <FormRow
                      key={row.id}
                      row={row}
                      columns={columns}
                      expanded={expandedId === row.id}
                      onToggle={() => setExpandedId(expandedId === row.id ? null : row.id)}
                      onDelete={handleDelete}
                      deleting={deleting === row.id}
                    />
                  ))}
                </tbody>
              </table>
              <Pagination page={page} total={total} perPage={perPage} onChange={setPage} />
            </>
        }
      </div>
    </div>
  )
}

/* ── Ligne de tableau avec détail dépliable ───────────────────────────────── */
function FormRow({ row, columns, expanded, onToggle, onDelete, deleting }) {
  return (
    <>
      <tr
        className="border-b cursor-pointer hover:bg-muted/30 transition-colors"
        onClick={onToggle}
      >
        {columns.map(col => (
          <td key={col.key} className="px-4 py-3">
            {col.render(row)}
          </td>
        ))}
      </tr>
      {expanded && (
        <tr className="bg-muted/20">
          <td colSpan={columns.length} className="px-4 py-0">
            <FormDetail row={row} onDelete={onDelete} deleting={deleting} />
          </td>
        </tr>
      )}
    </>
  )
}

/* ── Carte de détail inline ───────────────────────────────────────────────── */
function FormDetail({ row, onDelete, deleting }) {
  const canal = row.utm_source || extractDomain(row.referrer) || '—'

  const fields = [
    { label: 'Nom complet',       value: fullName(row) },
    { label: 'Email',             value: row.client_email || '—' },
    { label: 'Téléphone',         value: row.client_phone || '—' },
    { label: 'Excursion',         value: row.excursion_name || '—' },
    { label: 'Bateau',            value: row.boat_name || '—' },
    { label: 'Formule',           value: fmtDuration(row.duration_type) },
    { label: 'Dates souhaitées',  value: fmtDateRange(row.date_start, row.date_end) },
    { label: 'Message',           value: row.message || '—', full: true },
    { label: 'Canal',             value: canal },
    { label: 'UTM Source',        value: row.utm_source || '—' },
    { label: 'UTM Medium',        value: row.utm_medium || '—' },
    { label: 'UTM Campaign',      value: row.utm_campaign || '—' },
    { label: 'Referrer',          value: row.referrer || '—' },
    { label: 'Page URL',          value: row.page_url || '—' },
    { label: 'IP',                value: row.ip_address || '—' },
    { label: 'Date de soumission',value: fmtDateLong(row.created_at) },
    { label: 'Statut email',      value: row.email_sent ? 'Envoyé' : (row.email_error || 'Erreur') },
  ]

  return (
    <div className="py-4 space-y-4">
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3">
        {fields.map(f => (
          <div key={f.label} className={f.full ? 'sm:col-span-2 lg:col-span-3' : ''}>
            <p className="text-[11px] uppercase tracking-wider text-muted-foreground/60 font-medium">{f.label}</p>
            <p className="text-sm text-foreground mt-0.5 break-words">{f.value}</p>
          </div>
        ))}
      </div>
      <div className="flex justify-end pt-2 border-t border-border/50">
        <Btn
          variant="destructive"
          size="sm"
          onClick={e => { e.stopPropagation(); onDelete(row.id) }}
          disabled={deleting}
        >
          {deleting ? <Spinner size={14} /> : <Trash className="w-4 h-4" />}
          Supprimer
        </Btn>
      </div>
    </div>
  )
}
