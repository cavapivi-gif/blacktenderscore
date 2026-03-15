/**
 * Sélecteur de profils de mapping sauvegardés.
 * Permet de charger, sauvegarder et supprimer des profils.
 */
import { useState, useEffect, useCallback } from 'react'
import { api } from '../../lib/api'
import { Btn } from '../ui'

export function MappingProfileSelector({ importType, mapping, onLoadProfile }) {
  const [profiles, setProfiles]     = useState([])
  const [loading, setLoading]       = useState(false)
  const [saving, setSaving]         = useState(false)
  const [saveName, setSaveName]     = useState('')
  const [showSave, setShowSave]     = useState(false)
  const [selectedId, setSelectedId] = useState('')

  /** Charge la liste des profils pour ce type d'import. */
  const fetchProfiles = useCallback(async () => {
    setLoading(true)
    try {
      const res = await api.importProfiles(importType)
      setProfiles(res.data || [])
    } catch {
      // silencieux — la table n'existe peut-être pas encore
      setProfiles([])
    } finally {
      setLoading(false)
    }
  }, [importType])

  useEffect(() => { fetchProfiles() }, [fetchProfiles])

  /** Charge un profil sélectionné dans le mapper. */
  const handleLoad = useCallback(() => {
    const profile = profiles.find(p => p.id === Number(selectedId))
    if (profile) {
      onLoadProfile(profile.mapping)
    }
  }, [selectedId, profiles, onLoadProfile])

  /** Sauvegarde le mapping actuel comme profil. */
  const handleSave = useCallback(async () => {
    const name = saveName.trim()
    if (!name || !mapping || Object.keys(mapping).length === 0) return

    setSaving(true)
    try {
      await api.saveImportProfile({
        name,
        import_type: importType,
        mapping,
      })
      setSaveName('')
      setShowSave(false)
      await fetchProfiles()
    } catch {
      // erreur silencieuse — le profil n'a pas été sauvegardé
    } finally {
      setSaving(false)
    }
  }, [saveName, importType, mapping, fetchProfiles])

  /** Supprime un profil. */
  const handleDelete = useCallback(async () => {
    if (!selectedId) return
    try {
      await api.deleteImportProfile(Number(selectedId))
      setSelectedId('')
      await fetchProfiles()
    } catch {
      // silencieux
    }
  }, [selectedId, fetchProfiles])

  const hasMapping = mapping && Object.keys(mapping).length > 0

  return (
    <div className="flex items-center gap-2 flex-wrap">
      {/* Charger un profil */}
      <div className="flex items-center gap-1.5">
        <select
          value={selectedId}
          onChange={e => setSelectedId(e.target.value)}
          disabled={loading || profiles.length === 0}
          className="text-xs rounded-md border border-input px-2 py-1.5 bg-background text-foreground min-w-[140px]"
        >
          <option value="">
            {loading ? 'Chargement…' : profiles.length === 0 ? 'Aucun profil' : '— Charger un profil —'}
          </option>
          {profiles.map(p => (
            <option key={p.id} value={p.id}>{p.name}</option>
          ))}
        </select>
        <Btn size="sm" variant="ghost" disabled={!selectedId} onClick={handleLoad}>
          Charger
        </Btn>
        {selectedId && (
          <Btn size="sm" variant="ghost" onClick={handleDelete} className="text-destructive hover:text-destructive">
            Supprimer
          </Btn>
        )}
      </div>

      {/* Séparateur */}
      <div className="h-4 w-px bg-border mx-1 hidden sm:block" />

      {/* Sauvegarder le mapping actuel */}
      {!showSave ? (
        <Btn size="sm" variant="ghost" disabled={!hasMapping} onClick={() => setShowSave(true)}>
          Sauvegarder ce mapping
        </Btn>
      ) : (
        <div className="flex items-center gap-1.5">
          <input
            type="text"
            value={saveName}
            onChange={e => setSaveName(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && handleSave()}
            placeholder="Nom du profil…"
            className="text-xs rounded-md border border-input px-2 py-1.5 bg-background w-36"
            autoFocus
          />
          <Btn size="sm" onClick={handleSave} loading={saving} disabled={!saveName.trim()}>
            Sauver
          </Btn>
          <Btn size="sm" variant="ghost" onClick={() => { setShowSave(false); setSaveName('') }}>
            Annuler
          </Btn>
        </div>
      )}
    </div>
  )
}
