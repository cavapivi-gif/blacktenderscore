import { useState, useEffect } from 'react'
import { Xmark, Calendar, CreditCard, Star, ShoppingBagCheck, Mail } from 'iconoir-react'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import { api } from '../lib/api'
import { Badge, Spinner, Toggle } from './ui'
import { avatarColor } from '../lib/colors'

/** Mini carte KPI dans le drawer */
function KpiCard({ icon: Icon, label, value }) {
  return (
    <div className="flex flex-col gap-1 px-3 py-2.5 rounded-lg bg-secondary/60">
      <div className="flex items-center gap-1.5 text-[11px] uppercase tracking-wide text-muted-foreground">
        <Icon className="w-3 h-3" />{label}
      </div>
      <div className="text-lg font-semibold tabular-nums">{value}</div>
    </div>
  )
}

const fmtDate = d => (!d || d === '0000-00-00') ? '—' : format(new Date(d.includes?.('T') ? d : (d.includes?.(' ') ? d.replace(' ', 'T') : d + 'T12:00:00')), 'd MMM yyyy', { locale: fr })
const statusVariant = s => ({ confirmed: 'confirmed', cancelled: 'cancelled', rejected: 'cancelled' }[s] ?? 'pending')

/** Labels français pour duration_type. */
const DURATION_LABELS = { half: 'Demi-journée', full: 'Journée', multi: 'Multi-jours', custom: 'Sur mesure' }
const fmtDuration = v => DURATION_LABELS[v] || v || ''

/** Décode les entités HTML (&amp; &#8217; etc.) renvoyées par WP/MySQL. */
const decodeHtml = s => {
  if (!s || typeof s !== 'string') return s
  const el = document.createElement('textarea')
  el.innerHTML = s
  return el.value
}

/** Mini étoiles pour le drawer */
function MiniStars({ value }) {
  if (!value) return <span className="text-muted-foreground text-xs">—</span>
  return (
    <span className="flex items-center gap-0.5">
      {[1,2,3,4,5].map(i => (
        <svg key={i} width="10" height="10" viewBox="0 0 24 24"
          fill={i <= value ? '#f59e0b' : 'none'}
          stroke={i <= value ? '#f59e0b' : '#d1d5db'} strokeWidth="1.5">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
      ))}
    </span>
  )
}

/**
 * Drawer détail client. elevated=true monte les z-index pour s'empiler sur DayDrawer.
 * top-[32px] compense la WP admin bar.
 */
export default function CustomerDrawer({ customer: c, onClose, onUpdate, elevated = false }) {
  const zOverlay = elevated ? 'z-[100060]' : 'z-[100040]'
  const zPanel   = elevated ? 'z-[100070]' : 'z-[100050]'
  const [bookings, setBookings]     = useState([])
  const [reviews, setReviews]       = useState([])
  const [forms, setForms]           = useState([])
  const [loadingBk, setLoadingBk]   = useState(true)
  const [loadingRv, setLoadingRv]   = useState(true)
  const [loadingFm, setLoadingFm]   = useState(true)
  const [newsletter, setNewsletter] = useState(c.newsletter ?? false)
  const [toggling, setToggling]     = useState(false)

  useEffect(() => {
    api.reservations({ search: c.email, per_page: 8, page: 1 })
      .then(r => setBookings(r.data ?? []))
      .catch(() => {})
      .finally(() => setLoadingBk(false))
    api.avisByEmail(c.email)
      .then(r => setReviews(r.data ?? []))
      .catch(() => {})
      .finally(() => setLoadingRv(false))
    api.forms({ search: c.email, per_page: 10 })
      .then(r => setForms(r.data ?? []))
      .catch(() => {})
      .finally(() => setLoadingFm(false))
  }, [c.email])

  async function handleNewsletter(val) {
    setToggling(true)
    try {
      await api.newsletter(c.email, val)
      setNewsletter(val)
      onUpdate?.()
    } catch {
      // silently fail — CRM Regiondo endpoint peut être indisponible
    } finally {
      setToggling(false)
    }
  }

  return (
    <>
      {/* Overlay — top-[32px] pour ne pas passer sous la WP admin bar */}
      <div className={`fixed inset-0 top-[32px] bg-black/30 ${zOverlay}`} onClick={onClose} />

      {/* Panel slide-in */}
      <div className={`fixed right-0 top-[32px] bottom-0 w-[400px] bg-card border-l shadow-2xl ${zPanel} flex flex-col overflow-hidden`}>

        {/* Header */}
        <div className="flex items-center justify-between px-5 py-4 border-b shrink-0">
          <div className="flex items-center gap-3 min-w-0">
            <div className="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0" style={avatarColor(c.name)}>
              {(c.name?.[0] ?? '?').toUpperCase()}
            </div>
            <div className="min-w-0">
              <div className="font-semibold text-sm truncate">{c.name || '—'}</div>
              <div className="text-xs text-muted-foreground truncate">{c.email}</div>
            </div>
          </div>
          <button onClick={onClose} className="p-1.5 rounded-md hover:bg-accent transition-colors shrink-0 ml-2">
            <Xmark className="w-4 h-4" />
          </button>
        </div>

        {/* KPIs */}
        <div className="grid grid-cols-4 gap-2 px-4 py-3 border-b shrink-0">
          <KpiCard icon={ShoppingBagCheck} label="Résa"  value={c.bookings_count} />
          <KpiCard icon={CreditCard} label="CA"    value={c.total_spent > 0 ? `${c.total_spent.toFixed(0)} €` : '—'} />
          <KpiCard icon={Star}       label="Avis"  value={c.avis_count ?? 0} />
          <KpiCard icon={Mail}       label="Devis" value={forms.length} />
        </div>

        {/* Newsletter toggle */}
        <div className="flex items-center justify-between px-4 py-3 border-b shrink-0">
          <div>
            <div className="text-sm font-medium">Newsletter</div>
            <div className="text-xs text-muted-foreground">Sync Regiondo CRM</div>
          </div>
          <Toggle checked={newsletter} onChange={handleNewsletter} disabled={toggling} />
        </div>

        {/* Scroll area : réservations + devis + avis */}
        <div className="flex-1 overflow-y-auto">

          {/* Dernières réservations */}
          <SectionHeader icon={Calendar} label="Dernières réservations" count={bookings.length} />

          {loadingBk
            ? <LoadingBlock />
            : bookings.length === 0
              ? <EmptyBlock text="Aucune réservation trouvée." />
              : bookings.map((b, i) => (
                  <div key={b.id ?? i} className="flex items-start justify-between px-4 py-3 border-b last:border-0 hover:bg-muted/30 transition-colors">
                    <div className="min-w-0 flex-1">
                      <div className="text-sm font-medium truncate">{decodeHtml(b.product_name) || '—'}</div>
                      <div className="text-xs text-muted-foreground mt-0.5">{fmtDate(b.appointment_date)}</div>
                    </div>
                    <div className="text-right shrink-0 ml-3">
                      <div className="text-sm font-medium tabular-nums">
                        {b.price_total > 0 ? `${Number(b.price_total).toFixed(2)} €` : '—'}
                      </div>
                      <Badge variant={statusVariant(b.booking_status)} className="mt-1">
                        {b.booking_status || 'inconnu'}
                      </Badge>
                    </div>
                  </div>
                ))
          }

          {/* Demandes de devis */}
          <SectionHeader icon={Mail} label="Demandes de devis" count={forms.length} />

          {loadingFm
            ? <LoadingBlock />
            : forms.length === 0
              ? <EmptyBlock text="Aucune demande de devis." />
              : forms.map((f, i) => (
                  <a
                    key={f.id ?? i}
                    href={`#/forms`}
                    className="block px-4 py-3 border-b last:border-0 hover:bg-muted/30 transition-colors group"
                  >
                    <div className="flex items-start justify-between gap-2">
                      <div className="min-w-0 flex-1">
                        <div className="text-sm font-medium truncate group-hover:text-primary transition-colors">
                          {decodeHtml(f.excursion_name) || 'Devis'}
                        </div>
                        <div className="text-xs text-muted-foreground mt-0.5">
                          {[decodeHtml(f.boat_name), fmtDuration(f.duration_type)].filter(Boolean).join(' · ') || '—'}
                        </div>
                        {f.date_start && (
                          <div className="text-[11px] text-muted-foreground mt-0.5">
                            {f.date_start}{f.date_end && f.date_end !== f.date_start ? ` → ${f.date_end}` : ''}
                          </div>
                        )}
                      </div>
                      <div className="text-right shrink-0">
                        <Badge variant={f.email_sent ? 'ok' : 'error'} className="text-[10px]">
                          {f.email_sent ? 'Email envoyé' : 'Échec'}
                        </Badge>
                        <div className="text-[10px] text-muted-foreground mt-1">{fmtDate(f.created_at)}</div>
                      </div>
                    </div>
                    {f.message && (
                      <p className="text-xs text-muted-foreground mt-1.5 line-clamp-2 italic">"{f.message}"</p>
                    )}
                  </a>
                ))
          }

          {/* Avis clients */}
          <SectionHeader icon={Star} label="Avis" count={reviews.length} />

          {loadingRv
            ? <LoadingBlock />
            : reviews.length === 0
              ? <EmptyBlock text="Aucun avis importé pour ce client." />
              : reviews.map((r, i) => (
                  <div key={r.id ?? i} className="px-4 py-3 border-b last:border-0">
                    <div className="flex items-start justify-between gap-2 mb-1">
                      <div className="min-w-0 flex-1">
                        <div className="text-xs font-medium text-muted-foreground truncate">{r.product_name || '—'}</div>
                        {r.review_title && (
                          <div className="text-sm font-medium leading-tight mt-0.5">{r.review_title}</div>
                        )}
                      </div>
                      <div className="text-right shrink-0">
                        <MiniStars value={r.rating} />
                        <div className="text-[10px] text-muted-foreground mt-0.5">{fmtDate(r.review_date)}</div>
                      </div>
                    </div>
                    {r.review_body && (
                      <p className="text-xs text-muted-foreground leading-relaxed line-clamp-2">{r.review_body}</p>
                    )}
                    {r.response && (
                      <p className="text-xs text-primary/70 mt-1 line-clamp-1 italic">↩ {r.response}</p>
                    )}
                  </div>
                ))
          }
        </div>
      </div>
    </>
  )
}

/** En-tête de section sticky dans le drawer. */
function SectionHeader({ icon: Icon, label, count }) {
  return (
    <div className="flex items-center gap-2 px-4 py-2.5 border-b sticky top-0 bg-card z-10">
      <Icon className="w-3.5 h-3.5 text-muted-foreground" />
      <span className="text-xs uppercase tracking-wider text-muted-foreground font-medium">
        {label}{count != null ? ` (${count})` : ''}
      </span>
    </div>
  )
}

function LoadingBlock() {
  return <div className="flex justify-center py-8"><Spinner /></div>
}

function EmptyBlock({ text }) {
  return <p className="px-4 py-6 text-sm text-muted-foreground text-center">{text}</p>
}
