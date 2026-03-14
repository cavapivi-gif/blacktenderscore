/**
 * useConversations — gère la liste des conversations IA.
 * Sources : localStorage (conversations locales de l'owner) + API DB (partagées + owned synced).
 * Les conversations partagées (non-owner) n'ont pas de messages locaux : ils sont chargés à la demande.
 */
import { useState, useCallback, useEffect, useMemo } from 'react'
import { listChats, getChat } from '../lib/chatApi'

const STORAGE_KEY = 'bt_ai_conversations'
const MAX_CONV = 40

function loadLocal() {
  try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]') }
  catch { return [] }
}

function persistLocal(convs) {
  // Ne persiste que les conversations locales (owner sans db_id ou avec db_id)
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(convs.filter(c => !c.remote).slice(0, MAX_CONV))) }
  catch {}
}

/** Normalise une date MySQL ('2026-03-13 10:30:00') en ISO ('2026-03-13T10:30:00Z'). */
function toIso(dt) {
  if (!dt) return new Date().toISOString()
  return dt.includes('T') ? dt : dt.replace(' ', 'T') + 'Z'
}

/** Convertit un chat DB en objet conversation (sans messages — chargés à la demande). */
function dbChatToConv(dbChat) {
  const iso = toIso(dbChat.updated_at)
  return {
    id:           dbChat.uuid,
    db_id:        dbChat.uuid,
    title:        dbChat.title,
    provider:     dbChat.provider,
    createdAt:    iso,
    updatedAt:    iso,
    messages:     [],
    permission:   dbChat.permission,
    owner_id:     dbChat.owner_id,
    owner_color:  dbChat.owner_color,
    participants: [],
    remote:       true,
    filterParams: null,
  }
}

export function useConversations() {
  const [conversations, setConversations] = useState(loadLocal)
  const [activeId,      setActiveId]      = useState(null)
  const [dbLoading,     setDbLoading]     = useState(false)

  // ── Chargement initial des chats DB (partagés + owned synced) ───────────────
  useEffect(() => {
    setDbLoading(true)
    listChats()
      .then(({ chats = [] }) => {
        setConversations(prev => {
          // IDs déjà présents en local
          const localIds = new Set(prev.map(c => c.id))

          // Chats DB non présents localement → conversations "remote"
          const remoteNew = chats
            .filter(c => !localIds.has(c.uuid))
            .map(dbChatToConv)

          // Mettre à jour le flag permission sur les chats locaux déjà synced
          const updated = prev.map(c => {
            const dbMatch = chats.find(d => d.uuid === c.id)
            return dbMatch ? { ...c, permission: dbMatch.permission, db_id: dbMatch.uuid } : c
          })

          return [...updated, ...remoteNew]
        })
      })
      .catch(() => {}) // silencieux si pas d'accès API
      .finally(() => setDbLoading(false))
  }, [])

  const mutate = useCallback((fn) => {
    setConversations(prev => {
      const next = fn(prev)
      persistLocal(next)
      return next
    })
  }, [])

  // ── Actions ─────────────────────────────────────────────────────────────────

  const create = useCallback((provider = 'anthropic', filterParams = null) => {
    const id = `c_${Date.now()}`
    const conv = {
      id,
      title:       'Nouvelle conversation',
      createdAt:   new Date().toISOString(),
      updatedAt:   new Date().toISOString(),
      provider,
      filterParams,
      messages:    [],
      permission:  'owner',
      remote:      false,
    }
    mutate(prev => [conv, ...prev])
    setActiveId(id)
    return id
  }, [mutate])

  const updateMessages = useCallback((id, messages, title) => {
    mutate(prev => prev.map(c => c.id !== id ? c : {
      ...c,
      messages,
      remote: false, // une fois les messages chargés, on les garde localement
      updatedAt: new Date().toISOString(),
      ...(title ? { title } : {}),
    }))
  }, [mutate])

  const updateProvider = useCallback((id, provider) => {
    mutate(prev => prev.map(c => c.id !== id ? c : { ...c, provider }))
  }, [mutate])

  const rename = useCallback((id, title) => {
    const t = title.trim()
    if (!t) return
    mutate(prev => prev.map(c => c.id !== id ? c : { ...c, title: t }))
  }, [mutate])

  const remove = useCallback((id) => {
    mutate(prev => prev.filter(c => c.id !== id))
    setActiveId(prev => prev === id ? null : prev)
  }, [mutate])

  const clearAll = useCallback(() => {
    mutate(() => [])
    setActiveId(null)
  }, [mutate])

  /** Charge les messages d'une conversation remote depuis l'API. */
  const loadRemoteMessages = useCallback(async (id) => {
    try {
      const data = await getChat(id)
      mutate(prev => prev.map(c => c.id !== id ? c : {
        ...c,
        messages:     data.messages ?? [],
        title:        data.title ?? c.title,
        provider:     data.provider ?? c.provider,
        owner_id:     data.owner_id ?? c.owner_id,
        owner_color:  data.owner_color ?? c.owner_color,
        participants: data.participants ?? c.participants ?? [],
        remote:       false,
      }))
    } catch {}
  }, [mutate])

  /** Rafraîchit les messages d'une conversation depuis l'API (polling).
   *  Compare updated_at pour détecter tout changement (ajout, suppression, édition). */
  const refreshMessages = useCallback(async (id) => {
    try {
      const data = await getChat(id)
      mutate(prev => prev.map(c => {
        if (c.id !== id) return c
        const remoteUpdated = data.updated_at ?? data.updatedAt
        // Rien de nouveau si même timestamp
        if (remoteUpdated && c.updatedAt === toIso(remoteUpdated)) return c
        return {
          ...c,
          messages: data.messages ?? c.messages,
          participants: data.participants ?? c.participants ?? [],
          updatedAt: remoteUpdated ? toIso(remoteUpdated) : c.updatedAt,
        }
      }))
    } catch {}
  }, [mutate])

  const activeConv = conversations.find(c => c.id === activeId) ?? null

  // Group conversations by date for sidebar display (memoized)
  const grouped = useMemo(() => {
    const now = new Date()
    const todayStr  = now.toDateString()
    const yest      = new Date(now - 86400000).toDateString()
    const weekAgo   = new Date(now - 7 * 86400000)
    const g = { today: [], yesterday: [], week: [], older: [] }
    for (const c of conversations) {
      const d  = new Date(c.updatedAt || c.createdAt)
      const ds = d.toDateString()
      if (ds === todayStr)   g.today.push(c)
      else if (ds === yest)  g.yesterday.push(c)
      else if (d >= weekAgo) g.week.push(c)
      else                   g.older.push(c)
    }
    return g
  }, [conversations])

  return {
    conversations, activeId, activeConv, grouped, dbLoading,
    setActiveId, create, updateMessages, updateProvider,
    remove, rename, clearAll, loadRemoteMessages, refreshMessages,
  }
}
