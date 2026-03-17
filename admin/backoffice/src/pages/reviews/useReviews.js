import { useState, useEffect, useCallback, useMemo } from 'react'
import { api } from '../../lib/api'
import { today, monthsAgo } from '../../lib/utils'
import { computeComparePeriod } from './helpers'

/**
 * Custom hook encapsulating all Reviews page state, data loading, and actions.
 */
export default function useReviews() {
  // ── Period ───────────────────────────────────────────────────────────────────
  const [from, setFrom] = useState(() => monthsAgo(12))
  const [to,   setTo]   = useState(() => today())
  const [compareActive, setCompareActive] = useState(false)

  const comparePeriod = useMemo(() => computeComparePeriod(from, to), [from, to])

  // ── Stats ────────────────────────────────────────────────────────────────────
  const [stats,          setStats]          = useState(null)
  const [compareStats,   setCompareStats]   = useState(null)
  const [statsLoading,   setStatsLoading]   = useState(true)

  // ── List ─────────────────────────────────────────────────────────────────────
  const [data,         setData]         = useState([])
  const [total,        setTotal]        = useState(0)
  const [loading,      setLoading]      = useState(true)
  const [error,        setError]        = useState(null)
  const [page,         setPage]         = useState(1)
  const [q,            setQ]            = useState('')
  const [search,       setSearch]       = useState('')
  const [product,      setProduct]      = useState('')
  const [ratingFilter, setRatingFilter] = useState('')
  const [sort,         setSort]         = useState({ key: 'review_date', dir: 'desc' })
  const [expanded,     setExpanded]     = useState(null)

  // ── UI ───────────────────────────────────────────────────────────────────────
  const [showImporter, setShowImporter] = useState(false)
  const [resetting,    setResetting]    = useState(false)
  const [syncing,      setSyncing]      = useState(false)
  const [syncResult,   setSyncResult]   = useState(null)
  const [activeTab,    setActiveTab]    = useState('overview')
  const [resetConfirm, setResetConfirm] = useState(false)
  const perPage = 50

  // ── Loaders ──────────────────────────────────────────────────────────────────

  const loadStats = useCallback(() => {
    setStatsLoading(true)
    const params = { from, to }
    api.avisStats(params)
      .then(s => {
        setStats(s)
        // Load compare stats if active
        if (compareActive) {
          return api.avisStats({ from: comparePeriod.from, to: comparePeriod.to })
            .then(setCompareStats)
            .catch(() => setCompareStats(null))
        } else {
          setCompareStats(null)
        }
      })
      .catch(() => {})
      .finally(() => setStatsLoading(false))
  }, [from, to, compareActive, comparePeriod.from, comparePeriod.to])

  const load = useCallback(() => {
    setLoading(true)
    api.avis({
      page,
      per_page: perPage,
      search:  search  || undefined,
      product: product || undefined,
      rating:  ratingFilter || undefined,
      from,
      to,
      sort: sort.key,
      dir:  sort.dir.toUpperCase(),
    })
      .then(r => { setData(r.data ?? []); setTotal(r.total ?? 0) })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [page, search, product, ratingFilter, from, to, sort])

  useEffect(() => { loadStats() }, [loadStats])
  useEffect(() => { load() },      [load])

  // Debounce search input
  useEffect(() => {
    const t = setTimeout(() => { setSearch(q); setPage(1) }, 400)
    return () => clearTimeout(t)
  }, [q])

  // Reset page on filter changes
  const handlePeriodChange = useCallback((f, t) => {
    setFrom(f); setTo(t); setPage(1)
  }, [])

  const onSort = key => setSort(s => ({
    key, dir: s.key === key && s.dir === 'asc' ? 'desc' : 'asc',
  }))

  const handleReset = async () => {
    setResetConfirm(false)
    setResetting(true)
    try {
      await api.resetAvis()
      setData([]); setTotal(0); setStats(null); setCompareStats(null)
      loadStats()
    } catch (e) {
      setError(e.message)
    } finally {
      setResetting(false)
    }
  }

  const handleSync = async () => {
    setSyncing(true)
    setSyncResult(null)
    setError(null)
    try {
      const result = await api.syncAvis()
      setSyncResult(result)
      load(); loadStats()
    } catch (e) {
      setError(e.message)
    } finally {
      setSyncing(false)
    }
  }

  const handleImportDone = useCallback(() => {
    setShowImporter(false); load(); loadStats()
  }, [load, loadStats])

  const products = useMemo(() => stats?.products ?? [], [stats])

  return {
    // Period
    from, to, compareActive, comparePeriod,
    setCompareActive, handlePeriodChange,
    // Stats
    stats, compareStats, statsLoading,
    // List
    data, total, loading, error, page, q, search, product, ratingFilter, sort, expanded,
    setPage, setQ, setProduct, setRatingFilter, setExpanded, onSort,
    // UI
    showImporter, setShowImporter, resetting, syncing, syncResult,
    activeTab, setActiveTab, perPage, resetConfirm, setResetConfirm,
    // Actions
    load, loadStats, handleReset, handleSync, handleImportDone,
    // Derived
    products,
  }
}
