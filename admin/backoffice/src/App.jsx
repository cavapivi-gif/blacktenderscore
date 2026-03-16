import { Component, lazy, Suspense } from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout'

// Error boundary temporaire — affiche l'erreur React en clair au lieu d'une page blanche
class ErrorBoundary extends Component {
  state = { error: null }
  static getDerivedStateFromError(e) { return { error: e } }
  render() {
    if (this.state.error) {
      return (
        <div style={{ padding: 32, fontFamily: 'monospace', fontSize: 13, color: '#b91c1c', background: '#fff5f5', borderRadius: 8, margin: 24 }}>
          <strong>Erreur React (debug) :</strong>
          <pre style={{ whiteSpace: 'pre-wrap', marginTop: 8, fontSize: 12 }}>
            {this.state.error?.message}{'\n\n'}{this.state.error?.stack}
          </pre>
        </div>
      )
    }
    return this.props.children
  }
}

// Route-level code splitting — each page is lazy-loaded
const Dashboard  = lazy(() => import('./pages/Dashboard'))
const Products   = lazy(() => import('./pages/Products'))
const Bookings   = lazy(() => import('./pages/Bookings'))
const Customers  = lazy(() => import('./pages/Customers'))
const Reviews    = lazy(() => import('./pages/Reviews'))
const Planner    = lazy(() => import('./pages/Planner'))
const Settings   = lazy(() => import('./pages/Settings'))
const Analytics  = lazy(() => import('./pages/Analytics'))
const Onboarding = lazy(() => import('./pages/Onboarding'))
const AIChat     = lazy(() => import('./pages/AIChat'))
const Translator = lazy(() => import('./pages/Translator'))
const Corrector  = lazy(() => import('./pages/Corrector'))
const Forms      = lazy(() => import('./pages/Forms'))

// Valeur injectée par PHP via wp_localize_script — statique au chargement de la page.
// Après onboardingComplete(), window.location.reload() recharge la page avec onboarding_done = true.
const onboardingDone = !!window.btBackoffice?.onboarding_done

// Si un lien partagé (?bt_chat=TOKEN) est détecté, on démarre directement sur l'IA
const sharedChatToken = new URLSearchParams(window.location.search).get('bt_chat')
const DEFAULT_ROUTE = sharedChatToken ? '/ai-chat' : '/dashboard'

function PageFallback() {
  return (
    <div className="flex items-center justify-center h-64">
      <div className="w-5 h-5 border-2 border-muted-foreground/30 border-t-foreground rounded-full animate-spin" />
    </div>
  )
}

export default function App() {
  if (!onboardingDone) {
    return (
      <Suspense fallback={<PageFallback />}>
        <Onboarding />
      </Suspense>
    )
  }

  return (
    <ErrorBoundary>
    <Layout>
      <Suspense fallback={<PageFallback />}>
      <Routes>
        <Route path="/"                  element={<Navigate to={DEFAULT_ROUTE} replace />} />
        <Route path="/dashboard"         element={<Dashboard />} />
        <Route path="/products"          element={<Products />} />
        <Route path="/bookings"          element={<Bookings />} />
        <Route path="/customers"         element={<Customers />} />
        <Route path="/forms"             element={<Forms />} />
        <Route path="/reviews"           element={<Reviews />} />
        <Route path="/planner"           element={<Planner />} />
        <Route path="/analytics"         element={<Analytics />} />
        <Route path="/ai-chat"           element={<AIChat />} />
        <Route path="/translator"        element={<Translator />} />
        <Route path="/corrector"         element={<Corrector />} />
        {/* Settings : redirect racine → première section */}
        <Route path="/settings"          element={<Navigate to="/settings/api" replace />} />
        <Route path="/settings/:section" element={<Settings />} />
      </Routes>
      </Suspense>
    </Layout>
    </ErrorBoundary>
  )
}
