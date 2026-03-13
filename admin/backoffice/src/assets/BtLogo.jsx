/**
 * Logo BlackTenders — monogramme "BT" + ancre stylisée.
 * Utilise currentColor pour s'adapter au thème clair/sombre.
 *
 * @param {number} size    Largeur/hauteur en px (défaut 28)
 * @param {string} className
 */
export default function BtLogo({ size = 28, className = '' }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 40 40"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      className={className}
      aria-label="BlackTenders"
    >
      {/* Cercle fond */}
      <circle cx="20" cy="20" r="19" fill="currentColor" fillOpacity="0.07" stroke="currentColor" strokeOpacity="0.15" strokeWidth="1" />

      {/* Ancre — barre transversale */}
      <line x1="12" y1="19" x2="28" y2="19" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />

      {/* Anneau ancre */}
      <circle cx="20" cy="13" r="3" stroke="currentColor" strokeWidth="1.5" fill="none" />

      {/* Tige ancre */}
      <line x1="20" y1="16" x2="20" y2="30" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />

      {/* Branches ancre gauche */}
      <path d="M20 30 C17 28 13 26 12 23" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" fill="none" />
      {/* Branches ancre droite */}
      <path d="M20 30 C23 28 27 26 28 23" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" fill="none" />
    </svg>
  )
}
