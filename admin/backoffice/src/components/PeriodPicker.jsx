import { useState } from 'react'
import { today, daysAgo, monthsAgo } from '../lib/utils'

/**
 * Shared period picker — presets + custom date range.
 * Used by Planner (and compatible with Bookings).
 * Props:
 *   from / to : current value (YYYY-MM-DD)
 *   onChange(from, to) : called when period changes
 */

export const PERIOD_PRESETS_DEFAULT = [
  { label: '7j',   from: () => daysAgo(6),    to: today },
  { label: '30j',  from: () => daysAgo(29),   to: today },
  { label: '90j',  from: () => daysAgo(89),   to: today },
  { label: '1 an', from: () => monthsAgo(12), to: today },
  { label: 'Tout', from: () => '2017-01-01',  to: today },
]

export const PERIOD_PRESETS_PLANNER = [
  { label: '7j',   from: () => daysAgo(6),    to: today },
  { label: '30j',  from: () => daysAgo(29),   to: today },
  { label: '90j',  from: () => daysAgo(89),   to: today },
  { label: '1 an', from: () => monthsAgo(12), to: today },
]

const pill = active =>
  `px-2.5 py-1 rounded text-xs font-medium border transition-colors ${
    active
      ? 'bg-primary text-primary-foreground border-primary'
      : 'bg-card border-border text-muted-foreground hover:text-foreground hover:border-foreground/40'
  }`

export function PeriodPicker({ from, to, onChange, presets = PERIOD_PRESETS_DEFAULT }) {
  const [localFrom, setLocalFrom] = useState(from)
  const [localTo, setLocalTo]     = useState(to)
  const [active, setActive]       = useState(() =>
    presets.find(p => p.from() === from)?.label ?? null
  )

  function applyPreset(p) {
    const f = p.from(), t = p.to()
    setLocalFrom(f); setLocalTo(t); setActive(p.label)
    onChange(f, t)
  }

  function commitDates(f, t) {
    if (f && t && f <= t) { setActive(null); onChange(f, t) }
  }

  return (
    <div className="flex items-center gap-2 flex-wrap">
      <span className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium shrink-0">
        Période
      </span>
      <div className="flex items-center gap-1 flex-wrap">
        {presets.map(p => (
          <button key={p.label} onClick={() => applyPreset(p)} className={pill(active === p.label)}>
            {p.label}
          </button>
        ))}
      </div>
      <div className="h-4 w-px bg-border mx-1" />
      <input
        type="date" value={localFrom} max={localTo}
        onChange={e => { setLocalFrom(e.target.value); setActive(null) }}
        onBlur={() => commitDates(localFrom, localTo)}
        className="text-xs border border-input rounded px-2 py-1 bg-transparent"
      />
      <span className="text-[11px] text-muted-foreground">→</span>
      <input
        type="date" value={localTo} min={localFrom} max={today()}
        onChange={e => { setLocalTo(e.target.value); setActive(null) }}
        onBlur={() => commitDates(localFrom, localTo)}
        className="text-xs border border-input rounded px-2 py-1 bg-transparent"
      />
    </div>
  )
}
