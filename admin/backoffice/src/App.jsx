import { Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout'
import Dashboard from './pages/Dashboard'
import Products from './pages/Products'
import Bookings from './pages/Bookings'
import Customers from './pages/Customers'
import Reviews from './pages/Reviews'
import Planner from './pages/Planner'
import Settings from './pages/Settings'
import Onboarding from './pages/Onboarding'

// Valeur injectée par PHP via wp_localize_script — statique au chargement de la page.
// Après onboardingComplete(), window.location.reload() recharge la page avec onboarding_done = true.
const onboardingDone = !!window.btBackoffice?.onboarding_done

export default function App() {
  if (!onboardingDone) {
    return <Onboarding />
  }

  return (
    <Layout>
      <Routes>
        <Route path="/"                  element={<Navigate to="/dashboard" replace />} />
        <Route path="/dashboard"         element={<Dashboard />} />
        <Route path="/products"          element={<Products />} />
        <Route path="/bookings"          element={<Bookings />} />
        <Route path="/customers"         element={<Customers />} />
        <Route path="/reviews"           element={<Reviews />} />
        <Route path="/planner"           element={<Planner />} />
        {/* Settings : redirect racine → première section */}
        <Route path="/settings"          element={<Navigate to="/settings/api" replace />} />
        <Route path="/settings/:section" element={<Settings />} />
      </Routes>
    </Layout>
  )
}
