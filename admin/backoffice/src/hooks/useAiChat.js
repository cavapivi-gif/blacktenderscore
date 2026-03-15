/**
 * useAiChat — wraps @ai-sdk/react useChat pour le contexte WP/admin-ajax.
 *
 * Remplace :
 *   - streamChat() + ReadableStream reader loop (~60 lignes dans AIChat.jsx)
 *   - États streaming, streamText, showThinking, abortRef
 *
 * Design :
 *   - useConversations reste le store de persistance (localStorage + DB)
 *   - useChat gère UNIQUEMENT le streaming en cours
 *   - Après chaque réponse complète, setMessages([]) reset le buffer interne
 *   - experimental_prepareRequestBody injecte l'historique + contexte réels
 */
import { useRef, useCallback } from 'react'
import { useChat } from '@ai-sdk/react'

const { ajax_url, nonce } = window.btBackoffice ?? {}

export function useAiChat({ onFinish, onError }) {
  // Refs pour accéder aux valeurs courantes depuis experimental_prepareRequestBody
  // (les closures captures ne se mettent pas à jour dans le hook useChat)
  const historyRef    = useRef([])
  const providerRef   = useRef('anthropic')
  const dateRangeRef  = useRef({ from: '', to: '' })

  const { append, messages, isLoading, stop, setMessages } = useChat({
    api: `${ajax_url}?action=bt_ai_chat`,
    headers: { 'X-WP-Nonce': nonce },
    streamProtocol: 'text',

    // Reconstruit le payload réel à partir de l'historique useConversations
    // plutôt que de l'état interne de useChat (qui est réinitialisé après chaque tour)
    experimental_prepareRequestBody: ({ messages: chatMsgs }) => {
      const lastUserMsg = chatMsgs.at(-1)
      const history = [
        ...historyRef.current.slice(-19).map(m => ({ role: m.role, content: m.content })),
        { role: 'user', content: lastUserMsg?.content ?? '' },
      ]
      return {
        messages: history,
        from:     dateRangeRef.current.from,
        to:       dateRangeRef.current.to,
        provider: providerRef.current,
      }
    },

    onFinish: (message) => {
      // Reset le buffer interne — useConversations est le seul store
      setMessages([])
      onFinish?.(message.content, providerRef.current)
    },

    onError: (err) => {
      setMessages([])
      onError?.(err)
    },
  })

  // Démarre un stream.
  // history    = messages depuis useConversations (incluant le nouveau message user avec contexte injecté)
  // userContent = contenu à envoyer (avec contexte potentiellement injecté)
  const stream = useCallback((history, userContent, provider, dateRange) => {
    historyRef.current   = history
    providerRef.current  = provider
    dateRangeRef.current = dateRange
    return append({ role: 'user', content: userContent })
  }, [append])

  // Texte actuellement streamé (dernier message assistant dans le buffer interne)
  const streamText    = messages.at(-1)?.role === 'assistant' ? messages.at(-1).content : ''
  const showThinking  = isLoading && streamText === ''

  return { stream, stop, isLoading, streamText, showThinking }
}
