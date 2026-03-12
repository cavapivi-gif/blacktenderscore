import { Xmark, User } from 'iconoir-react'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import { Badge } from './ui'
import { STATUS_MAP } from '../lib/status'

/**
 * Drawer latéral affiché au clic sur un jour du planificateur.
 * Liste les réservations du jour avec lien vers le CustomerDrawer.
 */
export default function DayDrawer({ date, bookings, onClose, onOpenCustomer }) {
  const confirmed = bookings.filter(b => !['canceled','cancelled','rejected','refunded'].includes(b.status?.toLowerCase()))
  const fmtDay = format(new Date(date + 'T12:00:00'), 'EEEE d MMMM yyyy', { locale: fr })

  return (
    <>
      <div className="fixed inset-0 bg-black/30 z-40" onClick={onClose} />
      <div className="fixed right-0 top-0 bottom-0 w-[420px] bg-card border-l shadow-2xl z-50 flex flex-col overflow-hidden">

        {/* Header */}
        <div className="flex items-center justify-between px-5 py-4 border-b shrink-0">
          <div>
            <div className="font-semibold text-sm capitalize">{fmtDay}</div>
            <div className="text-xs text-muted-foreground mt-0.5">
              {bookings.length} réservation{bookings.length > 1 ? 's' : ''}
              {' · '}{confirmed.length} confirmée{confirmed.length > 1 ? 's' : ''}
            </div>
          </div>
          <button onClick={onClose} className="p-1.5 rounded-md hover:bg-accent transition-colors shrink-0">
            <Xmark className="w-4 h-4" />
          </button>
        </div>

        {/* Liste des réservations */}
        <div className="flex-1 overflow-y-auto">
          <div className="flex items-center gap-2 px-4 py-2.5 border-b sticky top-0 bg-card text-xs uppercase tracking-wider text-muted-foreground font-medium">
            Réservations du jour
          </div>

          {bookings.map((b, i) => {
            const s = STATUS_MAP[b.status] ?? { variant: 'default', label: b.status ?? '—' }
            return (
              <div key={b.booking_ref ?? i} className="px-4 py-3 border-b last:border-0 hover:bg-muted/30 transition-colors">
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0 flex-1">
                    <div className="text-sm font-medium truncate">{b.product_name || '—'}</div>
                    <div className="text-[11px] text-muted-foreground font-mono mt-0.5">{b.booking_ref || '—'}</div>
                  </div>
                  <div className="shrink-0">
                    <Badge variant={s.variant}>{s.label}</Badge>
                  </div>
                </div>

                {/* Lien client → cross-function avec CustomerDrawer */}
                {b.customer_name && (
                  <button
                    className="flex items-center gap-1.5 mt-2 text-xs text-primary hover:underline"
                    onClick={() => onOpenCustomer?.({
                      name: b.customer_name,
                      email: b.customer_email,
                      bookings_count: '—',
                      total_spent: 0,
                      avis_count: 0,
                    })}
                  >
                    <User className="w-3 h-3" />
                    {b.customer_name}
                  </button>
                )}
              </div>
            )
          })}
        </div>
      </div>
    </>
  )
}
