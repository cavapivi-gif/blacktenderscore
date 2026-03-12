import { useState } from 'react'
import { cn, today, daysAgo, monthsAgo, prevPeriod } from '../../lib/utils'

const PRESETS = [
  { label: '7j',   from: () => daysAgo(6),    to: today, granularity: 'day' },
  { label: '30j',  from: () => daysAgo(29),   to: today, granularity: 'day' },
  { label: '90j',  from: () => daysAgo(89),   to: today, granularity: 'week' },
  { label: '1an',  from: () => monthsAgo(11), to: today, granularity: 'month' },
  { label: 'Tout', from: () => '2017-01-01',  to: today, granularity: 'month' },
]

const pill = (active) =>
  `px-2.5 py-1 rounded text-xs font-medium border transition-colors ${
    active
      ? 'bg-primary text-primary-foreground border-primary'
      : 'bg-card border-border text-muted-foreground hover:text-foreground'
  }`

export function StatsToolbar({ from, to, granularity, showCompare, onApply }) {
  const [localFrom, setLocalFrom] = useState(from)
  const [localTo, setLocalTo] = useState(to)
  const [localGran, setLocalGran] = useState(granularity)
  const [localCompare, setLocalCompare] = useState(showCompare)
  const [activePreset, setActivePreset] = useState(
    from === '2017-01-01' ? 'Tout' : '1an'
  )

  function getCmp(f, t, compare) {
    if (!compare) return { compareFrom: '', compareTo: '' }
    const { cmpFrom, cmpTo } = prevPeriod(f, t)
    return { compareFrom: cmpFrom, compareTo: cmpTo }
  }

  function emit(overrides = {}) {
    const f = overrides.from ?? localFrom
    const t = overrides.to ?? localTo
    const g = overrides.granularity ?? localGran
    const c = overrides.compare ?? localCompare
    onApply({ from: f, to: t, granularity: g, ...getCmp(f, t, c) })
  }

  function applyPreset(p) {
    const f = p.from(), t = p.to()
    setLocalFrom(f); setLocalTo(t)
    setLocalGran(p.granularity)
    setActivePreset(p.label)
    emit({ from: f, to: t, granularity: p.granularity })
  }

  function toggleCompare(v) {
    setLocalCompare(v)
    emit({ compare: v })
  }

  function onDateBlur() {
    if (localFrom && localTo) { setActivePreset(null); emit({}) }
  }

  return (
    <div className="rounded-lg border bg-card p-4 space-y-3">
      {/* Row 1: Period presets + date pickers */}
      <div className="flex flex-wrap items-center gap-2">
        <span className="text-[11px] text-muted-foreground uppercase tracking-wider shrink-0 w-16 font-medium">Période</span>
        <div className="flex items-center gap-1 flex-wrap">
          {PRESETS.map(p => (
            <button key={p.label} onClick={() => applyPreset(p)} className={pill(activePreset === p.label)}>
              {p.label}
            </button>
          ))}
        </div>
        <div className="h-4 w-px bg-border mx-1" />
        <input type="date" value={localFrom} max={localTo}
          onChange={e => { setLocalFrom(e.target.value); setActivePreset(null) }}
          onBlur={onDateBlur}
          className="text-xs border border-input rounded px-2 py-1 bg-transparent" />
        <span className="text-[11px] text-muted-foreground">→</span>
        <input type="date" value={localTo} min={localFrom} max={today()}
          onChange={e => { setLocalTo(e.target.value); setActivePreset(null) }}
          onBlur={onDateBlur}
          className="text-xs border border-input rounded px-2 py-1 bg-transparent" />
      </div>

      {/* Row 2: Granularity + compare toggle */}
      <div className="flex flex-wrap items-center gap-3">
        <span className="text-[11px] text-muted-foreground uppercase tracking-wider shrink-0 w-16 font-medium">Vue</span>
        {[['day', 'Jour'], ['week', 'Semaine'], ['month', 'Mois']].map(([val, lbl]) => (
          <button key={val} onClick={() => { setLocalGran(val); emit({ granularity: val }) }} className={pill(localGran === val)}>
            {lbl}
          </button>
        ))}
        <div className="h-4 w-px bg-border mx-1" />
        <button
          type="button"
          onClick={() => toggleCompare(!localCompare)}
          className={cn(
            'flex items-center gap-2 px-3 py-1 rounded text-xs font-medium border transition-colors',
            localCompare
              ? 'bg-card border-primary/30 text-foreground'
              : 'bg-card border-border text-muted-foreground hover:text-foreground',
          )}
        >
          <span className={cn('w-3 h-0.5 rounded-full inline-block',
            localCompare ? 'bg-indigo-500' : 'bg-muted-foreground')} />
          Comparer la période préc.
        </button>
      </div>
    </div>
  )
}
