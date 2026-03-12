import { useState, useMemo } from 'react'

/**
 * Minimal Calendar stub — mirrors the react-day-picker v9 API surface
 * used by Planner.jsx: mode, month, onMonthChange, selected, onDayClick,
 * modifiers, modifiersClassNames, components.DayButton
 */

const DAYS_FR = ['lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.', 'dim.']
const MONTHS_FR = [
  'janvier','février','mars','avril','mai','juin',
  'juillet','août','septembre','octobre','novembre','décembre',
]

function toYMD(d) {
  return [
    d.getFullYear(),
    String(d.getMonth() + 1).padStart(2, '0'),
    String(d.getDate()).padStart(2, '0'),
  ].join('-')
}

function isSameDay(a, b) {
  return a && b && toYMD(a) === toYMD(b)
}

export function Calendar({
  month: controlledMonth,
  onMonthChange,
  selected,
  onDayClick,
  modifiers = {},
  modifiersClassNames = {},
  components = {},
}) {
  const [internalMonth, setInternalMonth] = useState(() => {
    const d = new Date(); d.setDate(1); return d
  })

  const month = controlledMonth ?? internalMonth
  const setMonth = (m) => {
    if (onMonthChange) onMonthChange(m)
    else setInternalMonth(m)
  }

  // Build modifier lookup: modifier name -> Set of YYYY-MM-DD strings
  const modifierSets = useMemo(() => {
    const sets = {}
    for (const [name, dates] of Object.entries(modifiers)) {
      const s = new Set()
      if (Array.isArray(dates)) {
        dates.forEach(d => {
          if (d instanceof Date) s.add(toYMD(d))
          else if (typeof d === 'string') s.add(d)
        })
      }
      sets[name] = s
    }
    return sets
  }, [modifiers])

  const year = month.getFullYear()
  const mo = month.getMonth()
  const daysInMonth = new Date(year, mo + 1, 0).getDate()

  // Day of week for the 1st (0=Sun). Shift to Mon-start: (dow + 6) % 7
  const startDow = (new Date(year, mo, 1).getDay() + 6) % 7

  const cells = []
  for (let i = 0; i < startDow; i++) cells.push(null)
  for (let d = 1; d <= daysInMonth; d++) cells.push(d)

  function prevMonth() {
    const m = new Date(year, mo - 1, 1)
    setMonth(m)
  }
  function nextMonth() {
    const m = new Date(year, mo + 1, 1)
    setMonth(m)
  }

  const today = toYMD(new Date())
  const DayButton = components.DayButton ?? null

  return (
    <div className="select-none">
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <button
          onClick={prevMonth}
          className="w-7 h-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
          aria-label="Mois précédent"
        >
          &lsaquo;
        </button>
        <span className="text-sm font-semibold capitalize">
          {MONTHS_FR[mo]} {year}
        </span>
        <button
          onClick={nextMonth}
          className="w-7 h-7 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
          aria-label="Mois suivant"
        >
          &rsaquo;
        </button>
      </div>

      {/* Weekday headers */}
      <div className="grid grid-cols-7 text-center text-[11px] text-muted-foreground mb-1">
        {DAYS_FR.map(d => <div key={d} className="py-1">{d}</div>)}
      </div>

      {/* Days grid */}
      <div className="grid grid-cols-7 gap-px">
        {cells.map((day, i) => {
          if (day == null) return <div key={`empty-${i}`} />

          const date = new Date(year, mo, day, 12, 0, 0)
          const ymd = toYMD(date)
          const isSelected = selected && isSameDay(date, selected)
          const isToday = ymd === today

          // Compute modifier classNames for this cell
          const modClasses = []
          for (const [name, set] of Object.entries(modifierSets)) {
            if (set.has(ymd) && modifiersClassNames[name]) {
              modClasses.push(modifiersClassNames[name])
            }
          }

          const cellClassName = [
            'relative text-center',
            ...modClasses,
          ].filter(Boolean).join(' ')

          const btnClassName = [
            'w-8 h-8 text-xs rounded-md transition-colors relative',
            isSelected
              ? 'bg-primary text-primary-foreground font-semibold'
              : isToday
                ? 'ring-2 ring-primary font-semibold'
                : 'hover:bg-accent text-foreground',
          ].join(' ')

          const handleClick = () => {
            if (onDayClick) onDayClick(date)
          }

          if (DayButton) {
            return (
              <td key={day} className={cellClassName} aria-selected={isSelected ? 'true' : undefined}>
                <DayButton
                  className={btnClassName}
                  onClick={handleClick}
                  day={{ date }}
                  modifiers={{}}
                >
                  {day}
                </DayButton>
              </td>
            )
          }

          return (
            <td key={day} className={cellClassName} aria-selected={isSelected ? 'true' : undefined}>
              <button className={btnClassName} onClick={handleClick}>
                {day}
              </button>
            </td>
          )
        })}
      </div>
    </div>
  )
}
