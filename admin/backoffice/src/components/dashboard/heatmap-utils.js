import { COLORS } from '../../lib/constants'

export const DAYS     = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
export const DOW_MAP  = { 2: 0, 3: 1, 4: 2, 5: 3, 6: 4, 7: 5, 1: 6 }
const MONTH_FR = ['jan','f\u00e9v','mar','avr','mai','jun','jul','ao\u00fb','sep','oct','nov','d\u00e9c']

export const SCALE = {
  current: [COLORS.map_empty, COLORS.map_low,        COLORS.map_mid,        COLORS.map_high,        COLORS.map_peak],
  compare: [COLORS.map_cmp_empty, COLORS.map_cmp_low,    COLORS.map_cmp_mid,    COLORS.map_cmp_high,    COLORS.map_cmp_peak],
  cancel:  [COLORS.map_cancel_empty, COLORS.map_cancel_low, COLORS.map_cancel_mid, COLORS.map_cancel_high, COLORS.map_cancel_peak],
}

export const SCALE_LABELS = {
  current: ['0 r\u00e9s.',   'Calme',   'Mod\u00e9r\u00e9',  'Actif',   'Pic'],
  compare: ['0 r\u00e9s.',   'Calme',   'Mod\u00e9r\u00e9',  'Actif',   'Pic'],
  cancel:  ['0 annul.', 'Rare',    'Notable', '\u00c9lev\u00e9',   'Critique'],
  pct:     ['0%',       'Faible',  'Moyen',   'Fort',    'Dominant'],
}

/** Cl\u00e9 de colonne selon la granularit\u00e9 choisie. */
export function getColKey(month, granularity) {
  if (granularity === 'mois') return month
  const [y, mo] = month.split('-')
  const m = +mo
  if (granularity === 'trimestre') return `${y}-Q${Math.ceil(m / 3)}`
  if (granularity === 'semestre')  return `${y}-S${m <= 6 ? 1 : 2}`
  return month
}

/** Label lisible selon la forme de la cl\u00e9. */
export function fmtCol(key) {
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

export function cellColor(val, maxVal, palette) {
  if (!val) return palette[0]
  const t = val / maxVal
  if (t < 0.25) return palette[1]
  if (t < 0.5)  return palette[2]
  if (t < 0.75) return palette[3]
  return palette[4]
}

/**
 * Transforme les donn\u00e9es brutes en grille col \u00d7 jour.
 */
export function buildGrid(data, granularity = 'mois', normalize = false) {
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
