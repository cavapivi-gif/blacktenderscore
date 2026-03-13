/**
 * Configuration centrale des providers IA.
 * Associe chaque provider key → label, modèle, fichier Lottie, couleur d'accent.
 * Utilisé par les pages Settings, AIChat, EventsCorrelator.
 */

// Imports Lottie (bundlés par Vite)
import claudeLottie from '../assets/lottie/claude.json'
import openaiLottie from '../assets/lottie/openai.json'
import geminiLottie from '../assets/lottie/gemini.json'

export const AI_PROVIDERS = {
  anthropic: {
    key:       'anthropic',
    label:     'Claude',
    sublabel:  'Anthropic',
    model:     'claude-sonnet-4-6',
    lottie:    claudeLottie,
    iconKey:   'Claude',
    accent:    '#cc785c',       // Claude orange-copper
    placeholder: 'sk-ant-...',
  },
  openai: {
    key:       'openai',
    label:     'GPT-4o',
    sublabel:  'OpenAI',
    model:     'gpt-4o',
    lottie:    openaiLottie,
    iconKey:   'OpenAI',
    accent:    '#10a37f',       // OpenAI green
    placeholder: 'sk-...',
  },
  gemini: {
    key:       'gemini',
    label:     'Gemini 1.5 Pro',
    sublabel:  'Google',
    model:     'gemini-1.5-pro',
    lottie:    geminiLottie,
    iconKey:   'Gemini',
    accent:    '#4285f4',       // Google blue
    placeholder: 'AIza...',
  },
  mistral: {
    key:       'mistral',
    label:     'Mistral Large',
    sublabel:  'Mistral AI',
    model:     'mistral-large-latest',
    lottie:    null,            // pas de Lottie — utilise @lobehub/icons animé
    iconKey:   'Mistral',
    accent:    '#ff7000',       // Mistral orange
    placeholder: '...',
  },
  grok: {
    key:       'grok',
    label:     'Grok 3',
    sublabel:  'xAI',
    model:     'grok-3',
    lottie:    null,
    iconKey:   'Grok',
    accent:    '#1a1917',       // xAI black
    placeholder: 'xai-...',
  },
  meta: {
    key:       'meta',
    label:     'Llama 3.3 70B',
    sublabel:  'Meta AI',
    model:     'llama-3.3-70b-versatile',  // via Groq
    lottie:    null,
    iconKey:   'MetaAI',
    accent:    '#0082fb',       // Meta blue
    placeholder: 'gsk_...',    // clé Groq
  },
}

/** Retourne la config d'un provider, fallback anthropic */
export function getProvider(key) {
  return AI_PROVIDERS[key] ?? AI_PROVIDERS.anthropic
}

/** Liste ordonnée pour les selects/UI */
export const PROVIDER_LIST = Object.values(AI_PROVIDERS)
