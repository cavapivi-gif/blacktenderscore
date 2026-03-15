/**
 * usePusherSync — abonnement Pusher pour la collaboration en temps réel.
 *
 * Remplace le polling setInterval(refreshMessages, 3000) dans AIChat.jsx.
 * Gain : 100% des requêtes de polling supprimées pour les conversations partagées.
 *
 * Setup requis (une seule fois) :
 *   1. Compte Pusher gratuit → https://pusher.com (plan Sandbox : 200k msg/jour)
 *   2. wp-config.php :
 *        define('BT_PUSHER_KEY',    'votre_app_key');
 *        define('BT_PUSHER_CLUSTER','eu');
 *        define('BT_PUSHER_SECRET', 'votre_secret');
 *        define('BT_PUSHER_APP_ID', 'votre_app_id');
 *   3. PHP : require_once 'vendor/autoload.php'; (après composer require pusher/pusher-php-server)
 *   4. Ajouter à window.btBackoffice dans class-backoffice.php :
 *        'pusher_key'     => defined('BT_PUSHER_KEY')     ? BT_PUSHER_KEY     : '',
 *        'pusher_cluster' => defined('BT_PUSHER_CLUSTER') ? BT_PUSHER_CLUSTER : 'eu',
 *
 * PHP — déclencher un event après sauvegarde d'un message (dans trait-rest-chat.php) :
 *   if (defined('BT_PUSHER_KEY') && BT_PUSHER_KEY) {
 *       $pusher = new \Pusher\Pusher(BT_PUSHER_KEY, BT_PUSHER_SECRET, BT_PUSHER_APP_ID,
 *                                    ['cluster' => BT_PUSHER_CLUSTER, 'useTLS' => true]);
 *       $pusher->trigger("conv-{$conv_uuid}", 'message.added', []);
 *   }
 */
import { useEffect, useRef } from 'react'

let PusherClass = null

async function loadPusher() {
  if (PusherClass) return PusherClass
  try {
    const mod = await import('pusher-js')
    PusherClass = mod.default ?? mod
    return PusherClass
  } catch {
    return null
  }
}

/**
 * S'abonne au channel Pusher d'une conversation.
 * Si Pusher n'est pas configuré, ne fait rien (le polling fallback reste en place).
 *
 * @param {string|null} convId      UUID de la conversation active
 * @param {Function}    onMessage   Appelé quand 'message.added' est reçu
 * @param {boolean}     enabled     true = s'abonner (conversation partagée)
 */
export function usePusherSync(convId, onMessage, enabled) {
  const channelRef = useRef(null)
  const pusherRef  = useRef(null)

  useEffect(() => {
    const key     = window.btBackoffice?.pusher_key
    const cluster = window.btBackoffice?.pusher_cluster ?? 'eu'

    if (!enabled || !convId || !key) return

    let cancelled = false

    loadPusher().then(Pusher => {
      if (!Pusher || cancelled) return

      // Réutiliser la connexion existante si même config
      if (!pusherRef.current || pusherRef.current.key !== key) {
        pusherRef.current?.disconnect()
        pusherRef.current = new Pusher(key, { cluster, forceTLS: true })
      }

      const channel = pusherRef.current.subscribe(`conv-${convId}`)
      channelRef.current = channel

      channel.bind('message.added', () => {
        if (!cancelled) onMessage?.()
      })
    })

    return () => {
      cancelled = true
      channelRef.current?.unbind_all()
      if (pusherRef.current && convId) {
        pusherRef.current.unsubscribe(`conv-${convId}`)
      }
      channelRef.current = null
    }
  }, [convId, enabled]) // eslint-disable-line react-hooks/exhaustive-deps
}
