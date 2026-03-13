import { useState, useMemo } from 'react'
import { cn } from '../../lib/utils'
import { COLORS, CHART_INFO } from '../../lib/constants'
import { fmtNum } from '../../lib/utils'
import { InfoTooltip } from './InfoTooltip'

// Inclut l'année pour éviter toute ambiguïté (ex: "27 juil. 2025")
const DEFAULT_FMT = d =>
  new Date(d + 'T12:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })

const LIMIT_OPTIONS = [7, 20, 30]

// Couleur barre annulations — hardcodée (CSS vars inopérantes dans inline style)
const COLOR_CANCEL = '#dc2626'  // red-600

/**
 * Ligne de classement individuelle.
 * Réutilisée pour réservations et annulations, couleur de barre paramétrable.
 */
function RankRow({ day, rank, maxCount, onClickDay, formatDate, barColor, compareMode, cmpIndex }) {
  const pct = maxCount > 0 ? (day.count / maxCount) * 100 : 0
  let variation = null
  if (compareMode && cmpIndex) {
    const cmpCount = cmpIndex[day.date] ?? 0
    if (cmpCount > 0) variation = Math.round(((day.count - cmpCount) / cmpCount) * 100)
  }

  return (
    <div
      className={cn('flex items-center gap-2', onClickDay && 'cursor-pointer group')}
      onClick={() => onClickDay?.(day.date)}
    >
      <span className="text-[10px] text-muted-foreground w-5 tabular-nums text-right shrink-0">
        {rank + 1}
      </span>
      <span className="text-[11px] font-medium w-20 shrink-0">
        {formatDate(day.date)}
      </span>
      <div className="flex-1 h-1.5 bg-muted rounded-full overflow-hidden">
        <div
          className="h-full rounded-full transition-all group-hover:opacity-80"
          style={{ width: `${pct}%`, background: barColor }}
        />
      </div>
      <span className="text-[11px] font-semibold tabular-nums w-6 shrink-0 text-right">
        {day.count}
      </span>
      {compareMode && (
        <span className={cn(
          'text-[10px] tabular-nums w-10 shrink-0 text-right',
          variation == null ? 'text-muted-foreground' :
          variation > 0    ? 'text-emerald-600' : 'text-red-500',
        )}>
          {variation == null ? '—' : `${variation > 0 ? '+' : ''}${variation}%`}
        </span>
      )}
    </div>
  )
}

/**
 * Résumé compact — met en avant le meilleur et le pire jour en un coup d'œil.
 * Design épuré : bordure gauche colorée, fond neutre.
 */
function InsightSummary({ topBooking, topCancellation, formatDate }) {
  if (!topBooking && !topCancellation) return null
  return (
    <div className="flex gap-3 mb-4 pb-4 border-b">
      {topBooking && (
        <div className="flex-1 min-w-0 border-l-2 border-emerald-500 pl-3 py-0.5">
          <p className="text-[9px] uppercase tracking-wider text-muted-foreground font-medium mb-0.5">
            Pic réservations
          </p>
          <p className="text-[11px] text-muted-foreground truncate">
            {formatDate(topBooking.date)}
          </p>
          <p className="text-sm font-bold tabular-nums">
            {fmtNum(topBooking.count)} <span className="text-[10px] font-normal text-muted-foreground">rés.</span>
          </p>
        </div>
      )}
      {topCancellation && (
        <div className="flex-1 min-w-0 border-l-2 border-red-500 pl-3 py-0.5">
          <p className="text-[9px] uppercase tracking-wider text-muted-foreground font-medium mb-0.5">
            Pic annulations
          </p>
          <p className="text-[11px] text-muted-foreground truncate">
            {formatDate(topCancellation.date)}
          </p>
          <p className="text-sm font-bold tabular-nums">
            {fmtNum(topCancellation.count)} <span className="text-[10px] font-normal text-muted-foreground">annul.</span>
          </p>
        </div>
      )}
    </div>
  )
}

/**
 * Classement des dates — réservations ET annulations en un seul widget.
 * Résumé "pic / pire" en tête, puis liste détaillée avec tabs et sélecteur 7/20/30.
 *
 * @param {Array}    data                 [{date, count}] réservations, trié DESC (jusqu'à 30)
 * @param {Array}    [dataCancellations]  [{date, count}] annulations, trié DESC (jusqu'à 30)
 * @param {number}   total                Total réservations sur la période
 * @param {Function} [onClickDay]         Callback(date) — optionnel
 * @param {Function} [formatDate]         Formatteur de date — optionnel
 * @param {number}   [limit]              Limite initiale (défaut 7)
 * @param {boolean}  [compareMode]        Active la variation % par ligne
 * @param {Array}    [dataCompare]        [{date, count}] période de comparaison
 * @param {string}   [comparePeriod]      Libellé période de comparaison
 */
export function TopDays({
  data = [],
  dataCancellations = [],
  total = 0,
  onClickDay,
  formatDate = DEFAULT_FMT,
  limit = 7,
  compareMode = false,
  dataCompare = [],
  comparePeriod,
}) {
  const [activeTab, setActiveTab]     = useState('bookings')
  const [localLimit, setLocalLimit]   = useState(limit)

  const hasCancel = dataCancellations.length > 0

  // Index comparaison O(1)
  const cmpIndex = useMemo(() => {
    const map = {}
    dataCompare.forEach(d => { map[d.date] = d.count })
    return map
  }, [dataCompare])

  if (!data.length) return null

  // Données actives selon l'onglet
  const activeData  = activeTab === 'cancellations' ? dataCancellations : data
  const activeColor = activeTab === 'cancellations' ? COLOR_CANCEL : COLORS.current

  const availableOptions = LIMIT_OPTIONS.filter(n => n <= activeData.length || n === LIMIT_OPTIONS[0])
  const items    = activeData.slice(0, localLimit)
  const maxCount = items[0]?.count ?? 1

  // Résumé : #1 de chaque liste
  const topBooking      = data[0] ?? null
  const topCancellation = dataCancellations[0] ?? null

  // Sous-titre selon onglet actif
  const subtitle = activeTab === 'cancellations'
    ? `${fmtNum(dataCancellations.reduce((s, d) => s + (Number(d.count) || 0), 0))} annul. sur la période`
    : total > 0
    ? `${fmtNum(total)} rés. sur la période`
    : ''

  return (
    <div className="rounded-lg border bg-card p-5">
      {/* Titre + sélecteur 7/20/30 */}
      <div className="flex items-start justify-between mb-4">
        <div>
          <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium inline-flex items-center">
            Top dates <InfoTooltip text={CHART_INFO.top_days} />
          </p>
          <p className="text-[10px] text-muted-foreground mt-0.5">
            {subtitle}
            {compareMode && comparePeriod && (
              <span className="ml-1 opacity-70">· vs {comparePeriod}</span>
            )}
          </p>
        </div>
        {availableOptions.length > 1 && (
          <div className="flex items-center gap-0.5 shrink-0">
            {availableOptions.map(n => (
              <button
                key={n}
                onClick={() => setLocalLimit(n)}
                className={cn(
                  'px-2 py-0.5 rounded text-[10px] font-medium transition-colors',
                  localLimit === n
                    ? 'bg-foreground text-background'
                    : 'text-muted-foreground hover:text-foreground hover:bg-accent',
                )}
              >
                {n}
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Résumé pic / pire — affiché uniquement si les deux listes sont disponibles */}
      {hasCancel && (
        <InsightSummary
          topBooking={topBooking}
          topCancellation={topCancellation}
          formatDate={formatDate}
        />
      )}

      {/* Tabs Réservations | Annulations */}
      {hasCancel && (
        <div className="flex gap-1 mb-3">
          <button
            onClick={() => setActiveTab('bookings')}
            className={cn(
              'flex-1 py-1.5 rounded text-[10px] font-medium transition-colors border',
              activeTab === 'bookings'
                ? 'bg-foreground text-background border-foreground'
                : 'text-muted-foreground border-border hover:text-foreground hover:bg-accent',
            )}
          >
            Réservations
          </button>
          <button
            onClick={() => setActiveTab('cancellations')}
            className={cn(
              'flex-1 py-1.5 rounded text-[10px] font-medium transition-colors border',
              activeTab === 'cancellations'
                ? 'bg-red-600 text-white border-red-600'
                : 'text-muted-foreground border-border hover:text-foreground hover:bg-accent',
            )}
          >
            Annulations
          </button>
        </div>
      )}

      {/* Liste */}
      <div className="space-y-1.5">
        {items.map((day, rank) => (
          <RankRow
            key={day.date}
            day={day}
            rank={rank}
            maxCount={maxCount}
            onClickDay={onClickDay}
            formatDate={formatDate}
            barColor={activeColor}
            compareMode={compareMode && activeTab === 'bookings'}
            cmpIndex={cmpIndex}
          />
        ))}
        {items.length === 0 && (
          <p className="text-[11px] text-muted-foreground text-center py-4">Aucune donnée</p>
        )}
      </div>
    </div>
  )
}
