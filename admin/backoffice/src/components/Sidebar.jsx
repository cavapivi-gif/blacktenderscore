import { useState } from 'react'
import { NavLink, useLocation } from 'react-router-dom'
import {
  HomeSimpleDoor,
  Box,
  Book,
  Group,
  Settings,
  Compass,
  NavArrowDown,
  OpenNewWindow,
  Calendar,
  Tools,
  Star,
} from 'iconoir-react'

// ── Settings sub-navigation ───────────────────────────────────────────────────
// Deux groupes : réglages (sauvegardables) et outils (actions)
const SETTINGS_CONFIG = [
  { path: '/settings/api',         label: 'Connexion API'    },
  { path: '/settings/sync',        label: 'Synchronisation'  },
  { path: '/settings/css',         label: 'CSS & JS'         },
  { path: '/settings/map',         label: 'Map Style'        },
  { path: '/settings/cache',       label: 'Cache'            },
]

const SETTINGS_TOOLS = [
  { path: '/settings/manual-sync',        label: 'Sync produits'      },
  { path: '/settings/bookings-sync',      label: 'Sync réservations'  },
  { path: '/settings/reservations-import',label: 'Import solditems'   },
  { path: '/settings/diagnostic',         label: 'Diagnostic'         },
  { path: '/settings/installation',       label: 'Installation'       },
]

const nav = [
  { to: '/dashboard', label: 'Tableau de bord', icon: HomeSimpleDoor },
  { to: '/products',  label: 'Produits',        icon: Box            },
  { to: '/bookings',  label: 'Réservations',    icon: Book           },
  { to: '/planner',   label: 'Planificateur',   icon: Calendar       },
  { to: '/customers', label: 'Clients',         icon: Group          },
  { to: '/reviews',   label: 'Avis',            icon: Star           },
  {
    to: '/settings', label: 'Réglages', icon: Settings,
    groups: [
      { label: 'Configuration', items: SETTINGS_CONFIG },
      { label: 'Outils',        items: SETTINGS_TOOLS  },
    ],
  },
]

// ── Composant dot actif / inactif ─────────────────────────────────────────────
function SubItem({ path, label, isLast }) {
  return (
    <NavLink
      to={path}
      className={({ isActive }) => [
        'flex items-center gap-2.5 px-3 py-1.5 text-xs rounded-md transition-colors ml-1',
        isActive
          ? 'text-foreground font-medium'
          : 'text-muted-foreground hover:text-foreground',
      ].join(' ')}
    >
      {({ isActive }) => (
        <>
          <span className={[
            'w-1.5 h-1.5 rounded-full shrink-0 transition-colors',
            isActive ? 'bg-primary' : 'bg-border group-hover:bg-muted-foreground',
          ].join(' ')} />
          {label}
        </>
      )}
    </NavLink>
  )
}

export default function Sidebar() {
  const location = useLocation()
  const isSettingsActive = location.pathname.startsWith('/settings')
  const [openMenus, setOpenMenus] = useState({ '/settings': true })

  function toggleMenu(to) {
    setOpenMenus(prev => ({ ...prev, [to]: !prev[to] }))
  }

  return (
    <aside
      className="w-52 shrink-0 border-r flex flex-col bg-card sticky top-0 self-start min-h-full overflow-y-auto"
      style={{ minHeight: 'calc(100vh - 32px)' }}
    >
      {/* Logo */}
      <div className="flex items-center gap-2.5 px-5 py-5 border-b">
        <Compass width={18} height={18} strokeWidth={1.5} />
        <span className="text-sm font-medium tracking-widest uppercase">BlackTenders</span>
      </div>

      {/* Nav */}
      <nav className="flex-1 py-3 space-y-0.5">
        {nav.map(({ to, label, icon: Icon, groups }) => {
          const isExpanded = openMenus[to] ?? false

          if (groups) {
            const isActive = location.pathname.startsWith(to)
            return (
              <div key={to}>
                {/* Entrée parent cliquable */}
                <div className="flex items-center mx-2">
                  <NavLink
                    to={to}
                    className={[
                      'flex-1 flex items-center gap-3 px-3 py-2.5 text-sm transition-colors rounded-md',
                      isActive
                        ? 'bg-primary/10 text-primary font-medium'
                        : 'text-muted-foreground hover:text-foreground hover:bg-accent',
                    ].join(' ')}
                  >
                    <Icon width={15} height={15} strokeWidth={1.5} />
                    {label}
                  </NavLink>
                  <button
                    onClick={() => toggleMenu(to)}
                    className="px-2 py-2.5 text-muted-foreground hover:text-foreground transition-colors"
                    aria-label="Afficher/masquer"
                  >
                    <NavArrowDown
                      width={12}
                      height={12}
                      strokeWidth={2}
                      className={`transition-transform duration-200 ${isExpanded ? 'rotate-180' : ''}`}
                    />
                  </button>
                </div>

                {/* Sous-menu avec groupes */}
                {isExpanded && (
                  <div className="ml-5 mt-0.5 mb-1 border-l border-border pl-2 space-y-3">
                    {groups.map((group, gi) => (
                      <div key={gi}>
                        {/* Label groupe */}
                        <p className="text-[10px] uppercase tracking-widest text-muted-foreground/60 px-3 py-1 font-medium">
                          {group.label}
                        </p>
                        <div className="space-y-0.5">
                          {group.items.map((item, ii) => (
                            <SubItem
                              key={item.path}
                              path={item.path}
                              label={item.label}
                              isLast={ii === group.items.length - 1}
                            />
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )
          }

          return (
            <NavLink
              key={to}
              to={to}
              className={({ isActive }) => [
                'flex items-center gap-3 px-3 py-2.5 text-sm transition-colors rounded-md mx-2',
                isActive
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:text-foreground hover:bg-accent',
              ].join(' ')}
            >
              <Icon width={15} height={15} strokeWidth={1.5} />
              {label}
            </NavLink>
          )
        })}
      </nav>

      {/* WP Admin link + Version */}
      <div className="px-5 py-4 border-t space-y-2">
        <a
          href={window.btBackoffice?.admin_url ?? '/wp-admin/'}
          className="flex items-center gap-2 text-xs text-muted-foreground hover:text-foreground transition-colors"
        >
          <OpenNewWindow width={12} height={12} strokeWidth={1.5} />
          WP Admin
        </a>
        <span className="text-[11px] text-muted-foreground/50">
          v{window.btBackoffice?.version ?? '1.0.0'}
        </span>
      </div>
    </aside>
  )
}
