import { useState, useEffect, useMemo, useCallback, useRef } from 'react'
import { Sparks } from 'iconoir-react'

import EventsCorrelator from '../components/EventsCorrelator'
import FullCalendar from '@fullcalendar/react'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import listPlugin from '@fullcalendar/list'
import interactionPlugin from '@fullcalendar/interaction'
import multiMonthPlugin from '@fullcalendar/multimonth'
import frLocale from '@fullcalendar/core/locales/fr'
import { LeadTimeChart, WeekdayChart, CancellationChart, TopDays } from '../components/dashboard'
import { api } from '../lib/api'
import { today, daysAgo, fmtNum } from '../lib/utils'
import { COLORS } from '../lib/constants'
import { Btn, PageHeader, Notice, Spinner } from '../components/ui'
import { PeriodPicker, PERIOD_PRESETS_PLANNER } from '../components/PeriodPicker'
import DayDrawer from '../components/DayDrawer'
import CustomerDrawer from '../components/CustomerDrawer'

const STATUS_COLOR = {
  confirmed: '#10b981', booked: '#10b981', approved: '#10b981',
  completed: '#10b981', paid: '#10b981',
  canceled: '#ef4444', cancelled: '#ef4444', rejected: '#ef4444', refunded: '#ef4444',
}
const getColor = s => STATUS_COLOR[s?.toLowerCase()] ?? '#f59e0b'

function toYMD(d) {
  return [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-')
}

/** Returns first day of month containing dateStr */
function monthStart(dateStr) {
  const d = new Date(dateStr + 'T12:00:00')
  return toYMD(new Date(d.getFullYear(), d.getMonth(), 1))
}

/** Returns first day of the month AFTER the one containing dateStr (FC exclusive end) */
function monthEnd(dateStr) {
  const d = new Date(dateStr + 'T12:00:00')
  return toYMD(new Date(d.getFullYear(), d.getMonth() + 1, 1))
}

const WDAY_LABELS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const CANCEL_SET = new Set(['canceled', 'cancelled', 'rejected', 'refunded'])

// Initial period: 30 days
const INIT_FROM = daysAgo(29)
const INIT_TO   = today()

export default function Planner() {
  const calRef = useRef(null)

  // PeriodPicker is the sole controller of this range (API data)
  // datesSet is intentionally NOT used to avoid circular updates
  const [range, setRange] = useState({ from: INIT_FROM, to: INIT_TO })
  const [data, setData]       = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState(null)
  const [dayDrawer, setDayDrawer]           = useState(null)
  const [customerDrawer, setCustomerDrawer] = useState(null)
  const [eventsOpen, setEventsOpen]         = useState(false)
  const [calEvents, setCalEvents]           = useState([])

  useEffect(() => {
    setLoading(true)
    setError(null)
    api.planner(range.from, range.to)
      .then(setData)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [range])

  /** Charge les événements IA depuis la DB à chaque changement de période. */
  useEffect(() => {
    api.aiEvents({ from: range.from, to: range.to })
      .then(res => setCalEvents(res.events ?? []))
      .catch(() => setCalEvents([]))
  }, [range])

  /**
   * Period picker change → update data range + adapt FC view.
   * PeriodPicker is the only source of truth — no datesSet feedback loop.
   */
  function handlePeriodChange(from, to) {
    setRange({ from, to })
    const calApi = calRef.current?.getApi()
    if (!calApi) return

    const days = Math.round(
      (new Date(to + 'T12:00:00') - new Date(from + 'T12:00:00')) / 86400000
    )

    if (days <= 45) {
      // Single month
      calApi.changeView('dayGridMonth', from)
    } else if (days <= 380) {
      // Multi-month blocks: align to full calendar months
      calApi.changeView('multiMonth', { start: monthStart(from), end: monthEnd(to) })
    } else {
      // Long range → list view
      calApi.changeView('listMonth', from)
    }
  }

  // Index date → bookings
  const byDate = useMemo(() =>
    Object.fromEntries((data?.calendar ?? []).map(d => [d.date, d.bookings]))
  , [data])

  // Events FullCalendar — réservations
  const events = useMemo(() =>
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

  /**
   * Fusion des réservations et des événements IA pour FullCalendar.
   * Les événements IA sont affichés en violet avec un emoji 🎉.
   */
  const allEvents = useMemo(() => [
    ...events,
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
  ], [events, calEvents])

  // Stats période
  const stats = useMemo(() => {
    let total = 0, confirmed = 0, cancelled = 0
    for (const bks of Object.values(byDate)) {
      for (const b of bks) {
        total++
        const s = b.status?.toLowerCase() ?? ''
        if (['canceled','cancelled','rejected','refunded'].includes(s)) cancelled++
        else confirmed++
      }
    }
    return { total, confirmed, cancelled }
  }, [byDate])

  /**
   * Données de réservations par jour au format attendu par EventsCorrelator.
   * Calculé depuis data.calendar (même source que le calendrier FullCalendar).
   */
  const plannerBookingsData = useMemo(() =>
    (data?.calendar ?? []).map(day => {
      const cancelled = day.bookings.filter(b => CANCEL_SET.has(b.status?.toLowerCase() ?? '')).length
      return {
        key:       day.date,
        bookings:  day.bookings.length,
        confirmed: day.bookings.length - cancelled,
        cancelled,
        revenue:   day.bookings.reduce((s, b) => s + parseFloat(b.total_price ?? 0), 0),
      }
    })
  , [data])

  // Top 7 jours par volume
  const topDays = useMemo(() =>
    Object.entries(byDate)
      .map(([date, bks]) => ({ date, count: bks.length }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 7)
  , [byDate])

  const fmtDayShort = d => new Date(d + 'T12:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })

  // Weekday distribution
  const weekdayDist = useMemo(() => {
    const counts = Array(7).fill(0)
    for (const day of (data?.calendar ?? [])) {
      const dow = new Date(day.date + 'T12:00:00').getDay()
      counts[dow === 0 ? 6 : dow - 1] += day.bookings.length
    }
    const max = Math.max(...counts, 1)
    return WDAY_LABELS.map((label, i) => ({ label, count: counts[i], pct: Math.round((counts[i] / max) * 100) }))
  }, [data])

  /**
   * Taux d'annulation groupé par jour (≤31j) ou par mois (>31j).
   * Calculé depuis data.calendar qui contient chaque réservation avec son statut.
   */
  const cancellationData = useMemo(() => {
    const days = data?.calendar ?? []
    if (!days.length) return []

    const rangeDays = Math.round(
      (new Date(range.to + 'T12:00:00') - new Date(range.from + 'T12:00:00')) / 86400000
    )

    if (rangeDays <= 31) {
      // Granularité : jour
      return days
        .filter(d => d.bookings.length > 0)
        .map(d => ({
          label:     fmtDayShort(d.date),
          total:     d.bookings.length,
          cancelled: d.bookings.filter(b => CANCEL_SET.has(b.status?.toLowerCase() ?? '')).length,
        }))
    } else {
      // Granularité : mois
      const months = {}
      for (const d of days) {
        const mk = d.date.slice(0, 7)
        if (!months[mk]) months[mk] = { total: 0, cancelled: 0 }
        months[mk].total     += d.bookings.length
        months[mk].cancelled += d.bookings.filter(b => CANCEL_SET.has(b.status?.toLowerCase() ?? '')).length
      }
      return Object.entries(months)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([mk, v]) => ({
          label: new Date(mk + '-01T12:00:00').toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' }),
          ...v,
        }))
    }
  }, [data, range, fmtDayShort])

  const openDay = useCallback(date => {
    const bookings = byDate[date]
    if (bookings?.length) setDayDrawer({ date, bookings })
  }, [byDate])


  return (
    <div>
      <PageHeader
        title="Planificateur"
        subtitle="Réservations par date d'activité"
        actions={!loading && stats.total > 0 && (
          <span className="text-xs text-muted-foreground">{stats.total} réservations</span>
        )}
      />

      {/* ── Period picker ─────────────────────────────────────────────── */}
      <div className="px-6 py-3 border-b bg-muted/30 flex items-center gap-3 flex-wrap">
        <PeriodPicker from={INIT_FROM} to={INIT_TO} onChange={handlePeriodChange} presets={PERIOD_PRESETS_PLANNER} />
        <span className="bt-ai-wrap">
          <Btn variant="ghost" size="sm" onClick={() => setEventsOpen(true)} className="gap-1.5 text-xs">
            <Sparks width={13} height={13} />
            Événements IA
          </Btn>
        </span>
      </div>

      {error && <div className="px-6 pt-5"><Notice type="error">{error}</Notice></div>}

      {/* ── Stats strip ───────────────────────────────────────────────── */}
      {!loading && stats.total > 0 && (
        <div className="flex divide-x border-b bg-card">
          {[
            ['Total',      stats.total,      ''],
            ['Confirmées', stats.confirmed,  'text-emerald-600'],
            ['Annulées',   stats.cancelled,  'text-red-500'],
          ].map(([label, value, cls]) => (
            <div key={label} className="flex-1 px-4 py-3 text-center">
              <div className={`text-xl font-bold tabular-nums ${cls}`}>{value}</div>
              <div className="text-[11px] text-muted-foreground mt-0.5">{label}</div>
            </div>
          ))}
        </div>
      )}

      {/* ── Analytics — trois graphiques ─────────────────────────────── */}
      {!loading && stats.total > 0 && (
        <div className="border-b bg-card">
          <div className="grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x">

            {/* Activité par jour */}
            <div className="px-6 py-4">
              <WeekdayChart data={weekdayDist} height={130} />
            </div>

            {/* Avance de réservation */}
            <div className="px-6 py-4">
              <LeadTimeChart data={data?.lead_time_buckets ?? []} />
            </div>

            {/* Taux d'annulation */}
            <div className="px-6 py-4">
              <CancellationChart data={cancellationData} />
            </div>

          </div>
        </div>
      )}

      {/* ── Top 7 jours ──────────────────────────────────────────────── */}
      {!loading && topDays.length > 0 && (
        <div className="border-b bg-card px-6 py-4">
          <TopDays
            data={topDays}
            total={stats.total}
            onClickDay={openDay}
            formatDate={fmtDayShort}
          />
        </div>
      )}

      {/* ── Calendar ─────────────────────────────────────────────────── */}
      <div className="mx-6 mt-5 mb-10 rounded-xl border bg-card shadow-sm overflow-hidden relative">
        {loading && (
          <div className="absolute inset-0 flex items-center justify-center bg-card/80 z-10">
            <Spinner size={20} />
          </div>
        )}

        <div className="flex items-center gap-4 px-5 py-3 border-b">
          {[['#10b981','Confirmé'], ['#f59e0b','En attente'], ['#ef4444','Annulé']].map(([c, l]) => (
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
            ref={calRef}
            plugins={[dayGridPlugin, multiMonthPlugin, timeGridPlugin, listPlugin, interactionPlugin]}
            initialView="dayGridMonth"
            initialDate={INIT_FROM}
            locale={frLocale}
            headerToolbar={{ left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' }}
            buttonText={{ dayGridMonth: 'Mois', timeGridWeek: 'Semaine', listMonth: 'Liste' }}
            multiMonthMaxColumns={3}
            height="auto"
            events={allEvents}
            eventClick={({ event }) => openDay(event.extendedProps.dayDate)}
            dateClick={({ dateStr }) => openDay(dateStr)}
            moreLinkClick={({ date }) => openDay(toYMD(date))}
            dayMaxEvents={3}
          />
        </div>
      </div>

      {dayDrawer && (
        <DayDrawer {...dayDrawer} onClose={() => setDayDrawer(null)} onOpenCustomer={setCustomerDrawer} />
      )}
      {customerDrawer && (
        <CustomerDrawer customer={customerDrawer} onClose={() => setCustomerDrawer(null)} elevated />
      )}

      <EventsCorrelator
        open={eventsOpen}
        onClose={() => setEventsOpen(false)}
        from={range.from}
        to={range.to}
        bookingsData={plannerBookingsData}
      />
    </div>
  )
}
