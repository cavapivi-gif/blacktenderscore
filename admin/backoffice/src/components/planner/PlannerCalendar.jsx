/**
 * Wrapper FullCalendar pour le Planificateur.
 * Affiche les réservations (dots colorés) + événements IA (violet 🎉).
 */
import { useMemo, forwardRef } from 'react'
import FullCalendar from '@fullcalendar/react'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import listPlugin from '@fullcalendar/list'
import interactionPlugin from '@fullcalendar/interaction'
import multiMonthPlugin from '@fullcalendar/multimonth'
import frLocale from '@fullcalendar/core/locales/fr'
import { Spinner } from '../ui'

const STATUS_COLOR = {
  confirmed: '#10b981', booked: '#10b981', approved: '#10b981',
  completed: '#10b981', paid: '#10b981',
  canceled: '#ef4444', cancelled: '#ef4444', rejected: '#ef4444', refunded: '#ef4444',
}
const getColor = s => STATUS_COLOR[s?.toLowerCase()] ?? '#f59e0b'

const LEGEND = [['#10b981','Confirmé'], ['#f59e0b','En attente'], ['#ef4444','Annulé']]

const PlannerCalendar = forwardRef(function PlannerCalendar({ data, calEvents, loading, initialDate, onDayClick }, ref) {
  // Réservations → events FullCalendar
  const bookingEvents = useMemo(() =>
    (data?.calendar ?? []).flatMap(day =>
      day.bookings.map(b => ({
        id: b.booking_ref || Math.random().toString(),
        title: b.product_name || '—',
        date: day.date,
        allDay: true,
        backgroundColor: getColor(b.status),
        borderColor: getColor(b.status),
        textColor: '#fff',
        extendedProps: { dayDate: day.date },
      }))
    )
  , [data])

  // Fusion réservations + événements IA
  const allEvents = useMemo(() => [
    ...bookingEvents,
    ...calEvents.map(e => ({
      id: 'bt-event-' + e.id,
      title: '🎉 ' + e.name,
      start: e.date_start,
      end: e.date_end ? e.date_end + 'T23:59:59' : undefined,
      allDay: true,
      backgroundColor: '#7c3aed22',
      borderColor: '#7c3aed',
      textColor: '#7c3aed',
      classNames: ['bt-calendar-event'],
      extendedProps: { type: 'bt_event', location: e.location },
    })),
  ], [bookingEvents, calEvents])

  return (
    <div className="mx-6 mt-5 mb-10 rounded-xl border bg-card shadow-sm overflow-hidden relative">
      {loading && (
        <div className="absolute inset-0 flex items-center justify-center bg-card/80 z-10">
          <Spinner size={20} />
        </div>
      )}

      <div className="flex items-center gap-4 px-5 py-3 border-b">
        {LEGEND.map(([c, l]) => (
          <span key={l} className="flex items-center gap-1.5 text-xs text-muted-foreground">
            <span className="w-2.5 h-2.5 rounded-full shrink-0" style={{ background: c }} />
            {l}
          </span>
        ))}
        <span className="text-[10px] text-muted-foreground ml-auto">
          Naviguez avec ← → ou changez la période ci-dessus
        </span>
      </div>

      <div className="p-5 fc-planner">
        <FullCalendar
          ref={ref}
          plugins={[dayGridPlugin, multiMonthPlugin, timeGridPlugin, listPlugin, interactionPlugin]}
          initialView="dayGridMonth"
          initialDate={initialDate}
          locale={frLocale}
          headerToolbar={{ left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' }}
          buttonText={{ dayGridMonth: 'Mois', timeGridWeek: 'Semaine', listMonth: 'Liste' }}
          multiMonthMaxColumns={3}
          height="auto"
          events={allEvents}
          eventClick={({ event }) => onDayClick(event.extendedProps.dayDate)}
          dateClick={({ dateStr }) => onDayClick(dateStr)}
          moreLinkClick={({ date }) => {
            const d = date
            onDayClick([d.getFullYear(), String(d.getMonth()+1).padStart(2,'0'), String(d.getDate()).padStart(2,'0')].join('-'))
          }}
          dayMaxEvents={3}
        />
      </div>
    </div>
  )
})

export default PlannerCalendar
