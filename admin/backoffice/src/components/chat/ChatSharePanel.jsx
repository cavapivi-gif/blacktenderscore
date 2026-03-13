/**
 * ChatSharePanel — modal centré de gestion du partage d'une conversation.
 * Utilise Dialog. Accessible uniquement à l'owner.
 * Fonctions : liste des accès, invitation, permission, couleur, révocation, copie lien.
 */
import { useState, useEffect } from 'react'
import { Copy, Check } from 'iconoir-react'
import { NavLink } from 'react-router-dom'
import { Dialog } from '../Dialog'
import { ShareUserRow } from './ShareUserRow'
import { UserSearchInput } from './UserSearchInput'
import { useChatShare } from '../../hooks/useChatShare'

const PERMISSIONS = [
  { value: 'read',  label: 'Lecture seule' },
  { value: 'write', label: 'Lecture + Écriture' },
]

export function ChatSharePanel({ open, onClose, conv }) {
  const { shares, loading, syncing, error, loadShares, invite, update, revoke } = useChatShare(conv)

  const [pendingUser,    setPendingUser]    = useState(null)
  const [pendingPerm,    setPendingPerm]    = useState('read')
  const [confirmOpen,    setConfirmOpen]    = useState(false)
  const [inviting,       setInviting]       = useState(false)
  const [copied,         setCopied]         = useState(false)
  const [inviteError,    setInviteError]    = useState(null)

  useEffect(() => { if (open && conv?.id) loadShares() }, [open, conv?.id])

  function handleInviteClick() {
    if (!pendingUser) return
    setInviteError(null)
    setConfirmOpen(true)
  }

  async function handleInviteConfirm() {
    setConfirmOpen(false)
    setInviting(true)
    try {
      await invite(pendingUser.id, pendingPerm)
      setPendingUser(null)
    } catch (e) {
      setInviteError(e.message)
    } finally {
      setInviting(false)
    }
  }

  function copyLink() {
    const url = `${window.location.origin}${window.location.pathname}#/ai-chat?share=${conv?.id}`
    navigator.clipboard.writeText(url).then(() => {
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    })
  }

  const excludeIds = [
    window.btBackoffice?.current_user?.id,
    ...shares.map(s => parseInt(s.user_id)),
  ].filter(Boolean)

  return (
    <>
      <Dialog
        open={open}
        onClose={onClose}
        title="Partager la conversation"
        description="Invitez des utilisateurs enregistrés sur le site."
        size="sm"
      >
        {/* Indicateur sync */}
        {syncing && (
          <p className="text-xs text-muted-foreground mb-3 flex items-center gap-1.5">
            <span className="w-3 h-3 border-2 border-muted border-t-foreground/50 rounded-full animate-spin" />
            Synchronisation…
          </p>
        )}

        {/* Erreur globale */}
        {error && <p className="text-xs text-red-600 bg-red-50 rounded px-2 py-1.5 mb-3">{error}</p>}

        {/* ── Participants actuels ──────────────────────────────────────────── */}
        <div className="mb-4">
          <h3 className="text-[11px] font-medium uppercase tracking-wider text-muted-foreground mb-2">
            Accès ({shares.length})
          </h3>

          {loading ? (
            <p className="text-xs text-muted-foreground">Chargement…</p>
          ) : shares.length === 0 ? (
            <p className="text-xs text-muted-foreground italic">Aucun utilisateur invité.</p>
          ) : (
            <div>
              {shares.map(s => (
                <ShareUserRow
                  key={s.user_id}
                  share={s}
                  onUpdate={(uid, data) => update(uid, data)}
                  onRevoke={uid => revoke(uid)}
                />
              ))}
            </div>
          )}
        </div>

        {/* ── Inviter un utilisateur ────────────────────────────────────────── */}
        <div className="border-t pt-4 space-y-2">
          <h3 className="text-[11px] font-medium uppercase tracking-wider text-muted-foreground mb-2">
            Inviter
          </h3>

          {pendingUser ? (
            <div className="flex items-center gap-2 p-2 rounded-lg border bg-muted/30">
              <img src={pendingUser.avatar} alt="" className="w-6 h-6 rounded-full shrink-0" />
              <span className="flex-1 text-xs font-medium truncate">{pendingUser.name}</span>
              <button onClick={() => setPendingUser(null)} className="text-muted-foreground hover:text-foreground text-xs">✕</button>
            </div>
          ) : (
            <UserSearchInput onSelect={setPendingUser} excludeIds={excludeIds} />
          )}

          <div className="flex items-center gap-2">
            <select
              value={pendingPerm}
              onChange={e => setPendingPerm(e.target.value)}
              className="flex-1 text-xs border rounded px-2 py-1.5 bg-background outline-none cursor-pointer"
            >
              {PERMISSIONS.map(p => (
                <option key={p.value} value={p.value}>{p.label}</option>
              ))}
            </select>

            <button
              onClick={handleInviteClick}
              disabled={!pendingUser || inviting}
              className="px-3 py-1.5 rounded-lg bg-foreground text-background text-xs font-medium disabled:opacity-40 hover:bg-foreground/90 transition-colors"
            >
              {inviting ? '…' : 'Inviter'}
            </button>
          </div>

          {inviteError && (
            <p className="text-[11px] text-red-600">
              {inviteError}
              {inviteError.toLowerCase().includes('permission') && (
                <NavLink
                  to="/settings/permissions"
                  className="ml-1.5 underline hover:text-red-800"
                  onClick={onClose}
                >
                  Gérer les permissions →
                </NavLink>
              )}
            </p>
          )}
        </div>

        {/* ── Copier le lien ────────────────────────────────────────────────── */}
        <button
          onClick={copyLink}
          className="mt-4 w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg border text-xs text-muted-foreground hover:text-foreground hover:bg-muted/50 transition-colors"
        >
          {copied ? <Check width={13} height={13} className="text-green-600" /> : <Copy width={13} height={13} />}
          {copied ? 'Lien copié !' : 'Copier le lien de partage'}
        </button>
      </Dialog>

      {/* Confirmation invitation */}
      {confirmOpen && pendingUser && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/40">
          <div className="bg-card rounded-xl border shadow-xl p-5 w-72 space-y-3">
            <p className="text-sm font-semibold">Confirmer l'invitation</p>
            <div className="flex items-center gap-2.5">
              <img src={pendingUser.avatar} alt="" className="w-8 h-8 rounded-full shrink-0" />
              <div className="min-w-0">
                <p className="text-xs font-medium truncate">{pendingUser.name}</p>
                <p className="text-[10px] text-muted-foreground truncate">{pendingUser.email}</p>
              </div>
            </div>
            <p className="text-xs text-muted-foreground">
              Permission : <strong>{PERMISSIONS.find(p => p.value === pendingPerm)?.label}</strong>
            </p>
            <div className="flex gap-2 justify-end pt-1">
              <button
                onClick={() => setConfirmOpen(false)}
                className="px-3 py-1.5 rounded-lg border text-xs hover:bg-muted/50 transition-colors"
              >
                Annuler
              </button>
              <button
                onClick={handleInviteConfirm}
                className="px-3 py-1.5 rounded-lg bg-foreground text-background text-xs font-medium hover:bg-foreground/90 transition-colors"
              >
                Confirmer
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
