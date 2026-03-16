import React from 'react'
import ReactDOM from 'react-dom/client'
import { HashRouter } from 'react-router-dom'
import App from './App'
import './index.css'
import './ai-gradient.css'

// Nettoie les nodes parasites (notices WP, update-nag, scripts de plugins tiers)
// injectées dans le React root avant le mount — évite le crash removeChild.
const root = document.getElementById('bt-backoffice-root')
if (root) {
  while (root.firstChild) root.removeChild(root.firstChild)

  ReactDOM.createRoot(root).render(
    <React.StrictMode>
      <HashRouter>
        <App />
      </HashRouter>
    </React.StrictMode>
  )
}
