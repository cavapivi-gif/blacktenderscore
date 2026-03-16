import { useState, useRef } from 'react'
import { motion } from 'motion/react'
import {
  Plus, Trash, ChatLines, EditPencil, Sparks,
} from 'iconoir-react'

// ─────────────────────────────────────────────────────────────────────────────
// Conversation sidebar — with inline rename
// ─────────────────────────────────────────────────────────────────────────────

function ConvItem({ conv, isActive, onSelect, onDelete, onRename }) {
  const [editing, setEditing]     = useState(false)
  const [draft,   setDraft]       = useState('')
  const [confirm, setConfirm]     = useState(false)
  const inputRef  = useRef(null)
  const timerRef  = useRef(null)

  function startEdit(e) {
    e.stopPropagation()
    setDraft(conv.title)
    setEditing(true)
    setTimeout(() => inputRef.current?.select(), 10)
  }

  function commitEdit() {
    if (draft.trim()) onRename(conv.id, draft.trim())
    setEditing(false)
  }

  function onKeyDown(e) {
    if (e.key === 'Enter')  { e.preventDefault(); commitEdit() }
    if (e.key === 'Escape') { setEditing(false) }
    e.stopPropagation()
  }

  return (
    <motion.div
      initial={{ opacity: 0, x: -8 }}
      animate={{ opacity: 1, x: 0 }}
      className={`group flex items-center gap-2 px-3 py-2 rounded-lg mx-1 cursor-pointer transition-colors text-sm relative ${
        isActive
          ? 'bg-accent text-foreground font-medium'
          : 'text-muted-foreground hover:text-foreground hover:bg-accent/60'
      }`}
      onClick={() => !editing && onSelect(conv.id)}
    >
      <ChatLines width={13} height={13} strokeWidth={1.5} className="shrink-0 opacity-50" />

      {editing ? (
        <input
          ref={inputRef}
          value={draft}
          onChange={e => setDraft(e.target.value)}
          onKeyDown={onKeyDown}
          onBlur={commitEdit}
          className="flex-1 text-xs bg-background border border-ring rounded px-1.5 py-0.5 focus:outline-none min-w-0"
          onClick={e => e.stopPropagation()}
        />
      ) : (
        <span className="flex-1 truncate text-xs">{conv.title}</span>
      )}

      {/* Badge conversations partagées / collaborateurs */}
      {!editing && conv.permission && conv.permission !== 'owner' && (
        <span className="shrink-0 text-[9px] px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-600 font-medium leading-none">
          Partagé
        </span>
      )}
      {!editing && conv.permission === 'owner' && conv.db_id && (conv.participants?.length ?? 0) > 1 && (
        <div className="shrink-0 flex -space-x-1">
          {(conv.participants ?? []).filter(p => p.user_id !== window.btBackoffice?.current_user?.id).slice(0, 2).map(p => (
            <img key={p.user_id} src={p.avatar} alt={p.display_name} title={p.display_name}
              className="w-3.5 h-3.5 rounded-full border border-background" />
          ))}
        </div>
      )}

      {!editing && (
        <div className="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-all shrink-0">
          <button
            onClick={startEdit}
            title="Renommer"
            className="p-0.5 rounded hover:text-foreground transition-colors"
          >
            <EditPencil width={10} height={10} strokeWidth={1.5} />
          </button>
          {confirm ? (
            <button
              onClick={e => { e.stopPropagation(); clearTimeout(timerRef.current); setConfirm(false); onDelete(conv.id) }}
              onBlur={() => { clearTimeout(timerRef.current); setConfirm(false) }}
              title="Confirmer la suppression"
              className="px-1.5 py-0.5 rounded text-[9px] font-medium bg-destructive text-white hover:bg-destructive/90 transition-colors leading-none"
            >
              Supprimer ?
            </button>
          ) : (
            <button
              onClick={e => {
                e.stopPropagation()
                clearTimeout(timerRef.current)
                setConfirm(true)
                timerRef.current = setTimeout(() => setConfirm(false), 3000)
              }}
              title="Supprimer"
              className="p-0.5 rounded hover:text-destructive transition-colors"
            >
              <Trash width={11} height={11} strokeWidth={1.5} />
            </button>
          )}
        </div>
      )}
    </motion.div>
  )
}

function ConvGroup({ label, convs, activeId, onSelect, onDelete, onRename }) {
  if (!convs.length) return null
  return (
    <div className="mb-4">
      <p className="text-[10px] uppercase tracking-widest text-muted-foreground/50 font-medium px-3 mb-1">{label}</p>
      {convs.map(c => (
        <ConvItem
          key={c.id}
          conv={c}
          isActive={c.id === activeId}
          onSelect={onSelect}
          onDelete={onDelete}
          onRename={onRename}
        />
      ))}
    </div>
  )
}

export function ConvSidebar({ grouped, activeId, onSelect, onNew, onDelete, onRename, onClear }) {
  const total = Object.values(grouped).flat().length
  return (
    <aside className="w-full flex flex-col bg-background overflow-hidden">
      <div className="p-3 border-b">
        <button
          onClick={onNew}
          className="w-full flex items-center gap-2 px-3 py-2 rounded-lg border border-dashed border-border text-xs text-muted-foreground hover:text-foreground hover:border-foreground/30 hover:bg-accent/50 transition-all"
        >
          <Plus width={13} height={13} strokeWidth={2} />
          Nouvelle conversation
        </button>
      </div>

      <div className="flex-1 overflow-y-auto py-2">
        {total === 0 ? (
          <div className="flex flex-col items-center gap-2 py-10 px-4 text-center">
            <Sparks width={20} height={20} strokeWidth={1.5} className="text-muted-foreground/40" />
            <p className="text-[11px] text-muted-foreground/60 leading-relaxed">
              Vos conversations apparaîtront ici
            </p>
          </div>
        ) : (
          <>
            <ConvGroup label="Aujourd'hui"       convs={grouped.today}     activeId={activeId} onSelect={onSelect} onDelete={onDelete} onRename={onRename} />
            <ConvGroup label="Hier"              convs={grouped.yesterday} activeId={activeId} onSelect={onSelect} onDelete={onDelete} onRename={onRename} />
            <ConvGroup label="7 derniers jours"  convs={grouped.week}      activeId={activeId} onSelect={onSelect} onDelete={onDelete} onRename={onRename} />
            <ConvGroup label="Plus ancien"       convs={grouped.older}     activeId={activeId} onSelect={onSelect} onDelete={onDelete} onRename={onRename} />
          </>
        )}
      </div>

      {total > 0 && (
        <div className="p-3 border-t">
          <button
            onClick={onClear}
            className="text-[11px] text-muted-foreground/60 hover:text-destructive transition-colors"
          >
            Effacer l'historique
          </button>
        </div>
      )}
    </aside>
  )
}
