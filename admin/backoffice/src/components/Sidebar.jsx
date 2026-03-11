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
} from 'iconoir-react'

const SETTINGS_CHILDREN = [
  { hash: '#api',     label: 'Connexion API' },
  { hash: '#sync',    label: 'Synchronisation' },
  { hash: '#widgets', label: 'Widgets' },
  { hash: '#widget-css', label: 'Custom CSS' },
  { hash: '#cache',   label: 'Cache' },
]

const nav = [
  { to: '/dashboard', label: 'Tableau de bord', icon: HomeSimpleDoor },
  { to: '/products',  label: 'Produits',        icon: Box },
  { to: '/bookings',  label: 'Réservations',    icon: Book },
  { to: '/customers', label: 'Clients',         icon: Group },
  { to: '/settings',  label: 'Réglages',        icon: Settings, children: SETTINGS_CHILDREN },
]

export default function Sidebar() {
  const location = useLocation()
  const [openMenus, setOpenMenus] = useState({ '/settings': true })

  function toggleMenu(to) {
    setOpenMenus(prev => ({ ...prev, [to]: !prev[to] }))
  }

  return (
    <aside className="w-52 shrink-0 border-r flex flex-col bg-card sticky top-0 self-start min-h-full overflow-y-auto" style={{ minHeight: 'calc(100vh - 32px)' }}>
      {/* Logo */}
      <div className="flex items-center gap-2.5 px-5 py-5 border-b">
        <Compass width={18} height={18} strokeWidth={1.5} />
        <span className="text-sm font-medium tracking-widest uppercase">BlackTenders</span>
      </div>

      {/* Nav */}
      <nav className="flex-1 py-3">
        {nav.map(({ to, label, icon: Icon, children }) => {
          const isExpanded = openMenus[to] ?? false

          if (children) {
            return (
              <div key={to}>
                <div className="flex items-center">
                  <NavLink
                    to={to}
                    className={({ isActive }) =>
                      `flex-1 flex items-center gap-3 px-5 py-2.5 text-sm transition-colors rounded-md mx-2 ${
                        isActive
                          ? 'bg-primary text-primary-foreground'
                          : 'text-muted-foreground hover:text-foreground hover:bg-accent'
                      }`
                    }
                  >
                    <Icon width={15} height={15} strokeWidth={1.5} />
                    {label}
                  </NavLink>
                  <button
                    onClick={() => toggleMenu(to)}
                    className="px-3 py-2.5 text-muted-foreground hover:text-foreground transition-colors"
                    aria-label="Toggle submenu"
                  >
                    <NavArrowDown
                      width={12}
                      height={12}
                      strokeWidth={2}
                      className={`transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                    />
                  </button>
                </div>

                {isExpanded && (
                  <div className="ml-9 border-l border-border">
                    {children.map(({ hash, label: childLabel }) => (
                      <NavLink
                        key={hash}
                        to={`/settings${hash}`}
                        className="flex items-center px-4 py-2 text-xs text-muted-foreground hover:text-foreground transition-colors"
                      >
                        {childLabel}
                      </NavLink>
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
              className={({ isActive }) =>
                `flex items-center gap-3 px-5 py-2.5 text-sm transition-colors rounded-md mx-2 ${
                  isActive
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:text-foreground hover:bg-accent'
                }`
              }
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
