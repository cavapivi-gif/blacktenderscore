/**
 * Hook de données pour le Planificateur.
 * Fetch planner bookings + AI events, calcule stats dérivées.
 */
import { useState, useEffect, useMemo, useCallback } from 'react'
import { api } from '../lib/api'

const CANCEL_SET = new Set(['canceled', 'cancelled', 'rejected', 'refunded'])
const WDAY_LABELS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

/**
 * @param {{ from: string, to: string }} range
 */
export function usePlannerData(range) {
  const [data, setData]         = useState(null)
  const [loading, setLoading]   = useState(true)
  const [error, setError]       = useState(null)
  const [calEvents, setCalEvents] = useState([])

  // Fetch planner data
  useEffect(() => {
    setLoading(true)
    setError(null)
    api.planner(range.from, range.to)
      .then(setData)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [range.from, range.to])

  // Fetch AI events
  useEffect(() => {
    api.aiEvents({ from: range.from, to: range.to })
      .then(res => setCalEvents(res.events ?? []))
      .catch(() => setCalEvents([]))
  }, [range.from, range.to])

  // Index date → bookings
  const byDate = useMemo(() =>
    Object.fromEntries((data?.calendar ?? []).map(d => [d.date, d.bookings]))
  , [data])

  // Stats période
  const stats = useMemo(() => {
    let total = 0, confirmed = 0, cancelled = 0
    for (const bks of Object.values(byDate)) {
      for (const b of bks) {
        total++
        if (CANCEL_SET.has(b.status?.toLowerCase() ?? '')) cancelled++
        else confirmed++
      }
    }
    return { total, confirmed, cancelled }
  }, [byDate])

  // Données pour EventsCorrelator
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

  // Top 7 jours
  const topDays = useMemo(() =>
    Object.entries(byDate)
      .map(([date, bks]) => ({ date, count: bks.length }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 7)
  , [byDate])

  // Taux d'annulation par jour ou mois
  const cancellationData = useMemo(() => {
    const days = data?.calendar ?? []
    if (!days.length) return []
    const rangeDays = Math.round(
      (new Date(range.to + 'T12:00:00') - new Date(range.from + 'T12:00:00')) / 86400000
    )
    const fmtDay = d => new Date(d + 'T12:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })

    if (rangeDays <= 31) {
      return days.filter(d => d.bookings.length > 0).map(d => ({
        label:     fmtDay(d.date),
        total:     d.bookings.length,
        cancelled: d.bookings.filter(b => CANCEL_SET.has(b.status?.toLowerCase() ?? '')).length,
      }))
    }
    const months = {}
    for (const d of days) {
      const mk = d.date.slice(0, 7)
      if (!months[mk]) months[mk] = { total: 0, cancelled: 0 }
      months[mk].total     += d.bookings.length
      months[mk].cancelled += d.bookings.filter(b => CANCEL_SET.has(b.status?.toLowerCase() ?? '')).length
    }
    return Object.entries(months).sort(([a], [b]) => a.localeCompare(b)).map(([mk, v]) => ({
      label: new Date(mk + '-01T12:00:00').toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' }),
      ...v,
    }))
  }, [data, range.from, range.to])

  // Open day callback helper
  const openDay = useCallback(date => {
    const bookings = byDate[date]
    return bookings?.length ? { date, bookings } : null
  }, [byDate])

  return {
    data, loading, error, calEvents, byDate,
    stats, plannerBookingsData, weekdayDist, topDays, cancellationData,
    openDay, leadTimeBuckets: data?.lead_time_buckets ?? [],
  }
}
