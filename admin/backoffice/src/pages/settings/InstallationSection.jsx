import { useState } from 'react'
import { api } from '../../lib/api'
import { Btn, Notice } from '../../components/ui'

export default function InstallationSection() {
  const [loading, setLoading] = useState(false)
  const [error,   setError]   = useState(null)

  async function handleLaunch() {
    setLoading(true)
    setError(null)
    try {
      await api.onboardingReset()
      window.location.reload()
    } catch (e) {
      setError(e.message)
      setLoading(false)
    }
  }

  return (
    <div className="space-y-4">
      <div className="rounded-lg border bg-card overflow-hidden">
        {/* En-tête illustré */}
        <div className="px-6 py-8 bg-primary/5 border-b flex items-center gap-5">
          <div className="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-primary">
              <path d="M12 2L2 7l10 5 10-5-10-5z"/>
              <path d="M2 17l10 5 10-5"/>
              <path d="M2 12l10 5 10-5"/>
            </svg>
          </div>
          <div>
            <h3 className="font-semibold text-foreground">Wizard d'installation</h3>
            <p className="text-sm text-muted-foreground mt-0.5">
              Configuration initiale guidée en 7 étapes
            </p>
          </div>
        </div>

        {/* Détail des étapes */}
        <div className="px-6 py-5 space-y-3">
          {[
            { n: 1, label: 'Prérequis système',   sub: 'Vérifie PHP ≥ 8.0, OpenSSL et crée les tables bt_reservations, bt_reviews, bt_participations' },
            { n: 2, label: 'Chiffrement RGPD',    sub: 'Génère et configure la clé AES-256 pour les données personnelles' },
            { n: 3, label: 'API Regiondo',         sub: 'Saisie et test des clés Public / Secret Regiondo'                },
            { n: 4, label: 'Récapitulatif',        sub: 'Bilan de la configuration et lancement du backoffice'            },
            { n: 5, label: 'Import solditems',     sub: 'Importe les réservations enrichies via CSV → Paramètres › Import solditems' },
            { n: 6, label: 'Import Stats',         sub: 'Importe les participations (OTA, billetterie…) via CSV → Paramètres › Import Stats' },
            { n: 7, label: 'Import avis',          sub: 'Importe les avis clients Regiondo via CSV → Paramètres › Avis clients' },
          ].map(({ n, label, sub }) => (
            <div key={n} className="flex items-start gap-3">
              <div className="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[11px] font-semibold shrink-0 mt-0.5">
                {n}
              </div>
              <div>
                <div className="text-sm font-medium">{label}</div>
                <div className="text-xs text-muted-foreground">{sub}</div>
              </div>
            </div>
          ))}
        </div>

        {/* Action */}
        <div className="px-6 py-4 border-t bg-muted/20 flex items-center gap-3 flex-wrap">
          <Btn onClick={handleLaunch} loading={loading}>
            Lancer l'installation
          </Btn>
          <span className="text-xs text-muted-foreground">
            La page va se recharger pour afficher le wizard.
          </span>
        </div>
      </div>

      {error && <Notice type="error">{error}</Notice>}

      <Notice type="warn">
        Relancer le wizard ne supprime pas vos données ni vos réglages — il réinitialise uniquement
        l'écran d'accueil du backoffice.
      </Notice>
    </div>
  )
}
