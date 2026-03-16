import { fmtCurrency } from './utils'

// Chart colors hardcodées (CSS vars ne marchent pas dans SVG)
export const CHART_REVENUE = '#10b981'
export const CHART_GRID    = '#e5e5e5'
export const CHART_AXIS    = '#737373'
export const CHART_CANCEL  = '#ef4444'
export const CHART_BOOK    = '#0a0a0a'
export const CHART_PALETTE = ['#10b981','#6366f1','#f59e0b','#ef4444','#8b5cf6','#06b6d4']

// ── Intent detection & date parsing ─────────────────────────────────────────

const MONTH_MAP = {
  'janvier':1,'jan':1,'février':2,'fev':2,'fév':2,'mars':3,'mar':3,
  'avril':4,'avr':4,'mai':5,'juin':6,'jun':6,'juillet':7,'jul':7,
  'août':8,'aout':8,'septembre':9,'sep':9,'octobre':10,'oct':10,
  'novembre':11,'nov':11,'décembre':12,'dec':12,'déc':12,
}

export function parseMessageDate(text) {
  const lower = text.toLowerCase()
  const names = Object.keys(MONTH_MAP).join('|')
  const rxFull = new RegExp(`\\b(${names})\\s+(\\d{4})\\b`)
  const rxMono = new RegExp(`\\b(${names})\\b`)
  const curYear = new Date().getFullYear()
  let m = lower.match(rxFull)
  if (!m) m = lower.match(rxMono)
  if (!m) return null
  const month = MONTH_MAP[m[1]]
  const year  = m[2] ? parseInt(m[2]) : curYear
  const from  = `${year}-${String(month).padStart(2,'0')}-01`
  const to    = `${year}-${String(month).padStart(2,'0')}-${new Date(year, month, 0).getDate()}`
  return { from, to }
}

export function detectDataIntent(text) {
  const intents = new Set()
  if (/annulation|annul[ée]|cancel/i.test(text))                          intents.add('cancellation')
  if (/\bca\b|chiffre.d.affaire|revenu|recette/i.test(text))              intents.add('revenue')
  if (/réservation|booking|commande|vendu/i.test(text))                   intents.add('bookings')
  if (/produit|activit|offre|article|\btop\b/i.test(text))                intents.add('products')
  if (/saisonnali|évolution|progression|tendance|trend/i.test(text))      intents.add('trend')
  if (/panier|basket|moyen/i.test(text))                                  intents.add('basket')
  return [...intents]
}

export function buildDataContext(stats, from, to) {
  const kpis     = stats.kpis     ?? {}
  const chart    = stats.chart    ?? stats.monthly ?? []
  const products = stats.by_product ?? []
  const lines = [
    `[DONNÉES RÉELLES — ${from} → ${to}]`,
    `CA total : ${fmtCurrency(kpis.revenue_total ?? 0)}`,
    `Réservations : ${kpis.bookings_count ?? 0}`,
    `Annulations : ${kpis.cancellations_count ?? 0} (taux : ${kpis.cancellation_rate ?? 0}%)`,
    `Panier moyen : ${fmtCurrency(kpis.avg_basket ?? 0)}`,
  ]
  if (chart.length) {
    lines.push('\nÉvolution par période :')
    chart.forEach(p => lines.push(`  ${p.label} → CA: ${fmtCurrency(p.revenue)}, rés: ${p.bookings}, annul: ${p.cancellations}`))
  }
  if (products.length) {
    lines.push('\nTop produits :')
    products.slice(0,5).forEach((p,i) => lines.push(`  ${i+1}. ${p.product_name ?? p.name} — ${p.bookings} rés, CA: ${fmtCurrency(p.revenue)}`))
  }
  return lines.join('\n')
}

export const SUGGESTIONS = [
  'Quels leviers pour augmenter le revenu moyen par réservation ?',
  'Identifie les signaux faibles dans mes données.',
  'Analyse ma saisonnalité et les pics de demande.',
  '3 actions concrètes pour améliorer les performances ce mois-ci.',
  'Quels canaux sous-performent et pourquoi ?',
  'Quelles opportunités de croissance non exploitées vois-tu ?',
]

export function getSuggestions(intents = []) {
  if (intents.includes('cancellation')) return [
    'Quelles causes expliquent ces annulations ?',
    "Comparer le taux d'annulation par produit",
    'Comment réduire les annulations ?',
  ]
  if (intents.includes('revenue') || intents.includes('trend')) return [
    "Comparer avec la même période l'an dernier",
    'Détailler l\'évolution par produit',
    'Quels leviers pour augmenter le CA ?',
  ]
  if (intents.includes('products')) return [
    'Quel produit a le meilleur panier moyen ?',
    'Quels produits sous-performent ?',
    'Évolution des ventes sur 12 mois',
  ]
  if (intents.includes('bookings') || intents.includes('basket')) return [
    'Analyser la saisonnalité des réservations',
    'Top produits de cette période',
    'Quels canaux génèrent le plus de réservations ?',
  ]
  return [
    'Quels leviers prioritaires pour progresser ?',
    'Analyse les tendances sur 6 mois',
    'Identifie les opportunités non exploitées',
  ]
}
