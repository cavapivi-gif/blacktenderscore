import { useState, useEffect } from 'react'
import { Xmark, Calendar, CreditCard, Star, ShoppingBagCheck } from 'iconoir-react'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import { api } from '../lib/api'
import { Badge, Spinner, Toggle } from './ui'

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

const fmtDate = d => (!d || d === '0000-00-00') ? '—' : format(new Date(d), 'd MMM yyyy', { locale: fr })
const statusVariant = s => ({ confirmed: 'confirmed', cancelled: 'cancelled', rejected: 'cancelled' }[s] ?? 'pending')

export default function CustomerDrawer({ customer: c, onClose, onUpdate }) {
  const [bookings, setBookings]     = useState([])
  const [loadingBk, setLoadingBk]   = useState(true)
  const [newsletter, setNewsletter] = useState(c.newsletter ?? false)
  const [toggling, setToggling]     = useState(false)

  useEffect(() => {
    api.reservations({ search: c.email, per_page: 8, page: 1 })
      .then(r => setBookings(r.data ?? []))
      .catch(() => {})
      .finally(() => setLoadingBk(false))
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
      {/* Overlay */}
      <div className="fixed inset-0 bg-black/30 z-40" onClick={onClose} />

      {/* Panel slide-in */}
      <div className="fixed right-0 top-0 bottom-0 w-[380px] bg-card border-l shadow-2xl z-50 flex flex-col overflow-hidden">

        {/* Header */}
        <div className="flex items-center justify-between px-5 py-4 border-b shrink-0">
          <div className="flex items-center gap-3 min-w-0">
            <div className="w-10 h-10 rounded-full bg-secondary flex items-center justify-center text-sm font-bold shrink-0">
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
        <div className="grid grid-cols-3 gap-2 px-4 py-3 border-b shrink-0">
          <KpiCard icon={ShoppingBagCheck} label="Résa"  value={c.bookings_count} />
          <KpiCard icon={CreditCard} label="CA"    value={c.total_spent > 0 ? `${c.total_spent.toFixed(0)} €` : '—'} />
          <KpiCard icon={Star}       label="Avis"  value={c.avis_count ?? 0} />
        </div>

        {/* Newsletter toggle */}
        <div className="flex items-center justify-between px-4 py-3 border-b shrink-0">
          <div>
            <div className="text-sm font-medium">Newsletter</div>
            <div className="text-xs text-muted-foreground">Sync Regiondo CRM</div>
          </div>
          <Toggle checked={newsletter} onChange={handleNewsletter} disabled={toggling} />
        </div>

        {/* Dernières réservations */}
        <div className="flex-1 overflow-y-auto">
          <div className="flex items-center gap-2 px-4 py-2.5 border-b sticky top-0 bg-card">
            <Calendar className="w-3.5 h-3.5 text-muted-foreground" />
            <span className="text-xs uppercase tracking-wider text-muted-foreground font-medium">Dernières réservations</span>
          </div>

          {loadingBk
            ? <div className="flex justify-center py-10"><Spinner /></div>
            : bookings.length === 0
              ? <p className="px-4 py-8 text-sm text-muted-foreground text-center">Aucune réservation trouvée.</p>
              : bookings.map((b, i) => (
                  <div key={b.id ?? i} className="flex items-start justify-between px-4 py-3 border-b last:border-0 hover:bg-muted/30 transition-colors">
                    <div className="min-w-0 flex-1">
                      <div className="text-sm font-medium truncate">{b.product_name || '—'}</div>
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
        </div>
      </div>
    </>
  )
}
