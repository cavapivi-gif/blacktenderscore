import { useState, useEffect, useMemo, useCallback, useRef } from 'react'
import { useSearchParams } from 'react-router-dom'
import { api } from '../lib/api'
import { STATUS_MAP } from '../lib/status'
import { Calendar } from '../components/Calendar'
import { TooltipProvider } from '../components/Tooltip'
import { PageHeader, Notice, Spinner, Badge } from '../components/ui'

// ── Helpers ───────────────────────────────────────────────────────────────────

function matchFilter(status, filter) {
  if (filter === 'all') return true
  const variant = STATUS_MAP[status]?.variant
  if (filter === 'confirmed') return variant === 'confirmed'
  if (filter === 'pending')   return variant === 'pending'
  if (filter === 'cancelled') return variant === 'cancelled'
  return true
}

const STATUS_FILTERS = [
  { key: 'all',       label: 'Tous' },
  { key: 'confirmed', label: 'Confirmés' },
  { key: 'pending',   label: 'En attente' },
  { key: 'cancelled', label: 'Annulés' },
]

function toYMD(date) {
  return [
    date.getFullYear(),
    String(date.getMonth() + 1).padStart(2, '0'),
    String(date.getDate()).padStart(2, '0'),
  ].join('-')
}

function monthBounds(date) {
  const y = date.getFullYear()
  const m = date.getMonth() + 1
  const from = `${y}-${String(m).padStart(2, '0')}-01`
  const to   = toYMD(new Date(y, m, 0))
  return { from, to }
}

function fmtDateShort(dateStr) {
  return new Date(dateStr + 'T12:00:00').toLocaleDateString('fr-FR', {
    weekday: 'short', day: 'numeric', month: 'short',
  })
}

function fmtDateLong(dateStr) {
  return new Date(dateStr + 'T12:00:00').toLocaleDateString('fr-FR', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  })
}

function isToday(dateStr) {
  return dateStr === toYMD(new Date())
}

function isPast(dateStr) {
  return dateStr < toYMD(new Date())
}

function fmtRevenue(amount) {
  if (amount == null) return null
  return Number(amount).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 })
}

// ── Booking card inline (timeline) ────────────────────────────────────────────
function BookingRow({ b }) {
  const s = STATUS_MAP[b.status] ?? { variant: 'default', label: b.status ?? '—' }
  return (
    <div className="flex items-start gap-3 py-2.5 border-b last:border-0">
      {/* Produit + ref */}
      <div className="flex-1 min-w-0">
        <p className="text-sm font-medium truncate">{b.product_name || '—'}</p>
        <p className="text-[11px] text-muted-foreground font-mono mt-0.5">{b.booking_ref || '—'}</p>
        {b.customer_name && (
          <p className="text-xs text-muted-foreground mt-0.5">{b.customer_name}</p>
        )}
      </div>

      {/* Prix + statut */}
      <div className="flex flex-col items-end gap-1 shrink-0">
        {b.total_price != null && (
          <span className={[
            'text-sm font-semibold tabular-nums',
            Number(b.total_price) < 0 ? 'text-destructive' : '',
          ].join(' ')}>
            {fmtRevenue(b.total_price)}
          </span>
        )}
        <Badge variant={s.variant} className="text-[10px] px-2 py-0.5">{s.label}</Badge>
      </div>
    </div>
  )
}

// ── Day block (timeline entry) ────────────────────────────────────────────────
function DayBlock({ date, bookings, isScrollTarget }) {
  const ref = useRef(null)

  // Scroll to this block when selected from calendar
  useEffect(() => {
    if (isScrollTarget && ref.current) {
      ref.current.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, [isScrollTarget])

  const past    = isPast(date)
  const today   = isToday(date)
  const revenue = bookings.reduce((acc, b) => {
    if (!['canceled','cancelled','rejected'].includes(b.status?.toLowerCase())) {
      return acc + Number(b.total_price ?? 0)
    }
    return acc
  }, 0)

  return (
    <div
      ref={ref}
      id={`day-${date}`}
      className={[
        'rounded-xl border bg-card shadow-sm overflow-hidden transition-all',
        today   ? 'ring-2 ring-primary'    : '',
        past    ? 'opacity-60'             : '',
      ].join(' ')}
    >
      {/* En-tête du jour */}
      <div className={[
        'flex items-center justify-between px-4 py-3 border-b',
        today ? 'bg-primary/5' : 'bg-muted/30',
      ].join(' ')}>
        <div className="flex items-center gap-2">
          {today && (
            <span className="text-[10px] font-bold uppercase tracking-wider text-primary bg-primary/10 px-1.5 py-0.5 rounded">
              Aujourd'hui
            </span>
          )}
          <span className={[
            'text-sm font-semibold capitalize',
            today ? 'text-primary' : '',
          ].join(' ')}>
            {fmtDateShort(date)}
          </span>
        </div>

        <div className="flex items-center gap-3">
          {/* CA du jour */}
          {revenue !== 0 && (
            <span className="text-xs font-semibold text-emerald-600 tabular-nums">
              {fmtRevenue(revenue)}
            </span>
          )}
          {/* Compteur */}
          <span className="text-xs text-muted-foreground">
            {bookings.length} rés.
          </span>
        </div>
      </div>

      {/* Liste des bookings */}
      <div className="px-4 divide-y divide-border/50">
        {bookings.map((b, i) => (
          <BookingRow key={b.booking_ref ?? i} b={b} />
        ))}
      </div>
    </div>
  )
}

// ── Component principal ────────────────────────────────────────────────────────
export default function Planner() {
  const [searchParams] = useSearchParams()

  const [month, setMonth] = useState(() => {
    const param = searchParams.get('month')
    if (param && /^\d{4}-\d{2}$/.test(param)) {
      const d = new Date(param + '-01T12:00:00')
      if (!isNaN(d)) return d
    }
    const d = new Date(); d.setDate(1); return d
  })

  const [data, setData]               = useState(null)
  const [loading, setLoading]         = useState(true)
  const [error, setError]             = useState(null)
  const [selectedDay, setSelectedDay] = useState(null)
  const [filterStatus, setFilterStatus] = useState('all')

  // ── Fetch on month change ──────────────────────────────────────────────────
  useEffect(() => {
    setLoading(true)
    setError(null)
    const { from, to } = monthBounds(month)
    api.planner(from, to)
      .then(setData)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [month])

  // ── Index brut par date ────────────────────────────────────────────────────
  const bookingsByDate = useMemo(() => {
    if (!data?.calendar) return {}
    return Object.fromEntries(data.calendar.map(d => [d.date, d.bookings]))
  }, [data])

  // ── Filtrage par statut ────────────────────────────────────────────────────
  const filteredByDate = useMemo(() => {
    if (filterStatus === 'all') return bookingsByDate
    const result = {}
    for (const [date, bookings] of Object.entries(bookingsByDate)) {
      const filtered = bookings.filter(b => matchFilter(b.status, filterStatus))
      if (filtered.length > 0) result[date] = filtered
    }
    return result
  }, [bookingsByDate, filterStatus])

  // ── Jours avec réservations (pour modifiersClassName calendrier) ───────────
  const daysWithBookings = useMemo(
    () => Object.keys(filteredByDate).map(d => new Date(d + 'T12:00:00')),
    [filteredByDate],
  )

  // ── Badge count par jour (calendrier) ─────────────────────────────────────
  const dayCountMap = useMemo(() => {
    const map = {}
    for (const [date, bookings] of Object.entries(filteredByDate)) {
      map[date] = bookings.length
    }
    return map
  }, [filteredByDate])

  const totalThisMonth = useMemo(
    () => Object.values(filteredByDate).reduce((acc, arr) => acc + arr.length, 0),
    [filteredByDate],
  )

  const revenueThisMonth = useMemo(() => {
    let total = 0
    for (const bookings of Object.values(filteredByDate)) {
      for (const b of bookings) {
        if (!['canceled','cancelled','rejected'].includes(b.status?.toLowerCase())) {
          total += Number(b.total_price ?? 0)
        }
      }
    }
    return total
  }, [filteredByDate])

  // ── DayButton custom pour le calendrier ───────────────────────────────────
  const dayCountMapRef = useRef(dayCountMap)
  useEffect(() => { dayCountMapRef.current = dayCountMap }, [dayCountMap])

  const DayButton = useCallback(function CustomDayButton({ children, day, modifiers, ...props }) {
    const ymd   = day?.date ? toYMD(day.date) : null
    const count = ymd ? (dayCountMapRef.current[ymd] ?? 0) : 0
    return (
      <button {...props}>
        {children}
        {count > 0 && (
          <span className="absolute bottom-0.5 left-0 right-0 flex justify-center pointer-events-none">
            <span className="text-[7px] leading-none font-bold px-0.5 rounded-sm bg-primary text-primary-foreground min-w-[12px] text-center">
              {count > 9 ? '9+' : count}
            </span>
          </span>
        )}
      </button>
    )
  }, [])

  // ── Clic calendrier → scroll vers le day block ────────────────────────────
  function handleDayClick(day) {
    const ymd = toYMD(day)
    if (!filteredByDate[ymd]?.length) return
    setSelectedDay(ymd)
  }

  // ── Tri des dates pour la timeline ────────────────────────────────────────
  const sortedDates = useMemo(
    () => Object.keys(filteredByDate).sort(),
    [filteredByDate],
  )

  // ── Render ────────────────────────────────────────────────────────────────
  return (
    <TooltipProvider>
      <div>
        <PageHeader
          title="Planificateur"
          subtitle="Réservations par date d'activité"
          actions={
            !loading && totalThisMonth > 0 && (
              <div className="flex items-center gap-4 text-xs text-muted-foreground">
                <span>{totalThisMonth} réservation{totalThisMonth > 1 ? 's' : ''}</span>
                {revenueThisMonth !== 0 && (
                  <span className="font-semibold text-emerald-600">{fmtRevenue(revenueThisMonth)}</span>
                )}
              </div>
            )
          }
        />

        {/* ── Filtre statut ─────────────────────────────────────────────── */}
        <div className="px-6 py-3 border-b flex items-center gap-2">
          {STATUS_FILTERS.map(f => (
            <button
              key={f.key}
              onClick={() => setFilterStatus(f.key)}
              className={[
                'px-3 py-1.5 text-xs border rounded-md transition-colors',
                filterStatus === f.key
                  ? 'bg-primary text-primary-foreground border-primary'
                  : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground',
              ].join(' ')}
            >
              {f.label}
            </button>
          ))}
        </div>

        {error && (
          <div className="px-6 pt-5">
            <Notice type="error">{error}</Notice>
          </div>
        )}

        {/* ── Layout 2 colonnes ─────────────────────────────────────────── */}
        <div className="px-6 pt-5 pb-10">
          <div className="grid grid-cols-1 lg:grid-cols-[340px_1fr] gap-6 items-start">

            {/* ── Colonne gauche : calendrier ─────────────────────────── */}
            <div className="sticky top-6">
              <div className="rounded-xl border bg-card p-5 shadow-sm relative">
                {loading && (
                  <div className="absolute inset-0 flex items-center justify-center bg-card/70 rounded-xl z-10">
                    <Spinner size={20} />
                  </div>
                )}

                <Calendar
                  mode="single"
                  month={month}
                  onMonthChange={m => { setMonth(m); setSelectedDay(null) }}
                  selected={selectedDay ? new Date(selectedDay + 'T12:00:00') : undefined}
                  onDayClick={handleDayClick}
                  modifiers={{ hasBookings: daysWithBookings }}
                  modifiersClassNames={{ hasBookings: 'has-bookings' }}
                  components={{ DayButton }}
                />

                {/* Légende */}
                <div className="flex items-center gap-5 mt-4 pt-4 border-t text-[11px] text-muted-foreground">
                  <span className="flex items-center gap-1.5">
                    <span className="w-5 h-5 rounded-md bg-primary/15 border border-primary/30 inline-flex items-center justify-center">
                      <span className="w-1.5 h-1.5 rounded-full bg-primary" />
                    </span>
                    Avec réservation(s)
                  </span>
                  <span className="flex items-center gap-1.5">
                    <span className="w-5 h-5 rounded-md ring-2 ring-primary inline-block" />
                    Aujourd'hui
                  </span>
                </div>
              </div>

              {/* KPIs du mois */}
              {!loading && totalThisMonth > 0 && (
                <div className="mt-4 grid grid-cols-2 gap-3">
                  <div className="rounded-lg border bg-card p-3 text-center">
                    <p className="text-2xl font-bold">{totalThisMonth}</p>
                    <p className="text-[11px] text-muted-foreground mt-0.5">réservations</p>
                  </div>
                  <div className="rounded-lg border bg-card p-3 text-center">
                    <p className="text-2xl font-bold text-emerald-600 tabular-nums">
                      {revenueThisMonth >= 0 ? '' : '-'}{fmtRevenue(Math.abs(revenueThisMonth))}
                    </p>
                    <p className="text-[11px] text-muted-foreground mt-0.5">CA confirmé</p>
                  </div>
                </div>
              )}
            </div>

            {/* ── Colonne droite : timeline ──────────────────────────── */}
            <div>
              {loading && (
                <div className="flex items-center gap-2 text-sm text-muted-foreground py-6">
                  <Spinner size={14} /> Chargement…
                </div>
              )}

              {!loading && sortedDates.length === 0 && !error && (
                <div className="rounded-xl border bg-card p-10 text-center">
                  <p className="text-sm text-muted-foreground">
                    {filterStatus === 'all'
                      ? 'Aucune réservation ce mois-ci.'
                      : 'Aucune réservation avec ce filtre.'}
                  </p>
                </div>
              )}

              {!loading && sortedDates.length > 0 && (
                <div className="space-y-3">
                  {sortedDates.map(date => (
                    <DayBlock
                      key={date}
                      date={date}
                      bookings={filteredByDate[date]}
                      isScrollTarget={date === selectedDay}
                    />
                  ))}
                </div>
              )}
            </div>

          </div>
        </div>
      </div>

      {/* ── CSS : .has-bookings sur <td> en v9, on cible le <button> dedans ── */}
      <style>{`
        #bt-backoffice-root td.has-bookings > button {
          background-color: color-mix(in srgb, var(--primary) 10%, transparent) !important;
        }
        #bt-backoffice-root td.has-bookings[aria-selected="true"] > button {
          background-color: var(--primary) !important;
          color: var(--primary-foreground) !important;
        }
      `}</style>
    </TooltipProvider>
  )
}
