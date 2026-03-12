import { useState, useEffect, useCallback, useMemo } from 'react'
import { api } from '../lib/api'
import { today, daysAgo, prevPeriod } from '../lib/utils'

function loadPrefs() {
  try {
    const s = JSON.parse(localStorage.getItem('bt-dashboard-prefs') || 'null')
    if (s?.from && s?.to && s?.granularity) return s
  } catch {}
  return null
}

/**
 * Main dashboard data hook — handles all API calls, filter state, and derived data.
 */
export function useDashboard() {
  const [data, setData] = useState(null)
  const [stats, setStats] = useState(null)
  const [sparkData, setSparkData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [statsLoading, setStatsLoading] = useState(true)
  const [error, setError] = useState(null)

  // Filter state with localStorage persistence
  const [filterParams, setFilterParams] = useState(() => {
    const saved = loadPrefs()
    if (saved) return saved
    const from = '2017-01-01', to = today()
    const { cmpFrom, cmpTo } = prevPeriod(from, to)
    return { from, to, granularity: 'month', compareFrom: cmpFrom, compareTo: cmpTo }
  })

  // Persist filter prefs
  useEffect(() => {
    try { localStorage.setItem('bt-dashboard-prefs', JSON.stringify(filterParams)) } catch {}
  }, [filterParams])

  // Load static dashboard data (products, customers, total_in_db, api_status)
  useEffect(() => {
    api.dashboard()
      .then(setData)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  // Load 7-day sparkline data
  useEffect(() => {
    api.bookingsStats({ from: daysAgo(6), to: today(), granularity: 'day' })
      .then(setSparkData)
      .catch(() => setSparkData(null))
  }, [])

  // Load stats based on filter params (with enhanced KPIs + all modules)
  const loadStats = useCallback(() => {
    setStatsLoading(true)
    const p = {
      from: filterParams.from,
      to: filterParams.to,
      granularity: filterParams.granularity,
      include: 'periods,kpis,by_product,by_channel,by_weekday,heatmap,payments,booking_hours,lead_time,repeat_customers,product_mix,channel_status,yoy,cumulative',
    }
    if (filterParams.compareFrom) p.compare_from = filterParams.compareFrom
    if (filterParams.compareTo) p.compare_to = filterParams.compareTo
    api.bookingsStats(p)
      .then(setStats)
      .catch(() => setStats(null))
      .finally(() => setStatsLoading(false))
  }, [filterParams])

  useEffect(() => { loadStats() }, [loadStats])

  // Merge current + comparison chart data
  const chartData = useMemo(() => {
    if (!stats?.periods?.length) return []
    const cmpMap = {}
    ;(stats.compare ?? []).forEach((c, i) => { cmpMap[i] = c })
    return stats.periods.map((p, i) => ({
      label: p.label,
      key: p.key,
      bookings: p.bookings,
      revenue: p.revenue,
      avg_basket: p.avg_basket ?? 0,
      cancelled: p.cancelled,
      bookings_prev: cmpMap[i]?.bookings ?? null,
      revenue_prev: cmpMap[i]?.revenue ?? null,
      avg_basket_prev: cmpMap[i]?.avg_basket ?? null,
    }))
  }, [stats])

  const hasCompare = !!(filterParams.compareFrom && stats?.compare?.length > 0)

  // KPIs
  const kpis = stats?.kpis ?? {}
  const kpisCmp = stats?.kpis_compare ?? null

  // Sparklines (7j)
  const sparkBookings = useMemo(() => sparkData?.periods?.map(p => p.bookings ?? 0) ?? [], [sparkData])
  const sparkRevenue = useMemo(() => sparkData?.periods?.map(p => p.revenue ?? 0) ?? [], [sparkData])
  const sparkAvgBasket = useMemo(() => sparkData?.periods?.map(p => p.avg_basket ?? 0) ?? [], [sparkData])

  // Peaks
  const peaks = {
    bookings: stats?.peak_bookings ?? 0,
    revenue: stats?.peak_revenue ?? 0,
    basket: stats?.peak_basket ?? 0,
  }

  function resetPeriod() {
    const from = '2017-01-01', to = today()
    const { cmpFrom, cmpTo } = prevPeriod(from, to)
    setFilterParams({ from, to, granularity: 'month', compareFrom: cmpFrom, compareTo: cmpTo })
  }

  function applyFilters(p) {
    setFilterParams(prev => ({ ...prev, ...p }))
  }

  return {
    // Raw data
    data, stats, loading, statsLoading, error,
    // Filter
    filterParams, applyFilters, resetPeriod,
    // Derived
    chartData, hasCompare, kpis, kpisCmp,
    sparkBookings, sparkRevenue, sparkAvgBasket,
    peaks,
  }
}
