import React from 'react'
import ReactDOM from 'react-dom/client'
import { HashRouter } from 'react-router-dom'
import App from './App'
import './index.css'
import './ai-gradient.css'

// Fix removeChild error: créer un div enfant isolé pour React.
// Les extensions navigateur (password managers, traducteurs) et WP admin notices
// injectent des nodes dans le container — React crash quand il essaie de les gérer.
// Solution: monter React dans un enfant qu'on contrôle, pas directement dans le root.
const container = document.getElementById('bt-backoffice-root')
if (container) {
  // Nettoyer les notices WP injectées avant le mount
  while (container.firstChild) container.removeChild(container.firstChild)

  // Créer un div isolé pour React — les extensions ne le toucheront pas
  const reactRoot = document.createElement('div')
  reactRoot.id = 'bt-react-app'
  container.appendChild(reactRoot)

  ReactDOM.createRoot(reactRoot).render(
    <React.StrictMode>
      <HashRouter>
        <App />
      </HashRouter>
    </React.StrictMode>
  )
}
