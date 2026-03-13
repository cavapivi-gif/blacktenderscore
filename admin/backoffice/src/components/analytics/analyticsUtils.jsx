/**
 * Analytics utilities — chart constants and shared micro-components.
 * Formatters are centralised in lib/utils.js and re-exported here for convenience.
 */
import { NavLink } from 'react-router-dom'
import { fmtNum, fmtDuration, fmtPct, fmtPctRaw, fmtDate, calcDelta } from '../../lib/utils'

// Re-export formatters so existing consumer imports don't break
export { fmtNum, fmtDuration, fmtPct, fmtPctRaw, fmtDate, calcDelta }

// ── Chart colors (hardcodées — CSS vars ne marchent pas en SVG Recharts) ──────
export const C_CURRENT   = '#10b981'  // emerald-500  — sessions / volume principal
export const C_COMPARE   = '#818cf8'  // indigo-400   — période de comparaison
export const C_BOOKINGS  = '#f59e0b'  // amber-500    — réservations
export const C_REVENUE   = '#6366f1'  // indigo-500   — CA
export const C_GRID      = '#f1f5f9'  // slate-100    — grille de chart
export const C_AXIS      = '#94a3b8'  // slate-400    — labels d'axe
export const C_PALETTE   = [
  '#10b981','#6366f1','#f59e0b','#ef4444',
  '#8b5cf6','#06b6d4','#ec4899','#84cc16',
  '#f97316','#64748b',
]

// ── KpiCell — cellule Bloomberg-style pour le strip horizontal ────────────────

/**
 * Cellule KPI compacte pour le strip CrossKpi.
 * @param {boolean} invertDelta - Vrai si une hausse est mauvaise (ex: bounce rate)
 */
export function KpiCell({ label, value, delta, invertDelta = false, loading = false }) {
  const vis = (invertDelta && delta != null) ? -delta : delta
  const pos = vis != null ? vis >= 0 : null

  if (loading) {
    return (
      <div className="flex flex-col gap-1 px-4 py-2 min-w-[110px]">
        <div className="h-2.5 w-16 bg-muted rounded animate-pulse" />
        <div className="h-5 w-20 bg-muted rounded animate-pulse" />
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-0.5 px-4 py-2 min-w-[110px]">
      <p className="text-[10px] text-muted-foreground uppercase tracking-wider font-medium whitespace-nowrap">{label}</p>
      <div className="flex items-baseline gap-1.5">
        <span className="text-sm font-bold tabular-nums leading-none">{value}</span>
        {delta != null && (
          <span
            className="text-[10px] font-semibold px-1 py-px rounded tabular-nums leading-none"
            style={{
              backgroundColor: pos ? '#ecfdf5' : '#fef2f2',
              color:           pos ? '#059669' : '#dc2626',
            }}
          >
            {delta > 0 ? '+' : ''}{delta}{typeof delta === 'number' && !String(delta).includes('%') ? '%' : ''}
          </span>
        )}
      </div>
    </div>
  )
}

// ── KpiCard — carte standard pour sections GA4/SC/Business ───────────────────

/**
 * Carte KPI avec badge delta. Compatible avec le design system existant.
 * @param {boolean} invertDelta - Vrai si une hausse est mauvaise (ex: bounce rate)
 */
export function KpiCard({ label, value, sub, delta, invertDelta = false }) {
  const vis = (invertDelta && delta != null) ? -delta : delta
  const pos = vis != null ? vis >= 0 : null
  return (
    <div className="rounded-lg border bg-card p-4 flex flex-col gap-1">
      <p className="text-[11px] text-muted-foreground uppercase tracking-wider font-medium">{label}</p>
      <div className="flex items-end justify-between gap-2">
        <p className="text-2xl font-bold tabular-nums leading-none tracking-tight">{value}</p>
        {delta != null && (
          <span
            className="text-[11px] font-semibold px-1.5 py-0.5 rounded tabular-nums"
            style={{
              backgroundColor: pos ? '#ecfdf5' : '#fef2f2',
              color:           pos ? '#059669' : '#dc2626',
            }}
          >
            {delta > 0 ? '+' : ''}{delta}%
          </span>
        )}
      </div>
      {sub && <p className="text-[11px] text-muted-foreground truncate">{sub}</p>}
    </div>
  )
}

// ── RelBar — mini barre relative ──────────────────────────────────────────────

/** Barre de progression relative (0–100%), utilisée dans les listes de pages/requêtes. */
export function RelBar({ value, max, color = C_CURRENT }) {
  const pct = max > 0 ? Math.min(100, (value / max) * 100) : 0
  return (
    <div className="mt-0.5 h-1 bg-muted rounded-full overflow-hidden w-full">
      <div className="h-full rounded-full" style={{ width: `${pct}%`, background: color }} />
    </div>
  )
}

// ── PosBadge — badge de position GSC ─────────────────────────────────────────

/** Badge de position Google (vert ≤3, amber ≤10, rouge >10). */
export function PosBadge({ pos }) {
  const color = pos <= 3 ? '#15803d' : pos <= 10 ? '#b45309' : '#dc2626'
  const bg    = pos <= 3 ? '#dcfce7' : pos <= 10 ? '#fef3c7' : '#fee2e2'
  return (
    <span className="text-[10px] font-bold px-1.5 py-0.5 rounded tabular-nums" style={{ color, backgroundColor: bg }}>
      #{pos}
    </span>
  )
}

// ── NotConfigured — placeholder "non configuré" ───────────────────────────────

/** Affichage quand un service Google n'est pas encore configuré. */
export function NotConfigured({ service }) {
  return (
    <div className="rounded-lg border bg-card p-8 flex flex-col items-center gap-3 text-center">
      <div className="w-12 h-12 rounded-full bg-muted flex items-center justify-center">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-muted-foreground">
          <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
          <line x1="12" y1="9" x2="12" y2="13"/>
          <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
      </div>
      <div>
        <p className="font-semibold">{service} non configuré</p>
        <p className="text-sm text-muted-foreground mt-1">
          Ajoutez vos credentials Google (service account JSON) dans{' '}
          <NavLink to="/settings/api" className="underline hover:text-foreground">
            Réglages → Connexion API → Google
          </NavLink>.
        </p>
      </div>
    </div>
  )
}

// ── CustomTooltip — tooltip Recharts partagé ──────────────────────────────────

/** Tooltip Recharts stylisé, compatible avec tous les charts analytics. */
export function CustomTooltip({ active, payload, label, formatter }) {
  if (!active || !payload?.length) return null
  return (
    <div className="rounded-lg border bg-white shadow-md px-3 py-2 text-xs space-y-1">
      <p className="font-medium text-muted-foreground">{fmtDate(label)}</p>
      {payload.map((p, i) => (
        <p key={i} style={{ color: p.color }}>
          {p.name} : <span className="font-semibold">{formatter ? formatter(p.value, p.name) : fmtNum(p.value)}</span>
        </p>
      ))}
    </div>
  )
}
