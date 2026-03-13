import { useMemo, useState } from 'react'
import { COLORS, CHART_INFO } from '../../lib/constants'
import { InfoTooltip } from './InfoTooltip'
import { cn } from '../../lib/utils'

const DAYS     = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const DOW_MAP  = { 2: 0, 3: 1, 4: 2, 5: 3, 6: 4, 7: 5, 1: 6 }
const MONTH_FR = ['jan','fév','mar','avr','mai','jun','jul','aoû','sep','oct','nov','déc']

// ── Helpers colonnes ────────────────────────────────────────────────────────

/** Clé de colonne selon la granularité choisie. */
function getColKey(month, granularity) {
  if (granularity === 'mois') return month
  const [y, mo] = month.split('-')
  const m = +mo
  if (granularity === 'trimestre') return `${y}-Q${Math.ceil(m / 3)}`
  if (granularity === 'semestre')  return `${y}-S${m <= 6 ? 1 : 2}`
  return month
}

/** Label lisible selon la forme de la clé (YYYY-MM / YYYY-QN / YYYY-SN). */
function fmtCol(key) {
  if (key.includes('-Q')) {
    const [y, q] = key.split('-Q')
    return `T${q} ${y.slice(2)}`
  }
  if (key.includes('-S')) {
    const [y, s] = key.split('-S')
    return `S${s} ${y.slice(2)}`
  }
  const [y, mo] = key.split('-')
  return `${MONTH_FR[+mo - 1]} ${y.slice(2)}`
}

// ── Palettes — couleurs hardcodées (CSS vars inopérantes dans inline style) ─

const SCALE = {
  current: [COLORS.map_empty, COLORS.map_low,        COLORS.map_mid,        COLORS.map_high,        COLORS.map_peak],
  compare: [COLORS.map_cmp_empty, COLORS.map_cmp_low,    COLORS.map_cmp_mid,    COLORS.map_cmp_high,    COLORS.map_cmp_peak],
  cancel:  [COLORS.map_cancel_empty, COLORS.map_cancel_low, COLORS.map_cancel_mid, COLORS.map_cancel_high, COLORS.map_cancel_peak],
}

const SCALE_LABELS = {
  current: ['0 rés.',   'Calme',   'Modéré',  'Actif',   'Pic'],
  compare: ['0 rés.',   'Calme',   'Modéré',  'Actif',   'Pic'],
  cancel:  ['0 annul.', 'Rare',    'Notable', 'Élevé',   'Critique'],
  pct:     ['0%',       'Faible',  'Moyen',   'Fort',    'Dominant'],
}

function cellColor(val, maxVal, palette) {
  if (!val) return palette[0]
  const t = val / maxVal
  if (t < 0.25) return palette[1]
  if (t < 0.5)  return palette[2]
  if (t < 0.75) return palette[3]
  return palette[4]
}

// ── Construction de la grille ───────────────────────────────────────────────

/**
 * Transforme les données brutes en grille col × jour.
 *
 * @param {Array}   data        [{month, dow, total}]
 * @param {string}  granularity 'mois' | 'trimestre' | 'semestre'
 * @param {boolean} normalize   false = valeurs absolues, true = % par colonne
 */
function buildGrid(data, granularity = 'mois', normalize = false) {
  if (!data?.length) return { grid: {}, cols: [], maxVal: 1, isNormalized: false }

  const colSet  = new Set()
  const rawGrid = {}

  data.forEach(d => {
    const col    = getColKey(d.month, granularity)
    const dayIdx = DOW_MAP[d.dow] ?? 0
    const key    = `${col}-${dayIdx}`
    colSet.add(col)
    rawGrid[key] = (rawGrid[key] || 0) + Number(d.total)
  })

  const cols = [...colSet].sort()
  let grid   = { ...rawGrid }
  let maxVal = 1

  if (normalize) {
    // Chaque cellule devient le % du total de sa colonne
    cols.forEach(col => {
      let colTotal = 0
      for (let d = 0; d < 7; d++) colTotal += rawGrid[`${col}-${d}`] || 0
      if (colTotal > 0) {
        for (let d = 0; d < 7; d++) {
          const key = `${col}-${d}`
          if (rawGrid[key]) grid[key] = Math.round((rawGrid[key] / colTotal) * 100)
        }
      }
    })
    maxVal = 100
  } else {
    Object.values(grid).forEach(v => { maxVal = Math.max(maxVal, v) })
  }

  return { grid, cols, maxVal, isNormalized: normalize }
}

// ── Grille visuelle ─────────────────────────────────────────────────────────

/**
 * Grille col × jour pour une seule série (réservations, annulations ou comparaison).
 *
 * @param {Object}  grid          Grille {`col-dayIdx`: valeur}
 * @param {Array}   cols          Clés de colonnes triées
 * @param {number}  maxVal        Valeur max pour normalisation couleur
 * @param {string}  [label]       Libellé de série (ex: "Réservations")
 * @param {string}  accentColor   Couleur accent pour le label
 * @param {Array}   palette       5 couleurs (SCALE.current / .cancel / .compare)
 * @param {string}  unit          Unité tooltip ("rés." / "annul." / "%")
 * @param {boolean} isNormalized  Affiche "X%" dans le tooltip si true
 */
function HeatmapGrid({ grid, cols, maxVal, label, accentColor, palette, unit, isNormalized }) {
  const visible = cols.slice(-12)
  if (!visible.length) return null

  const periodLabel = visible.length > 1
    ? `${fmtCol(visible[0])} – ${fmtCol(visible[visible.length - 1])}`
    : fmtCol(visible[0])

  return (
    <div className="min-w-0 flex-1">
      {label && (
        <div className="flex items-center gap-2 mb-2">
          <span className="w-2.5 h-2.5 rounded-sm shrink-0" style={{ background: accentColor }} />
          <span className="text-[11px] font-semibold" style={{ color: accentColor }}>{label}</span>
          <span className="text-[9px] text-muted-foreground">{periodLabel}</span>
        </div>
      )}
      <div className="w-full">
        <div className="flex w-full gap-0.5">
          {/* Étiquettes jours */}
          <div className="flex flex-col gap-0.5 mr-1 shrink-0 justify-end">
            {DAYS.map(d => (
              <div key={d} className="flex items-center" style={{ height: 'calc((100% - 14px) / 7)' }}>
                <span className="text-[9px] text-muted-foreground w-6 leading-none">{d}</span>
              </div>
            ))}
            <div className="h-3.5" />
          </div>
          {/* Colonnes */}
          {visible.map(col => (
            <div key={col} className="flex flex-col gap-0.5 flex-1 min-w-[14px]">
              {DAYS.map((day, dayIdx) => {
                const val = grid[`${col}-${dayIdx}`] || 0
                const tip = isNormalized
                  ? `${fmtCol(col)} · ${day} : ${val}%`
                  : `${fmtCol(col)} · ${day} : ${val} ${unit}`
                return (
                  <div
                    key={dayIdx}
                    className="w-full aspect-square rounded-[2px] transition-colors cursor-default hover:ring-1 hover:ring-offset-1 hover:ring-current"
                    style={{ backgroundColor: cellColor(val, maxVal, palette) }}
                    title={tip}
                  />
                )
              })}
              <span className="text-[8px] text-muted-foreground text-center mt-0.5 truncate leading-none">
                {fmtCol(col)}
              </span>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}

// ── Contrôles pill ──────────────────────────────────────────────────────────

function PillGroup({ options, value, onChange }) {
  return (
    <div className="flex items-center gap-0.5">
      {options.map(opt => (
        <button
          key={opt.value}
          onClick={() => onChange(opt.value)}
          className={cn(
            'px-2 py-0.5 rounded text-[10px] font-medium transition-colors',
            value === opt.value
              ? 'bg-foreground text-background'
              : 'text-muted-foreground hover:text-foreground hover:bg-accent',
          )}
        >
          {opt.label}
        </button>
      ))}
    </div>
  )
}

// ── Composant principal ─────────────────────────────────────────────────────

/**
 * Calendrier d'activité — heatmap col × jour de semaine.
 *
 * Contrôles internes :
 * - Granularité : Mois / Trimestre / Semestre (regroupe les colonnes)
 * - Vue : Absolu (comptage brut) / Relatif (% par colonne — révèle la structure sans le volume)
 * - Toggle "vs annulations" : deux grilles côte à côte (réservations vert + annulations rouge)
 *
 * @param {Array}      data                [{month, dow, total}] réservations
 * @param {Array|null} dataCmp             Données période comparaison (même format)
 * @param {Array|null} dataCancellations   Données annulations (même format)
 * @param {boolean}    [compareMode]       Force le mode comparatif
 * @param {string}     [comparePeriod]     Libellé période comparaison
 */
export function HeatmapChart({ data = [], dataCmp = null, dataCancellations = null, compareMode, comparePeriod }) {
  const [showCancellations, setShowCancellations] = useState(false)
  const [granularity, setGranularity] = useState('mois')      // 'mois' | 'trimestre' | 'semestre'
  const [normalize, setNormalize]     = useState(false)         // false = absolu, true = relatif

  const cur    = useMemo(() => buildGrid(data,               granularity, normalize), [data,               granularity, normalize])
  const cmp    = useMemo(() => buildGrid(dataCmp,            granularity, normalize), [dataCmp,            granularity, normalize])
  const cancel = useMemo(() => buildGrid(dataCancellations,  granularity, normalize), [dataCancellations,  granularity, normalize])

  if (!cur.cols.length) return null

  const isCompare     = compareMode ?? (dataCmp?.length > 0)
  const hasCancelData = dataCancellations?.length > 0

  const activeMode = showCancellations && hasCancelData ? 'cancel' : (isCompare ? 'compare' : 'none')

  // Sous-titre : période courante + info granularité
  const visibleCols  = cur.cols.slice(-12)
  const periodRange  = visibleCols.length > 1
    ? `${fmtCol(visibleCols[0])} – ${fmtCol(visibleCols[visibleCols.length - 1])}`
    : fmtCol(visibleCols[0])
  const granLabel    = granularity === 'trimestre' ? 'par trimestre' : granularity === 'semestre' ? 'par semestre' : '7 jours × mois'
  const subtitle     = activeMode === 'cancel'
    ? `Réservations vs annulations · ${granLabel}`
    : activeMode === 'compare'
    ? `Période vs comparaison · ${granLabel}`
    : `${periodRange} · ${granLabel}`

  // Unité tooltip
  const unitBookings = normalize ? '%' : 'rés.'
  const unitCancel   = normalize ? '%' : 'annul.'

  // Labels légende selon mode normalize
  const labelsBookings = normalize ? SCALE_LABELS.pct : SCALE_LABELS.current
  const labelsCompare  = normalize ? SCALE_LABELS.pct : SCALE_LABELS.compare
  const labelsCancel   = normalize ? SCALE_LABELS.pct : SCALE_LABELS.cancel

  const granOptions = [
    { value: 'mois',      label: 'Mois' },
    { value: 'trimestre', label: 'Trim.' },
    { value: 'semestre',  label: 'Sem.' },
  ]
  const normalizeOptions = [
    { value: false, label: 'Absolu' },
    { value: true,  label: 'Relatif' },
  ]

  return (
    <div className="rounded-lg border bg-card p-5">
      {/* Header */}
      <div className="flex items-start justify-between mb-3">
        <div>
          <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium inline-flex items-center">
            Calendrier d'activité <InfoTooltip text={CHART_INFO.heatmap} />
          </p>
          <p className="text-[10px] text-muted-foreground mt-0.5">{subtitle}</p>
        </div>
        {hasCancelData && (
          <button
            onClick={() => setShowCancellations(v => !v)}
            className={cn(
              'shrink-0 px-2.5 py-1 rounded text-[10px] font-medium transition-colors border',
              showCancellations
                ? 'bg-red-50 border-red-200 text-red-700 hover:bg-red-100'
                : 'bg-card border-border text-muted-foreground hover:text-foreground hover:bg-accent',
            )}
          >
            {showCancellations ? '← Réservations' : 'vs annulations'}
          </button>
        )}
      </div>

      {/* Contrôles granularité + normalisation */}
      <div className="flex items-center gap-3 mb-4 flex-wrap">
        <PillGroup options={granOptions} value={granularity} onChange={setGranularity} />
        <div className="w-px h-3 bg-border shrink-0" />
        <PillGroup options={normalizeOptions} value={normalize} onChange={v => setNormalize(v === 'true' || v === true)} />
        {normalize && (
          <span className="text-[9px] text-muted-foreground italic">
            % des rés. dans la colonne — révèle la structure sans le volume
          </span>
        )}
      </div>

      {/* Grilles */}
      <div className={activeMode !== 'none' ? 'flex flex-col lg:flex-row lg:gap-8 gap-6 items-start' : ''}>
        <HeatmapGrid
          grid={cur.grid}
          cols={cur.cols}
          maxVal={cur.maxVal}
          label={activeMode !== 'none' ? 'Réservations' : null}
          accentColor={COLORS.current}
          palette={SCALE.current}
          unit={unitBookings}
          isNormalized={normalize}
        />

        {activeMode === 'compare' && cmp.cols.length > 0 && (
          <>
            <div className="hidden lg:block w-px bg-border self-stretch shrink-0" />
            <HeatmapGrid
              grid={cmp.grid}
              cols={cmp.cols}
              maxVal={cmp.maxVal}
              label="Comparaison"
              accentColor={COLORS.compare}
              palette={SCALE.compare}
              unit={unitBookings}
              isNormalized={normalize}
            />
          </>
        )}

        {activeMode === 'cancel' && cancel.cols.length > 0 && (
          <>
            <div className="hidden lg:block w-px bg-border self-stretch shrink-0" />
            <HeatmapGrid
              grid={cancel.grid}
              cols={cancel.cols}
              maxVal={cancel.maxVal}
              label="Annulations"
              accentColor="#dc2626"
              palette={SCALE.cancel}
              unit={unitCancel}
              isNormalized={normalize}
            />
          </>
        )}
      </div>

      {/* Légendes */}
      <div className={cn(
        'flex items-start mt-4 pt-3 border-t gap-4',
        activeMode !== 'none' ? 'flex-col gap-2' : 'flex-row flex-wrap',
      )}>
        <div className="flex flex-col gap-1">
          {activeMode !== 'none' && (
            <span className="text-[9px] text-muted-foreground font-medium">Réservations</span>
          )}
          <div className="flex items-center gap-1.5 flex-wrap">
            {SCALE.current.map((c, i) => (
              <div key={i} className="flex items-center gap-1">
                <div className="w-3 h-3 rounded-sm shrink-0" style={{ backgroundColor: c }} />
                <span className="text-[9px] text-muted-foreground">{labelsBookings[i]}</span>
              </div>
            ))}
          </div>
        </div>

        {activeMode === 'compare' && (
          <div className="flex flex-col gap-1">
            <span className="text-[9px] text-muted-foreground font-medium">
              {comparePeriod ? `Compar. · ${comparePeriod}` : 'Comparaison'}
            </span>
            <div className="flex items-center gap-1.5 flex-wrap">
              {SCALE.compare.map((c, i) => (
                <div key={i} className="flex items-center gap-1">
                  <div className="w-3 h-3 rounded-sm shrink-0" style={{ backgroundColor: c }} />
                  <span className="text-[9px] text-muted-foreground">{labelsCompare[i]}</span>
                </div>
              ))}
            </div>
          </div>
        )}

        {activeMode === 'cancel' && (
          <div className="flex flex-col gap-1">
            <span className="text-[9px] font-medium" style={{ color: '#dc2626' }}>Annulations</span>
            <div className="flex items-center gap-1.5 flex-wrap">
              {SCALE.cancel.map((c, i) => (
                <div key={i} className="flex items-center gap-1">
                  <div className="w-3 h-3 rounded-sm shrink-0" style={{ backgroundColor: c }} />
                  <span className="text-[9px] text-muted-foreground">{labelsCancel[i]}</span>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
