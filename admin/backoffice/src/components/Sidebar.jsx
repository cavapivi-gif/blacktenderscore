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
} from 'iconoir-react'

const SETTINGS_CHILDREN = [
  { hash: '#api',     label: 'Connexion API' },
  { hash: '#sync',    label: 'Synchronisation' },
  { hash: '#widgets', label: 'Widget ID' },
  { hash: '#cache',   label: 'Cache' },
]

const nav = [
  { to: '/dashboard', label: 'Tableau de bord', icon: HomeSimpleDoor },
  { to: '/products',  label: 'Produits',         icon: Box },
  { to: '/bookings',  label: 'Réservations',      icon: Book },
  { to: '/customers', label: 'Clients',           icon: Group },
  { to: '/settings',  label: 'Réglages',          icon: Settings, children: SETTINGS_CHILDREN },
]

export default function Sidebar() {
  const location = useLocation()
  const [openMenus, setOpenMenus] = useState({ '/settings': true })

  function toggleMenu(to) {
    setOpenMenus(prev => ({ ...prev, [to]: !prev[to] }))
  }

  return (
    <aside className="w-52 shrink-0 border-r border-gray-200 flex flex-col bg-white sticky top-0 h-screen overflow-y-auto">
      {/* Logo */}
      <div className="flex items-center gap-2 px-5 py-5 border-b border-gray-200">
        <Compass width={18} height={18} strokeWidth={1.5} />
        <span className="text-sm tracking-widest uppercase">BlackTenders</span>
      </div>

      {/* Nav */}
      <nav className="flex-1 py-4">
        {nav.map(({ to, label, icon: Icon, children }) => {
          const isActive   = location.pathname.startsWith(to)
          const isExpanded = openMenus[to] ?? false

          if (children) {
            return (
              <div key={to}>
                {/* Parent row */}
                <div className="flex items-center">
                  <NavLink
                    to={to}
                    className={({ isActive }) =>
                      `flex-1 flex items-center gap-3 px-5 py-2.5 text-sm transition-colors ${
                        isActive
                          ? 'bg-black text-white'
                          : 'text-gray-600 hover:text-black hover:bg-gray-50'
                      }`
                    }
                  >
                    <Icon width={15} height={15} strokeWidth={1.5} />
                    {label}
                  </NavLink>
                  <button
                    onClick={() => toggleMenu(to)}
                    className="px-3 py-2.5 text-gray-400 hover:text-black transition-colors"
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

                {/* Children */}
                {isExpanded && (
                  <div className="ml-8 border-l border-gray-100">
                    {children.map(({ hash, label: childLabel }) => (
                      <a
                        key={hash}
                        href={`#${hash.slice(1)}`}
                        onClick={e => {
                          e.preventDefault()
                          const el = document.getElementById(hash.slice(1))
                          if (el) el.scrollIntoView({ behavior: 'smooth' })
                        }}
                        className="flex items-center px-4 py-2 text-xs text-gray-500 hover:text-black transition-colors"
                      >
                        {childLabel}
                      </a>
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
                `flex items-center gap-3 px-5 py-2.5 text-sm transition-colors ${
                  isActive
                    ? 'bg-black text-white'
                    : 'text-gray-600 hover:text-black hover:bg-gray-50'
                }`
              }
            >
              <Icon width={15} height={15} strokeWidth={1.5} />
              {label}
            </NavLink>
          )
        })}
      </nav>

      {/* Version */}
      <div className="px-5 py-4 border-t border-gray-200">
        <span className="text-xs text-gray-400">
          {window.btBackoffice?.version ?? '1.0.0'}
        </span>
      </div>
    </aside>
  )
}
