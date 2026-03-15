/**
 * Bandeau de stats + 3 graphiques du Planificateur.
 */
import { LeadTimeChart, WeekdayChart, CancellationChart, TopDays } from '../dashboard'

/**
 * @param {{ total, confirmed, cancelled }} stats
 * @param {Array} weekdayDist
 * @param {Array} leadTimeBuckets
 * @param {Array} cancellationData
 * @param {Array} topDays
 * @param {function} onClickDay
 */
export default function PlannerAnalytics({ stats, weekdayDist, leadTimeBuckets, cancellationData, topDays, onClickDay }) {
  const fmtDayShort = d => new Date(d + 'T12:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })

  if (stats.total === 0) return null

  return (
    <>
      {/* Stats strip */}
      <div className="flex divide-x border-b bg-card">
        {[
          ['Total',      stats.total,     ''],
          ['Confirmées', stats.confirmed, 'text-emerald-600'],
          ['Annulées',   stats.cancelled, 'text-red-500'],
        ].map(([label, value, cls]) => (
          <div key={label} className="flex-1 px-4 py-3 text-center">
            <div className={`text-xl font-bold tabular-nums ${cls}`}>{value}</div>
            <div className="text-[11px] text-muted-foreground mt-0.5">{label}</div>
          </div>
        ))}
      </div>

      {/* 3 graphiques */}
      <div className="border-b bg-card">
        <div className="grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x">
          <div className="px-6 py-4">
            <WeekdayChart data={weekdayDist} height={130} />
          </div>
          <div className="px-6 py-4">
            <LeadTimeChart data={leadTimeBuckets} />
          </div>
          <div className="px-6 py-4">
            <CancellationChart data={cancellationData} />
          </div>
        </div>
      </div>

      {/* Top 7 jours */}
      {topDays.length > 0 && (
        <div className="border-b bg-card px-6 py-4">
          <TopDays data={topDays} total={stats.total} onClickDay={onClickDay} formatDate={fmtDayShort} />
        </div>
      )}
    </>
  )
}
