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

  // Observe les mutations post-mount : WordPress et plugins tiers peuvent
  // injecter des nodes (notices, update-nag) dans le root React après le mount.
  // Sans ce nettoyage, React crash avec "removeChild: not a child of this node".
  const observer = new MutationObserver((mutations) => {
    for (const m of mutations) {
      for (const node of m.addedNodes) {
        // Supprimer tout élément non-React injecté (pas le conteneur React)
        if (node.nodeType === 1 && !node._reactRootContainer && !node.hasAttribute?.('data-reactroot')) {
          const tag = node.tagName?.toLowerCase()
          const cls = node.className || ''
          // Ne supprimer que les nodes typiques WP (notices, nags, scripts)
          if (cls.includes('notice') || cls.includes('update-nag') || cls.includes('error')
              || tag === 'script' || tag === 'style' || cls.includes('wp-')) {
            node.remove()
          }
        }
      }
    }
  })
  observer.observe(root, { childList: true })

  ReactDOM.createRoot(root).render(
    <React.StrictMode>
      <HashRouter>
        <App />
      </HashRouter>
    </React.StrictMode>
  )
}
