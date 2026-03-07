import { Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout'
import Dashboard from './pages/Dashboard'
import Products from './pages/Products'
import Bookings from './pages/Bookings'
import Customers from './pages/Customers'
import Settings from './pages/Settings'

export default function App() {
  return (
    <Layout>
      <Routes>
        <Route path="/"           element={<Navigate to="/dashboard" replace />} />
        <Route path="/dashboard"  element={<Dashboard />} />
        <Route path="/products"   element={<Products />} />
        <Route path="/bookings"   element={<Bookings />} />
        <Route path="/customers"  element={<Customers />} />
        <Route path="/settings"   element={<Settings />} />
      </Routes>
    </Layout>
  )
}
