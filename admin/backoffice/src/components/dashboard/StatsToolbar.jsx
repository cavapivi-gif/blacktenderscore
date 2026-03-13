import { useState } from 'react'
import { cn, today, daysAgo, monthsAgo, prevPeriod, calendarYear, availableYears } from '../../lib/utils'
import { Settings } from 'iconoir-react'

const PRESETS = [
  { label: '7j',   from: () => daysAgo(6),    to: today, granularity: 'day'   },
  { label: '30j',  from: () => daysAgo(29),   to: today, granularity: 'day'   },
  { label: '90j',  from: () => daysAgo(89),   to: today, granularity: 'week'  },
  { label: '1an',  from: () => monthsAgo(11), to: today, granularity: 'month' },
  { label: 'Tout', from: () => '2017-01-01',  to: today, granularity: 'month' },
]

const YEARS = availableYears(2017)

const preset = (active) => cn(
  'px-2.5 py-1 rounded text-xs font-medium transition-colors',
  active
    ? 'bg-foreground text-background'
    : 'text-muted-foreground hover:text-foreground hover:bg-accent',
)

const gran = (active) => cn(
  'w-7 h-7 flex items-center justify-center rounded text-xs font-medium transition-colors',
  active
    ? 'bg-foreground text-background'
    : 'text-muted-foreground hover:text-foreground hover:bg-accent',
)

/**
 * Barre de filtres compacte : presets · années · plage de dates · granularité · comparer · filtres avancés.
 *
 * @param {string}   from          Date début active
 * @param {string}   to            Date fin active
 * @param {string}   granularity   Granularité active (day|week|month)
 * @param {boolean}  showCompare   Comparaison activée
 * @param {Function} onApply       Callback ({ from, to, granularity, compareFrom, compareTo })
 * @param {number}   filterCount   Nombre de filtres avancés actifs (badge sur le bouton)
 * @param {Function} onOpenFilters Ouvre le tiroir de filtres avancés
 */
export function StatsToolbar({ from, to, granularity, showCompare, onApply, filterCount = 0, onOpenFilters }) {
  const [localFrom,    setLocalFrom]    = useState(from)
  const [localTo,      setLocalTo]      = useState(to)
  const [localGran,    setLocalGran]    = useState(granularity)
  const [localCompare, setLocalCompare] = useState(showCompare)
  const [activePreset, setActivePreset] = useState(
    from === '2017-01-01' ? 'Tout' : '1an'
  )
  const [activeYear,   setActiveYear]   = useState(null)

  function getCmp(f, t, compare) {
    if (!compare) return { compareFrom: '', compareTo: '' }
    const { cmpFrom, cmpTo } = prevPeriod(f, t)
    return { compareFrom: cmpFrom, compareTo: cmpTo }
  }

  function emit(overrides = {}) {
    const f = overrides.from    ?? localFrom
    const t = overrides.to      ?? localTo
    const g = overrides.granularity ?? localGran
    const c = overrides.compare ?? localCompare
    // Si un filtre année est actif, utiliser sa comparaison calendaire plutôt que rolling
    if (overrides.cmpFrom != null) {
      onApply({ from: f, to: t, granularity: g, compareFrom: c ? overrides.cmpFrom : '', compareTo: c ? overrides.cmpTo : '' })
    } else {
      onApply({ from: f, to: t, granularity: g, ...getCmp(f, t, c) })
    }
  }

  function applyPreset(p) {
    const f = p.from(), t = p.to()
    setLocalFrom(f); setLocalTo(t)
    setLocalGran(p.granularity)
    setActivePreset(p.label)
    setActiveYear(null)
    // "Tout" désactive la comparaison (pas de période précédente pertinente)
    const nextCompare = p.label === 'Tout' ? false : localCompare
    if (p.label === 'Tout' && localCompare) setLocalCompare(false)
    emit({ from: f, to: t, granularity: p.granularity, compare: nextCompare })
  }

  function applyYear(year) {
    const { from: f, to: t, cmpFrom, cmpTo } = calendarYear(year)
    setLocalFrom(f); setLocalTo(t)
    setLocalGran('month')
    setActivePreset(null)
    setActiveYear(year)
    const nextCompare = true
    setLocalCompare(true)
    onApply({ from: f, to: t, granularity: 'month', compareFrom: cmpFrom, compareTo: cmpTo })
  }

  function toggleCompare() {
    const next = !localCompare
    setLocalCompare(next)
    // Si filtre année actif, recalculer la comparaison calendaire
    if (activeYear) {
      const { from: f, to: t, cmpFrom, cmpTo } = calendarYear(activeYear)
      onApply({ from: f, to: t, granularity: 'month', compareFrom: next ? cmpFrom : '', compareTo: next ? cmpTo : '' })
    } else {
      emit({ compare: next })
    }
  }

  function onDateBlur() {
    if (localFrom && localTo) { setActivePreset(null); setActiveYear(null); emit({}) }
  }

  return (
    <div className="flex flex-wrap items-center gap-x-1 gap-y-2">

      {/* ── Presets ─────────────────────────────────────── */}
      <div className="flex items-center gap-0.5">
        {PRESETS.map(p => (
          <button key={p.label} onClick={() => applyPreset(p)} className={preset(activePreset === p.label)}>
            {p.label}
          </button>
        ))}
      </div>

      <span className="text-border select-none mx-1">|</span>

      {/* ── Année calendaire (dropdown) ─────────────────── */}
      <select
        value={activeYear ?? ''}
        onChange={e => e.target.value ? applyYear(Number(e.target.value)) : null}
        className={cn(
          'px-2 py-1 rounded text-xs font-medium transition-colors border-0 outline-none cursor-pointer',
          activeYear
            ? 'bg-foreground text-background'
            : 'bg-transparent text-muted-foreground hover:text-foreground hover:bg-accent',
        )}
      >
        <option value="" disabled>Année</option>
        {YEARS.map(y => <option key={y} value={y}>{y}</option>)}
      </select>

      <span className="text-border select-none mx-1">|</span>

      {/* ── Date range ──────────────────────────────────── */}
      {activeYear ? (
        <span className="px-2 py-1 rounded bg-foreground/10 text-xs text-muted-foreground">
          {activeYear === new Date().getFullYear()
            ? `1 jan. ${activeYear} – aujourd'hui`
            : `1 jan. ${activeYear} – 31 déc. ${activeYear}`}
        </span>
      ) : (
        <div className="flex items-center gap-1.5 text-xs">
          <input
            type="date" value={localFrom} max={localTo}
            onChange={e => { setLocalFrom(e.target.value); setActivePreset(null) }}
            onBlur={onDateBlur}
            className="w-[118px] px-2 py-1 rounded bg-accent/60 border border-transparent hover:border-border focus:border-border focus:outline-none text-xs transition-colors"
          />
          <span className="text-muted-foreground">–</span>
          <input
            type="date" value={localTo} min={localFrom} max={today()}
            onChange={e => { setLocalTo(e.target.value); setActivePreset(null) }}
            onBlur={onDateBlur}
            className="w-[118px] px-2 py-1 rounded bg-accent/60 border border-transparent hover:border-border focus:border-border focus:outline-none text-xs transition-colors"
          />
        </div>
      )}

      <span className="text-border select-none mx-1">|</span>

      {/* ── Granularité ─────────────────────────────────── */}
      <div className="flex items-center gap-0.5">
        {[['day','J'], ['week','S'], ['month','M']].map(([val, lbl]) => (
          <button key={val} onClick={() => { setLocalGran(val); emit({ granularity: val }) }} className={gran(localGran === val)} title={{ day: 'Jour', week: 'Semaine', month: 'Mois' }[val]}>
            {lbl}
          </button>
        ))}
      </div>

      {/* ── Compare + Filtres (poussés à droite) ─────────── */}
      <div className="ml-auto flex items-center gap-1.5">
        {/* Comparer — masqué quand preset "Tout" */}
        {activePreset !== 'Tout' && (
          <button
            type="button"
            onClick={toggleCompare}
            className={cn(
              'flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium transition-colors',
              localCompare
                ? 'bg-indigo-500 text-white hover:bg-indigo-600'
                : 'text-muted-foreground hover:text-foreground hover:bg-accent',
            )}
            title="Comparer avec la période précédente"
          >
            <svg width="11" height="11" viewBox="0 0 16 16" fill="none" className="shrink-0">
              <path d="M1 5h14M1 5l3-3M1 5l3 3M15 11H1M15 11l-3-3M15 11l-3 3" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
            </svg>
            Comparer
          </button>
        )}

        {/* Filtres avancés */}
        {onOpenFilters && (
          <button
            type="button"
            onClick={onOpenFilters}
            className={cn(
              'flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium border transition-colors',
              filterCount > 0
                ? 'bg-primary text-primary-foreground border-primary hover:bg-primary/90'
                : 'border-border text-muted-foreground hover:text-foreground hover:bg-accent',
            )}
          >
            <Settings width={11} height={11} className="shrink-0" />
            Filtres
            {filterCount > 0 && (
              <span className="flex items-center justify-center w-4 h-4 rounded-full bg-white/25 text-[10px] font-bold leading-none tabular-nums">
                {filterCount}
              </span>
            )}
          </button>
        )}
      </div>

    </div>
  )
}
