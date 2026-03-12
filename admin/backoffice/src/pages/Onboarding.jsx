import { useState } from 'react'
import { api } from '../lib/api'
import { Btn, Input, Notice, Spinner } from '../components/ui'

export default function Onboarding() {
  const [step, setStep] = useState(0)
  const [apiKey, setApiKey] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  async function handleSetup() {
    if (!apiKey.trim()) return
    setLoading(true)
    setError(null)
    try {
      await api.onboardingSetup({ api_key: apiKey.trim() })
      setStep(1)
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }

  async function handleComplete() {
    setLoading(true)
    try {
      await api.onboardingComplete()
      window.location.reload()
    } catch (e) {
      setError(e.message)
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-6">
      <div className="w-full max-w-md space-y-6">
        <div className="text-center">
          <h1 className="text-lg font-semibold">Configuration BlackTenders</h1>
          <p className="text-sm text-muted-foreground mt-1">Connectez votre compte Regiondo pour commencer.</p>
        </div>

        {error && <Notice type="error">{error}</Notice>}

        {step === 0 && (
          <div className="space-y-4">
            <Input
              label="Clé API Regiondo"
              placeholder="Votre clé API..."
              value={apiKey}
              onChange={e => setApiKey(e.target.value)}
            />
            <Btn onClick={handleSetup} loading={loading} className="w-full">
              Connecter
            </Btn>
          </div>
        )}

        {step === 1 && (
          <div className="space-y-4 text-center">
            <p className="text-sm">Connexion réussie. Cliquez pour finaliser.</p>
            <Btn onClick={handleComplete} loading={loading} className="w-full">
              Terminer la configuration
            </Btn>
          </div>
        )}
      </div>
    </div>
  )
}
