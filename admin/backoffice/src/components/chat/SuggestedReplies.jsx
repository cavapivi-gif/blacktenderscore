import { memo } from 'react'
import { motion } from 'motion/react'

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

export const SuggestedReplies = memo(function SuggestedReplies({ intents, onSend }) {
  const suggestions = getSuggestions(intents)
  return (
    <motion.div
      initial={{ opacity: 0, y: 6 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.25, delay: 0.35, ease: 'easeOut' }}
      className="flex flex-wrap gap-2 mt-2 pl-10"
    >
      {suggestions.map((s, i) => (
        <button
          key={i}
          onClick={() => onSend(s)}
          className="text-xs px-3 py-1.5 rounded-full border border-border/70 text-muted-foreground hover:text-foreground hover:border-foreground/30 hover:bg-accent transition-all"
        >
          {s}
        </button>
      ))}
    </motion.div>
  )
})
