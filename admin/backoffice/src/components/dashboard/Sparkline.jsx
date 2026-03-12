import { COLORS } from '../../lib/constants'

/**
 * Tiny SVG sparkline — Bloomberg-style inline chart.
 */
export function Sparkline({ data = [], color = COLORS.current, w = 64, h = 24 }) {
  if (!data.length || data.every(v => v === 0)) return null
  const max = Math.max(...data, 1)
  const min = Math.min(...data, 0)
  const range = max - min || 1
  const pts = data.map((v, i) => {
    const x = (i / Math.max(data.length - 1, 1)) * w
    const y = h - ((v - min) / range) * (h - 3) - 1.5
    return `${x.toFixed(1)},${y.toFixed(1)}`
  })

  // Fill area under the line
  const firstX = '0'
  const lastX = w.toFixed(1)
  const fillPath = `M${firstX},${h} L${pts.join(' L')} L${lastX},${h} Z`

  return (
    <svg width={w} height={h} viewBox={`0 0 ${w} ${h}`} className="shrink-0">
      <path d={fillPath} fill={color} fillOpacity="0.08" />
      <path d={`M${pts.join(' L')}`} fill="none" stroke={color} strokeWidth="1.5"
        strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}
