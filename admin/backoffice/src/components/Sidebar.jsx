import { NavLink } from 'react-router-dom'
import {
  HomeSimpleDoor,
  Box,
  Book,
  Group,
  Settings,
  Compass,
} from 'iconoir-react'

const nav = [
  { to: '/dashboard', label: 'Tableau de bord', icon: HomeSimpleDoor },
  { to: '/products',  label: 'Produits',         icon: Box },
  { to: '/bookings',  label: 'Réservations',      icon: Book },
  { to: '/customers', label: 'Clients',           icon: Group },
  { to: '/settings',  label: 'Réglages',          icon: Settings },
]

export default function Sidebar() {
  return (
    <aside className="w-52 shrink-0 border-r border-gray-200 min-h-screen flex flex-col bg-white">
      {/* Logo */}
      <div className="flex items-center gap-2 px-5 py-5 border-b border-gray-200">
        <Compass width={18} height={18} strokeWidth={1.5} />
        <span className="text-sm tracking-widest uppercase">BlackTenders</span>
      </div>

      {/* Nav */}
      <nav className="flex-1 py-4">
        {nav.map(({ to, label, icon: Icon }) => (
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
        ))}
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
