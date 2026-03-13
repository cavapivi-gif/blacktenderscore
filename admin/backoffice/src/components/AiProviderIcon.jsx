/**
 * Icônes providers IA — utilise les SVGs brand depuis src/assets/img/logos/ai/.
 * Vite les bundle et optimise automatiquement.
 */
import claudeSvg   from '../assets/img/logos/ai/claude-color.svg'
import openaiSvg   from '../assets/img/logos/ai/openai.svg'
import geminiSvg   from '../assets/img/logos/ai/gemini-color.svg'
import mistralSvg  from '../assets/img/logos/ai/mistral-color.svg'
import metaSvg     from '../assets/img/logos/ai/meta-color.svg'
import grokSvg     from '../assets/img/logos/ai/grok-color.svg'

const LOGOS = {
  Claude:  claudeSvg,
  OpenAI:  openaiSvg,
  Gemini:  geminiSvg,
  Mistral: mistralSvg,
  MetaAI:  metaSvg,
  Grok:    grokSvg,
}

/**
 * @param {string} iconKey   Clé issue de AI_PROVIDERS[x].iconKey
 * @param {number} size      Taille en px (défaut 24)
 * @param {string} className
 */
export default function AiProviderIcon({ iconKey, size = 24, className }) {
  const src = LOGOS[iconKey]
  if (!src) return null
  return (
    <img
      src={src}
      width={size}
      height={size}
      alt={iconKey}
      className={className}
      style={{ display: 'inline-block', flexShrink: 0, objectFit: 'contain' }}
    />
  )
}
