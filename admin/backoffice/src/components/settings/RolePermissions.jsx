/**
 * RolePermissions — section Settings pour gérer les droits par rôle WordPress.
 * Niveau 1 du système de permissions : accès global aux vues + fonctions du plugin.
 */
import { useState, useEffect } from 'react'
import { getRolePermissions, saveRolePermissions } from '../../lib/chatApi'
import { Btn, Notice, Spinner } from '../ui'

// Groupes de permissions — reflète exactement ChatDb::ALL_CAPS côté PHP
const CAP_GROUPS = [
  {
    label: 'Navigation',
    caps: [
      { key: 'plugin',       label: 'Accès plugin'   },
      { key: 'dashboard',    label: 'Dashboard'      },
      { key: 'settings',     label: 'Paramètres'     },
    ],
  },
  {
    label: 'Données',
    caps: [
      { key: 'bookings',     label: 'Réservations'   },
      { key: 'reservations', label: 'Solditems'       },
      { key: 'products',     label: 'Produits'        },
      { key: 'planner',      label: 'Planner'         },
      { key: 'customers',    label: 'Clients'         },
      { key: 'analytics',    label: 'Analytics'       },
      { key: 'avis',         label: 'Avis clients'    },
    ],
  },
  {
    label: 'Outils IA',
    caps: [
      { key: 'chat_access',  label: 'Conseiller IA'  },
      { key: 'chat_create',  label: 'Chat (créer)'   },
      { key: 'chat_share',   label: 'Chat (partager)'},
      { key: 'translations', label: 'Traducteur/Correcteur' },
    ],
  },
]

const CAPS = CAP_GROUPS.flatMap(g => g.caps)

const ALL_CAPS_KEYS = CAPS.map(c => c.key)

/** Vérifie si toutes les permissions d'un rôle sont cochées */
function allChecked(rolePerms) {
  return ALL_CAPS_KEYS.every(k => !!rolePerms?.[k])
}

export function RolePermissions() {
  const [perms,   setPerms]   = useState(null)
  const [saving,  setSaving]  = useState(false)
  const [status,  setStatus]  = useState(null)

  useEffect(() => {
    getRolePermissions().then(setPerms).catch(() => setStatus({ type: 'error', msg: 'Erreur de chargement.' }))
  }, [])

  function toggle(role, cap) {
    setPerms(prev => ({ ...prev, [role]: { ...prev[role], [cap]: !prev[role][cap] } }))
  }

  /** Coche ou décoche toutes les permissions d'un rôle */
  function toggleAll(role) {
    const enable = !allChecked(perms[role])
    const updated = {}
    for (const k of ALL_CAPS_KEYS) updated[k] = enable
    setPerms(prev => ({ ...prev, [role]: { ...prev[role], ...updated } }))
  }

  async function save() {
    setSaving(true)
    setStatus(null)
    try {
      await saveRolePermissions(perms)
      setStatus({ type: 'success', msg: 'Permissions sauvegardées.' })
    } catch {
      setStatus({ type: 'error', msg: 'Erreur lors de la sauvegarde.' })
    } finally {
      setSaving(false)
    }
  }

  if (!perms) return <Spinner />

  const roles = Object.keys(perms)

  return (
    <div className="space-y-4">
      <p className="text-xs text-muted-foreground">
        Définit ce que chaque rôle WordPress peut faire dans le plugin.
        Les administrateurs ont toujours accès complet, indépendamment de ces réglages.
      </p>

      {status && <Notice type={status.type}>{status.msg}</Notice>}

      <div className="overflow-x-auto rounded-lg border">
        <table className="w-full text-xs">
          <thead>
            <tr className="border-b bg-muted/30">
              <th className="px-3 py-2.5 text-left font-medium text-muted-foreground sticky left-0 bg-muted/30 z-10">Rôle</th>
              {CAP_GROUPS.map(g => (
                <th key={g.label} colSpan={g.caps.length} className="px-2 py-1.5 text-center font-semibold text-muted-foreground border-l border-border text-[10px] uppercase tracking-wider">
                  {g.label}
                </th>
              ))}
              <th className="px-2 py-2.5 text-center font-medium text-muted-foreground whitespace-nowrap border-l border-border">Tout</th>
            </tr>
            <tr className="border-b bg-muted/10">
              <th className="sticky left-0 bg-muted/10 z-10" />
              {CAP_GROUPS.map(g => g.caps.map(c => (
                <th key={c.key} className="px-2 py-1.5 text-center font-medium text-muted-foreground whitespace-nowrap first:border-l first:border-border">
                  {c.label}
                </th>
              )))}
              <th className="border-l border-border" />
            </tr>
          </thead>
          <tbody>
            {roles.map(role => (
              <tr key={role} className="border-b last:border-0 hover:bg-muted/20">
                <td className="px-3 py-2.5 font-medium capitalize">{role.replace(/_/g, ' ')}</td>
                {CAPS.map(c => (
                  <td key={c.key} className="px-2 py-2.5 text-center">
                    <input
                      type="checkbox"
                      checked={!!perms[role]?.[c.key]}
                      onChange={() => toggle(role, c.key)}
                      className="w-3.5 h-3.5 cursor-pointer accent-foreground"
                    />
                  </td>
                ))}
                {/* Bouton tout cocher / tout décocher */}
                <td className="px-2 py-2.5 text-center">
                  <button
                    onClick={() => toggleAll(role)}
                    title={allChecked(perms[role]) ? 'Tout décocher' : 'Tout cocher'}
                    className="text-[10px] px-2 py-0.5 rounded border border-border hover:bg-muted/60 transition-colors text-muted-foreground hover:text-foreground whitespace-nowrap"
                  >
                    {allChecked(perms[role]) ? 'Aucun' : 'Tous'}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="flex justify-end">
        <Btn onClick={save} disabled={saving}>
          {saving ? 'Sauvegarde…' : 'Sauvegarder les permissions'}
        </Btn>
      </div>
    </div>
  )
}
