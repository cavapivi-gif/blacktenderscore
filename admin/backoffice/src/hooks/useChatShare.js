/**
 * Hook useChatShare — gère l'état du panel de partage d'une conversation.
 * Charge les partages, expose les actions invite/update/revoke/sync.
 */
import { useState, useCallback } from 'react'
import { syncChat, listShares, addShare, updateShare, removeShare, searchUsers } from '../lib/chatApi'

export function useChatShare(conv) {
  const [shares,   setShares]   = useState([])
  const [loading,  setLoading]  = useState(false)
  const [syncing,  setSyncing]  = useState(false)
  const [error,    setError]    = useState(null)
  const [synced,   setSynced]   = useState(!!conv?.db_id) // chat déjà persisté en DB

  /** Synchronise le chat en DB si pas encore fait, retourne l'uuid. */
  const ensureSynced = useCallback(async () => {
    if (synced) return conv.id
    setSyncing(true)
    try {
      await syncChat(conv)
      setSynced(true)
      return conv.id
    } finally {
      setSyncing(false)
    }
  }, [conv, synced])

  /** Charge la liste des partages depuis l'API. */
  const loadShares = useCallback(async () => {
    if (!conv?.id) return
    setLoading(true)
    setError(null)
    try {
      const uuid = await ensureSynced()
      const res  = await listShares(uuid)
      setShares(res.shares ?? [])
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }, [conv?.id, ensureSynced])

  /** Invite un utilisateur (permission: 'read' | 'write'). */
  const invite = useCallback(async (userId, permission) => {
    const res = await addShare(conv.id, userId, permission)
    setShares(res.shares ?? [])
  }, [conv?.id])

  /** Met à jour la permission ou la couleur d'un invité. */
  const update = useCallback(async (userId, data) => {
    const res = await updateShare(conv.id, userId, data)
    setShares(res.shares ?? [])
  }, [conv?.id])

  /** Révoque l'accès d'un invité. */
  const revoke = useCallback(async (userId) => {
    await removeShare(conv.id, userId)
    setShares(prev => prev.filter(s => String(s.user_id) !== String(userId)))
  }, [conv?.id])

  return {
    shares, loading, syncing, error, synced,
    loadShares, invite, update, revoke,
    searchUsers: (q, exclude) => searchUsers(q, exclude),
  }
}
