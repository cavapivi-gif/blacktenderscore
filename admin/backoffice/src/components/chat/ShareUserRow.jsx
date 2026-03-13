/**
 * ShareUserRow — ligne d'un utilisateur dans le panel de partage.
 * Affiche avatar, nom, badge permission, sélecteur couleur, bouton révoquer.
 */
import { Trash } from 'iconoir-react'

const PERMISSIONS = [
  { value: 'read',  label: 'Lecture seule' },
  { value: 'write', label: 'Lecture + Écriture' },
]

export function ShareUserRow({ share, onUpdate, onRevoke }) {
  return (
    <div className="flex items-center gap-2.5 py-2.5 border-b last:border-0">
      {/* Avatar + nom */}
      <img src={share.avatar} alt="" className="w-7 h-7 rounded-full shrink-0" />
      <div className="flex-1 min-w-0">
        <p className="text-xs font-medium truncate">{share.display_name}</p>
        <p className="text-[10px] text-muted-foreground truncate">{share.user_email}</p>
      </div>

      {/* Sélecteur couleur bulle */}
      <div className="relative shrink-0" title="Couleur dans la conversation">
        <input
          type="color"
          value={share.color}
          onChange={e => onUpdate(share.user_id, { color: e.target.value })}
          className="w-5 h-5 rounded-full cursor-pointer border-0 p-0 opacity-0 absolute inset-0"
        />
        <div
          className="w-5 h-5 rounded-full border border-border cursor-pointer"
          style={{ backgroundColor: share.color }}
        />
      </div>

      {/* Sélecteur permission */}
      <select
        value={share.permission}
        onChange={e => onUpdate(share.user_id, { permission: e.target.value })}
        className="text-[10px] border rounded px-1.5 py-0.5 bg-background cursor-pointer outline-none shrink-0"
      >
        {PERMISSIONS.map(p => (
          <option key={p.value} value={p.value}>{p.label}</option>
        ))}
      </select>

      {/* Révoquer */}
      <button
        onClick={() => onRevoke(share.user_id)}
        title="Révoquer l'accès"
        className="p-1 text-muted-foreground hover:text-red-500 transition-colors shrink-0"
      >
        <Trash width={13} height={13} />
      </button>
    </div>
  )
}
