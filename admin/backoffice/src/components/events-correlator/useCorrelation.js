/**
 * Hook de calcul de corrélation événements ↔ réservations.
 * Extrait d'EventsCorrelator pour réutilisabilité et testabilité.
 */
import { useMemo } from 'react'

const DAY_MS = 86400000

/**
 * Calcule la fenêtre temporelle d'influence d'un événement.
 * @param {{ date_start: string, date_end: string }} event
 * @param {string} windowFilter - '±2j' | '±7j' | '7j_before_after' | 'full'
 * @returns {{ wFrom: number, wTo: number }} timestamps ms
 */
function getEventWindow(event, windowFilter) {
  const startMs = new Date(event.date_start + 'T12:00:00').getTime()
  const endMs   = new Date(event.date_end   + 'T12:00:00').getTime()
  if (windowFilter === '±2j')             return { wFrom: startMs - 2 * DAY_MS, wTo: startMs + 2 * DAY_MS }
  if (windowFilter === '±7j')             return { wFrom: startMs - 7 * DAY_MS, wTo: startMs + 7 * DAY_MS }
  if (windowFilter === '7j_before_after') return { wFrom: startMs - 7 * DAY_MS, wTo: endMs + 7 * DAY_MS }
  return { wFrom: startMs, wTo: endMs }
}

/**
 * Calcule les données de corrélation entre événements sélectionnés et réservations.
 * @param {Array} events - liste complète des événements générés
 * @param {Set} selected - indices des événements sélectionnés
 * @param {Array} bookingsData - [{key: 'YYYY-MM-DD', bookings, cancelled, revenue}, ...]
 * @param {string} windowFilter - fenêtre de corrélation
 * @param {boolean} active - ne calculer que si l'étape est 'correlate'
 * @returns {Array} résultats triés par volume décroissant
 */
export function useCorrelation(events, selected, bookingsData, windowFilter, active) {
  return useMemo(() => {
    if (!bookingsData?.length || !active) return []

    return Array.from(selected).map(i => {
      const event = events[i]
      if (!event) return null
      const { wFrom, wTo } = getEventWindow(event, windowFilter)
      const inWindow = bookingsData.filter(d => {
        const dMs = new Date(d.key + 'T12:00:00').getTime()
        return dMs >= wFrom && dMs <= wTo
      })
      const total_bookings = inWindow.reduce((s, d) => s + (d.bookings ?? 0), 0)
      const cancelled      = inWindow.reduce((s, d) => s + (d.cancelled ?? 0), 0)
      return {
        event,
        total_bookings,
        cancelled,
        cancel_rate:    total_bookings > 0 ? Math.round((cancelled / total_bookings) * 100) : 0,
        peak_bookings:  Math.max(0, ...inWindow.map(d => d.bookings ?? 0)),
        total_revenue:  inWindow.reduce((s, d) => s + (d.revenue ?? 0), 0),
        days_in_window: inWindow.length,
      }
    })
      .filter(Boolean)
      .sort((a, b) => b.total_bookings - a.total_bookings)
  }, [selected, events, bookingsData, windowFilter, active])
}
