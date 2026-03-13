import { useState, useEffect } from 'react'
import { api } from '../../lib/api'
import { fmtNum } from '../../lib/utils'
import { CHART_INFO } from '../../lib/constants'
import { InfoTooltip } from './InfoTooltip'

/** Mini composant étoiles SVG */
function Stars({ rating, size = 13 }) {
  return (
    <span className="flex gap-0.5">
      {Array.from({ length: 5 }, (_, i) => {
        const filled = i < Math.floor(rating)
        const half   = !filled && i === Math.floor(rating) && (rating % 1) >= 0.5
        return (
          <svg key={i} width={size} height={size} viewBox="0 0 24 24"
            fill={filled ? '#f59e0b' : half ? 'url(#half)' : 'none'}
            stroke="#f59e0b" strokeWidth="1.5">
            {half && (
              <defs>
                <linearGradient id="half" x1="0" x2="1" y1="0" y2="0">
                  <stop offset="50%" stopColor="#f59e0b" />
                  <stop offset="50%" stopColor="transparent" />
                </linearGradient>
              </defs>
            )}
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg>
        )
      })}
    </span>
  )
}

/**
 * Widget avis clients — se charge tout seul, affiche note moyenne + distribution.
 * Données issues de api.avisStats() (bt_reviews).
 *
 * Quand compareFrom + compareTo sont fournis, affiche un delta de note par rapport
 * à la période précédente.
 *
 * @param {string}      from        Date de début (optionnel — si absent, stats globales)
 * @param {string}      to          Date de fin
 * @param {string|null} compareFrom Date début comparaison
 * @param {string|null} compareTo   Date fin comparaison
 */
export function AvisWidget({ from, to, compareFrom, compareTo }) {
  const [stats, setStats]       = useState(null)
  const [statsCmp, setStatsCmp] = useState(null)
  const [loading, setLoading]   = useState(true)

  useEffect(() => {
    setLoading(true)
    const params = from && to ? { from, to } : {}
    api.avisStats(params)
      .then(setStats)
      .catch(() => setStats(null))
      .finally(() => setLoading(false))
  }, [from, to])

  useEffect(() => {
    if (!compareFrom || !compareTo) { setStatsCmp(null); return }
    api.avisStats({ from: compareFrom, to: compareTo })
      .then(setStatsCmp)
      .catch(() => setStatsCmp(null))
  }, [compareFrom, compareTo])

  if (loading) {
    return (
      <div className="rounded-lg border bg-card p-5 flex items-center justify-center min-h-[130px]">
        <span className="text-xs text-muted-foreground">Chargement avis…</span>
      </div>
    )
  }

  if (!stats?.avg_rating || !stats?.total_rated) {
    return (
      <div className="rounded-lg border bg-card p-5">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium mb-2">
          Avis clients
        </p>
        <p className="text-xs text-muted-foreground">Aucun avis importé.</p>
      </div>
    )
  }

  const dist    = stats.distribution ?? {}
  const maxDist = Math.max(...Object.values(dist), 1)
  const total   = stats.total_all ?? stats.total_rated

  // Delta de note vs période de comparaison
  const ratingDelta = (statsCmp?.avg_rating != null && stats.avg_rating != null)
    ? +(stats.avg_rating - statsCmp.avg_rating).toFixed(2)
    : null

  return (
    <div className="rounded-lg border bg-card p-5">
      <div className="mb-3">
        <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium inline-flex items-center">
          Avis clients <InfoTooltip text={CHART_INFO.avis} />
        </p>
        <p className="text-[10px] text-muted-foreground mt-0.5">
          {fmtNum(total)} avis au total
        </p>
      </div>

      {/* Note principale + delta */}
      <div className="flex items-end gap-3 mb-4">
        <span className="text-3xl font-bold tabular-nums leading-none">
          {stats.avg_rating}
        </span>
        <div className="mb-0.5">
          <Stars rating={stats.avg_rating} />
          <p className="text-[10px] text-muted-foreground mt-1">
            sur {fmtNum(stats.total_rated)} notés
          </p>
        </div>
        {ratingDelta != null && (
          <span className={`mb-0.5 ml-auto text-xs font-semibold tabular-nums px-1.5 py-0.5 rounded ${
            ratingDelta > 0
              ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40'
              : ratingDelta < 0
              ? 'bg-red-50 text-red-500 dark:bg-red-950/40'
              : 'bg-muted text-muted-foreground'
          }`}>
            {ratingDelta > 0 ? '+' : ''}{ratingDelta}★
          </span>
        )}
      </div>

      {/* Distribution ★5 → ★1 */}
      <div className="space-y-1.5">
        {[5, 4, 3, 2, 1].map(star => {
          const count = dist[star] ?? 0
          const w = maxDist > 0 ? Math.round((count / maxDist) * 100) : 0
          return (
            <div key={star} className="flex items-center gap-2">
              <span className="text-[10px] text-muted-foreground w-5 shrink-0 tabular-nums">{star}★</span>
              <div className="flex-1 h-1.5 bg-muted rounded-full overflow-hidden">
                <div className="h-full bg-amber-400 rounded-full" style={{ width: `${w}%` }} />
              </div>
              <span className="text-[10px] text-muted-foreground w-6 tabular-nums text-right">{count}</span>
            </div>
          )
        })}
      </div>

      {/* Projection 4.8★ */}
      {stats.reviews_needed_4_8 != null && (
        <p className="text-[10px] text-muted-foreground mt-3 pt-2 border-t">
          +{stats.reviews_needed_4_8} avis 5★ pour atteindre 4.8
        </p>
      )}

      {/* Note comparaison */}
      {statsCmp?.avg_rating != null && (
        <p className="text-[10px] text-muted-foreground mt-1">
          Période préc. : <span className="font-medium">{statsCmp.avg_rating}★</span>
          {statsCmp.total_rated > 0 && <span className="ml-1">({fmtNum(statsCmp.total_rated)} notés)</span>}
        </p>
      )}
    </div>
  )
}
